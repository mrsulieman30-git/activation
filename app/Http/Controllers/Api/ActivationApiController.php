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
            return response()->json(['message' => 'Invalid activation code.'], 404);
        }

        if ($serialKey->status !== 'active') {
            return response()->json(['message' => "Serial key is {$serialKey->status}."], 403);
        }

        // Validate hardware fingerprint match if pre-bound by admin
        if ($serialKey->hardware_fingerprint && $serialKey->hardware_fingerprint !== $request->device_fingerprint) {
            return response()->json(['message' => 'Hardware fingerprint mismatch. This code is generated for a different machine.'], 403);
        }

        // Check if request already exists
        $existingRequest = ActivationRequest::where('serial_key_id', $serialKey->id)
            ->where('device_fingerprint', $request->device_fingerprint)
            ->first();

        if ($existingRequest) {
            if ($existingRequest->status === 'approved') {
                $certificate = ActivationCertificate::where('activation_request_id', $existingRequest->id)->first();
                if ($certificate) {
                    $customer = $serialKey->customer;
                    return response()->json([
                        'status' => 'approved',
                        'message' => 'Device already activated.',
                        'customer_name' => $customer->name,
                        'server_url' => $certificate->server_url ?: env('DEFAULT_HMS_SERVER_URL', 'https://hms.seeha.tech'),
                        'api_url' => $certificate->api_url ?: env('DEFAULT_HMS_API_URL', 'https://hms.seeha.tech/api'),
                        'sync_credentials' => [
                            'api_key' => env('DESKTOP_API_KEY', 'hms-sync-2026-secret'),
                            'api_secret' => env('DESKTOP_API_SECRET', 'super-secret-password-123'),
                        ],
                        'certificate' => [
                            'license_id' => $certificate->license_id,
                            'customer_id' => $certificate->customer_id,
                            'device_fingerprint' => $certificate->device_fingerprint,
                            'payload' => $certificate->certificate_data,
                            'signature' => $certificate->digital_signature
                        ]
                    ]);
                }
            }

            return response()->json([
                'status' => $existingRequest->status,
                'request_id' => $existingRequest->request_id,
                'message' => $existingRequest->status === 'pending' 
                    ? 'Awaiting administrator approval.' 
                    : ($existingRequest->status === 'rejected' ? 'Activation request was rejected.' : 'Activation request was revoked.')
            ]);
        }

        // Create pending request
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
            'message' => 'Activation request submitted. Awaiting administrator approval.'
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
            
            if (!$certificate) {
                // Generate and sign certificate
                $signer = app(\App\Services\CertificateSignerService::class);
                $customer = $activationRequest->serialKey->customer;
                $licenseId = Str::uuid()->toString();

                $payload = [
                    'license_id' => $licenseId,
                    'customer_id' => $customer->code,
                    'customer_name' => $customer->name,
                    'license_type' => $customer->license_type,
                    'device_fingerprint' => $activationRequest->device_fingerprint,
                    'max_devices' => $customer->max_devices,
                    'issued_at' => now()->toIso8601String(),
                    'expires_at' => $activationRequest->serialKey->expires_at ? $activationRequest->serialKey->expires_at->toIso8601String() : null,
                    'features' => ['offline_mode', 'sync'], 
                ];

                $signedData = $signer->signCertificate($payload);

                $certificate = ActivationCertificate::create([
                    'activation_request_id' => $activationRequest->id,
                    'serial_key_id' => $activationRequest->serial_key_id,
                    'customer_id' => $customer->id,
                    'device_fingerprint' => $activationRequest->device_fingerprint,
                    'license_id' => $licenseId,
                    'server_url' => $customer->hms_server_url ?? '',
                    'api_url' => $customer->hms_api_url ?? '',
                    'certificate_data' => $signedData['payload'],
                    'digital_signature' => $signedData['signature'],
                    'issued_at' => now()
                ]);
            }

            return response()->json([
                'status' => 'approved',
                'customer_name' => $activationRequest->serialKey->customer->name,
                'server_url' => $certificate->server_url ?: env('DEFAULT_HMS_SERVER_URL', 'https://hms.seeha.tech'),
                'api_url' => $certificate->api_url ?: env('DEFAULT_HMS_API_URL', 'https://hms.seeha.tech/api'),
                'sync_credentials' => [
                    'api_key' => env('DESKTOP_API_KEY', 'hms-sync-2026-secret'),
                    'api_secret' => env('DESKTOP_API_SECRET', 'super-secret-password-123'),
                ],
                'certificate' => [
                    'license_id' => $certificate->license_id,
                    'customer_id' => $certificate->customer_id,
                    'device_fingerprint' => $certificate->device_fingerprint,
                    'payload' => $certificate->certificate_data,
                    'signature' => $certificate->digital_signature
                ]
            ]);
        }

        if ($activationRequest->status === 'rejected') {
            return response()->json([
                'status' => 'rejected',
                'message' => $activationRequest->rejection_reason ?? 'Your activation request has been rejected.'
            ]);
        }

        if ($activationRequest->status === 'revoked') {
            return response()->json([
                'status' => 'revoked',
                'message' => 'License has been revoked.'
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => 'Awaiting administrator approval.'
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
