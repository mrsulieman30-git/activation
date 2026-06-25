<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\SerialKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@seeha.tech',
            'password' => bcrypt('password'),
        ]);

        // Create sample customers
        $customers = [
            [
                'name' => 'Al-Amal Hospital',
                'code' => 'CUST-' . strtoupper(Str::random(6)),
                'contact_name' => 'Dr. Ahmed Hassan',
                'contact_email' => 'ahmed@alamal-hospital.com',
                'contact_phone' => '+966 501234567',
                'hms_server_url' => 'https://hms.seeha.tech',
                'hms_api_url' => 'https://hms.seeha.tech/api',
                'max_devices' => 10,
                'license_type' => 'hospital',
                'status' => 'active',
                'notes' => 'Main hospital client - Riyadh branch',
            ],
            [
                'name' => 'Al-Shifa Clinic',
                'code' => 'CUST-' . strtoupper(Str::random(6)),
                'contact_name' => 'Dr. Fatima Ali',
                'contact_email' => 'fatima@alshifa-clinic.com',
                'contact_phone' => '+966 559876543',
                'hms_server_url' => null,
                'hms_api_url' => null,
                'max_devices' => 5,
                'license_type' => 'clinic',
                'status' => 'active',
                'notes' => 'Small clinic - Jeddah',
            ],
            [
                'name' => 'National Medical Center',
                'code' => 'CUST-' . strtoupper(Str::random(6)),
                'contact_name' => 'Eng. Khalid Nasser',
                'contact_email' => 'khalid@nmc.sa',
                'contact_phone' => '+966 512345678',
                'hms_server_url' => null,
                'hms_api_url' => null,
                'max_devices' => 20,
                'license_type' => 'hospital',
                'status' => 'active',
                'notes' => 'Enterprise client - multi-branch',
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);

            // Generate 1-2 serial keys per customer
            $keyCount = rand(1, 2);
            for ($i = 0; $i < $keyCount; $i++) {
                SerialKey::create([
                    'customer_id' => $customer->id,
                    'key_value' => collect(range(1, 4))
                        ->map(fn () => strtoupper(Str::random(4)))
                        ->join('-'),
                    'status' => 'active',
                    'max_activations' => rand(1, 3),
                    'expires_at' => now()->addYear(),
                ]);
            }
        }

        $this->command->info('✅ Seeded: 1 admin user, ' . count($customers) . ' customers with serial keys.');
    }
}
