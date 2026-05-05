<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed structured sections (hero / how_it_works / faq / cta) for the home page
 * in both locales so admin sees real defaults in PageEdit instead of empty
 * fields. Only fills sections when the column is null — never overwrites
 * admin-edited content.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sections = [
            'en' => [
                'hero' => [
                    'title' => '24/7 Smart Luggage Storage in Belgrade',
                    'subtitle' => 'Drop your bags, explore hands-free. Smart PIN lockers in central Belgrade — book online in 60 seconds, pay on arrival.',
                    'cta_primary' => 'Book a Locker',
                    'cta_secondary' => 'See Locations',
                    'image' => '',
                ],
                'how_it_works_heading' => [
                    'title' => 'How It Works',
                    'subtitle' => 'Four steps from your phone to a locked-up bag.',
                ],
                'how_it_works' => [
                    ['icon' => 'computer', 'title' => 'Book online', 'desc' => 'Pick a location, locker size, and duration. Takes about 60 seconds.'],
                    ['icon' => 'search', 'title' => 'Find your spot', 'desc' => 'We send the address, entry door code, and step-by-step instructions to your email and WhatsApp.'],
                    ['icon' => 'lock', 'title' => 'Drop your bags', 'desc' => 'Open your locker with the PIN we sent you. Same PIN for re-entry until check-out.'],
                    ['icon' => 'smile', 'title' => 'Enjoy Belgrade', 'desc' => 'Hands-free city exploring. Come back any time — your locker stays yours until check-out.'],
                ],
                'faq' => [
                    'title' => 'Frequently Asked Questions',
                    'subtitle' => 'Quick answers about pricing, security, and access.',
                ],
                'cta' => [
                    'title' => 'Ready to drop your bags?',
                    'subtitle' => 'Book a locker now and explore Belgrade hands-free.',
                    'button' => 'Book a Locker',
                ],
            ],
            'sr' => [
                'hero' => [
                    'title' => 'Pametna garderoba u Beogradu 24/7',
                    'subtitle' => 'Ostavi prtljag, istražuj bez tereta. Pametni PIN ormarići u centru Beograda — rezervacija online za 60 sekundi, plaćanje po dolasku.',
                    'cta_primary' => 'Rezerviši ormarić',
                    'cta_secondary' => 'Pogledaj lokacije',
                    'image' => '',
                ],
                'how_it_works_heading' => [
                    'title' => 'Kako funkcioniše',
                    'subtitle' => 'Četiri koraka od telefona do zaključanog prtljaga.',
                ],
                'how_it_works' => [
                    ['icon' => 'computer', 'title' => 'Rezerviši online', 'desc' => 'Izaberi lokaciju, veličinu ormarića i trajanje. Traje oko 60 sekundi.'],
                    ['icon' => 'search', 'title' => 'Pronađi lokaciju', 'desc' => 'Šaljemo adresu, kod ulaznih vrata i uputstva korak po korak na email i WhatsApp.'],
                    ['icon' => 'lock', 'title' => 'Ostavi prtljag', 'desc' => 'Otvori ormarić PIN-om koji smo poslali. Isti PIN važi za ponovni ulaz do isteka rezervacije.'],
                    ['icon' => 'smile', 'title' => 'Uživaj u Beogradu', 'desc' => 'Istražuj grad bez tereta. Vraćaj se kad god želiš — ormarić je tvoj do odjave.'],
                ],
                'faq' => [
                    'title' => 'Česta pitanja',
                    'subtitle' => 'Brzi odgovori o cenama, bezbednosti i pristupu.',
                ],
                'cta' => [
                    'title' => 'Spreman da ostaviš prtljag?',
                    'subtitle' => 'Rezerviši ormarić i istražuj Beograd bez tereta.',
                    'button' => 'Rezerviši ormarić',
                ],
            ],
        ];

        foreach ($sections as $locale => $payload) {
            DB::table('pages')
                ->where('slug', 'home')
                ->where('locale', $locale)
                ->whereNull('sections')
                ->update([
                    'sections' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('pages')->where('slug', 'home')->update(['sections' => null]);
    }
};
