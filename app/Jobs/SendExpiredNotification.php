<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendExpiredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::with(['customer', 'location'])->findOrFail($this->bookingId);

        try {
            Mail::send('emails.booking-expired', ['booking' => $booking], function ($message) use ($booking) {
                $message->to($booking->customer->email)
                    ->subject('Your booking has expired — Belgrade Luggage Locker');
            });

            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'email',
                'template' => 'expired',
                'recipient' => $booking->customer->email,
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => 'email',
                'template' => 'expired',
                'recipient' => $booking->customer->email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);
        }
    }
}
