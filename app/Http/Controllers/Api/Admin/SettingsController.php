<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /** Per-key validation rules enforced on update. */
    private const RULES = [
        'site_name' => 'required|string|max:100',
        'company_email' => 'required|email|max:150',
        'company_phone' => ['required', 'regex:/^\+?[0-9]{7,15}$/'],
        'site_address' => 'nullable|string|max:200',
        'site_city' => 'nullable|string|max:100',
        'site_country' => 'nullable|string|max:100',
        'default_locale' => 'required|in:en,sr',
        'booking_tolerance_minutes' => 'required|integer|min:0|max:240',
        'expiry_reminder_minutes' => 'required|integer|min:0|max:1440',
        'eur_rsd_rate' => 'required|numeric|min:1|max:10000',
        'google_rating' => ['nullable', 'regex:/^[0-5](\.\d)?$/'],
        'google_review_count' => 'nullable|string|max:20',
        'google_reviews_url' => 'nullable|url|max:500',
        'hero_image' => 'nullable|string|max:500',
        'hero_headline_en' => 'nullable|string|max:120',
        'hero_headline_sr' => 'nullable|string|max:120',
        'hero_subhead_en' => 'nullable|string|max:300',
        'hero_subhead_sr' => 'nullable|string|max:300',
        'home_meta_title' => 'nullable|string|max:60',
        'home_meta_description' => 'nullable|string|max:150',
        'home_meta_title_sr' => 'nullable|string|max:60',
        'home_meta_description_sr' => 'nullable|string|max:150',
        'map_default_lat' => 'nullable|numeric|between:-90,90',
        'map_default_lng' => 'nullable|numeric|between:-180,180',
        'map_default_zoom' => 'nullable|integer|min:1|max:20',
        'social_facebook_url' => 'nullable|url|max:500',
        'social_instagram_url' => 'nullable|url|max:500',
        'social_tiktok_url' => 'nullable|url|max:500',
        'legal_company_name' => 'nullable|string|max:200',
        'legal_vat' => 'nullable|string|max:50',
        'legal_registration_number' => 'nullable|string|max:50',
        'locker_standard_capacity_en' => 'nullable|string|max:120',
        'locker_standard_capacity_sr' => 'nullable|string|max:120',
        'locker_standard_dimensions' => 'nullable|string|max:60',
        'locker_standard_image' => 'nullable|string|max:500',
        'locker_large_capacity_en' => 'nullable|string|max:120',
        'locker_large_capacity_sr' => 'nullable|string|max:120',
        'locker_large_dimensions' => 'nullable|string|max:60',
        'locker_large_image' => 'nullable|string|max:500',
        'entry_door_code' => 'nullable|string|max:20',
        'notifications_admin_email' => 'nullable|email|max:150',
        'notifications_admin_whatsapp' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
        'notifications_dev_mode' => 'nullable|in:0,1,true,false',
        'notifications_notify_admin' => 'nullable|in:0,1,true,false',
        'notifications_disabled' => 'nullable|in:0,1,true,false',
    ];

    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn($group) => $group->mapWithKeys(fn($s) => [$s->key => $s->value]));

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['settings' => 'required|array']);
        $incoming = $request->input('settings');

        // Normalize phone to E.164 (strip spaces/dashes, ensure leading +)
        if (isset($incoming['company_phone'])) {
            $incoming['company_phone'] = $this->normalizePhone($incoming['company_phone']);
        }
        if (!empty($incoming['notifications_admin_whatsapp'])) {
            $incoming['notifications_admin_whatsapp'] = $this->normalizePhone($incoming['notifications_admin_whatsapp']);
        }

        // Validate only the keys we know about and that the client sent.
        $rules = array_intersect_key(self::RULES, $incoming);
        $validator = Validator::make($incoming, $rules, [
            'company_phone.regex' => 'Phone number must be in international format, digits only (e.g. +381652941503).',
        ]);
        $validator->validate();

        foreach ($incoming as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => (string) ($value ?? '')]);
            }
        }

        SiteSettings::flush();

        return response()->json(['message' => 'Settings updated']);
    }

    private function normalizePhone(string $input): string
    {
        $trim = trim($input);
        if ($trim === '') return '';
        $hasPlus = str_starts_with($trim, '+');
        $digits = preg_replace('/\D/', '', $trim);
        return $hasPlus ? '+'.$digits : $digits;
    }
}
