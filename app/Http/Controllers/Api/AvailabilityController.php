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
            'duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
        ]);

        $result = $service->check(
            $id,
            $request->input('date'),
            $request->input('time'),
            $request->input('duration')
        );

        return response()->json($result);
    }
}
