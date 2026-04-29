<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Location;
use App\Models\PricingRule;

class BookingController extends Controller
{
    public function index(string $slug)
    {
        $location = Location::where(fn ($q) => $q->where('slug', $slug)->orWhere('slug_sr', $slug))->active()->firstOrFail();

        $durations = PricingRule::active()
            ->where(function ($q) use ($location) {
                $q->where('location_id', $location->id)
                    ->orWhereNull('location_id');
            })
            ->orderByRaw("FIELD(duration_key, '6h','24h','2_days','3_days','4_days','5_days','1_week','2_weeks','1_month')")
            ->get()
            ->groupBy('locker_size');

        return view('public.booking.index', compact('location', 'durations'));
    }

    public function confirmation(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)
            ->with(['location', 'customer', 'lockers', 'items'])
            ->firstOrFail();

        return view('public.booking.confirmation', compact('booking'));
    }

    public function cancelForm(\Illuminate\Http\Request $request, string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)
            ->with(['location', 'customer', 'items'])
            ->firstOrFail();

        // Token-gate the cancel form. If no/wrong token, hide PII and ask for the email
        // matching the booking — defends against UUID-scraping while keeping the flow
        // friendly for the customer who got the email link.
        $token = $request->query('token');
        $expected = hash_hmac('sha256', $booking->uuid.'|'.$booking->customer_id, config('app.key'));
        $authorized = $token && hash_equals($expected, $token);

        return view('public.booking.cancel', [
            'booking' => $booking,
            'cancelToken' => $authorized ? $token : null,
            'authorized' => $authorized,
        ]);
    }
}
