<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Locker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LockerSeeder extends Seeder
{
    public function run(): void
    {
        $locations = Location::all();

        foreach ($locations as $location) {
            // 8 standard lockers
            for ($i = 1; $i <= 8; $i++) {
                Locker::create([
                    'location_id' => $location->id,
                    'uuid' => Str::uuid(),
                    'number' => 'A' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'size' => 'standard',
                    'status' => 'available',
                    'battery_level' => rand(60, 100),
                    'is_online' => true,
                    'dimensions_cm' => ['width' => 40, 'height' => 55, 'depth' => 35],
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }

            // 4 large lockers
            for ($i = 1; $i <= 4; $i++) {
                Locker::create([
                    'location_id' => $location->id,
                    'uuid' => Str::uuid(),
                    'number' => 'B' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'size' => 'large',
                    'status' => 'available',
                    'battery_level' => rand(60, 100),
                    'is_online' => true,
                    'dimensions_cm' => ['width' => 60, 'height' => 75, 'depth' => 45],
                    'sort_order' => 8 + $i,
                    'is_active' => true,
                ]);
            }
        }
    }
}
