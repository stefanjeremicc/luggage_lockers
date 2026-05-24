<?php

namespace App\Services\Lock;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tracks TTLock monthly API usage and the "quota exceeded" state, and sends
 * threshold alerts. All keys are scoped to the current calendar month so they
 * auto-reset on the 1st (matching TTLock's monthly reset).
 *
 * - recordCall(): increment the monthly counter; alert at 20k / 25k.
 * - markExceeded(): set the exceeded flag (until month end) + alert.
 * - isExceeded(): is the quota currently exhausted?
 *
 * The free TTLock "basic" tier allows 30,000 calls/month per app.
 */
class TtlockQuota
{
    private const MONTHLY_LIMIT = 30000;
    private const ALERT_THRESHOLDS = [20000, 25000];

    /** Recipients for usage alerts. */
    private const ALERT_TO = ['info@belgradeluggagelocker.com', 'stefan77studio@gmail.com'];

    private static function ym(): string
    {
        return now()->format('Y-m');
    }

    private static function secondsToMonthEnd(): int
    {
        // Argument order matters: $a->diffInSeconds($b) == ($b - $a). We want
        // (endOfMonth - now) which is POSITIVE. The reversed form returned a
        // negative TTL on Carbon 3, so Cache::put/add stored nothing → the
        // exceeded flag and the once-per-alert guard never persisted, spamming
        // an email every scheduler tick. Keep now() first.
        return max(60, (int) now()->diffInSeconds(now()->endOfMonth()));
    }

    public static function count(): int
    {
        return (int) Cache::get('ttlock:calls:' . self::ym(), 0);
    }

    public static function isExceeded(): bool
    {
        return (bool) Cache::get('ttlock:exceeded:' . self::ym(), false);
    }

    /** Increment the monthly call counter and fire threshold alerts once each. */
    public static function recordCall(): void
    {
        $key = 'ttlock:calls:' . self::ym();
        $n = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $n, self::secondsToMonthEnd());

        foreach (self::ALERT_THRESHOLDS as $threshold) {
            if ($n >= $threshold) {
                self::alertOnce("threshold:{$threshold}",
                    "TTLock API usage alert — {$n} / " . self::MONTHLY_LIMIT . " calls this month",
                    "Your TTLock app has used {$n} of " . self::MONTHLY_LIMIT . " monthly API calls (crossed {$threshold}). "
                    . "If it reaches the limit, the system switches to permanent PINs automatically.");
            }
        }
    }

    /** Mark the quota as exhausted for the rest of the month + alert once. */
    public static function markExceeded(): void
    {
        $key = 'ttlock:exceeded:' . self::ym();
        if (!Cache::get($key, false)) {
            Cache::put($key, true, self::secondsToMonthEnd());
            self::alertOnce('exceeded',
                'TTLock API monthly limit EXCEEDED — switched to permanent PINs',
                'The TTLock monthly API limit (' . self::MONTHLY_LIMIT . ') is exhausted. '
                . 'New bookings now use each locker\'s permanent PIN (no TTLock calls) until the quota resets next month. '
                . 'Lockers without a permanent PIN are temporarily excluded from booking.');
        }
    }

    /** Manually clear the exceeded flag (e.g. after a paid upgrade). */
    public static function clearExceeded(): void
    {
        Cache::forget('ttlock:exceeded:' . self::ym());
    }

    /** Send an email alert at most once per month per key. */
    private static function alertOnce(string $key, string $subject, string $body): void
    {
        $guard = 'ttlock:alert:' . $key . ':' . self::ym();
        // Cache::add returns true only the first time (atomic once-guard).
        if (!Cache::add($guard, true, self::secondsToMonthEnd())) {
            return;
        }
        try {
            Mail::raw($body, function ($m) use ($subject) {
                $m->to(self::ALERT_TO)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('TTLock quota alert email failed: ' . $e->getMessage());
        }
    }
}
