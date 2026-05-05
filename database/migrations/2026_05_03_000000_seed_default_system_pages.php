<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensure every public route has a corresponding `pages` row in en + sr so the
 * SEO composer can serve meta_title/meta_description/og_* from the DB instead
 * of hardcoded blade strings. Idempotent — only inserts rows that don't exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'home' => [
                'en' => ['title' => 'Belgrade Luggage Locker', 'meta_title' => 'Luggage Storage in Belgrade — 24/7 Smart Lockers', 'meta_description' => 'Secure 24/7 luggage storage in central Belgrade. Smart PIN lockers, book online in 60 seconds, pay on arrival.'],
                'sr' => ['title' => 'Belgrade Luggage Locker', 'meta_title' => 'Garderoba u Beogradu — Pametni ormarići 24/7', 'meta_description' => 'Sigurna garderoba u centru Beograda 24/7. Pametni PIN ormarići, rezervacija online za 60 sekundi, plaćanje po dolasku.'],
            ],
            'about' => [
                'en' => ['title' => 'About Us', 'meta_title' => 'About Belgrade Luggage Locker', 'meta_description' => 'Learn about our 24/7 smart luggage storage service in Belgrade — secure, affordable, and made for travelers.'],
                'sr' => ['title' => 'O nama', 'meta_title' => 'O nama — Belgrade Luggage Locker', 'meta_description' => 'Saznajte više o našoj usluzi pametne garderobe 24/7 u Beogradu — sigurno, povoljno i prilagođeno putnicima.'],
            ],
            'pricing' => [
                'en' => ['title' => 'Pricing', 'meta_title' => 'Luggage Storage Pricing — Belgrade Luggage Locker', 'meta_description' => 'Transparent prices for luggage storage in Belgrade. Standard and large lockers, 6h to 1-month rates.'],
                'sr' => ['title' => 'Cenovnik', 'meta_title' => 'Cene garderobe — Belgrade Luggage Locker', 'meta_description' => 'Transparentne cene garderobe u Beogradu. Standardni i veliki ormarići, od 6h do 1 mesec.'],
            ],
            'contact' => [
                'en' => ['title' => 'Contact', 'meta_title' => 'Contact — Belgrade Luggage Locker', 'meta_description' => 'Get in touch with Belgrade Luggage Locker. Email, phone, and on-site location addresses.'],
                'sr' => ['title' => 'Kontakt', 'meta_title' => 'Kontakt — Belgrade Luggage Locker', 'meta_description' => 'Kontaktirajte Belgrade Luggage Locker. Email, telefon i adrese lokacija.'],
            ],
            'faq' => [
                'en' => ['title' => 'FAQ', 'meta_title' => 'Frequently Asked Questions — Belgrade Luggage Locker', 'meta_description' => 'Answers to common questions about pricing, access, security, and how Belgrade Luggage Locker works.'],
                'sr' => ['title' => 'Česta pitanja', 'meta_title' => 'Česta pitanja — Belgrade Luggage Locker', 'meta_description' => 'Odgovori na najčešća pitanja o cenama, pristupu, bezbednosti i radu Belgrade Luggage Locker.'],
            ],
            'terms' => [
                'en' => ['title' => 'Terms & Conditions', 'meta_title' => 'Terms & Conditions — Belgrade Luggage Locker', 'meta_description' => 'Terms and conditions governing the use of Belgrade Luggage Locker services.'],
                'sr' => ['title' => 'Uslovi korišćenja', 'meta_title' => 'Uslovi korišćenja — Belgrade Luggage Locker', 'meta_description' => 'Uslovi korišćenja usluga Belgrade Luggage Locker.'],
            ],
            'privacy' => [
                'en' => ['title' => 'Privacy Policy', 'meta_title' => 'Privacy Policy — Belgrade Luggage Locker', 'meta_description' => 'How we collect, use, and protect your personal data when you use Belgrade Luggage Locker.'],
                'sr' => ['title' => 'Politika privatnosti', 'meta_title' => 'Politika privatnosti — Belgrade Luggage Locker', 'meta_description' => 'Kako prikupljamo, koristimo i štitimo vaše lične podatke prilikom korišćenja Belgrade Luggage Locker.'],
            ],
            'blog' => [
                'en' => ['title' => 'Blog', 'meta_title' => 'Belgrade Travel Tips & News — Belgrade Luggage Locker', 'meta_description' => 'Travel tips, neighborhood guides, and news about luggage storage and exploring Belgrade hands-free.'],
                'sr' => ['title' => 'Blog', 'meta_title' => 'Saveti za putovanje i vesti — Belgrade Luggage Locker', 'meta_description' => 'Saveti za putovanje, vodiči kroz kvartove i vesti o garderobi i istraživanju Beograda bez prtljaga.'],
            ],
            'locations' => [
                'en' => ['title' => 'Our Locations', 'meta_title' => 'Locations — Belgrade Luggage Locker', 'meta_description' => 'All Belgrade Luggage Locker locations across Belgrade. Pick the closest one and book online.'],
                'sr' => ['title' => 'Lokacije', 'meta_title' => 'Lokacije — Belgrade Luggage Locker', 'meta_description' => 'Sve lokacije Belgrade Luggage Locker u Beogradu. Izaberite najbližu i rezervišite online.'],
            ],
            'booking-index' => [
                'en' => ['title' => 'Book a Locker', 'meta_title' => 'Book a Luggage Locker — Belgrade Luggage Locker', 'meta_description' => 'Reserve your luggage locker online in 60 seconds. Pick a size, choose a duration, pay on arrival.'],
                'sr' => ['title' => 'Rezerviši ormarić', 'meta_title' => 'Rezerviši garderobu — Belgrade Luggage Locker', 'meta_description' => 'Rezerviši svoj ormarić online za 60 sekundi. Izaberi veličinu i trajanje, plati po dolasku.'],
            ],
            'booking-confirmation' => [
                'en' => ['title' => 'Booking Confirmed', 'meta_title' => 'Booking Confirmed — Belgrade Luggage Locker', 'meta_description' => 'Your luggage locker booking is confirmed. Check your inbox for the PIN and entry instructions.'],
                'sr' => ['title' => 'Rezervacija potvrđena', 'meta_title' => 'Rezervacija potvrđena — Belgrade Luggage Locker', 'meta_description' => 'Vaša rezervacija ormarića je potvrđena. Proverite email za PIN i uputstva za pristup.'],
            ],
            'booking-cancel' => [
                'en' => ['title' => 'Cancel Booking', 'meta_title' => 'Cancel Booking — Belgrade Luggage Locker', 'meta_description' => 'Cancel your luggage locker booking. Use the secure link from your confirmation email.'],
                'sr' => ['title' => 'Otkaži rezervaciju', 'meta_title' => 'Otkaži rezervaciju — Belgrade Luggage Locker', 'meta_description' => 'Otkaži rezervaciju ormarića. Koristite siguran link iz potvrde email-a.'],
            ],
        ];

        $now = now();
        foreach ($defaults as $slug => $byLocale) {
            foreach ($byLocale as $locale => $row) {
                $exists = DB::table('pages')->where('slug', $slug)->where('locale', $locale)->exists();
                if ($exists) continue;
                DB::table('pages')->insert([
                    'slug' => $slug,
                    'locale' => $locale,
                    'type' => 'page',
                    'title' => $row['title'],
                    'meta_title' => $row['meta_title'],
                    'meta_description' => $row['meta_description'],
                    'is_published' => true,
                    'published_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive — leave the seeded rows in place. If the operator
        // really wants them gone they can delete via admin UI.
    }
};
