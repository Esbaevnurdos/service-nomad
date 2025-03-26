<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'phone' => '1234567890', // Required
            'role' => 'passenger',
            'otp' => null, // Can be null
            'latitude' => null, // Optional
            'longitude' => null, // Optional
            'is_available' => false,
        ]);
    }
}

