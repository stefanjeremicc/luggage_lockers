<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What are your opening hours?',
                'answer' => 'We are a self-service fully automated luggage store open 24/7, so you can store and pick up your luggage at any time, day or night!',
                'category' => 'General',
                'sort_order' => 1,
            ],
            [
                'question' => 'Where are you located?',
                'answer' => 'We have two locations in Belgrade: Kralja Milana 20 (near the airport shuttle bus stop, just 10 minutes from the city center) and Kapetan Mišina 2A (in the old town, near Knez Mihailova Street and Kalemegdan Fortress).',
                'category' => 'General',
                'sort_order' => 2,
            ],
            [
                'question' => 'How does the luggage storage work?',
                'answer' => 'Simply book online, choose your locker size and duration, and receive a PIN code via email and optionally WhatsApp. When you arrive, enter your PIN on the locker keypad to open it. Store your bags and lock. Use the same PIN to retrieve your belongings anytime during your booking period.',
                'category' => 'General',
                'sort_order' => 3,
            ],
            [
                'question' => 'What sizes of lockers are available?',
                'answer' => 'We offer two sizes: Standard — perfect for backpacks, carry-ons, and one suitcase (40x35x55 cm), and Large — fits up to 2 suitcases and bags (60x45x75 cm).',
                'category' => 'General',
                'sort_order' => 4,
            ],
            [
                'question' => 'What are the prices?',
                'answer' => 'Our pricing starts from €6 for 2 hours. We offer hourly rates (2h, 3h, 4h, 5h, 6h, 7h) and daily rates (24h, multi-day). Full pricing is visible during booking. Payment is accepted in euros or Serbian dinars, cash on arrival.',
                'category' => 'Pricing',
                'sort_order' => 5,
            ],
            [
                'question' => 'What payment methods do you accept?',
                'answer' => 'Currently we accept cash payments on site in euros (EUR) or Serbian dinars (RSD). Online payment via credit card is coming soon.',
                'category' => 'Pricing',
                'sort_order' => 6,
            ],
            [
                'question' => 'Is my luggage safe?',
                'answer' => 'Absolutely. Our lockers use smart lock technology with unique PIN codes for each booking. The storage area is monitored 24/7 with security cameras, and only you have access to your locker via your personal PIN code.',
                'category' => 'Security',
                'sort_order' => 7,
            ],
            [
                'question' => 'Can I book instantly if I need a locker right now?',
                'answer' => 'Yes! You can book online and arrive immediately. Booking is instant, and you can arrive up to 20 minutes earlier than your selected arrival time.',
                'category' => 'Booking',
                'sort_order' => 8,
            ],
            [
                'question' => 'What if I\'m running late?',
                'answer' => 'You can arrive up to 20 minutes later than your scheduled arrival time. If you need to arrive even later, please contact us via WhatsApp or email and we\'ll do our best to accommodate you.',
                'category' => 'Booking',
                'sort_order' => 9,
            ],
            [
                'question' => 'How do I cancel my booking?',
                'answer' => 'You can cancel your booking anytime before check-in using the cancellation link in your confirmation email. Since payment is cash on arrival, there are no charges for cancellation.',
                'category' => 'Booking',
                'sort_order' => 10,
            ],
            [
                'question' => 'Can I access my locker at any time?',
                'answer' => 'Yes! Our locations are open 24/7. You can access your locker as many times as you need during your booking period using your PIN code.',
                'category' => 'General',
                'sort_order' => 11,
            ],
            [
                'question' => 'What happens if I\'m late to pick up?',
                'answer' => 'We have a 20-minute grace period after your booking expires. If you need more time, please contact us to extend your booking. Uncollected luggage after the grace period may incur additional charges.',
                'category' => 'General',
                'sort_order' => 12,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create(array_merge($faq, ['locale' => 'en', 'is_active' => true]));
        }
    }
}
