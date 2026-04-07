<?php

namespace App\Services\Lock;

use App\Models\TtlockApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TTLockService implements LockServiceInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct()
    {
        $this->baseUrl = config('services.ttlock.base_url', 'https://euapi.ttlock.com');
        $this->clientId = config('services.ttlock.client_id', '');
        $this->clientSecret = config('services.ttlock.client_secret', '');
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $response = $this->request('POST', '/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => config('services.ttlock.username'),
            'password' => md5(config('services.ttlock.password', '')),
        ], false);

        $this->accessToken = $response['access_token'] ?? '';
        $this->tokenExpiry = time() + ($response['expires_in'] ?? 7200) - 300;

        return $this->accessToken;
    }

    private function request(string $method, string $endpoint, array $params = [], bool $auth = true): array
    {
        $startTime = microtime(true);

        if ($auth) {
            $params['clientId'] = $this->clientId;
            $params['accessToken'] = $this->getAccessToken();
            $params['date'] = (int)(microtime(true) * 1000);
        }

        $url = $this->baseUrl . $endpoint;

        $response = match ($method) {
            'POST' => Http::asForm()->post($url, $params),
            default => Http::get($url, $params),
        };

        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $body = $response->json() ?? [];
        $errcode = $body['errcode'] ?? null;

        TtlockApiLog::create([
            'endpoint' => $endpoint,
            'method' => $method,
            'request_params' => $params,
            'response_body' => $body,
            'response_code' => $response->status(),
            'errcode' => $errcode,
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);

        if ($errcode !== null && $errcode !== 0) {
            throw new \RuntimeException("TTLock API error [{$errcode}]: " . ($body['errmsg'] ?? 'Unknown'));
        }

        return $body;
    }

    public function createTimedAccessCode(int $lockId, string $code, Carbon $start, Carbon $end): array
    {
        return $this->request('POST', '/v3/keyboardPwd/add', [
            'lockId' => $lockId,
            'keyboardPwd' => $code,
            'keyboardPwdType' => 3, // Timed
            'startDate' => $start->getTimestampMs(),
            'endDate' => $end->getTimestampMs(),
        ]);
    }

    public function deleteAccessCode(int $lockId, int $keyboardPwdId): bool
    {
        $response = $this->request('POST', '/v3/keyboardPwd/delete', [
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
        ]);
        return ($response['errcode'] ?? -1) === 0;
    }

    public function updateAccessCodeTime(int $lockId, int $keyboardPwdId, Carbon $newEnd): bool
    {
        $response = $this->request('POST', '/v3/keyboardPwd/change', [
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
            'newEndDate' => $newEnd->getTimestampMs(),
        ]);
        return ($response['errcode'] ?? -1) === 0;
    }

    public function remoteLock(int $lockId): bool
    {
        $response = $this->request('POST', '/v3/lock/lock', ['lockId' => $lockId]);
        return ($response['errcode'] ?? -1) === 0;
    }

    public function remoteUnlock(int $lockId): bool
    {
        $response = $this->request('POST', '/v3/lock/unlock', ['lockId' => $lockId]);
        return ($response['errcode'] ?? -1) === 0;
    }

    public function getLockDetail(int $lockId): array
    {
        return $this->request('GET', '/v3/lock/detail', ['lockId' => $lockId]);
    }

    public function getLockList(): array
    {
        return $this->request('GET', '/v3/lock/list', ['pageNo' => 1, 'pageSize' => 100]);
    }

    public function getGatewayList(): array
    {
        return $this->request('GET', '/v3/gateway/list', ['pageNo' => 1, 'pageSize' => 100]);
    }

    public function getUnlockRecords(int $lockId, Carbon $start, Carbon $end): array
    {
        return $this->request('GET', '/v3/lockRecord/list', [
            'lockId' => $lockId,
            'startDate' => $start->getTimestampMs(),
            'endDate' => $end->getTimestampMs(),
            'pageNo' => 1,
            'pageSize' => 100,
        ]);
    }
}
