<?php

namespace App\Services\Lock;

use App\Exceptions\TtlockQuotaExceededException;
use App\Models\TtlockApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        $this->baseUrl = config('services.ttlock.base_url') ?: 'https://euapi.ttlock.com';
        $this->clientId = (string) (config('services.ttlock.client_id') ?? '');
        $this->clientSecret = (string) (config('services.ttlock.client_secret') ?? '');
    }

    /**
     * Whether TTLock credentials are configured. When false the service is
     * effectively offline — calls return cached locker state from DB instead
     * of throwing, so the admin UI keeps working on staging where the
     * provider isn't wired up.
     */
    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    private function getAccessToken(): string
    {
        // Cache the OAuth token GLOBALLY (database cache) so it's shared across
        // every request and queued job, instead of fetching a fresh token per
        // process. TTLock tokens last ~2h; this collapses thousands of
        // /oauth2/token calls per day down to a handful.
        $cached = Cache::get('ttlock:access_token');
        if (is_array($cached) && !empty($cached['token']) && ($cached['expiry'] ?? 0) > time()) {
            return $cached['token'];
        }

        $response = $this->request('POST', '/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => config('services.ttlock.username'),
            'password' => md5(config('services.ttlock.password', '')),
        ], false);

        $token = $response['access_token'] ?? '';
        $ttl = ($response['expires_in'] ?? 7200) - 300; // refresh 5 min early
        if ($token !== '') {
            Cache::put('ttlock:access_token', ['token' => $token, 'expiry' => time() + $ttl], $ttl);
        }

        return $token;
    }

    private function request(string $method, string $endpoint, array $params = [], bool $auth = true): array
    {
        // Short-circuit when TTLock isn't wired up (e.g. on a staging subdomain
        // without provider credentials). Throw a typed runtime error so the
        // controllers' try/catch blocks degrade gracefully instead of returning
        // a 500 to the admin SPA.
        if (!$this->isConfigured()) {
            throw new \RuntimeException('TTLock provider is not configured (TTLOCK_CLIENT_ID empty).');
        }

        // When the monthly quota is exhausted, do NOT make any HTTP call (every
        // call — even a failing one — still counts against TTLock's limit). Fail
        // fast so callers fall back to permanent PINs and we stop bleeding calls.
        if (TtlockQuota::isExceeded()) {
            throw new TtlockQuotaExceededException();
        }

        $startTime = microtime(true);

        if ($auth) {
            $params['clientId'] = $this->clientId;
            $params['accessToken'] = $this->getAccessToken();
            $params['date'] = (int)(microtime(true) * 1000);
        }

        $url = $this->baseUrl . $endpoint;

        $caBundle = config('services.ttlock.ca_bundle') ?: (is_file('C:\\php\\cacert.pem') ? 'C:\\php\\cacert.pem' : true);
        $client = Http::withOptions(['verify' => $caBundle]);

        $response = match ($method) {
            'POST' => $client->asForm()->post($url, $params),
            default => $client->get($url, $params),
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

        // Count this call toward the monthly budget + fire 20k/25k alerts.
        TtlockQuota::recordCall();

        if ($errcode !== null && $errcode !== 0) {
            // 30007 = monthly API limit exceeded → flip into permanent-PIN mode.
            if ((int) $errcode === 30007) {
                TtlockQuota::markExceeded();
                throw new TtlockQuotaExceededException("TTLock API error [30007]: " . ($body['errmsg'] ?? 'monthly limit exceeded'));
            }
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
            // addType=2 makes TTLock cloud push the passcode through the gateway
            // to the physical lock. Default (1) only stores it cloud-side and
            // expects an app-paired Bluetooth sync, so the keypad rejects the PIN.
            'addType' => 2,
        ]);
    }

    public function deleteAccessCode(int $lockId, int $keyboardPwdId): bool
    {
        // deleteType=2 → push the delete through the online gateway to the
        // physical lock immediately. Without this, TTLock cloud accepts the
        // delete but the keypad keeps the passcode in memory until the next
        // BLE/app sync. Mirrors addType=2 (create) and changeType=2 (update);
        // the lock-side state stays consistent with cloud-side state.
        $response = $this->request('POST', '/v3/keyboardPwd/delete', [
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
            'deleteType' => 2,
        ]);
        return ($response['errcode'] ?? -1) === 0;
    }

    public function updateAccessCodeTime(int $lockId, int $keyboardPwdId, Carbon $newEnd): bool
    {
        // changeType=2 → push the new end-date through the online gateway so the
        // physical lock actually applies it. Without this, TTLock cloud silently
        // accepts the change but only commits it the next time the app touches
        // the lock over Bluetooth — which on our setup is essentially "never".
        $response = $this->request('POST', '/v3/keyboardPwd/change', [
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
            'newEndDate' => $newEnd->getTimestampMs(),
            'changeType' => 2,
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

    public function createPasscode(int $lockId, string $code, int $type, ?Carbon $start, ?Carbon $end, ?string $name): array
    {
        $params = [
            'lockId' => $lockId,
            'keyboardPwd' => $code,
            'keyboardPwdType' => $type,
        ];
        if ($name !== null && $name !== '') {
            $params['keyboardPwdName'] = $name;
        }
        // TTLock requires startDate + endDate for type 1 (Custom) and type 3 (Timed).
        // Without them the cloud silently registers epoch dates → passcode is invalid on the lock.
        if ($type === 1 || $type === 3) {
            if (!$start || !$end) {
                throw new \InvalidArgumentException('startDate and endDate are required for TTLock passcode type '.$type);
            }
            $params['startDate'] = $start->getTimestampMs();
            $params['endDate'] = $end->getTimestampMs();
        }
        // Push to physical lock via gateway, not just cloud storage.
        $params['addType'] = 2;
        return $this->request('POST', '/v3/keyboardPwd/add', $params);
    }

    public function getPasscodes(int $lockId, int $page = 1, int $pageSize = 100): array
    {
        return $this->request('GET', '/v3/lock/listKeyboardPwd', [
            'lockId' => $lockId,
            'pageNo' => $page,
            'pageSize' => $pageSize,
            'orderBy' => 1,
        ]);
    }

    public function renameLockAlias(int $lockId, string $alias): bool
    {
        $response = $this->request('POST', '/v3/lock/rename', [
            'lockId' => $lockId,
            'lockAlias' => $alias,
        ]);
        return ($response['errcode'] ?? -1) === 0;
    }
}
