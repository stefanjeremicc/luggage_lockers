<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'top-things-to-do-in-belgrade-without-luggage',
                'locale' => 'en',
                'title' => 'Top 10 Things to Do in Belgrade When You\'re Luggage-Free',
                'excerpt' => 'Discover the best attractions, hidden gems, and local favorites in Belgrade — all easier to enjoy when your bags are safely stored.',
                'content' => '<p>Belgrade is a city best explored on foot — but dragging suitcases through cobblestone streets? Not ideal. Whether you have a few hours between flights or a full day to explore, here are the top things to do once you\'ve dropped your bags at a secure locker.</p>

<h2>1. Walk Through Kalemegdan Fortress</h2>
<p>Perched at the confluence of the Sava and Danube rivers, Kalemegdan is Belgrade\'s most iconic landmark. The fortress grounds offer stunning panoramic views, historical monuments, and peaceful green spaces. It\'s the perfect first stop for any visitor.</p>

<h2>2. Stroll Down Knez Mihailova Street</h2>
<p>Belgrade\'s main pedestrian boulevard is lined with elegant 19th-century buildings, shops, cafes, and street performers. It connects Kalemegdan to Republic Square and is the beating heart of the city center.</p>

<h2>3. Explore Skadarlija — Belgrade\'s Bohemian Quarter</h2>
<p>Often compared to Montmartre in Paris, this charming cobblestone street is full of traditional Serbian restaurants (kafanas), live music, and artistic atmosphere. Perfect for lunch or dinner.</p>

<h2>4. Visit the Temple of Saint Sava</h2>
<p>One of the largest Orthodox churches in the world, the Temple of Saint Sava is a breathtaking example of Serbian-Byzantine architecture. The recently completed interior mosaics are absolutely stunning.</p>

<h2>5. Discover Belgrade Waterfront</h2>
<p>The newest addition to Belgrade\'s skyline, this modern development along the Sava River offers upscale shopping, dining, and a beautiful promenade. The Kula Belgrade tower is an impressive sight.</p>

<h2>6. Try Serbian Cuisine</h2>
<p>Don\'t leave without trying ćevapi, pljeskavica, or kajmak. Belgrade\'s food scene ranges from traditional kafanas to modern fusion restaurants — all best enjoyed without carrying heavy bags!</p>

<h2>7. Relax at Ada Ciganlija</h2>
<p>Known as "Belgrade\'s Sea," this river island turned peninsula offers beaches, sports facilities, and nature trails. A great spot to unwind on a warm day.</p>

<h2>8. Explore Zemun</h2>
<p>This charming neighborhood across the Danube has its own distinct character with Austro-Hungarian architecture, the Gardoš Tower, and excellent fish restaurants along the river.</p>

<h2>9. Visit the Nikola Tesla Museum</h2>
<p>A must for science enthusiasts, this museum houses personal items and interactive exhibits about one of history\'s greatest inventors — who was born in modern-day Serbia.</p>

<h2>10. Experience Belgrade\'s Nightlife</h2>
<p>Belgrade is famous for its vibrant nightlife scene. From floating river clubs (splavovi) to underground bars in Savamala, the city truly comes alive after dark.</p>

<h2>Pro Tip: Store Your Luggage First</h2>
<p>All of these experiences are infinitely better without dragging suitcases around. Our 24/7 smart lockers at two central locations let you drop your bags in seconds and pick them up whenever you\'re ready. Book online from just €5!</p>',
                'featured_image' => '/images/blog/things-to-do-belgrade.jpg',
                'category' => 'Travel Guide',
                'tags' => ['belgrade', 'travel', 'sightseeing', 'tourism'],
                'meta_title' => 'Top 10 Things to Do in Belgrade Without Luggage | Belgrade Luggage Locker',
                'meta_description' => 'Explore Belgrade hands-free! Discover the top 10 attractions, restaurants, and experiences — all better without carrying heavy bags.',
                'author_name' => 'Belgrade Luggage Locker',
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'slug' => 'belgrade-layover-guide-what-to-do',
                'locale' => 'en',
                'title' => 'Belgrade Layover Guide: How to Spend 4-8 Hours in the City',
                'excerpt' => 'Got a long layover in Belgrade? Here\'s your complete guide to making the most of your time — from the airport to the city center and back.',
                'content' => '<p>Belgrade Nikola Tesla Airport is just 18km from the city center, making it one of Europe\'s most accessible airports for a layover adventure. If you have 4-8 hours between flights, here\'s how to make the most of it.</p>

<h2>Getting from the Airport to the City</h2>
<p>The A1 airport bus runs every 30 minutes and costs around €3. The journey takes about 30-40 minutes to Slavija Square. Taxis cost €15-20 and take about 25 minutes depending on traffic.</p>

<h2>First Stop: Drop Your Bags</h2>
<p>Don\'t waste your precious layover time lugging suitcases around Belgrade\'s cobblestone streets. Our luggage storage locations are just minutes from the main bus stops and key attractions. Drop your bags, get your PIN code, and you\'re free to explore.</p>

<h2>4-Hour Layover Itinerary</h2>
<ul>
<li><strong>Hour 1:</strong> Airport to city center + drop bags at locker</li>
<li><strong>Hour 2:</strong> Walk Knez Mihailova to Kalemegdan Fortress — enjoy the river views</li>
<li><strong>Hour 3:</strong> Quick lunch in Skadarlija (try ćevapi!) + coffee break</li>
<li><strong>Hour 4:</strong> Pick up bags + head back to airport</li>
</ul>

