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
        $location = Location::where('slug', $slug)->active()->firstOrFail();

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
            ->with(['location', 'customer', 'lockers'])
            ->firstOrFail();

        return view('public.booking.confirmation', compact('booking'));
    }

    public function cancelForm(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)
            ->with(['location', 'customer'])
            ->firstOrFail();

        return view('public.booking.cancel', compact('booking'));
    }
}
