<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@belgradeluggagelocker.com',
            'password' => 'password',
            'role' => 'super_admin',
            'location_ids' => null,
            'is_active' => true,
        ]);
    }
}
