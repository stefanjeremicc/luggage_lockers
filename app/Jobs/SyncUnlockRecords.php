<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\BookingLocker;
use App\Models\Locker;
use App\Services\Lock\LockServiceInterface;
use App\Services\Lock\TtlockQuota;
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
        // Quota exhausted → skip polling until reset (the TTLock webhook still
        // delivers unlock events; this is only the backup path).
        if (TtlockQuota::isExceeded()) {
            return;
        }

        $end = Carbon::now();
        // Overlap the look-back window so we don't miss records if TTLock's
        // cloud is laggy.
        $start = $end->copy()->subHours(2);

        // Poll ONLY lockers that have a currently-active booking — not all 41.
        // This is the single biggest API-call saver. (The TTLock webhook delivers
        // most unlock events with zero polling; this is the backup path.)
        $activeLockerIds = BookingLocker::query()
            ->whereHas('booking', fn ($q) => $q
                ->where('booking_status', '!=', BookingStatus::Cancelled)
                ->where('check_in', '<=', $end)
                ->where('check_out', '>=', $end))
            ->pluck('locker_id')
            ->unique()
            ->all();

        if (empty($activeLockerIds)) {
            return;
        }

        $lockers = Locker::whereNotNull('ttlock_lock_id')->whereIn('id', $activeLockerIds)->get();

        foreach ($lockers as $locker) {
            try {
                $records = $lockService->getUnlockRecords($locker->ttlock_lock_id, $start, $end);
                $list = $records['list'] ?? [];

                $latestMs = 0;
                foreach ($list as $r) {
                    $ts = (int) ($r['lockDate'] ?? 0);
                    if ($ts > $latestMs) $latestMs = $ts;

                    // Attribute the open to the booking whose PIN was used (only
                    // successful passcode unlocks match; admin/remote/app do not).
                    if ($ts > 0 && (int) ($r['success'] ?? 1) === 1 && !empty($r['keyboardPwd'])) {
                        BookingLocker::recordCustomerUnlock(
                            $locker->ttlock_lock_id,
                            (string) $r['keyboardPwd'],
                            Carbon::createFromTimestampMs($ts)
                        );
                    }
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
