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
            ['key' => 'site_address', 'value' => 'Kralja Milana 12', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'site_city', 'value' => 'Belgrade', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'site_country', 'value' => 'Serbia', 'type' => 'string', 'group' => 'contact'],

            // --- General ---
            ['key' => 'default_locale', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'booking_tolerance_minutes', 'value' => '20', 'type' => 'int', 'group' => 'general'],
            ['key' => 'expiry_reminder_minutes', 'value' => '30', 'type' => 'int', 'group' => 'general'],
            ['key' => 'eur_rsd_rate', 'value' => '120.00', 'type' => 'float', 'group' => 'general'],

            // --- Homepage ---
            ['key' => 'google_rating', 'value' => '4.9', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'google_review_count', 'value' => '70+', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'google_reviews_url', 'value' => 'https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJq3Y86Jl7WkcRJRP0r-8Tg5M', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'hero_image', 'value' => '/images/hero-belgrade.webp', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'hero_headline_en', 'value' => 'Luggage Storage in Belgrade', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'hero_headline_sr', 'value' => 'Garderoba za Prtljag u Beogradu', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'hero_subhead_en', 'value' => 'Secure smart lockers in the heart of Belgrade. Book in 60 seconds, pay cash on arrival.', 'type' => 'string', 'group' => 'homepage'],
            ['key' => 'hero_subhead_sr', 'value' => 'Sigurni pametni ormančići u centru Beograda. Rezerviši za 60 sekundi, plati gotovinom.', 'type' => 'string', 'group' => 'homepage'],

            // --- SEO ---
            ['key' => 'home_meta_title', 'value' => 'Belgrade Luggage Locker — 24/7 Secure Luggage Storage', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'home_meta_description', 'value' => 'Secure 24/7 luggage storage in Belgrade city center. Smart lockers at 2 locations. Book online in 60 seconds, pay cash on arrival. From €5.', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'home_meta_title_sr', 'value' => 'Belgrade Luggage Locker — Garderoba za Prtljag 24/7', 'type' => 'string', 'group' => 'seo'],
            ['key' => 'home_meta_description_sr', 'value' => 'Sigurna garderoba za prtljag 24/7 u centru Beograda. Pametni ormančići na 2 lokacije. Rezerviši online za 60 sekundi.', 'type' => 'string', 'group' => 'seo'],

            // --- Map ---
            ['key' => 'map_default_lat', 'value' => '44.812', 'type' => 'float', 'group' => 'map'],
            ['key' => 'map_default_lng', 'value' => '20.460', 'type' => 'float', 'group' => 'map'],
            ['key' => 'map_default_zoom', 'value' => '14', 'type' => 'int', 'group' => 'map'],

            // --- Social ---
            ['key' => 'social_facebook_url', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'social_instagram_url', 'value' => '', 'type' => 'string', 'group' => 'social'],
            ['key' => 'social_tiktok_url', 'value' => '', 'type' => 'string', 'group' => 'social'],

            // --- Legal ---
            ['key' => 'legal_company_name', 'value' => 'Belgrade Luggage Locker d.o.o.', 'type' => 'string', 'group' => 'legal'],
            ['key' => 'legal_vat', 'value' => '', 'type' => 'string', 'group' => 'legal'],
            ['key' => 'legal_registration_number', 'value' => '', 'type' => 'string', 'group' => 'legal'],

            // --- Access ---
            ['key' => 'entry_door_code', 'value' => '0717#', 'type' => 'string', 'group' => 'access'],

            // --- Notifications (developer + admin contact) ---
            // Admin email/phone are used by both: dev-mode redirect AND notify-admin BCC.
            ['key' => 'notifications_admin_email', 'value' => 'stefan.jeremic@outlook.com', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'notifications_admin_whatsapp', 'value' => '+381649679212', 'type' => 'string', 'group' => 'notifications'],

            // Dev mode: redirect everything to admin (customer gets nothing).
            // MUST be '0' on production. Toggle from Settings UI for testing.
            ['key' => 'notifications_dev_mode', 'value' => '0', 'type' => 'bool', 'group' => 'notifications'],

            // Notify admin: also send a copy to admin alongside customer (when dev mode OFF).
            ['key' => 'notifications_notify_admin', 'value' => '1', 'type' => 'bool', 'group' => 'notifications'],

            // Hard kill-switch.
            ['key' => 'notifications_disabled', 'value' => '0', 'type' => 'bool', 'group' => 'notifications'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
