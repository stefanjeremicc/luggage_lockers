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
 * Used by the admin "Resend confirmation" action. The booking-create flow does
 * not dispatch this — it calls BookingNotifier::sendBookingConfirmation
 * directly after every TTLock passcode is registered, so the customer gets one
 * synchronous email with the PIN already live.
 */
class SendBookingConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::with(['customer', 'location', 'bookingLockers.locker'])->findOrFail($this->bookingId);
        BookingNotifier::sendBookingConfirmation($booking);
    }
}
