<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivationCertificate;
use App\Models\ActivationRequest;
use App\Models\LicenseValidation;
use App\Models\SerialKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivationApiController extends Controller
{
    /**
     * Endpoint for desktop app to request activation.
     */
    public function requestActivation(Request $request)
    {
        $request->validate([
            'serial_key' => 'required|string',
            'device_fingerprint' => 'required|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
            'os_version' => 'nullable|string',
        ]);

        $serialKey = SerialKey::where('key_value', $request->serial_key)->first();

        if (!$serialKey) {
            return response()->json(['message' => 'Invalid serial key.'], 404);
        }

        if ($serialKey->status !== 'active') {
            return response()->json(['message' => "Serial key is {$serialKey->status}."], 403);
        }

        // Check if device already requested
        $existingRequest = ActivationRequest::where('serial_key_id', $serialKey->id)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'status' => $existingRequest->status,
                'request_id' => $existingRequest->request_id,
                'message' => 'Activation request already exists.'
            ]);
        }

        // Create new request
        $activationRequest = ActivationRequest::create([
            'request_id' => 'REQ-' . date('Y') . '-' . strtoupper(Str::random(8)),
            'serial_key_id' => $serialKey->id,
            'device_fingerprint' => $request->device_fingerprint,
            'device_name' => $request->device_name,
            'app_version' => $request->app_version,
            'os_version' => $request->os_version,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'pending',
            'request_id' => $activationRequest->request_id,
            'message' => 'Activation request submitted and pending approval.'
        ]);
    }

    /**
     * Endpoint to check the status of a request.
     */
    public function checkStatus(Request $request, $requestId)
    {
        $activationRequest = ActivationRequest::where('request_id', $requestId)
            ->with(['serialKey.customer'])
            ->firstOrFail();

        if ($activationRequest->status === 'approved') {
            $certificate = ActivationCertificate::where('activation_request_id', $activationRequest->id)->first();
            
            return response()->json([
                'status' => 'approved',
                'customer_name' => $activationRequest->serialKey->customer->name,
                'server_url' => $certificate->server_url,
                'api_url' => $certificate->api_url,
                'certificate' => [
                    'license_id' => $certificate->license_id,
                    'customer_id' => $certificate->customer_id,
                    'device_fingerprint' => $certificate->device_fingerprint,
                    'payload' => $certificate->certificate_data,
                    'signature' => $certificate->digital_signature
                ]
            ]);
        }

        return response()->json([
            'status' => $activationRequest->status,
            'rejection_reason' => $activationRequest->rejection_reason
        ]);
    }

    /**
     * Endpoint for periodic license validation.
     */
    public function validateLicense(Request $request)
    {
        $request->validate([
            'license_id' => 'required|string',
            'device_fingerprint' => 'required|string',
        ]);

        $certificate = ActivationCertificate::where('license_id', $request->license_id)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->with('serialKey')
            ->first();

        if (!$certificate) {
            return response()->json(['status' => 'invalid', 'message' => 'License not found or device mismatch.']);
        }

        if ($certificate->revoked_at !== null) {
            $this->logValidation($certificate->id, $request, 'revoked');
            return response()->json(['status' => 'revoked', 'message' => 'License has been revoked.']);
        }

        if ($certificate->serialKey->status !== 'active') {
            $this->logValidation($certificate->id, $request, $certificate->serialKey->status);
            return response()->json(['status' => $certificate->serialKey->status, 'message' => 'Serial key is no longer active.']);
        }

        $this->logValidation($certificate->id, $request, 'valid');

        return response()->json(['status' => 'active']);
    }

    private function logValidation($certId, Request $request, $status)
    {
        LicenseValidation::create([
            'activation_certificate_id' => $certId,
            'device_fingerprint' => $request->device_fingerprint,
            'validation_result' => $status,
            'ip_address' => $request->ip(),
            'validated_at' => now()
        ]);
    }
}
