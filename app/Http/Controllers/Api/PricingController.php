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
        // Accept either:
        //   • new multi-size: items[] = [{size, qty}, ...]
        //   • legacy single-size: size + qty
        $rawItems = $request->input('items');
        $items = [];

        if (is_array($rawItems) && count($rawItems)) {
            $request->validate([
                'duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
                'items' => 'required|array|min:1',
                'items.*.size' => 'required|in:standard,large',
                'items.*.qty' => 'required|integer|min:1|max:20',
            ]);
            $items = $rawItems;
        } else {
            $request->validate([
                'size' => 'required|in:standard,large',
                'duration' => 'required|string|in:6h,24h,2_days,3_days,4_days,5_days,1_week,2_weeks,1_month',
                'qty' => 'required|integer|min:1|max:20',
            ]);
            $items = [['size' => $request->input('size'), 'qty' => (int) $request->input('qty', 1)]];
        }

        $result = $service->calculateForItems($id, $items, $request->input('duration'));

        return response()->json($result);
    }
}
