<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['customer', 'location', 'lockers'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('booking_status', $request->input('status'));
        }
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->has('date_from')) {
            $query->whereDate('check_in', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('check_in', '<=', $request->input('date_to'));
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['customer', 'location', 'lockers', 'notificationLogs'])->findOrFail($id);
        return response()->json($booking);
    }

    public function markPaid(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
        return response()->json(['message' => 'Marked as paid', 'booking' => $booking]);
    }

    public function extend(Request $request, int $id): JsonResponse
    {
        $request->validate(['duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month']);

        $booking = Booking::findOrFail($id);
        $service = app(\App\Services\Booking\AvailabilityService::class);
        $newCheckOut = $service->calculateCheckOut($booking->check_out, $request->input('duration'));

        $booking->update(['check_out' => $newCheckOut]);

        return response()->json(['message' => 'Booking extended', 'new_check_out' => $newCheckOut]);
    }

    public function destroy(int $id, BookingService $service): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $service->cancel($booking, 'Cancelled by admin');
        return response()->json(['message' => 'Booking cancelled']);
    }

    public function export(Request $request): JsonResponse
    {
        $bookings = Booking::with(['customer', 'location'])
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get()
            ->map(fn($b) => [
                'uuid' => $b->uuid,
                'customer' => $b->customer->full_name,
                'email' => $b->customer->email,
                'location' => $b->location->name,
                'locker_size' => $b->locker_size->value,
                'locker_qty' => $b->locker_qty,
                'check_in' => $b->check_in->format('Y-m-d H:i'),
                'check_out' => $b->check_out->format('Y-m-d H:i'),
                'total_eur' => $b->total_eur,
                'booking_status' => $b->booking_status->value,
                'payment_status' => $b->payment_status->value,
                'created_at' => $b->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json($bookings);
    }
}
