<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Remove any legacy keys that were split or superseded.
        Setting::whereIn('key', [
            'company_phone_link',
            'company_whatsapp',
            'company_whatsapp_link',
            'service_fee_eur',
            'how_it_works_steps',
        ])->delete();

        $settings = [
            // --- Contact ---
            ['key' => 'site_name', 'value' => 'Belgrade Luggage Locker', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'company_phone', 'value' => '+381652941503', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'company_email', 'value' => 'info@belgradeluggagelocker.com', 'type' => 'string', 'group' => 'contact'],

            // --- General ---
            ['key' => 'default_locale', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'booking_tolerance_minutes', 'value' => '20', 'type' => 'int', 'group' => 'general'],
            ['key' => 'expiry_reminder_minutes', 'value' => '30', 'type' => 'int', 'group' => 'general'],
            ['key' => 'eur_rsd_rate', 'value' => '117.00', 'type' => 'float', 'group' => 'general'],

            // --- Homepage ---
            ['key' => 'google_rating', 'value' => '4.9', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'google_review_count', 'value' => '70+', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'google_reviews_url', 'value' => 'https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJq3Y86Jl7WkcRJRP0r-8Tg5M', 'type' => 'string', 'group' => 'homepage'],

            // --- SEO ---
            ['key' => 'home_meta_title', 'value' => 'Belgrade Luggage Locker — 24/7 Secure Luggage Storage', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'home_meta_description', 'value' => 'Secure 24/7 luggage storage in Belgrade city center. Smart lockers at 2 locations. Book online in 60 seconds, pay cash on arrival. From €5.', 'type' => 'string', 'group' => 'seo'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
