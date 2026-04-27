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
