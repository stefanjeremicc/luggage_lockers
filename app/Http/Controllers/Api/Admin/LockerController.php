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
            // Inactive/unreachable lockers float to the bottom of the list so the
            // admin sees actionable rows first.
            ->orderByRaw('is_active DESC')
            ->orderByRaw('is_online DESC')
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
            'sort_order', 'location_id',
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
            'field' => 'nullable|in:sort_order',
        ]);
        $field = $validated['field'] ?? 'sort_order';

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

        // Fast path: if the lock firmware actually supports motorised remote unlock,
        // use it. Smart door locks support this; most cabinet/locker SKUs return
        // -4043. We catch and fall through to a one-time-PIN fallback so the admin
        // always gets a working unlock action regardless of lock model.
        try {
            $lockService->remoteUnlock($locker->ttlock_lock_id);
            return response()->json(['message' => 'Locker unlocked']);
        } catch (\Throwable $e) {
            // Fallback: 5-minute timed passcode pushed via gateway. Admin (or whoever
            // is on-site) types it on the keypad — same end result, just one extra tap.
            try {
                // Wide window (-15 min … +30 min) so RTC drift on the cabinet lock
                // doesn't reject the PIN as "not yet valid" or "expired". Type 3
                // (Timed) with addType=2 is the path we've proven actually pushes
                // through the gateway to the physical keypad.
                $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $start = now()->subMinutes(15);
                $end = now()->addMinutes(30);
                $resp = $lockService->createTimedAccessCode(
                    $locker->ttlock_lock_id,
                    $pin,
                    $start,
                    $end
                );
                return response()->json([
                    'message' => "One-time PIN {$pin} pushed to {$locker->number}. Valid ~30 minutes — wait 10 sec, then enter on keypad.",
                    'pin' => $pin,
                    'expires_at' => $end->toIso8601String(),
                    'keyboard_pwd_id' => $resp['keyboardPwdId'] ?? null,
                ]);
            } catch (\Throwable $f) {
                return response()->json(['message' => 'Unlock failed: ' . $f->getMessage()], 500);
            }
        }
    }

    public function remoteLock(int $id, LockServiceInterface $lockService): JsonResponse
    {
        $locker = $this->requireWithLockId($id);
        try {
            $lockService->remoteLock($locker->ttlock_lock_id);
            return response()->json(['message' => 'Locker locked']);
        } catch (\Throwable $e) {
            // Cabinet/locker firmware that doesn't expose a remote lock command auto-locks
            // when the door is shut — surface that as a non-error so the UI flow doesn't
            // dead-end on a model that physically can't accept the command.
            return response()->json([
                'message' => "{$locker->number} locks automatically when the door is closed.",
                'auto_lock' => true,
            ]);
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
                !empty($validated['start']) ? Carbon::parse($validated['start'], config('app.display_timezone')) : null,
                !empty($validated['end']) ? Carbon::parse($validated['end'], config('app.display_timezone')) : null,
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
            $ok = $lockService->updateAccessCodeTime($locker->ttlock_lock_id, $pwdId, Carbon::parse($validated['end'], config('app.display_timezone')));
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
        $tz = config('app.display_timezone');
        // Default window 90 days so admins see meaningful history without picking a range
        // (TTLock keeps records ~3 months on cabinet locks).
        $start = $request->has('start') ? Carbon::parse($request->input('start'), $tz) : Carbon::now()->subDays(90);
        $end = $request->has('end') ? Carbon::parse($request->input('end'), $tz) : Carbon::now();
        try {
            $data = $lockService->getUnlockRecords($locker->ttlock_lock_id, $start, $end);
            $list = collect($data['list'] ?? [])->map(function ($r) {
                $rt = (int) ($r['recordType'] ?? 0);
                $meta = $this->ttlockRecordMeta($rt);
                $r['event_label'] = $meta['label'];
                $r['event_kind'] = $meta['kind']; // unlock | lock | fail | other
                $r['event_source'] = $meta['source']; // app | passcode | card | fingerprint | remote | manual | auto
                return $r;
            })->sortByDesc(fn ($r) => $r['lockDate'] ?? 0)->values()->all();

            return response()->json([
                'list' => $list,
                'range' => [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * TTLock recordType decoder. The TTLock API returns a numeric type per record;
     * we map it to a human label, an action kind (lock/unlock/fail), and the source
     * (how the action was initiated). Unknown codes fall back to "Event #N".
     */
    private function ttlockRecordMeta(int $type): array
    {
        $map = [
            1 => ['Unlocked via app', 'unlock', 'app'],
            4 => ['Unlocked with passcode', 'unlock', 'passcode'],
            7 => ['Unlocked with card', 'unlock', 'card'],
            8 => ['Unlocked with fingerprint', 'unlock', 'fingerprint'],
            9 => ['Wrong passcode', 'fail', 'passcode'],
            11 => ['Unlocked remotely', 'unlock', 'remote'],
            12 => ['Wrong fingerprint', 'fail', 'fingerprint'],
            27 => ['Locked manually', 'lock', 'manual'],
            29 => ['Unlocked by admin', 'unlock', 'app'],
            31 => ['Unlocked with mechanical key', 'unlock', 'manual'],
            32 => ['Locked via app', 'lock', 'app'],
            33 => ['Unlocked via Bluetooth', 'unlock', 'app'],
            34 => ['Locked with passcode', 'lock', 'passcode'],
            36 => ['Unlocked via API', 'unlock', 'remote'],
            37 => ['Locked via API', 'lock', 'remote'],
            42 => ['Auto-locked (timeout)', 'lock', 'auto'],
            43 => ['Auto-locked (door closed)', 'lock', 'auto'],
            44 => ['Locked with button', 'lock', 'manual'],
            45 => ['Wrong card', 'fail', 'card'],
            46 => ['Unlocked with one-time code', 'unlock', 'passcode'],
            47 => ['Locked remotely', 'lock', 'remote'],
            48 => ['Unlocked with master code', 'unlock', 'passcode'],
            55 => ['Unlocked (hand wave)', 'unlock', 'manual'],
        ];
        if (isset($map[$type])) {
            return ['label' => $map[$type][0], 'kind' => $map[$type][1], 'source' => $map[$type][2]];
        }
        return ['label' => "Event #{$type}", 'kind' => 'other', 'source' => 'other'];
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
                $hasGateway = (int) ($lock['hasGateway'] ?? 0) === 1;
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
                    // Infer size from alias prefix: "L-*" / "L - *" = large; everything else = standard.
                    // Admin can override afterwards via the locker edit screen.
                    $size = preg_match('/^\s*L\b/i', $alias) ? 'large' : 'standard';
                    $locker = Locker::create([
                        'uuid' => Str::uuid(),
                        'ttlock_lock_id' => $lockId,
                        'number' => $alias,
                        'size' => $size,
                        'status' => 'available',
                        // Offline locks come in as inactive so they don't get booked until
                        // the gateway can reach them again. Admin can flip this on manually.
                        'is_active' => $hasGateway,
                    ]);
                    $created++;
                }

                if ($locker) {
                    $locker->update([
                        'battery_level' => $lock['electricQuantity'] ?? $locker->battery_level,
                        'is_online' => $hasGateway,
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
