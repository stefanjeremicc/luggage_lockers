<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn($group) => $group->mapWithKeys(fn($s) => [$s->key => $s->value]));

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['settings' => 'required|array']);

        foreach ($request->input('settings') as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
            }
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
