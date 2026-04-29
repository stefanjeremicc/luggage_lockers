<?php

namespace App\Jobs;

use App\Models\Locker;
use App\Services\Lock\LockServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLockerStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LockServiceInterface $lockService): void
    {
        try {
            $response = $lockService->getLockList();
            $locks = $response['list'] ?? [];

            foreach ($locks as $lock) {
                $locker = Locker::where('ttlock_lock_id', $lock['lockId'])->first();
                if (!$locker) continue;

                // TTLock /v3/lock/list returns `hasGateway` (1 = paired with an
                // online gateway, 0 = unreachable). `lockStatus` is not in this
                // payload — only in /v3/lock/detail. So use hasGateway here.
                $locker->update([
                    'battery_level' => $lock['electricQuantity'] ?? $locker->battery_level,
                    'is_online' => (int) ($lock['hasGateway'] ?? 0) === 1,
                    'last_synced_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SyncLockerStatus failed: ' . $e->getMessage());
        }
    }
}
