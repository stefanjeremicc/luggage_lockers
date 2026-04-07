<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locker;
use App\Services\Lock\LockServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LockerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Locker::with('location')->orderBy('location_id')->orderBy('sort_order');

        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'number' => 'required|string|max:20',
            'size' => 'required|in:standard,large',
            'ttlock_lock_id' => 'nullable|integer',
        ]);

        $locker = Locker::create(array_merge($validated, [
            'uuid' => Str::uuid(),
            'status' => 'available',
            'is_active' => true,
        ]));

        return response()->json($locker, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $locker = Locker::findOrFail($id);
        $locker->update($request->only(['number', 'size', 'ttlock_lock_id', 'status', 'is_active', 'sort_order']));
        return response()->json($locker);
    }

    public function destroy(int $id): JsonResponse
    {
        Locker::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'Locker deactivated']);
    }

    public function remoteUnlock(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = Locker::findOrFail($id);

        if (!$locker->ttlock_lock_id) {
            return response()->json(['message' => 'No TTLock ID configured'], 400);
        }

        try {
            $lockService->remoteUnlock($locker->ttlock_lock_id);
            return response()->json(['message' => 'Locker unlocked']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unlock failed: ' . $e->getMessage()], 500);
        }
    }
}
