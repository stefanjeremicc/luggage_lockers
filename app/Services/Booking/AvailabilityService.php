<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\BookingItem;
use App\Models\Locker;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Legacy entry point: shared duration for both sizes. Returns standard + large
     * availability for the same time window.
     */
    public function check(int $locationId, string $date, string $time, string $duration): array
    {
        $checkIn = Carbon::parse("{$date} {$time}", config('app.display_timezone'))->setTimezone('UTC');
        $checkOut = $this->calculateCheckOut($checkIn, $duration);

        $bookedStandard = $this->getBookedCount($locationId, 'standard', $checkIn, $checkOut);
        $totalStandard = Locker::where('location_id', $locationId)->where('size', 'standard')->bookable()->count();

        $bookedLarge = $this->getBookedCount($locationId, 'large', $checkIn, $checkOut);
        $totalLarge = Locker::where('location_id', $locationId)->where('size', 'large')->bookable()->count();

        return [
            'standard' => [
                'total' => $totalStandard,
                'booked' => $bookedStandard,
                'available' => max(0, $totalStandard - $bookedStandard),
            ],
            'large' => [
                'total' => $totalLarge,
                'booked' => $bookedLarge,
                'available' => max(0, $totalLarge - $bookedLarge),
            ],
            'check_out_time' => $checkOut->toIso8601String(),
            'location_open' => true,
        ];
    }

    /**
     * Per-item availability: items[] = [{size, duration}, ...]. Each item is
     * checked in its own time window; result is keyed by size and reflects the
     * per-size duration the caller asked about. When the same size appears
     * twice with different durations only the LAST one is returned (callers
     * should not duplicate sizes — coalesce qty before calling).
     */
    public function checkPerItem(int $locationId, string $date, string $time, array $items): array
    {
        $base = Carbon::parse("{$date} {$time}", config('app.display_timezone'))->setTimezone('UTC');
        $out = ['location_open' => true];

        foreach ($items as $item) {
            $size = $item['size'] ?? null;
            $dur = $item['duration'] ?? null;
            if (!$size || !$dur) continue;

            $checkIn = $base->copy();
            $checkOut = $this->calculateCheckOut($checkIn, $dur);
            $booked = $this->getBookedCount($locationId, $size, $checkIn, $checkOut);
            $total = Locker::where('location_id', $locationId)->where('size', $size)->bookable()->count();

            $out[$size] = [
                'total' => $total,
                'booked' => $booked,
                'available' => max(0, $total - $booked),
                'duration' => $dur,
                'check_out_time' => $checkOut->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * Count physical lockers already booked for (locationId, size, window).
     * Counts via booking_items which carry the authoritative per-item size +
     * window — covers mixed-size bookings correctly.
     */
    private function getBookedCount(int $locationId, string $size, Carbon $checkIn, Carbon $checkOut): int
    {
        return BookingItem::query()
            ->where('locker_size', $size)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->whereHas('booking', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                    ->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active]);
            })
            ->sum('qty');
    }

    public function calculateCheckOut(Carbon $checkIn, string $duration): Carbon
    {
        return match ($duration) {
            '6h' => $checkIn->copy()->addHours(6),
            '24h' => $checkIn->copy()->addHours(24),
            '2_days' => $checkIn->copy()->addDays(2),
            '3_days' => $checkIn->copy()->addDays(3),
            '4_days' => $checkIn->copy()->addDays(4),
            '5_days' => $checkIn->copy()->addDays(5),
            '1_week' => $checkIn->copy()->addWeek(),
            '2_weeks' => $checkIn->copy()->addWeeks(2),
            '1_month' => $checkIn->copy()->addMonth(),
            default => $checkIn->copy()->addHours(6),
        };
    }

    public function getDurationLabel(string $duration): string
    {
        // Translated via __() so the booking flow's order summary picks up the
        // current request locale; sr.json already has these keys mapped.
        $key = match ($duration) {
            '6h' => 'Up to 6 hours',
            '24h' => '24 hours',
            '2_days' => '2 days',
            '3_days' => '3 days',
            '4_days' => '4 days',
            '5_days' => '5 days',
            '1_week' => '1 week',
            '2_weeks' => '2 weeks',
            '1_month' => '1 month',
            default => $duration,
        };
        return __($key);
    }
}
