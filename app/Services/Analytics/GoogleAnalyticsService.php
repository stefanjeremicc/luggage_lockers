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
     * Full traffic snapshot for a date range. Cached 5 min per range so
     * re-selecting the same range is instant, and all reports are fetched with
     * batchRunReports (2 HTTP calls instead of ~8) to cut load time.
     */
    public function traffic(string $from, string $to): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not_configured'];
        }
        return Cache::remember("ga:traffic:{$from}:{$to}", 300, fn () => $this->buildTraffic($from, $to));
    }

    private function buildTraffic(string $from, string $to): array
    {
        $range = [['startDate' => $from, 'endDate' => $to]];
        $topBy = fn (string $dim, string $metric, ?int $limit) => array_filter([
            'dateRanges' => $range,
            'dimensions' => [['name' => $dim]],
            'metrics' => [['name' => $metric]],
            'orderBys' => [['metric' => ['metricName' => $metric], 'desc' => true]],
            'limit' => $limit,
        ], fn ($v) => $v !== null);

        // Report order is fixed — results come back in the same order.
        $requests = [
            /* 0 totals  */ ['dateRanges' => $range, 'metrics' => [['name' => 'sessions'], ['name' => 'totalUsers'], ['name' => 'newUsers'], ['name' => 'screenPageViews'], ['name' => 'averageSessionDuration'], ['name' => 'engagementRate']]],
            /* 1 daily   */ ['dateRanges' => $range, 'dimensions' => [['name' => 'date']], 'metrics' => [['name' => 'sessions'], ['name' => 'totalUsers']], 'orderBys' => [['dimension' => ['dimensionName' => 'date']]]],
            /* 2 channel */ $topBy('sessionDefaultChannelGroup', 'sessions', 20),
            /* 3 landing */ $topBy('landingPagePlusQueryString', 'sessions', 20),
            /* 4 pages   */ $topBy('pagePath', 'screenPageViews', 20),
            /* 5 hour    */ ['dateRanges' => $range, 'dimensions' => [['name' => 'hour']], 'metrics' => [['name' => 'sessions']]],
            /* 6 devices */ $topBy('deviceCategory', 'sessions', null),
            /* 7 country */ $topBy('country', 'sessions', 8),
        ];

        $reports = $this->batchRunReports($requests);
        if ($reports === null || !isset($reports[0])) {
            return ['ok' => false, 'reason' => 'api_error'];
        }

        $m = fn (int $rep, int $i) => $reports[$rep]['rows'][0]['metricValues'][$i]['value'] ?? '0';
        $headline = [
            'sessions'        => (int) $m(0, 0),
            'users'           => (int) $m(0, 1),
            'new_users'       => (int) $m(0, 2),
            'pageviews'       => (int) $m(0, 3),
            'avg_duration'    => (float) $m(0, 4),
            'engagement_rate' => (float) $m(0, 5),
        ];

        $timeseries = [];
        foreach ($reports[1]['rows'] ?? [] as $r) {
            $d = $r['dimensionValues'][0]['value'] ?? '';
            $timeseries[] = [
                'date'     => $d ? substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2) : $d,
                'sessions' => (int) ($r['metricValues'][0]['value'] ?? 0),
                'users'    => (int) ($r['metricValues'][1]['value'] ?? 0),
            ];
        }

        // Generic "dimension + single metric" rows → list of [key => label, valKey => int].
        $rows = function (int $rep, string $labelKey, string $valKey) use ($reports) {
            $out = [];
            foreach ($reports[$rep]['rows'] ?? [] as $r) {
                $out[] = [
                    $labelKey => $r['dimensionValues'][0]['value'] ?? '',
                    $valKey   => (int) ($r['metricValues'][0]['value'] ?? 0),
                ];
            }
            return $out;
        };

        $byHour = array_fill(0, 24, 0);
        foreach ($reports[5]['rows'] ?? [] as $r) {
            $h = (int) ($r['dimensionValues'][0]['value'] ?? 0);
            if ($h >= 0 && $h <= 23) {
                $byHour[$h] = (int) ($r['metricValues'][0]['value'] ?? 0);
            }
        }

        return [
            'ok'         => true,
            'headline'   => $headline,
            'timeseries' => $timeseries,
            'channels'   => $rows(2, 'channel', 'sessions'),
            'landing'    => $rows(3, 'page', 'sessions'),
            'pages'      => $rows(4, 'page', 'views'),
            'by_hour'    => $byHour,
            'devices'    => $rows(6, 'device', 'sessions'),
            'countries'  => $rows(7, 'country', 'sessions'),
        ];
    }

    /** Run multiple reports in as few HTTP calls as possible (max 5 per batch). */
    private function batchRunReports(array $requests): ?array
    {
        $token = $this->accessToken();
        if (!$token) {
            return null;
        }
        $all = [];
        foreach (array_chunk($requests, 5) as $chunk) {
            try {
                $res = Http::withToken($token)->post(
                    'https://analyticsdata.googleapis.com/v1beta/properties/' . self::PROPERTY_ID . ':batchRunReports',
                    ['requests' => array_values($chunk)]
                );
                if (!$res->successful()) {
                    Log::error('GA batchRunReports failed', ['body' => $res->body()]);
                    return null;
                }
                foreach ($res->json('reports', []) as $rep) {
                    $all[] = $rep;
                }
            } catch (\Throwable $e) {
                Log::error('GA batchRunReports error: ' . $e->getMessage());
                return null;
            }
        }
        return $all;
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
