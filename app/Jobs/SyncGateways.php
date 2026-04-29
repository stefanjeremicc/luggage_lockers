<?php

namespace App\Jobs;

use App\Models\TtlockGateway;
use App\Services\Lock\LockServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGateways implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LockServiceInterface $lockService): void
    {
        try {
            $response = $lockService->getGatewayList();
            $list = $response['list'] ?? [];

            foreach ($list as $gw) {
                $isOnline = (int) ($gw['isOnline'] ?? 0) === 1;
                TtlockGateway::updateOrCreate(
                    ['ttlock_gateway_id' => $gw['gatewayId']],
                    [
                        'name' => $gw['gatewayName'] ?? null,
                        'lock_count' => (int) ($gw['lockNum'] ?? 0),
                        'is_online' => $isOnline,
                        'last_seen_at' => $isOnline ? now() : null,
                        'last_synced_at' => now(),
                    ]
                );
            }

            $remoteIds = array_column($list, 'gatewayId');
            if (!empty($remoteIds)) {
                TtlockGateway::whereNotIn('ttlock_gateway_id', $remoteIds)->delete();
            }

            Log::info('SyncGateways: ' . count($list) . ' gateways synced');
        } catch (\Exception $e) {
            Log::error('SyncGateways failed: ' . $e->getMessage());
        }
    }
}
