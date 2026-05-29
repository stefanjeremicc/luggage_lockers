<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin Google Search Console API client. Reuses the SAME service-account JSON
 * key as GoogleAnalyticsService (signs a JWT with the private key, exchanges it
 * for an OAuth token), but requests the webmasters.readonly scope.
 *
 * IMPORTANT: unlike GA4, Search Console has NO user-management API, so the
 * service account must be added MANUALLY as a user on the property
 * (Search Console → Settings → Users and permissions → Add user →
 * ga-reader@luggage-analytics.iam.gserviceaccount.com, "Full" or "Restricted").
 * Until then the API returns 403 and this service reports reason => 'forbidden'
 * so the admin UI can show the exact step needed.
 *
 * Never throws — on failure returns ['ok' => false, 'reason' => ...].
 */
class SearchConsoleService
{
    private const CREDENTIALS_FILE = 'luggage-analytics-44042be4d033.json';
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    /** Service-account email — surfaced to the UI so the user knows who to grant. */
    public const SERVICE_ACCOUNT = 'ga-reader@luggage-analytics.iam.gserviceaccount.com';

    /**
     * Candidate property identifiers, tried in order. The site was verified via
     * a DNS TXT record → a Domain property (sc-domain:), but we fall back to the
     * URL-prefix form just in case the property was set up that way instead.
     */
    private const SITES = [
        'sc-domain:belgradeluggagelocker.com',
        'https://belgradeluggagelocker.com/',
    ];

    public function isConfigured(): bool
    {
        return $this->oauthConfigured() || is_readable($this->credentialsPath());
    }

    /** OAuth (user) credentials present? This is the primary auth for SC. */
    private function oauthConfigured(): bool
    {
        $o = config('services.search_console');
        return !empty($o['client_id']) && !empty($o['client_secret']) && !empty($o['refresh_token']);
    }

    private function credentialsPath(): string
    {
        return storage_path('app/' . self::CREDENTIALS_FILE);
    }

    /**
     * Get (and cache) an access token for the Search Console API.
     * Prefers OAuth (the property owner's refresh token) — Search Console does
     * not accept the service account. Falls back to the service-account JWT
     * only if no OAuth credentials are configured.
     */
    private function accessToken(): ?string
    {
        if ($this->oauthConfigured()) {
            return Cache::remember('sc:oauth_token', 3000, function () { // 50 min
                $o = config('services.search_console');
                try {
                    $res = Http::asForm()->post(self::TOKEN_URI, [
                        'client_id'     => $o['client_id'],
                        'client_secret' => $o['client_secret'],
                        'refresh_token' => $o['refresh_token'],
                        'grant_type'    => 'refresh_token',
                    ]);
                    if ($res->successful()) {
                        return $res->json('access_token');
                    }
                    Log::error('SC OAuth refresh failed', ['body' => $res->body()]);
                } catch (\Throwable $e) {
                    Log::error('SC OAuth refresh error: ' . $e->getMessage());
                }
                return null;
            });
        }

        return Cache::remember('sc:access_token', 3000, function () { // 50 min
            $path = $this->credentialsPath();
            if (!is_readable($path)) {
                Log::warning('SC credentials file missing', ['path' => $path]);
                return null;
            }
            $creds = json_decode((string) file_get_contents($path), true);
            if (!isset($creds['client_email'], $creds['private_key'])) {
                Log::warning('SC credentials malformed');
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
                Log::error('SC JWT signing failed');
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
                Log::error('SC token exchange failed', ['body' => $res->body()]);
            } catch (\Throwable $e) {
                Log::error('SC token request error: ' . $e->getMessage());
            }
            return null;
        });
    }

