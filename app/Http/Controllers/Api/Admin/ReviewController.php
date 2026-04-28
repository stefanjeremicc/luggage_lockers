<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Review::orderBy('sort_order')->orderBy('id')->get());
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:reviews,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Review::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'text' => 'required|string',
            'text_sr' => 'nullable|string',
            'rating' => 'integer|min:1|max:5',
            'source' => 'string|max:50',
            'status' => 'nullable|in:pending,approved,rejected',
            'avatar_letter' => 'nullable|string|max:1',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);
        $validated['status'] = $validated['status'] ?? 'approved';
        if (empty($validated['avatar_letter'])) {
            $validated['avatar_letter'] = mb_strtoupper(mb_substr($validated['name'], 0, 1));
        }

        return response()->json(Review::create($validated), 201);
    }

    public function approve(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved', 'is_active' => true]);
        return response()->json($review);
    }

    public function reject(int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'rejected', 'is_active' => false]);
        return response()->json($review);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Review::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->update($request->all());
        return response()->json($review);
    }

    public function destroy(int $id): JsonResponse
    {
        Review::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
