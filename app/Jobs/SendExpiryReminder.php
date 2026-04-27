<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\Notification\BookingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendExpiryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $reminderMinutes = (int) Setting::getValue('expiry_reminder_minutes', 30);
        $reminderTime = now()->addMinutes($reminderMinutes);

        $bookings = Booking::whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
            ->where('check_out', '<=', $reminderTime)
            ->where('check_out', '>', now())
            ->whereDoesntHave('notificationLogs', fn($q) => $q->where('template', 'expiry_reminder'))
            ->with(['customer', 'location'])
            ->get();

        foreach ($bookings as $booking) {
            BookingNotifier::send($booking, 'expiry_reminder');
        }
    }
}
