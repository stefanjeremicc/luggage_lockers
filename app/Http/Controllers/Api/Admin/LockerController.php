<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locker;
use App\Services\Lock\LockServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LockerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Natural sort by locker number via LENGTH() + number.
        $query = Locker::with('location')
            ->orderBy('location_id')
            ->orderBy('sort_order')
            ->orderByRaw('LENGTH(number)')
            ->orderBy('number');

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

    public function show(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = Locker::with('location')->findOrFail($id);
        $detail = null;
        if ($locker->ttlock_lock_id) {
            try {
                $detail = $lockService->getLockDetail($locker->ttlock_lock_id);
            } catch (\Throwable $e) {
                $detail = ['error' => $e->getMessage()];
            }
        }
        return response()->json(['locker' => $locker, 'ttlock_detail' => $detail]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $locker = Locker::findOrFail($id);
        $locker->update($request->only([
            'number', 'size', 'ttlock_lock_id', 'status', 'is_active',
            'sort_order', 'site_sort_order', 'location_id', 'is_published_on_site',
        ]));
        return response()->json($locker);
    }

    public function destroy(int $id): JsonResponse
    {
        Locker::findOrFail($id)->update(['is_active' => false]);
        return response()->json(['message' => 'Locker deactivated']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:lockers,id',
            'field' => 'nullable|in:sort_order,site_sort_order',
        ]);
        $field = $validated['field'] ?? 'site_sort_order';

        foreach ($validated['ids'] as $idx => $lockerId) {
            Locker::where('id', $lockerId)->update([$field => $idx]);
        }

        return response()->json(['message' => 'Reordered']);
    }

    public function remoteUnlock(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $lockService->remoteUnlock($locker->ttlock_lock_id);
            return response()->json(['message' => 'Locker unlocked']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unlock failed: ' . $e->getMessage()], 500);
        }
    }

    public function remoteLock(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $lockService->remoteLock($locker->ttlock_lock_id);
            return response()->json(['message' => 'Locker locked']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lock failed: ' . $e->getMessage()], 500);
        }
    }

    public function passcodesIndex(int $id, Request $request, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $data = $lockService->getPasscodes(
                $locker->ttlock_lock_id,
                (int)$request->input('page', 1),
                (int)$request->input('pageSize', 100),
            );
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function passcodeStore(int $id, Request $request, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        $validated = $request->validate([
            'code' => 'required|string|min:4|max:9',
            'type' => 'required|integer|in:1,2,3',
            'name' => 'nullable|string|max:100',
            'start' => 'nullable|date',
            'end' => 'nullable|date|after:start',
        ]);

        try {
            $resp = $lockService->createPasscode(
                $locker->ttlock_lock_id,
                $validated['code'],
                $validated['type'],
                !empty($validated['start']) ? Carbon::parse($validated['start']) : null,
                !empty($validated['end']) ? Carbon::parse($validated['end']) : null,
                $validated['name'] ?? null,
            );
            return response()->json($resp);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function passcodeUpdate(int $id, int $pwdId, Request $request, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        $validated = $request->validate(['end' => 'required|date']);
        try {
            $ok = $lockService->updateAccessCodeTime($locker->ttlock_lock_id, $pwdId, Carbon::parse($validated['end']));
            return response()->json(['success' => $ok]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function passcodeDestroy(int $id, int $pwdId, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $ok = $lockService->deleteAccessCode($locker->ttlock_lock_id, $pwdId);
            return response()->json(['success' => $ok]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function unlockRecords(int $id, Request $request, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        $start = $request->has('start') ? Carbon::parse($request->input('start')) : Carbon::now()->subDays(7);
        $end = $request->has('end') ? Carbon::parse($request->input('end')) : Carbon::now();
        try {
            $data = $lockService->getUnlockRecords($locker->ttlock_lock_id, $start, $end);
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function rename(int $id, Request $request, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        $validated = $request->validate(['alias' => 'required|string|max:50']);
        try {
            $ok = $lockService->renameLockAlias($locker->ttlock_lock_id, $validated['alias']);
            if ($ok) {
                $locker->update(['number' => $validated['alias']]);
            }
            return response()->json(['success' => $ok, 'locker' => $locker->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function sync(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $detail = $lockService->getLockDetail($locker->ttlock_lock_id);
            $locker->update([
                'battery_level' => $detail['electricQuantity'] ?? $locker->battery_level,
                'is_online' => true,
                'last_synced_at' => now(),
            ]);
            return response()->json(['locker' => $locker->fresh(), 'ttlock_detail' => $detail]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function syncAll(LockServiceInterface $lockService): JsonResponse
    {
        try {
            $data = $lockService->getLockList();
            $locks = $data['list'] ?? [];
            $updated = 0;
            foreach ($locks as $lock) {
                $locker = Locker::where('ttlock_lock_id', $lock['lockId'])->first();
                if ($locker) {
                    $locker->update([
                        'battery_level' => $lock['electricQuantity'] ?? $locker->battery_level,
                        'is_online' => true,
                        'last_synced_at' => now(),
                    ]);
                    $updated++;
                }
            }
            return response()->json(['updated' => $updated, 'total_api' => count($locks)]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function requireWithLockId(int $id): Locker
    {
        $locker = Locker::findOrFail($id);
        abort_if(!$locker->ttlock_lock_id, 400, 'No TTLock ID configured');
        return $locker;
    }
}
