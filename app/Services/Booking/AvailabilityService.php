<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Locker;
use Carbon\Carbon;

class AvailabilityService
{
    public function check(int $locationId, string $date, string $time, string $duration): array
    {
        $checkIn = Carbon::parse("{$date} {$time}", config('app.display_timezone'))->setTimezone('UTC');
        $checkOut = $this->calculateCheckOut($checkIn, $duration);

        $bookedStandard = $this->getBookedCount($locationId, 'standard', $checkIn, $checkOut);
        $totalStandard = Locker::where('location_id', $locationId)->where('size', 'standard')->active()->count();

        $bookedLarge = $this->getBookedCount($locationId, 'large', $checkIn, $checkOut);
        $totalLarge = Locker::where('location_id', $locationId)->where('size', 'large')->active()->count();

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

    private function getBookedCount(int $locationId, string $size, Carbon $checkIn, Carbon $checkOut): int
    {
        return Booking::where('location_id', $locationId)
            ->where('locker_size', $size)
            ->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->sum('locker_qty');
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
        return match ($duration) {
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
    }
}
