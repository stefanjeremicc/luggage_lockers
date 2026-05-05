<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * New global price list (effective 2026-05-05). Touches only rules with
 * location_id NULL; per-location overrides (if any) are left intact.
 *
 * Prices in EUR.                STANDARD   LARGE
 *   6h                              5        10
 *   24h                            10        15
 *   2_days                         20        25
 *   3_days                         25        35
 *   4_days                         30        45
 *   5_days                         35        50
 *   1_week                         45        60
 *   2_weeks                        80       100
 *   1_month                       120       200
 *
 * Past bookings carry their own line_total_eur snapshot in booking_items, so
 * existing reservations keep the price they were quoted.
 */
return new class extends Migration
{
    public function up(): void
    {
        $prices = [
            'standard' => [
                '6h' => 5,
                '24h' => 10,
                '2_days' => 20,
                '3_days' => 25,
                '4_days' => 30,
                '5_days' => 35,
                '1_week' => 45,
                '2_weeks' => 80,
                '1_month' => 120,
            ],
            'large' => [
                '6h' => 10,
                '24h' => 15,
                '2_days' => 25,
                '3_days' => 35,
                '4_days' => 45,
                '5_days' => 50,
                '1_week' => 60,
                '2_weeks' => 100,
                '1_month' => 200,
            ],
        ];

        foreach ($prices as $size => $byDuration) {
            foreach ($byDuration as $key => $eur) {
                DB::table('pricing_rules')
                    ->where('locker_size', $size)
                    ->where('duration_key', $key)
                    ->whereNull('location_id')
                    ->update([
                        'price_eur' => $eur,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Restore previous price list.
        $prev = [
            'standard' => ['6h'=>5,'24h'=>10,'2_days'=>18,'3_days'=>25,'4_days'=>30,'5_days'=>35,'1_week'=>50,'2_weeks'=>85,'1_month'=>150],
            'large' => ['6h'=>10,'24h'=>15,'2_days'=>27,'3_days'=>38,'4_days'=>45,'5_days'=>52,'1_week'=>75,'2_weeks'=>130,'1_month'=>230],
        ];
        foreach ($prev as $size => $byDuration) {
            foreach ($byDuration as $key => $eur) {
                DB::table('pricing_rules')
                    ->where('locker_size', $size)
                    ->where('duration_key', $key)
                    ->whereNull('location_id')
                    ->update(['price_eur' => $eur, 'updated_at' => now()]);
            }
        }
    }
};
