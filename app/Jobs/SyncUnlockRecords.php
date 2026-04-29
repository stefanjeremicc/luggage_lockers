<?php

namespace App\Jobs;

use App\Models\Locker;
use App\Services\Lock\LockServiceInterface;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUnlockRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LockServiceInterface $lockService): void
    {
        $end = Carbon::now();
        // Look back 1h on each run; the scheduler runs this every 15 min so we
        // overlap to avoid missing records when TTLock's cloud is laggy.
        $start = $end->copy()->subHour();

        $lockers = Locker::whereNotNull('ttlock_lock_id')->get();

        foreach ($lockers as $locker) {
            try {
                $records = $lockService->getUnlockRecords($locker->ttlock_lock_id, $start, $end);
                $list = $records['list'] ?? [];

                $latestMs = 0;
                foreach ($list as $r) {
                    $ts = (int) ($r['lockDate'] ?? 0);
                    if ($ts > $latestMs) $latestMs = $ts;
                }

                if ($latestMs > 0) {
                    $latestAt = Carbon::createFromTimestampMs($latestMs);
                    if (!$locker->last_used_at || $locker->last_used_at->lt($latestAt)) {
                        $locker->update(['last_used_at' => $latestAt]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('SyncUnlockRecords: failed for locker ' . $locker->id . ': ' . $e->getMessage());
            }
        }
    }
}
