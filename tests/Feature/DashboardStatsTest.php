<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The dashboard's money figures must agree with the Analytics screen, which is
 * the reference implementation: every aggregate is bounded to the period and
 * excludes cancelled bookings. Four places on the dashboard drifted from that
 * and are pinned here.
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function seedFixtures(): void
    {
        DB::table('locations')->insert([
            'id' => 1, 'name' => 'Central', 'slug' => 'central',
            'address' => 'Nemanjina 12', 'city' => 'Belgrade', 'lat' => 44.8, 'lng' => 20.45,
        ]);
        DB::table('customers')->insert([
            'id' => 1, 'uuid' => (string) Str::uuid(), 'full_name' => 'Test Guest', 'email' => 't@example.com',
        ]);
        DB::table('lockers')->insert([
            'id' => 1, 'location_id' => 1, 'uuid' => (string) Str::uuid(),
            'number' => 'A1', 'size' => 'standard', 'status' => 'available',
        ]);
    }

    /** @param array<string,mixed> $attrs */
    private function booking(array $attrs): int
    {
        static $id = 0;
        $id++;
        DB::table('bookings')->insert(array_merge([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'customer_id' => 1,
            'location_id' => 1,
            'locker_size' => 'standard',
            'locker_qty' => 1,
            'check_in' => now(),
            'check_out' => now()->addDay(),
            'duration_label' => '24h',
            'price_eur' => 10,
            'total_eur' => 10,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'created_at' => now(),
        ], $attrs));

        return $id;
    }

    /** @return array<string,mixed> */
    private function stats(): array
    {
        DB::table('users')->insert([
            'id' => 1, 'username' => 'admin', 'name' => 'Admin',
            'email' => 'a@example.com', 'password' => bcrypt('x'),
            'role' => 'super_admin', 'is_active' => 1,
        ]);
        Sanctum::actingAs(User::find(1));

        $res = $this->getJson('/api/admin/dashboard');
        $res->assertOk();

        return $res->json('stats');
    }

    public function test_todays_bookings_excludes_cancelled(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');
        $this->seedFixtures();

        $this->booking(['check_in' => '2026-11-25 09:00:00']);
        $this->booking(['check_in' => '2026-11-25 10:00:00', 'booking_status' => 'cancelled']);

        $this->assertSame(1, $this->stats()['today_bookings'],
            'a cancelled booking must not be counted as a booking for today');
    }

    public function test_weekly_revenue_is_bounded_at_both_ends(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00'); // Wednesday
        $this->seedFixtures();

        $this->booking(['check_in' => '2026-11-23 09:00:00', 'total_eur' => 20]); // Monday, in range
        $this->booking(['check_in' => '2026-12-14 09:00:00', 'total_eur' => 99]); // paid, weeks ahead

        $this->assertEquals(20.0, (float) $this->stats()['revenue_week'],
            'a paid booking for a future week must not land in this week\'s revenue');
    }

    public function test_average_uses_the_same_filters_as_the_revenue_it_divides(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');
        $this->seedFixtures();

        // Two real paid stays this month: 20 + 40 = 60 over 2 bookings -> 30.00
        $this->booking(['check_in' => '2026-11-10 09:00:00', 'total_eur' => 20]);
        $this->booking(['check_in' => '2026-11-20 09:00:00', 'total_eur' => 40]);
        // Noise that must not enter the denominator: paid but cancelled, and
        // paid but still in the future.
        $this->booking(['check_in' => '2026-11-12 09:00:00', 'total_eur' => 50, 'booking_status' => 'cancelled']);
        $this->booking(['check_in' => '2026-11-30 09:00:00', 'total_eur' => 50]);

        $stats = $this->stats();
        $this->assertEquals(60.0, (float) $stats['revenue_month']);
        $this->assertEquals(30.0, (float) $stats['avg_booking_month'],
            'the average must divide month-to-date paid revenue by the matching count');
    }

    public function test_unrecognised_marketing_source_is_folded_into_other(): void
    {
        Carbon::setTestNow('2026-11-25 12:00:00');
        $this->seedFixtures();

        $this->booking(['marketing_source' => 'google_ads']);
        $this->booking(['marketing_source' => 'instagram']); // not in the channel list
        $this->booking(['marketing_source' => 'tiktok']);    // not in the channel list

        DB::table('users')->insert([
            'id' => 1, 'username' => 'admin', 'name' => 'Admin',
            'email' => 'a@example.com', 'password' => bcrypt('x'),
            'role' => 'super_admin', 'is_active' => 1,
        ]);
        Sanctum::actingAs(User::find(1));

        $attribution = $this->getJson('/api/admin/dashboard')->json('attribution');
        $byKey = collect($attribution['channels'])->keyBy('key');

        $this->assertSame(3, $attribution['total']);
        $this->assertSame(2, $byKey['other']['count'] ?? null,
            'sources outside the known list must be bucketed into "other", not dropped');
        $this->assertSame(100, collect($attribution['channels'])->sum('pct'),
            'the percentages must still add up to 100');
    }
}
