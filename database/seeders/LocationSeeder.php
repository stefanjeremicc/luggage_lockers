<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::create([
            'name' => 'City Center',
            'slug' => 'city-center',
            'address' => 'Kapetan Mišina 2A',
            'city' => 'Belgrade',
            'lat' => 44.8176,
            'lng' => 20.4633,
            'description' => 'Just steps from Knez Mihailova street and Kalemegdan fortress, our luggage store sits in the heart of Belgrade\'s old town. Perfect for exploring the city center hands-free.',
            'description_sr' => 'Na par koraka od Knez Mihailove ulice i Kalemegdanske tvrđave, naše skladište prtljaga se nalazi u srcu starog dela Beograda. Savršeno za istraživanje centra grada bez prtljaga.',
            'is_24h' => true,
            'phone' => '+381 64 294 1503',
            'whatsapp' => '+381653322319',
            'email' => 'info@belgradeluggagelocker.com',
            'google_maps_url' => 'https://maps.app.goo.gl/i52R2RoNb7RXjJ8e9',
            'meta_title' => 'Luggage Storage City Center Belgrade - 24/7 Secure Lockers',
            'meta_description' => 'Secure 24/7 luggage storage in Belgrade city center near Knez Mihailova. Smart lockers, instant booking. From €5.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Location::create([
            'name' => 'Near A1 Airport Bus Stop',
            'slug' => 'kralja-milana',
            'address' => 'Kralja Milana 20',
            'city' => 'Belgrade',
            'lat' => 44.8076,
            'lng' => 20.4644,
            'description' => '5 min from the airport shuttle stop, 10 min from city center by foot — the perfect central location for travelers arriving or departing Belgrade.',
            'description_sr' => '5 minuta od stanice aerodromskog autobusa, 10 minuta pešice od centra grada — savršena centralna lokacija za putnike koji dolaze ili odlaze iz Beograda.',
            'is_24h' => true,
            'phone' => '+381 64 294 1503',
            'whatsapp' => '+381653322319',
            'email' => 'info@belgradeluggagelocker.com',
            'google_maps_url' => 'https://maps.app.goo.gl/SPFF8w36eKfdUwgm7',
            'meta_title' => 'Luggage Storage Near Airport Bus Belgrade - Kralja Milana 20',
            'meta_description' => 'Secure luggage storage near Belgrade airport shuttle stop at Kralja Milana 20. Smart lockers, 24/7 access. From €5.',
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }
}
