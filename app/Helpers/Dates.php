<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Locale-stable date formatting for booking/admin/confirmation views.
 *
 * Carbon's translatedFormat() with sr locale produces Cyrillic and depends on the
 * ICU/Symfony translator being properly registered, which has bitten us in the past.
 * The booking flow needs latin Serbian regardless of which sr variant Carbon picks,
 * so we render numerically (28.04.2026 14:30) and skip translation entirely.
 */
class Dates
{
    /** "28.04.2026 14:30" — date and time in display timezone. */
    public static function dt(DateTimeInterface|Carbon|string|null $value): string
    {
        $c = static::carbon($value);
        return $c ? $c->format('d.m.Y H:i') : '';
    }

    /** "28.04.2026" — date only. */
    public static function d(DateTimeInterface|Carbon|string|null $value): string
    {
        $c = static::carbon($value);
        return $c ? $c->format('d.m.Y') : '';
    }

    /** "14:30" — time only. */
    public static function t(DateTimeInterface|Carbon|string|null $value): string
    {
        $c = static::carbon($value);
        return $c ? $c->format('H:i') : '';
    }

    private static function carbon(DateTimeInterface|Carbon|string|null $value): ?Carbon
    {
        if ($value === null) return null;
        $c = $value instanceof Carbon ? $value->copy() : Carbon::parse((string) $value);
        return $c->setTimezone(config('app.display_timezone', 'Europe/Belgrade'));
    }
}
