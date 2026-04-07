<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Only 9 duration tiers — matching the real business model
        // Standard: from €5 (6h) | Large: from €10 (6h)
        $prices = [
            ['duration_key' => '6h',      'standard' => 5.00,   'large' => 10.00],
            ['duration_key' => '24h',     'standard' => 10.00,  'large' => 15.00],
            ['duration_key' => '2_days',  'standard' => 18.00,  'large' => 27.00],
            ['duration_key' => '3_days',  'standard' => 25.00,  'large' => 38.00],
            ['duration_key' => '4_days',  'standard' => 30.00,  'large' => 45.00],
            ['duration_key' => '5_days',  'standard' => 35.00,  'large' => 52.00],
            ['duration_key' => '1_week',  'standard' => 50.00,  'large' => 75.00],
            ['duration_key' => '2_weeks', 'standard' => 85.00,  'large' => 130.00],
            ['duration_key' => '1_month', 'standard' => 150.00, 'large' => 230.00],
        ];

        foreach ($prices as $price) {
            PricingRule::create([
                'location_id' => null,
                'locker_size' => 'standard',
                'duration_key' => $price['duration_key'],
                'price_eur' => $price['standard'],
            ]);

            PricingRule::create([
                'location_id' => null,
                'locker_size' => 'large',
                'duration_key' => $price['duration_key'],
                'price_eur' => $price['large'],
            ]);
        }
    }
}
