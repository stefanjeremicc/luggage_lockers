<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            [
                'name' => 'Lusine Titanyan',
                'text' => "Thank you for service, it's really help us and the most important things it was safe. Highly recommended!",
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'L',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Taylor Hanson',
                'text' => 'Highly recommend to store your luggage here! I had a 5 hour layover and wanted to explore the city. Super easy booking and very secure.',
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'T',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Rossana Reyna',
                'text' => "The service is excellent, it's very easy and the location is great!! Would definitely use again on my next visit.",
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'R',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Ali Canakci',
                'text' => 'That was safe and easy. They communicated so well and gave instant response. Perfect for travelers passing through Belgrade.',
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'A',
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Damjan Poposki',
                'text' => 'Great experience! Wonderful location, clear instructions, safe and clean space. Exactly what you need when exploring the city.',
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'D',
                'is_featured' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Maria K.',
                'text' => 'Used it twice already. Best luggage storage option in Belgrade. Very responsive on WhatsApp and lockers are super clean.',
                'rating' => 5,
                'source' => 'google',
                'avatar_letter' => 'M',
                'is_featured' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($reviews as $review) {
            Review::firstOrCreate(['name' => $review['name']], $review);
        }
    }
}
