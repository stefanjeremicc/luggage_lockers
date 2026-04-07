<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Booking\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function calculate(Request $request, int $id, PricingService $service): JsonResponse
    {
        $request->validate([
            'size' => 'required|in:standard,large',
            'duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
            'qty' => 'required|integer|min:1|max:20',
        ]);

        $result = $service->calculate(
            $id,
            $request->input('size'),
            $request->input('duration'),
            (int) $request->input('qty', 1)
        );

        return response()->json($result);
    }
}
