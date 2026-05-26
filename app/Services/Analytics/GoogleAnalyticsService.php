<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin GA4 Data API client. Authenticates as a service account by signing a
 * JWT with the account's private key (RS256) and exchanging it for an OAuth
 * access token — no heavy SDK / extra Composer deps, just openssl + HTTP.
 *
 * Credentials: a service-account JSON key stored OUTSIDE the web root at
 * storage/app/<file>.json (gitignored). The account must be added as a Viewer
 * on the GA4 property.
 *
 * Returns associative arrays; never throws — on failure returns ['ok' => false]
 * so the admin UI can degrade gracefully.
 */
class GoogleAnalyticsService
{
    private const PROPERTY_ID = '538958120';
    private const CREDENTIALS_FILE = 'luggage-analytics-44042be4d033.json';
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public function isConfigured(): bool
    {
        return is_readable($this->credentialsPath());
    }

    private function credentialsPath(): string
    {
        return storage_path('app/' . self::CREDENTIALS_FILE);
    }

    /** Get (and cache) an OAuth access token for the service account. */
    private function accessToken(): ?string
    {
        return Cache::remember('ga:access_token', 3000, function () { // 50 min
            $path = $this->credentialsPath();
            if (!is_readable($path)) {
                Log::warning('GA credentials file missing', ['path' => $path]);
                return null;
            }
            $creds = json_decode((string) file_get_contents($path), true);
            if (!isset($creds['client_email'], $creds['private_key'])) {
                Log::warning('GA credentials malformed');
                return null;
            }

            $now = time();
            $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64(json_encode([
                'iss'   => $creds['client_email'],
                'scope' => self::SCOPE,
                'aud'   => self::TOKEN_URI,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));
            $input = $header . '.' . $claims;

            $signature = '';
            if (!openssl_sign($input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::error('GA JWT signing failed');
                return null;
            }
            $jwt = $input . '.' . $this->b64($signature);

            try {
                $res = Http::asForm()->post(self::TOKEN_URI, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);
                if ($res->successful()) {
                    return $res->json('access_token');
                }
                Log::error('GA token exchange failed', ['body' => $res->body()]);
            } catch (\Throwable $e) {
                Log::error('GA token request error: ' . $e->getMessage());
            }
            return null;
        });
    }

    /** Low-level runReport call. */
    private function runReport(array $body): ?array
    {
        $token = $this->accessToken();
        if (!$token) {
            return null;
        }
        try {
            $res = Http::withToken($token)
                ->post('https://analyticsdata.googleapis.com/v1beta/properties/' . self::PROPERTY_ID . ':runReport', $body);
            if ($res->successful()) {
                return $res->json();
            }
            Log::error('GA runReport failed', ['body' => $res->body()]);
        } catch (\Throwable $e) {
            Log::error('GA runReport error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Full traffic snapshot for a date range: headline metrics, daily series,
     * channel breakdown, and top landing pages.
     */
    public function traffic(string $from, string $to): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not_configured'];
        }

        $range = [['startDate' => $from, 'endDate' => $to]];

        // Headline totals.
        $totals = $this->runReport([
            'dateRanges' => $range,
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'newUsers'],
                ['name' => 'screenPageViews'],
            ],
        ]);
        if ($totals === null) {
            return ['ok' => false, 'reason' => 'api_error'];
        }

        $headline = [
            'sessions'  => (int) $this->metric($totals, 0),
            'users'     => (int) $this->metric($totals, 1),
            'new_users' => (int) $this->metric($totals, 2),
            'pageviews' => (int) $this->metric($totals, 3),
        ];

        // Daily sessions/users time-series.
        $daily = $this->runReport([
            'dateRanges' => $range,
            'dimensions' => [['name' => 'date']],
            'metrics' => [['name' => 'sessions'], ['name' => 'totalUsers']],
            'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
        ]);
        $timeseries = [];
        foreach ($daily['rows'] ?? [] as $r) {
            $d = $r['dimensionValues'][0]['value'] ?? '';
            $timeseries[] = [
                'date'     => $d ? substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2) : $d,
                'sessions' => (int) ($r['metricValues'][0]['value'] ?? 0),
                'users'    => (int) ($r['metricValues'][1]['value'] ?? 0),
            ];
        }

        // Channel breakdown.
        $channelsRep = $this->runReport([
            'dateRanges' => $range,
            'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
            'metrics' => [['name' => 'sessions']],
            'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
            'limit' => 20,
        ]);
        $channels = [];
        foreach ($channelsRep['rows'] ?? [] as $r) {
            $channels[] = [
                'channel'  => $r['dimensionValues'][0]['value'] ?? '(other)',
                'sessions' => (int) ($r['metricValues'][0]['value'] ?? 0),
            ];
        }

        // Top landing pages.
        $landingRep = $this->runReport([
            'dateRanges' => $range,
            'dimensions' => [['name' => 'landingPagePlusQueryString']],
            'metrics' => [['name' => 'sessions']],
            'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
            'limit' => 20,
        ]);
        $landing = [];
        foreach ($landingRep['rows'] ?? [] as $r) {
            $landing[] = [
                'page'     => $r['dimensionValues'][0]['value'] ?? '/',
                'sessions' => (int) ($r['metricValues'][0]['value'] ?? 0),
            ];
        }

        return [
            'ok'         => true,
            'headline'   => $headline,
            'timeseries' => $timeseries,
            'channels'   => $channels,
            'landing'    => $landing,
        ];
    }

    private function metric(array $report, int $i): string
    {
        return $report['rows'][0]['metricValues'][$i]['value'] ?? '0';
    }

    /** URL-safe base64 without padding (JWT format). */
    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
