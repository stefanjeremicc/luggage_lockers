<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\Notification\BookingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by BookingService::cancel(). The exact template (and therefore
 * the voice — "we cancelled your booking" vs "your booking was cancelled at
 * your request") is decided by the caller and passed in.
 */
class SendBookingCancelled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $bookingId,
        private string $templateKey = 'booking_cancelled_by_customer',
    ) {}

    public function handle(): void
    {
        $booking = Booking::with(['customer', 'location'])->findOrFail($this->bookingId);
        BookingNotifier::send($booking, $this->templateKey);
    }
}
