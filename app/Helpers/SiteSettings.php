<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache === null) {
            static::$cache = Cache::remember('site_settings', 300, function () {
                return Setting::all()->pluck('value', 'key')->toArray();
            });
        }
        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public static function flush(): void
    {
        static::$cache = null;
        Cache::forget('site_settings');
    }

    /** Raw E.164 value stored in DB (e.g. +381652941503). */
    public static function phone(): ?string
    {
        return static::all()['company_phone'] ?? null;
    }

    /** Human-friendly display, e.g. "+381 65 294 1503". */
    public static function phoneDisplay(): string
    {
        return static::formatDisplay(static::phone());
    }

    /** Value for tel: href — E.164 without spaces, e.g. "+381652941503". */
    public static function phoneTel(): string
    {
        $raw = static::phone();
        if (!$raw) return '';
        $digits = preg_replace('/[^\d+]/', '', $raw);
        return str_starts_with($digits, '+') ? $digits : '+'.$digits;
    }

    /** Value for wa.me href — digits only, no "+", e.g. "381652941503". */
    public static function whatsappLink(): string
    {
        $raw = static::phone();
        if (!$raw) return '';
        return preg_replace('/\D/', '', $raw);
    }

    public static function email(): ?string
    {
        return static::all()['company_email'] ?? null;
    }

    public static function siteName(): string
    {
        return static::all()['site_name'] ?? 'Belgrade Luggage Locker';
    }

    public static function address(): ?string
    {
        return static::all()['site_address'] ?? null;
    }

    public static function city(): ?string
    {
        return static::all()['site_city'] ?? null;
    }

    public static function country(): ?string
    {
        return static::all()['site_country'] ?? null;
    }

    public static function legalCompany(): string
    {
        return static::all()['legal_company_name'] ?? static::siteName();
    }

    public static function social(): array
    {
        $a = static::all();
        return array_filter([
            'facebook' => $a['social_facebook_url'] ?? null,
            'instagram' => $a['social_instagram_url'] ?? null,
            'tiktok' => $a['social_tiktok_url'] ?? null,
        ]);
    }

    public static function mapCenter(): array
    {
        $a = static::all();
        return [
            'lat' => (float) ($a['map_default_lat'] ?? 44.812),
            'lng' => (float) ($a['map_default_lng'] ?? 20.460),
            'zoom' => (int) ($a['map_default_zoom'] ?? 14),
        ];
    }

    public static function heroImage(): string
    {
        return static::all()['hero_image'] ?? '/images/hero-belgrade.webp';
    }

    public static function heroHeadline(string $locale = 'en'): ?string
    {
        $a = static::all();
        return $locale === 'sr'
            ? ($a['hero_headline_sr'] ?? null)
            : ($a['hero_headline_en'] ?? null);
    }

    public static function heroSubhead(string $locale = 'en'): ?string
    {
        $a = static::all();
        return $locale === 'sr'
            ? ($a['hero_subhead_sr'] ?? null)
            : ($a['hero_subhead_en'] ?? null);
    }

    /**
     * Locker capacity / dimensions / image — admin-editable per size.
     * Returns ['capacity' => ..., 'dimensions' => ..., 'image' => ...].
     */
    public static function lockerInfo(string $size, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $a = static::all();
        $size = $size === 'large' ? 'large' : 'standard';
        $capKey = "locker_{$size}_capacity_".($locale === 'sr' ? 'sr' : 'en');
        return [
            'capacity' => $a[$capKey] ?? ($a["locker_{$size}_capacity_en"] ?? null),
            'dimensions' => $a["locker_{$size}_dimensions"] ?? null,
            'image' => $a["locker_{$size}_image"] ?? null,
        ];
    }

    private static function formatDisplay(?string $e164): string
    {
        if (!$e164) return '';
        $digits = preg_replace('/\D/', '', $e164);
        if (strlen($digits) < 4) return $e164;
        // Format: +XXX XX XXX XXXX (country code + space-grouped national)
        $country = substr($digits, 0, -9);
        if (strlen($country) < 1) $country = substr($digits, 0, 3);
        $national = substr($digits, strlen($country));
        $groups = str_split($national, 3);
        return '+'.$country.' '.implode(' ', $groups);
    }
}
