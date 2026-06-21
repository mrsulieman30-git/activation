<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegistrationApiController extends Controller
{
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'device_fingerprint' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if there's already a pending request for this device
        $existing = RegistrationRequest::where('device_fingerprint', $request->device_fingerprint)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Registration request is already pending approval.',
                'data' => $existing
            ]);
        }

        $regRequest = RegistrationRequest::create([
            'clinic_name' => $request->clinic_name,
            'contact_name' => $request->contact_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'device_fingerprint' => $request->device_fingerprint,
            'device_name' => $request->device_name,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration request submitted successfully.',
            'data' => $regRequest
        ]);
    }
}
