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
        $activeBookings = Booking::active()->count();
        $overdueBookings = Booking::active()->where('check_out', '<', now())->count();

        // Revenue is attributed to the booking's CHECK-IN date (the day the
        // stay starts), not created_at or check_out. A booking made on the 23rd
        // for arrival on the 23rd counts on the 23rd even if it ends the 24th.
        $revenueToday = Booking::whereDate('check_in', $today)
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueWeek = Booking::where('check_in', '>=', $today->copy()->startOfWeek())
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueMonth = Booking::where('check_in', '>=', $today->copy()->startOfMonth())
            ->where('payment_status', 'paid')->sum('total_eur');

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
                'active_bookings' => $activeBookings,
                'overdue_bookings' => $overdueBookings,
                'revenue_today' => $revenueToday,
                'revenue_week' => $revenueWeek,
                'revenue_month' => $revenueMonth,
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
