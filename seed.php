<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Generate Keypair
$kp = sodium_crypto_sign_keypair();
$pub = sodium_bin2hex(sodium_crypto_sign_publickey($kp));
$priv = sodium_bin2hex(sodium_crypto_sign_secretkey($kp));

echo "PUB: $pub\nPRIV: $priv\n";

// Update SKM .env with private key
$envPath = '.env';
$env = file_get_contents($envPath);
$env .= "\nED25519_PRIVATE_KEY=$priv\n";
file_put_contents($envPath, $env);

// Update Desktop app .env with public key
$desktopEnvPath = '../hms-laravel/.env';
if (file_exists($desktopEnvPath)) {
    $denv = file_get_contents($desktopEnvPath);
    if (!str_contains($denv, 'SEHTECH_CERTIFICATE_PUBLIC_KEY')) {
        $denv .= "\nSEHTECH_CERTIFICATE_PUBLIC_KEY=$pub\n";
        file_put_contents($desktopEnvPath, $denv);
    }
}

// Create a test customer and serial key
use App\Models\Customer;
use App\Models\SerialKey;

$customer = Customer::create([
    'name' => 'Seeha Hospital',
    'code' => 'KAAFI',
    'hms_server_url' => 'https://demo.seeha.tech',
    'hms_api_url' => 'https://demo.seeha.tech/api/v1',
    'max_devices' => 10,
    'license_type' => 'hospital',
    'status' => 'active'
]);

$key = SerialKey::create([
    'customer_id' => $customer->id,
    'key_value' => 'ST-KAAFI-1A2B-3C4D',
    'status' => 'active',
    'max_activations' => 10
]);

echo "Created test customer and serial key: " . $key->key_value . "\n";
