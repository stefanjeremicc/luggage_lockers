<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(PricingRule::with('location')->orderBy('location_id')->orderByRaw("FIELD(locker_size, 'standard', 'large')")->orderByRaw("FIELD(duration_key, '6h','24h','2_days','3_days','4_days','5_days','1_week','2_weeks','1_month')")->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'locker_size' => 'required|in:standard,large',
            'duration_key' => 'required|string',
            'price_eur' => 'required|numeric|min:0',
        ]);

        $rule = PricingRule::create($validated);
        return response()->json($rule, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = PricingRule::findOrFail($id);
        $rule->update($request->only(['price_eur', 'is_active']));
        return response()->json($rule);
    }

    public function destroy(int $id): JsonResponse
    {
        PricingRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
