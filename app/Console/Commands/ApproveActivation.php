<?php

namespace App\Console\Commands;

use App\Models\ActivationCertificate;
use App\Models\ActivationRequest;
use App\Services\CertificateSignerService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ApproveActivation extends Command
{
    protected $signature = 'activation:approve {request_id}';
    protected $description = 'Approve an activation request and generate a certificate';

    public function handle(CertificateSignerService $signer)
    {
        $requestId = $this->argument('request_id');
        $request = ActivationRequest::where('request_id', $requestId)->with('serialKey.customer')->first();

        if (!$request) {
            $this->error("Request {$requestId} not found.");
            return 1;
        }

        if ($request->status === 'approved') {
            $this->warn("Request {$requestId} is already approved.");
            return 0;
        }

        $customer = $request->serialKey->customer;
        
        $licenseId = Str::uuid()->toString();

        $payload = [
            'license_id' => $licenseId,
            'customer_id' => $customer->code,
            'customer_name' => $customer->name,
            'license_type' => $customer->license_type,
            'device_fingerprint' => $request->device_fingerprint,
            'max_devices' => $customer->max_devices,
            'issued_at' => now()->toIso8601String(),
            'expires_at' => $request->serialKey->expires_at ? $request->serialKey->expires_at->toIso8601String() : null,
            'features' => ['offline_mode', 'sync'], // Expand based on customer
        ];

        try {
            $signedData = $signer->signCertificate($payload);
        } catch (\Exception $e) {
            $this->error("Failed to sign certificate: " . $e->getMessage());
            return 1;
        }

        ActivationCertificate::create([
            'activation_request_id' => $request->id,
            'serial_key_id' => $request->serial_key_id,
            'customer_id' => $customer->id,
            'device_fingerprint' => $request->device_fingerprint,
            'license_id' => $licenseId,
            'server_url' => $customer->hms_server_url,
            'api_url' => $customer->hms_api_url,
            'certificate_data' => $signedData['payload'],
            'digital_signature' => $signedData['signature'],
            'issued_at' => now()
        ]);

        $request->update([
            'status' => 'approved',
            'reviewed_at' => now()
        ]);

        $this->info("Activation request {$requestId} approved and certificate generated.");
        return 0;
    }
}
