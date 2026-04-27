<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@belgradeluggagelocker.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => 'password',
                'role' => 'super_admin',
                'location_ids' => null,
                'is_active' => true,
            ]
        );
    }
}
