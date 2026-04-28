<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_INITIAL_EMAIL', 'admin@belgradeluggagelocker.com');
        $password = env('ADMIN_INITIAL_PASSWORD');

        // In production we refuse to seed a default password — it must be supplied via env
        // (and rotated immediately after first login). In local/testing we generate a random
        // one and print it once so the developer can log in.
        if (!$password) {
            if (app()->environment('production')) {
                $this->command?->error('ADMIN_INITIAL_PASSWORD env var is required to seed admin user in production. Aborting.');
                return;
            }
            $password = Str::random(16);
            $this->command?->warn("[AdminUserSeeder] No ADMIN_INITIAL_PASSWORD set. Generated one-time password for {$email}: {$password}");
            $this->command?->warn('[AdminUserSeeder] Save it now — it will not be shown again. Change it after first login.');
        }

        // Don't overwrite an existing admin's password on re-seed — that would let a leak of the
        // env var rotate prod credentials silently. Set password only when creating the user.
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $existing->fill([
                'name' => 'Super Admin',
                'username' => 'admin',
                'role' => 'super_admin',
                'is_active' => true,
            ])->save();
            return;
        }

        User::create([
            'email' => $email,
            'name' => 'Super Admin',
            'username' => 'admin',
            'password' => $password,
            'role' => 'super_admin',
            'location_ids' => null,
            'is_active' => true,
        ]);
    }
}
