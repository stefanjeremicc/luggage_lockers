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
        // Validation supports two shapes: legacy single-size (locker_size + locker_qty) and
        // new multi-size (items[]). Internally we normalise to items[] so BookingService
        // sees one uniform format.
        $base = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
            'payment_method' => 'required|in:cash,stripe',
        ]);

        if (is_array($request->input('items')) && count($request->input('items'))) {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.size' => 'required|in:standard,large',
                'items.*.qty' => 'required|integer|min:1|max:20',
            ]);
            $base['items'] = $request->input('items');
        } else {
            $request->validate([
                'locker_size' => 'required|in:standard,large',
                'locker_qty' => 'required|integer|min:1|max:20',
            ]);
            $base['items'] = [[
                'size' => $request->input('locker_size'),
                'qty' => (int) $request->input('locker_qty'),
            ]];
        }

        $base['customer_id'] = $request->user()->id;
        $validated = $base;

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

        // Authorisation: either the customer who owns this booking (via sanctum), or a
        // valid signed cancel token. The cancel link in confirmation emails embeds a token.
        $authorized = false;
        $user = $request->user();
        if ($user && $user->id === $booking->customer_id) {
            $authorized = true;
        }
        if (!$authorized) {
            $token = $request->input('token') ?? $request->query('token');
            $expected = hash_hmac('sha256', $booking->uuid.'|'.$booking->customer_id, config('app.key'));
            if ($token && hash_equals($expected, $token)) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking = $service->cancel($booking, $request->input('reason'));

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'booking_status' => $booking->booking_status->value,
        ]);
    }
}
