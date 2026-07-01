<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Locker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        $locations = Location::active()->with([
            'lockers' => fn($q) => $q->active(),
            'lockers.currentBookings.customer:id,full_name,email',
        ])->get();

        $todayBookings = Booking::whereDate('check_in', $today)->count();

        // Revenue is attributed to the booking's CHECK-IN date (the day the
        // stay starts), not created_at or check_out. A booking made on the 23rd
        // for arrival on the 23rd counts on the 23rd even if it ends the 24th.
        $revenueToday = Booking::whereDate('check_in', $today)
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueWeek = Booking::where('check_in', '>=', $today->copy()->startOfWeek())
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueMonth = Booking::where('check_in', '>=', $today->copy()->startOfMonth())
            ->where('payment_status', 'paid')->sum('total_eur');

        // Quick-look metrics that surface concrete numbers the Analytics view
        // hides behind filters. Kept cheap (raw counts/sums, no joins) so the
        // dashboard endpoint stays under ~50ms even with thousands of rows.
        $tomorrow = $today->copy()->addDay();
        $tomorrowBookings = Booking::whereDate('check_in', $tomorrow)
            ->where('booking_status', '!=', BookingStatus::Cancelled)
            ->count();

        // Outstanding money (this month): bookings with a check-in date inside
        // the current calendar month that haven't been marked paid AND aren't
        // cancelled. Both bounds matter — without the upper bound this also sums
        // every *future* unpaid booking and reads like an all-time figure.
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfNextMonth = $startOfMonth->copy()->addMonth();
        $unpaidMonth = Booking::where('check_in', '>=', $startOfMonth)
            ->where('check_in', '<', $startOfNextMonth)
            ->where('payment_status', '!=', 'paid')
            ->where('booking_status', '!=', BookingStatus::Cancelled)
            ->sum('total_eur');

        // Average paid booking value (this month). Divide by the count, not by
        // the SUM — sum / 0 would silently produce 0 and look like "no money".
        $paidCountMonth = Booking::where('check_in', '>=', $today->copy()->startOfMonth())
            ->where('payment_status', 'paid')->count();
        $avgBookingMonth = $paidCountMonth > 0
            ? round($revenueMonth / $paidCountMonth, 2)
            : 0;

        // Live occupancy: lockers currently rented (active booking covering now)
        // vs all bookable lockers. Useful at-a-glance "how full are we right now".
        $totalActiveLockers = Locker::active()->count();
        $occupiedLockers = Locker::active()
            ->whereHas('bookings', function ($q) {
                $q->whereIn('booking_status', [BookingStatus::Confirmed, BookingStatus::Active])
                  ->where('check_in', '<=', now())
                  ->where('check_out', '>=', now());
            })->count();
        $occupancyPct = $totalActiveLockers > 0
            ? (int) round($occupiedLockers / $totalActiveLockers * 100)
            : 0;

        // Marketing attribution breakdown (first-touch) over the last 30 days.
        // Bookings predating attribution tracking have a null source → "unknown".
        $attrSince = $today->copy()->subDays(30);
        $sourceCounts = Booking::where('created_at', '>=', $attrSince)
            ->selectRaw("COALESCE(NULLIF(marketing_source, ''), 'unknown') as src, COUNT(*) as c")
            ->groupBy('src')
            ->pluck('c', 'src');

        $channels = ['google_ads', 'facebook', 'organic', 'referral', 'qr', 'direct', 'other', 'unknown'];
        $attrTotal = (int) $sourceCounts->sum();
        $attribution = collect($channels)
            ->map(fn($key) => [
                'key' => $key,
                'count' => (int) ($sourceCounts[$key] ?? 0),
                'pct' => $attrTotal > 0 ? round(($sourceCounts[$key] ?? 0) / $attrTotal * 100) : 0,
            ])
            ->filter(fn($r) => $r['count'] > 0)
            ->sortByDesc('count')
            ->values();

        $lowBatteryLockers = Locker::active()->where('battery_level', '<', 20)->with('location')->get();
        $offlineLockers = Locker::active()->where('is_online', false)->with('location')->get();

        $lockerGrid = $locations->map(fn($loc) => [
            'id' => $loc->id,
            'name' => $loc->name,
            'lockers' => $loc->lockers->map(function($l) {
                $cur = $l->currentBookings->first();
                return [
                    'id' => $l->id,
                    'number' => $l->number,
                    'size' => $l->size->value,
                    'status' => $cur ? 'occupied' : $l->status->value,
                    'battery_level' => $l->battery_level,
                    'is_online' => $l->is_online,
                    'current_booking' => $cur ? [
                        'id' => $cur->id,
                        'customer_name' => $cur->customer?->full_name,
                        'check_out' => $cur->check_out,
                    ] : null,
                ];
            }),
        ]);

        return response()->json([
            'locker_grid' => $lockerGrid,
            'stats' => [
                'today_bookings' => $todayBookings,
                'revenue_today' => $revenueToday,
                'revenue_week' => $revenueWeek,
                'revenue_month' => $revenueMonth,
                // Quick-look additions surfaced in the dashboard cards
                'tomorrow_bookings' => $tomorrowBookings,
                'unpaid_month' => (float) $unpaidMonth,
                'avg_booking_month' => (float) $avgBookingMonth,
                'occupancy_pct' => $occupancyPct,
                'occupied_lockers' => $occupiedLockers,
                'total_lockers' => $totalActiveLockers,
            ],
            'attribution' => [
                'total' => $attrTotal,
                'channels' => $attribution,
            ],
            'alerts' => [
                'low_battery' => $lowBatteryLockers,
                'offline' => $offlineLockers,
            ],
        ]);
    }
}
