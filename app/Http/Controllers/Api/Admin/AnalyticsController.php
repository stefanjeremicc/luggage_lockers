<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Location;
use App\Services\Analytics\GoogleAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Advanced booking analytics for the admin "Analytics" page.
 *
 * Everything is attributed to the booking's CHECK-IN date (the day the stay
 * starts) per the business rule: a booking made on the 23rd for arrival on the
 * 23rd counts on the 23rd, even if it ends on the 24th.
 *
 * Revenue figures use total_eur and split paid vs unpaid; cancelled bookings
 * are reported separately and never counted as revenue. All breakdowns respect
 * the same filters (date range, location, status, channel, payment state).
 */
class AnalyticsController extends Controller
{
    private const CHANNELS = ['google_ads', 'facebook', 'organic', 'referral', 'qr', 'direct', 'other', 'unknown'];
    private const STATUSES = ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'expired'];

    /**
     * Google Analytics traffic snapshot for the given date range (separate
     * endpoint so the heavier external API call doesn't slow the booking
     * analytics page; the frontend fetches it asynchronously).
     */
    public function ga(Request $request, GoogleAnalyticsService $ga): JsonResponse
    {
        $today = Carbon::today();
        $from = $this->parseDate($request->query('from'), $today->copy()->subDays(29));
        $to   = $this->parseDate($request->query('to'), $today);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        return response()->json($ga->traffic($from->toDateString(), $to->toDateString()));
    }

    public function index(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $from = $this->parseDate($request->query('from'), $today->copy()->subDays(29));
        $to   = $this->parseDate($request->query('to'), $today);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $group   = in_array($request->query('group'), ['day', 'week', 'month'], true) ? $request->query('group') : 'day';
        $locationId = $request->query('location_id');
        $status  = $request->query('status');
        $channel = $request->query('channel');
        $payment = in_array($request->query('payment'), ['paid', 'unpaid'], true) ? $request->query('payment') : 'all';

        $rangeStart = $from->copy()->startOfDay();
        $rangeEnd   = $to->copy()->endOfDay();

        // Base query factory — every aggregate re-applies the same filters.
        $base = function () use ($rangeStart, $rangeEnd, $locationId, $status, $channel, $payment): Builder {
            $q = Booking::query()
                ->whereBetween('check_in', [$rangeStart, $rangeEnd]);
            if ($locationId) {
                $q->where('location_id', (int) $locationId);
            }
            if ($status && in_array($status, self::STATUSES, true)) {
                $q->where('booking_status', $status);
            }
            if ($channel) {
                $q->where('marketing_source', $channel);
            }
            if ($payment === 'paid') {
                $q->where('payment_status', 'paid');
            } elseif ($payment === 'unpaid') {
                $q->where('payment_status', '!=', 'paid');
            }
            return $q;
        };

        // Revenue helpers: "paid_sum" = money collected (non-cancelled, paid).
        $paidSum = "SUM(CASE WHEN payment_status = 'paid' AND booking_status <> 'cancelled' THEN total_eur ELSE 0 END)";
        $unpaidSum = "SUM(CASE WHEN payment_status <> 'paid' AND booking_status <> 'cancelled' THEN total_eur ELSE 0 END)";

        // ── Summary ──────────────────────────────────────────────────────────
        $summaryRow = (clone $base())
            ->selectRaw("COUNT(*) as bookings, $paidSum as paid_eur, $unpaidSum as unpaid_eur,
                SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN booking_status = 'cancelled' THEN total_eur ELSE 0 END) as cancelled_eur")
            ->first();

        $paidEur = round((float) ($summaryRow->paid_eur ?? 0), 2);
        $unpaidEur = round((float) ($summaryRow->unpaid_eur ?? 0), 2);
        $summary = [
            'bookings'        => (int) ($summaryRow->bookings ?? 0),
            'paid_eur'        => $paidEur,
            'unpaid_eur'      => $unpaidEur,
            'total_eur'       => round($paidEur + $unpaidEur, 2),
            'cancelled_count' => (int) ($summaryRow->cancelled_count ?? 0),
            'cancelled_eur'   => round((float) ($summaryRow->cancelled_eur ?? 0), 2),
        ];

        // ── Time series (filled, no gaps) ────────────────────────────────────
        $periodExpr = match ($group) {
            'week'  => "DATE_FORMAT(check_in, '%x-W%v')",
            'month' => "DATE_FORMAT(check_in, '%Y-%m')",
            default => "DATE_FORMAT(check_in, '%Y-%m-%d')",
        };
        $tsRows = (clone $base())
            ->selectRaw("$periodExpr as period, COUNT(*) as bookings, $paidSum as revenue")
            ->groupBy('period')
            ->pluck('bookings', 'period');
        $tsRev = (clone $base())
            ->selectRaw("$periodExpr as period, $paidSum as revenue")
            ->groupBy('period')
            ->pluck('revenue', 'period');

        $timeseries = [];
        foreach ($this->periodKeys($from, $to, $group) as $key) {
            $timeseries[] = [
                'period'   => $key,
                'bookings' => (int) ($tsRows[$key] ?? 0),
                'revenue'  => round((float) ($tsRev[$key] ?? 0), 2),
            ];
        }

        // ── Breakdowns ───────────────────────────────────────────────────────
        $byChannel = $this->breakdown($base(), "COALESCE(NULLIF(marketing_source, ''), 'unknown')", 'key', $paidSum);
        // Order channels by our canonical list, then by count.
        $byChannel = $byChannel->sortByDesc('count')->values();

        $byLanding = $this->breakdown($base(), "COALESCE(NULLIF(landing_page, ''), '(unknown)')", 'landing_page', $paidSum)
            ->sortByDesc('count')->take(30)->values();

        // Source = the explicit UTM tag if present, otherwise the detected
        // channel (e.g. Google Ads is identified via Google's gclid/gbraid, not
        // a utm_source) so paid/organic traffic isn't lumped into "untagged".
        $bySource = (clone $base())
            ->selectRaw("COALESCE(NULLIF(utm_source, ''), NULLIF(marketing_source, ''), 'unknown') as utm_source,
                COALESCE(NULLIF(utm_medium, ''), '') as utm_medium,
                COALESCE(NULLIF(utm_campaign, ''), '') as utm_campaign,
                COUNT(*) as count, $paidSum as revenue")
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->get()
            ->map(fn ($r) => [
                'utm_source'   => $r->utm_source,
                'utm_medium'   => $r->utm_medium,
                'utm_campaign' => $r->utm_campaign,
                'count'        => (int) $r->count,
                'revenue'      => round((float) $r->revenue, 2),
            ])
            ->sortByDesc('count')->take(30)->values();

        $byStatus = $this->breakdown($base(), 'booking_status', 'status', $paidSum)
            ->sortByDesc('count')->values();

        $byLocationRows = (clone $base())
            ->selectRaw("location_id, COUNT(*) as count, $paidSum as revenue")
            ->groupBy('location_id')
            ->get();
        $locationNames = Location::pluck('name', 'id');
        $byLocation = $byLocationRows->map(fn ($r) => [
            'id'      => (int) $r->location_id,
            'name'    => $locationNames[$r->location_id] ?? ('#' . $r->location_id),
            'count'   => (int) $r->count,
            'revenue' => round((float) $r->revenue, 2),
        ])->sortByDesc('count')->values();

        return response()->json([
            'filters' => [
                'from'        => $from->toDateString(),
                'to'          => $to->toDateString(),
                'group'       => $group,
                'location_id' => $locationId ? (int) $locationId : null,
                'status'      => $status,
                'channel'     => $channel,
                'payment'     => $payment,
                'locations'   => Location::orderBy('name')->get(['id', 'name']),
                'statuses'    => self::STATUSES,
                'channels'    => self::CHANNELS,
            ],
            'summary'     => $summary,
            'timeseries'  => $timeseries,
            'by_channel'  => $byChannel,
            'by_landing'  => $byLanding,
            'by_source'   => $bySource,
            'by_status'   => $byStatus,
            'by_location' => $byLocation,
        ]);
    }

    /** Generic "group by one expression, count + paid revenue" breakdown. */
    private function breakdown(Builder $q, string $expr, string $label, string $paidSum)
    {
        return $q->selectRaw("$expr as `$label`, COUNT(*) as count, $paidSum as revenue")
            ->groupBy($label)
            ->get()
            ->map(fn ($r) => [
                $label    => $r->{$label},
                'count'   => (int) $r->count,
                'revenue' => round((float) $r->revenue, 2),
            ]);
    }

    /** Ordered list of period keys spanning from→to, matching the SQL format. */
    private function periodKeys(Carbon $from, Carbon $to, string $group): array
    {
        $keys = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        if ($group === 'month') {
            $cursor->startOfMonth();
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        } elseif ($group === 'week') {
            $cursor->startOfWeek();
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('o-\WW'); // ISO year + ISO week, e.g. 2026-W21
                $cursor->addWeek();
            }
        } else {
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }
        }
        return $keys;
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (!$value) {
            return $fallback->copy();
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }
}
