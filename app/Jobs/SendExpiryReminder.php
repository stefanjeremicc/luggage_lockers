<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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
            try {
                Mail::send('emails.booking-reminder', ['booking' => $booking], function ($message) use ($booking) {
                    $message->to($booking->customer->email)
                        ->subject('Your locker time ends soon — Belgrade Luggage Locker');
                });

                NotificationLog::create([
                    'booking_id' => $booking->id,
                    'channel' => 'email',
                    'template' => 'expiry_reminder',
                    'recipient' => $booking->customer->email,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                NotificationLog::create([
                    'booking_id' => $booking->id,
                    'channel' => 'email',
                    'template' => 'expiry_reminder',
                    'recipient' => $booking->customer->email,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'created_at' => now(),
                ]);
            }
        }
    }
}
