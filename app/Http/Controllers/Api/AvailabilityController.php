<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Booking\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function check(Request $request, int $id, AvailabilityService $service): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'duration' => 'nullable|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
            'items' => 'nullable|array',
            'items.*.size' => 'required_with:items|in:standard,large',
            'items.*.duration' => 'required_with:items|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
        ]);

        // Per-item mode: client sends items[]={size, duration} so each size is
        // checked in its own window. Falls back to legacy single-window mode
        // when only `duration` is provided.
        if (is_array($request->input('items')) && count($request->input('items'))) {
            $result = $service->checkPerItem(
                $id,
                $request->input('date'),
                $request->input('time'),
                $request->input('items')
            );
        } else {
            $duration = $request->input('duration');
            if (!$duration) {
                return response()->json(['error' => 'duration or items[] required'], 422);
            }
            $result = $service->check($id, $request->input('date'), $request->input('time'), $duration);
        }

        return response()->json($result);
    }
}
