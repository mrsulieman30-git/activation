<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class CertificateSignerService
{
    /**
     * The Ed25519 private key, loaded from environment.
     * Must be a 64-byte hex string (or bin).
     */
    private string $privateKeyHex;

    public function __construct()
    {
        $this->privateKeyHex = env('ED25519_PRIVATE_KEY', '');
    }

    /**
     * Sign a certificate payload and return the signature + base64 encoded payload.
     *
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function signCertificate(array $payload): array
    {
        if (empty($this->privateKeyHex)) {
            // Generate a temporary one for development if not set
            $keyPair = sodium_crypto_sign_keypair();
            $this->privateKeyHex = sodium_bin2hex(sodium_crypto_sign_secretkey($keyPair));
            Log::warning('CertificateSigner: Using temporary generated private key. Set ED25519_PRIVATE_KEY in .env!');
        }

        try {
            $privateKey = sodium_hex2bin($this->privateKeyHex);
            $payloadString = json_encode($payload, JSON_THROW_ON_ERROR);
            
            $signature = sodium_crypto_sign_detached($payloadString, $privateKey);
            
            return [
                'payload' => $payload,
                'signature' => sodium_bin2hex($signature),
            ];
        } catch (\Throwable $e) {
            Log::error('CertificateSigner: Failed to sign certificate', ['error' => $e->getMessage()]);
            throw new Exception('Could not generate digital signature.');
        }
    }

    /**
     * Generate a new Ed25519 keypair for configuration.
     *
     * @return array{public: string, private: string}
     */
    public static function generateKeyPair(): array
    {
        $keyPair = sodium_crypto_sign_keypair();
        return [
            'public' => sodium_bin2hex(sodium_crypto_sign_publickey($keyPair)),
            'private' => sodium_bin2hex(sodium_crypto_sign_secretkey($keyPair)),
        ];
    }
}