    /**
     * Full Search Console snapshot for a date range. Cached 1h per range
     * (SC data is daily and lags ~2 days, so frequent calls add no value).
     */
    public function performance(string $from, string $to): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'reason' => 'not_configured'];
        }
        $key = "sc:perf:{$from}:{$to}";
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        $result = $this->build($from, $to);
        // Only cache successful responses — so a transient error or a
        // not-yet-granted (forbidden) state doesn't stick for an hour. Once you
        // grant access the panel fills in on the next load.
        if ($result['ok'] ?? false) {
            Cache::put($key, $result, 3600);
        }
        return $result;
    }

    private function build(string $from, string $to): array
    {
        $token = $this->accessToken();
        if (!$token) {
            return ['ok' => false, 'reason' => 'auth_failed'];
        }

        // Resolve which property identifier this service account can read.
        $site = $this->resolveSite($token, $from, $to);
        if ($site === null) {
            return ['ok' => false, 'reason' => 'forbidden', 'service_account' => self::SERVICE_ACCOUNT];
        }

        $totals  = $this->call($token, $site, $from, $to, [], 1);
        $queries = $this->call($token, $site, $from, $to, ['query'], 25);
        $pages   = $this->call($token, $site, $from, $to, ['page'], 25);
        $byDate  = $this->call($token, $site, $from, $to, ['date'], 1000);

        if ($totals === null) {
            return ['ok' => false, 'reason' => 'api_error'];
        }

        $headlineRow = $totals['rows'][0] ?? null;
        $headline = [
            'clicks'      => (int) round($headlineRow['clicks'] ?? 0),
            'impressions' => (int) round($headlineRow['impressions'] ?? 0),
            'ctr'         => round(($headlineRow['ctr'] ?? 0) * 100, 2), // %
            'position'    => round($headlineRow['position'] ?? 0, 1),
        ];

        $mapRows = function (?array $resp, string $key) {
            $out = [];
            foreach (($resp['rows'] ?? []) as $r) {
                $out[] = [
                    $key          => $r['keys'][0] ?? '',
                    'clicks'      => (int) round($r['clicks'] ?? 0),
                    'impressions' => (int) round($r['impressions'] ?? 0),
                    'ctr'         => round(($r['ctr'] ?? 0) * 100, 2),
                    'position'    => round($r['position'] ?? 0, 1),
                ];
            }
            return $out;
        };

        $timeseries = [];
        foreach (($byDate['rows'] ?? []) as $r) {
            $timeseries[] = [
                'date'        => $r['keys'][0] ?? '',
                'clicks'      => (int) round($r['clicks'] ?? 0),
                'impressions' => (int) round($r['impressions'] ?? 0),
            ];
        }

        return [
            'ok'         => true,
            'site'       => $site,
            'headline'   => $headline,
            'queries'    => $mapRows($queries, 'query'),
            'pages'      => $mapRows($pages, 'page'),
            'timeseries' => $timeseries,
        ];
    }

    /** Find the first property identifier the account can actually query. */
    private function resolveSite(string $token, string $from, string $to): ?string
    {
        $cacheKey = 'sc:site';
        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }
        foreach (self::SITES as $site) {
            $probe = $this->call($token, $site, $from, $to, [], 1);
            if ($probe !== null) {
                Cache::put($cacheKey, $site, 86400);
                return $site;
            }
        }
        return null;
    }

    /** One searchAnalytics.query call. Returns null on any non-2xx. */
    private function call(string $token, string $site, string $from, string $to, array $dimensions, int $rowLimit): ?array
    {
        $url = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
            . rawurlencode($site) . '/searchAnalytics/query';
        $body = array_filter([
            'startDate'  => $from,
            'endDate'    => $to,
            'dimensions' => $dimensions ?: null,
            'rowLimit'   => $rowLimit,
        ], fn ($v) => $v !== null);

        try {
            $res = Http::withToken($token)->post($url, $body);
            if ($res->successful()) {
                return $res->json();
            }
            // 403 = service account not added to the property (expected until
            // the user grants access). Log quietly without flooding.
            if ($res->status() !== 403) {
                Log::error('SC query failed', ['status' => $res->status(), 'body' => $res->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('SC query error: ' . $e->getMessage());
        }
        return null;
    }

    /** URL-safe base64 without padding (JWT format). */
    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
