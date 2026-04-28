<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'home' => [
                'en' => [
                    'title' => 'Home',
                    'meta_title' => 'Belgrade Luggage Locker — 24/7 Secure Luggage Storage',
                    'meta_description' => 'Secure 24/7 luggage storage in Belgrade city center. Smart lockers, book online in 60 seconds, pay cash on arrival.',
                ],
                'sr' => [
                    'title' => 'Početna',
                    'meta_title' => 'Belgrade Luggage Locker — Garderoba za Prtljag 24/7',
                    'meta_description' => 'Sigurna garderoba za prtljag 24/7 u centru Beograda. Pametni ormančići, rezerviši online za 60 sekundi.',
                ],
            ],
            'locations' => [
                'en' => [
                    'title' => 'Locations',
                    'meta_title' => 'Our Locations — Belgrade Luggage Locker',
                    'meta_description' => 'Find a luggage locker location in Belgrade city center. Open 24/7, secure, smart lock access.',
                ],
                'sr' => [
                    'title' => 'Lokacije',
                    'meta_title' => 'Naše Lokacije — Belgrade Luggage Locker',
                    'meta_description' => 'Pronađite lokaciju za garderobu prtljaga u centru Beograda. Otvoreno 24/7, sigurno, pametna brava.',
                ],
            ],
            'pricing' => [
                'en' => [
                    'title' => 'Pricing',
                    'meta_title' => 'Pricing — Luggage Storage from €5 in Belgrade',
                    'meta_description' => 'Transparent pricing for luggage storage in Belgrade. Standard from €5, Large from €10. No hidden fees.',
                ],
                'sr' => [
                    'title' => 'Cenovnik',
                    'meta_title' => 'Cenovnik — Garderoba za Prtljag od €5 u Beogradu',
                    'meta_description' => 'Transparentne cene za čuvanje prtljaga u Beogradu. Standardni od €5, Veliki od €10. Bez skrivenih troškova.',
                ],
            ],
            'faq' => [
                'en' => [
                    'title' => 'Frequently Asked Questions',
                    'meta_title' => 'FAQ — Belgrade Luggage Locker',
                    'meta_description' => 'Answers to common questions about luggage storage, locker sizes, security, and bookings in Belgrade.',
                ],
                'sr' => [
                    'title' => 'Česta Pitanja',
                    'meta_title' => 'Česta Pitanja — Belgrade Luggage Locker',
                    'meta_description' => 'Odgovori na česta pitanja o čuvanju prtljaga, veličinama ormana, bezbednosti i rezervacijama u Beogradu.',
                ],
            ],
            'contact' => [
                'en' => [
                    'title' => 'Contact',
                    'meta_title' => 'Contact — Belgrade Luggage Locker',
                    'meta_description' => 'Get in touch with Belgrade Luggage Locker. Phone, email, WhatsApp, and our locations on the map.',
                ],
                'sr' => [
                    'title' => 'Kontakt',
                    'meta_title' => 'Kontakt — Belgrade Luggage Locker',
                    'meta_description' => 'Kontaktirajte Belgrade Luggage Locker. Telefon, email, WhatsApp i naše lokacije na mapi.',
                ],
            ],
            'blog' => [
                'en' => [
                    'title' => 'Blog',
                    'meta_title' => 'Blog — Travel Tips & Belgrade Guides',
                    'meta_description' => 'Travel tips, Belgrade guides, and luggage storage advice from Belgrade Luggage Locker.',
                ],
                'sr' => [
                    'title' => 'Blog',
                    'meta_title' => 'Blog — Saveti za Putovanja i Vodiči po Beogradu',
                    'meta_description' => 'Saveti za putovanja, vodiči po Beogradu i preporuke za garderobu prtljaga.',
                ],
            ],
            'about' => [
                'en' => [
                    'title' => 'About Us',
                    'meta_title' => 'About — Belgrade Luggage Locker',
                    'meta_description' => 'Learn about Belgrade Luggage Locker — our mission, locations, and commitment to safe, simple luggage storage.',
                ],
                'sr' => [
                    'title' => 'O Nama',
                    'meta_title' => 'O Nama — Belgrade Luggage Locker',
                    'meta_description' => 'Saznajte više o Belgrade Luggage Locker — naša misija, lokacije i posvećenost sigurnom čuvanju prtljaga.',
                ],
            ],
            'terms' => [
                'en' => [
                    'title' => 'Terms & Conditions',
                    'meta_title' => 'Terms & Conditions — Belgrade Luggage Locker',
                    'meta_description' => 'Terms and conditions of using Belgrade Luggage Locker services.',
                ],
                'sr' => [
                    'title' => 'Uslovi Korišćenja',
                    'meta_title' => 'Uslovi Korišćenja — Belgrade Luggage Locker',
                    'meta_description' => 'Uslovi korišćenja usluga Belgrade Luggage Locker.',
                ],
            ],
            'privacy' => [
                'en' => [
                    'title' => 'Privacy Policy',
                    'meta_title' => 'Privacy Policy — Belgrade Luggage Locker',
                    'meta_description' => 'How Belgrade Luggage Locker collects, uses, and protects your personal data.',
                ],
                'sr' => [
                    'title' => 'Politika Privatnosti',
                    'meta_title' => 'Politika Privatnosti — Belgrade Luggage Locker',
                    'meta_description' => 'Kako Belgrade Luggage Locker prikuplja, koristi i štiti vaše lične podatke.',
                ],
            ],
        ];

        foreach ($pages as $slug => $locales) {
            foreach ($locales as $locale => $data) {
                Page::updateOrCreate(
                    ['slug' => $slug, 'locale' => $locale],
                    array_merge($data, ['is_published' => true, 'published_at' => now()])
                );
            }
        }
    }
}
