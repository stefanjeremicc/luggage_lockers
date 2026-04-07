<?php

namespace App\Jobs;

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
            Log::info('SyncGateways: ' . count($response['list'] ?? []) . ' gateways found');
        } catch (\Exception $e) {
            Log::error('SyncGateways failed: ' . $e->getMessage());
        }
    }
}
