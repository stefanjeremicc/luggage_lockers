<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'eur_rsd_rate', 'value' => '117.00', 'type' => 'float', 'group' => 'pricing'],
            ['key' => 'service_fee_eur', 'value' => '0.00', 'type' => 'float', 'group' => 'pricing'],
            ['key' => 'booking_tolerance_minutes', 'value' => '20', 'type' => 'int', 'group' => 'general'],
            ['key' => 'expiry_reminder_minutes', 'value' => '30', 'type' => 'int', 'group' => 'general'],
            ['key' => 'default_locale', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'site_name', 'value' => 'Belgrade Luggage Locker', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+381 65 332 2319', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'info@belgradeluggagelocker.com', 'type' => 'string', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
