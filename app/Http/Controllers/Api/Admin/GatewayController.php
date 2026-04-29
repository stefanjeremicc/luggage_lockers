<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGateways;
use App\Models\TtlockGateway;
use Illuminate\Http\JsonResponse;

class GatewayController extends Controller
{
    public function index(): JsonResponse
    {
        $gateways = TtlockGateway::orderBy('name')->get();
        return response()->json([
            'gateways' => $gateways,
            'any_online' => $gateways->where('is_online', true)->isNotEmpty(),
            'total' => $gateways->count(),
            'online_count' => $gateways->where('is_online', true)->count(),
        ]);
    }

    public function sync(): JsonResponse
    {
        SyncGateways::dispatchSync();
        return response()->json(['message' => 'Gateways synced']);
    }
}
