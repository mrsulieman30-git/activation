<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@seeha.tech')->count() === 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@seeha.tech',
                'password' => bcrypt('password'),
            ]);
        }
    }
}
