<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            NotificationTemplate::orderBy('key')->orderBy('channel')->orderBy('locale')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);
        return response()->json(NotificationTemplate::create($validated), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(NotificationTemplate::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $validated = $this->validateRequest($request, $id);
        $template->update($validated);
        return response()->json($template);
    }

    public function destroy(int $id): JsonResponse
    {
        NotificationTemplate::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function validateRequest(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'key' => 'required|string|max:100',
            'channel' => 'required|in:email,whatsapp,sms',
            'locale' => 'required|in:en,sr',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
    }
}
