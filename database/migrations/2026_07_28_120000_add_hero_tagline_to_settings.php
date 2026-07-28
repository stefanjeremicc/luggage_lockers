<?php

use App\Helpers\SiteSettings;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Small social-proof line shown under the hero buttons — admin-editable,
        // per-locale (mirrors hero_subhead_en/sr). Seeded here so existing/prod
        // DBs get the rows; SettingsController::update() only writes EXISTING
        // keys, so without this the admin's saved value would be silently dropped.
        Setting::updateOrCreate(
            ['key' => 'hero_tagline_en'],
            ['value' => '2,000+ Happy Travelers — And Growing Every Day', 'type' => 'string', 'group' => 'homepage']
        );
        Setting::updateOrCreate(
            ['key' => 'hero_tagline_sr'],
            ['value' => '2.000+ zadovoljnih putnika — i svakim danom sve više', 'type' => 'string', 'group' => 'homepage']
        );

        // Bust the 5-min settings cache so the new value renders immediately.
        SiteSettings::flush();
    }

    public function down(): void
    {
        Setting::whereIn('key', ['hero_tagline_en', 'hero_tagline_sr'])->delete();
        SiteSettings::flush();
    }
};
