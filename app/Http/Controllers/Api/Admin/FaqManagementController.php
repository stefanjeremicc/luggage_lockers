<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Faq::with('category')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['sort_order'] = (int) Faq::max('sort_order') + 1;

        return response()->json(Faq::create($validated), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Faq::with('category')->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $faq = Faq::findOrFail($id);
        $validated = $this->validatePayload($request, updating: true);
        $faq->update($validated);
        return response()->json($faq->load('category'));
    }

    public function destroy(int $id): JsonResponse
    {
        Faq::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:faqs,id',
        ]);

        foreach ($validated['ids'] as $position => $id) {
            Faq::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    private function validatePayload(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes|required' : 'required';

        return $request->validate([
            'question' => "{$required}|string|max:500",
            'question_sr' => 'nullable|string|max:500',
            'answer' => "{$required}|string",
            'answer_sr' => 'nullable|string',
            'faq_category_id' => 'nullable|integer|exists:faq_categories,id',
            'is_active' => 'boolean',
        ]);
    }
}
