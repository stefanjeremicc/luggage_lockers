<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\Notification\BookingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendExpiredNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::with(['customer', 'location'])->findOrFail($this->bookingId);
        BookingNotifier::send($booking, 'booking_expired');
    }
}
