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
        return response()->json(Faq::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|in:en,sr',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer',
        ]);

        return response()->json(Faq::create($validated), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $faq = Faq::findOrFail($id);
        $faq->update($request->all());
        return response()->json($faq);
    }

    public function destroy(int $id): JsonResponse
    {
        Faq::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
