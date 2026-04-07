<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotAvailableException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    public function store(Request $request, BookingService $service): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'locker_size' => 'required|in:standard,large',
            'locker_qty' => 'required|integer|min:1|max:20',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
            'payment_method' => 'required|in:cash,stripe',
        ]);

        $validated['customer_id'] = $request->user()->id;

        try {
            $booking = $service->create($validated);

            return response()->json([
                'booking_uuid' => $booking->uuid,
                'redirect_url' => "/booking/{$booking->uuid}/confirmation",
            ], 201);
        } catch (NotAvailableException $e) {
            return response()->json([
                'error' => 'not_available',
                'message' => $e->getMessage(),
                'available' => $e->availability,
            ], 409);
        }
    }

    public function cancel(Request $request, string $uuid, BookingService $service): JsonResponse
    {
        $booking = Booking::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $booking = $service->cancel($booking, $request->input('reason'));

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'booking_status' => $booking->booking_status->value,
        ]);
    }
}
