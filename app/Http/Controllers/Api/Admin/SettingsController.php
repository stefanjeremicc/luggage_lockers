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
        'default_locale' => 'required|in:en,sr',
        'booking_tolerance_minutes' => 'required|integer|min:0|max:240',
        'expiry_reminder_minutes' => 'required|integer|min:0|max:1440',
        'eur_rsd_rate' => 'required|numeric|min:1|max:10000',
        'google_rating' => ['nullable', 'regex:/^[0-5](\.\d)?$/'],
        'google_review_count' => 'nullable|string|max:20',
        'google_reviews_url' => 'nullable|url|max:500',
        'home_meta_title' => 'nullable|string|max:60',
        'home_meta_description' => 'nullable|string|max:150',
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
