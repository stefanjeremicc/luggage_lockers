<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            FaqCategory::withCount('faqs')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'name_sr' => 'nullable|string|max:100',
        ]);
        $validated['sort_order'] = (int) FaqCategory::max('sort_order') + 1;

        return response()->json(FaqCategory::create($validated), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = FaqCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'name_sr' => 'nullable|string|max:100',
        ]);
        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(int $id): JsonResponse
    {
        FaqCategory::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:faq_categories,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            FaqCategory::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Reordered']);
    }
}
