<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // Locker size descriptions (capacity, dimensions, image) live in settings — single
        // source of truth for pricing page, booking flow, home page, and emails.
        $defaults = [
            'locker_standard_capacity_en' => '1 suitcase & 1 bag',
            'locker_standard_capacity_sr' => '1 kofer i 1 torba',
            'locker_standard_dimensions' => '50 × 65 × 28 cm',
            'locker_standard_image' => '/images/lockers/standard-dimensions.jpg',
            'locker_large_capacity_en' => '2 suitcases & 1 bag',
            'locker_large_capacity_sr' => '2 kofera i 1 torba',
            'locker_large_dimensions' => '50 × 65 × 90 cm',
            'locker_large_image' => '/images/lockers/large-dimensions.jpg',
        ];
        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string', 'group' => 'lockers']
            );
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'locker_standard_capacity_en', 'locker_standard_capacity_sr',
            'locker_standard_dimensions', 'locker_standard_image',
            'locker_large_capacity_en', 'locker_large_capacity_sr',
            'locker_large_dimensions', 'locker_large_image',
        ])->delete();
    }
};
