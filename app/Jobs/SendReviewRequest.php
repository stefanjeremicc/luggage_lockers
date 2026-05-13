<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Notification\BookingNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduler hook (every 5 min): for bookings that completed at least 30 min
 * ago and that we haven't yet emailed for review, send the Google review
 * request. Tracked via notification_log so we never spam the same customer
 * twice even if the scheduler races itself.
 */
class SendReviewRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Minutes after check_out before we ping the customer. */
    public const POST_CHECKOUT_DELAY_MINUTES = 30;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(self::POST_CHECKOUT_DELAY_MINUTES);

        $bookings = Booking::query()
            ->whereIn('booking_status', [BookingStatus::Completed, BookingStatus::Expired, BookingStatus::Active, BookingStatus::Confirmed])
            ->where('check_out', '<=', $cutoff)
            ->whereDoesntHave('notificationLogs', fn ($q) => $q->where('template', 'review_request'))
            ->with(['customer', 'location'])
            ->limit(50)
            ->get();

        foreach ($bookings as $booking) {
            // Skip cancelled bookings entirely — but the where above already
            // filters them. Belt + braces.
            if ($booking->booking_status === BookingStatus::Cancelled) continue;

            try {
                BookingNotifier::send($booking, 'review_request');
            } catch (\Throwable $e) {
                Log::error('review_request send failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
