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

        $locations = Location::active()->with(['lockers' => fn($q) => $q->active()])->get();

        $todayBookings = Booking::whereDate('check_in', $today)->count();
        $activeBookings = Booking::active()->count();
        $overdueBookings = Booking::active()->where('check_out', '<', now())->count();

        $revenueToday = Booking::whereDate('created_at', $today)
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueWeek = Booking::where('created_at', '>=', $today->copy()->startOfWeek())
            ->where('payment_status', 'paid')->sum('total_eur');
        $revenueMonth = Booking::where('created_at', '>=', $today->copy()->startOfMonth())
            ->where('payment_status', 'paid')->sum('total_eur');

        $lowBatteryLockers = Locker::active()->where('battery_level', '<', 20)->with('location')->get();
        $offlineLockers = Locker::active()->where('is_online', false)->with('location')->get();

        $lockerGrid = $locations->map(fn($loc) => [
            'id' => $loc->id,
            'name' => $loc->name,
            'lockers' => $loc->lockers->map(fn($l) => [
                'id' => $l->id,
                'number' => $l->number,
                'size' => $l->size->value,
                'status' => $l->status->value,
                'battery_level' => $l->battery_level,
                'is_online' => $l->is_online,
            ]),
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
            'alerts' => [
                'low_battery' => $lowBatteryLockers,
                'offline' => $offlineLockers,
            ],
        ]);
    }
}