<h2>8-Hour Layover Itinerary</h2>
<ul>
<li><strong>Hours 1-2:</strong> Airport to city + drop bags + Kalemegdan exploration</li>
<li><strong>Hours 3-4:</strong> Knez Mihailova shopping + Republic Square + National Museum</li>
<li><strong>Hours 5-6:</strong> Lunch in Skadarlija + Temple of Saint Sava</li>
<li><strong>Hours 7-8:</strong> Belgrade Waterfront stroll + pick up bags + airport</li>
</ul>

<h2>Essential Tips for Belgrade Layovers</h2>
<ul>
<li>Serbian dinar (RSD) is the local currency, but euros are widely accepted in the city center</li>
<li>Free Wi-Fi is available in most cafes and restaurants</li>
<li>Belgrade is very walkable — most attractions are within 20 minutes of each other</li>
<li>Book your luggage locker in advance to save time on arrival</li>
</ul>

<h2>Don\'t Just Sit at the Airport</h2>
<p>Belgrade is too interesting to spend your layover at an airport gate. With secure 24/7 luggage storage starting from just €5, there\'s no excuse not to explore one of Europe\'s most underrated capitals.</p>',
                'featured_image' => '/images/blog/belgrade-layover.jpg',
                'category' => 'Travel Tips',
                'tags' => ['layover', 'belgrade', 'airport', 'travel tips'],
                'meta_title' => 'Belgrade Layover Guide: What to Do in 4-8 Hours | Belgrade Luggage Locker',
                'meta_description' => 'Make the most of your Belgrade layover! Complete guide with 4-hour and 8-hour itineraries, transport tips, and luggage storage info.',
                'author_name' => 'Belgrade Luggage Locker',
                'is_published' => true,
                'published_at' => now()->subDays(12),
            ],
            [
                'slug' => 'is-luggage-storage-safe-in-belgrade',
                'locale' => 'en',
                'title' => 'Is Luggage Storage Safe in Belgrade? Everything You Need to Know',
                'excerpt' => 'Wondering about the safety of luggage storage in Belgrade? Here\'s what to expect from modern smart locker services and how they keep your belongings secure.',
                'content' => '<p>One of the most common questions travelers ask is: "Is it safe to leave my luggage with a storage service?" It\'s a fair question — your belongings are important. Here\'s everything you need to know about luggage storage safety in Belgrade.</p>

<h2>How Modern Smart Lockers Work</h2>
<p>Gone are the days of handing your bags to a stranger behind a counter. Modern luggage storage uses smart locker technology — individual compartments with electronic PIN-based locks. Only you have the code, and only your code opens your specific locker.</p>

<h2>Security Features to Look For</h2>
<ul>
<li><strong>24/7 CCTV Surveillance:</strong> Look for locations with constant camera monitoring. Our locations are monitored around the clock.</li>
<li><strong>Individual PIN Codes:</strong> Each booking generates a unique access code that\'s sent only to you via email.</li>
<li><strong>Smart Lock Technology:</strong> Electronic locks are more secure than traditional padlocks and can\'t be picked.</li>
<li><strong>Indoor Locations:</strong> Your luggage should be stored indoors, protected from weather and opportunistic theft.</li>
<li><strong>Insurance Coverage:</strong> Reputable services include basic insurance coverage for stored items.</li>
</ul>

<h2>Tips for Extra Peace of Mind</h2>
<ul>
<li>Keep valuables like passports, cash, and electronics with you</li>
<li>Take a photo of your locker number and the facility for reference</li>
<li>Save your confirmation email with the PIN code somewhere accessible</li>
<li>Use a luggage lock on your suitcase zippers as an extra layer</li>
<li>Choose services with verified Google reviews from real travelers</li>
</ul>

<h2>What Our Customers Say</h2>
<p>With a 4.9 rating on Google from over 70 reviews, our customers consistently highlight safety and ease of use. Comments like "felt very safe," "super secure," and "easy and reliable" are the norm.</p>

<h2>Belgrade Is a Safe City for Travelers</h2>
<p>Belgrade consistently ranks as one of the safer capitals in Southeast Europe. Petty crime exists (as in any city), but violent crime targeting tourists is extremely rare. The city center, where our locations are based, is well-lit, well-policed, and bustling with locals and visitors at all hours.</p>

<h2>Book With Confidence</h2>
<p>Our smart lockers are monitored 24/7, use individual PIN codes, and are located in secure indoor facilities in the city center. Book online in 60 seconds, pay on arrival, and explore Belgrade knowing your belongings are safe.</p>',
                'featured_image' => '/images/blog/luggage-storage-safety.jpg',
                'category' => 'Safety & Security',
                'tags' => ['safety', 'security', 'luggage storage', 'belgrade'],
                'meta_title' => 'Is Luggage Storage Safe in Belgrade? Complete Safety Guide | Belgrade Luggage Locker',
                'meta_description' => 'Learn about luggage storage safety in Belgrade. Smart lockers, CCTV, PIN codes, and tips to keep your belongings secure while you explore.',
                'author_name' => 'Belgrade Luggage Locker',
                'is_published' => true,
                'published_at' => now()->subDays(20),
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug'], 'locale' => $post['locale']],
                $post
            );
        }
    }
}
