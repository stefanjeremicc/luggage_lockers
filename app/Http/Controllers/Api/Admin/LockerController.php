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
        $query = Locker::with([
                'location',
                'currentBookings.customer:id,full_name,email',
                'upcomingBookings' => fn($q) => $q->orderBy('check_in')->limit(1),
                'upcomingBookings.customer:id,full_name,email',
            ])
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
            'location_id' => 'nullable|exists:locations,id',
            'number' => 'required|string|max:20',
            'size' => 'required|in:standard,large',
            'ttlock_lock_id' => 'nullable|integer|unique:lockers,ttlock_lock_id',
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

    /**
     * All bookings on this locker that are currently in progress or scheduled in the future.
     * Used by the LockerDetail page to show admins the upcoming reservation timeline.
     */
    public function bookings(int $id): JsonResponse
    {
        $locker = Locker::findOrFail($id);

        $rows = $locker->bookings()
            ->with('customer:id,full_name,email,phone')
            ->whereIn('booking_status', [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Active])
            ->where('check_out', '>=', now())
            ->orderBy('check_in')
            ->get(['bookings.id', 'uuid', 'customer_id', 'check_in', 'check_out', 'booking_status', 'payment_status', 'total_eur'])
            ->map(function ($b) {
                $now = now();
                return [
                    'id' => $b->id,
                    'uuid' => $b->uuid,
                    'customer' => $b->customer ? [
                        'full_name' => $b->customer->full_name,
                        'email' => $b->customer->email,
                        'phone' => $b->customer->phone,
                    ] : null,
                    'check_in' => $b->check_in,
                    'check_out' => $b->check_out,
                    'booking_status' => $b->booking_status->value,
                    'payment_status' => $b->payment_status->value,
                    'total_eur' => $b->total_eur,
                    'is_active_now' => $b->check_in <= $now && $b->check_out >= $now,
                ];
            });

        return response()->json($rows);
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
        // TTLock keyboardPwdType:
        //   1 = Custom (requires startDate + endDate)
        //   2 = Permanent (no dates — never expires)
        //   3 = Timed (requires startDate + endDate)
        $validated = $request->validate([
            'code' => 'required|string|min:4|max:9',
            'type' => 'required|integer|in:1,2,3',
            'name' => 'nullable|string|max:100',
            'start' => 'required_unless:type,2|nullable|date',
            'end' => 'required_unless:type,2|nullable|date|after:start',
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
            $matchedByAlias = 0;
            $created = 0;

            foreach ($locks as $lock) {
                $lockId = $lock['lockId'] ?? null;
                $alias = trim((string) ($lock['lockAlias'] ?? ''));
                if (!$lockId) continue;

                // 1) Already mapped by ttlock_lock_id?
                $locker = Locker::where('ttlock_lock_id', $lockId)->first();

                // 2) Try to map by matching alias to existing locker.number that has no TTLock id yet.
                if (!$locker && $alias !== '') {
                    $candidate = Locker::whereNull('ttlock_lock_id')->where('number', $alias)->first();
                    if ($candidate) {
                        $candidate->update(['ttlock_lock_id' => $lockId]);
                        $locker = $candidate;
                        $matchedByAlias++;
                    }
                }

                // 3) Auto-create only if requested AND alias is non-empty (so we don't pollute DB with garbage).
                if (!$locker && request()->boolean('auto_create') && $alias !== '') {
                    $locker = Locker::create([
                        'uuid' => Str::uuid(),
                        'ttlock_lock_id' => $lockId,
                        'number' => $alias,
                        'size' => 'standard',
                        'status' => 'available',
                        'is_active' => true,
                        'is_published_on_site' => false,
                    ]);
                    $created++;
                }

                if ($locker) {
                    $locker->update([
                        'battery_level' => $lock['electricQuantity'] ?? $locker->battery_level,
                        'is_online' => true,
                        'last_synced_at' => now(),
                    ]);
                    $updated++;
                }
            }

            $unmapped = collect($locks)->filter(fn ($l) =>
                !Locker::where('ttlock_lock_id', $l['lockId'] ?? 0)->exists()
            )->values()->all();

            return response()->json([
                'updated' => $updated,
                'matched_by_alias' => $matchedByAlias,
                'created' => $created,
                'total_api' => count($locks),
                'unmapped' => $unmapped, // list of TTLock locks still without a DB mapping
            ]);
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
