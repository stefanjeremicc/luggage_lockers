<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingLocker;
use App\Models\Locker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives unlock-record pushes from the TTLock Open Platform (set as the app's
 * "Callback URL"). This replaces per-lock polling — TTLock notifies us when a
 * lock is operated, so we update last_used_at with ZERO API calls on our side.
 *
 * Tolerant by design: always returns errcode 0 so TTLock doesn't retry-storm,
 * and never throws on malformed payloads.
 */
class TtlockCallbackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            // TTLock posts either a `records` JSON array, or flat fields for a
            // single event. Handle both shapes.
            $records = $request->input('records');
            if (is_string($records)) {
                $records = json_decode($records, true);
            }
            if (!is_array($records) || empty($records)) {
                $records = [[
                    'lockId' => $request->input('lockId'),
                    'lockDate' => $request->input('lockDate') ?? $request->input('serverDate'),
                    'success' => $request->input('success', 1),
                ]];
            }

            // Keep the latest unlock timestamp per lock (lock-health display), and
            // attribute customer opens to the specific booking whose PIN was used.
            $latestByLock = [];
            foreach ($records as $r) {
                if (!is_array($r)) continue;
                $lockId = (int) ($r['lockId'] ?? $request->input('lockId') ?? 0);
                $ms = (int) ($r['lockDate'] ?? $r['serverDate'] ?? 0);
                if ($lockId > 0 && $ms > 0) {
                    $latestByLock[$lockId] = max($latestByLock[$lockId] ?? 0, $ms);

                    // Only SUCCESSFUL passcode unlocks count as a customer open;
                    // recordCustomerUnlock ignores anything without a matching PIN.
                    if ((int) ($r['success'] ?? 1) === 1) {
                        BookingLocker::recordCustomerUnlock(
                            $lockId,
                            $r['keyboardPwd'] ?? null,
                            Carbon::createFromTimestampMs($ms)
                        );
                    }
                }
            }

            foreach ($latestByLock as $lockId => $ms) {
                $locker = Locker::where('ttlock_lock_id', $lockId)->first();
                if (!$locker) continue;
                $at = Carbon::createFromTimestampMs($ms);
                if (!$locker->last_used_at || $locker->last_used_at->lt($at)) {
                    $locker->update(['last_used_at' => $at]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TTLock callback handling failed: ' . $e->getMessage());
        }

        // TTLock expects a success acknowledgement.
        return response()->json(['errcode' => 0]);
    }
}
