<?php

/*
|--------------------------------------------------------------------------
| SEO landing pages
|--------------------------------------------------------------------------
| Query-targeted pages that render the FULL homepage layout (HomeController@
| landing) but with a unique H1, subtitle, hero image + alt, and meta tags.
| URL = root-level slug (e.g. /luggage-storage-belgrade). Add an entry here,
| drop a hero image at the given path, and it's live (route is config-driven).
| `*_sr` fields are kept for future Serbian versions (not routed yet).
*/

return [

    'luggage-storage-belgrade' => [
        'h1'               => "Luggage Storage in Belgrade",
        'subtitle'         => "Secure, self-service luggage & bag storage 24/7 in central Belgrade — minutes from Slavija Square and the main bus station. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Luggage Storage Belgrade — 24/7 Secure Bag Storage",
        'meta_description' => "Store your luggage in Belgrade 24/7. Secure smart lockers near Slavija Square and the bus station. Book in 60 seconds, pay on arrival — drop your bags and explore the city.",
        'hero_image'       => '/images/landing/luggage-storage-belgrade.webp',
        'hero_alt'         => "24/7 secure luggage storage lockers in central Belgrade",

        'slug_sr'             => 'cuvanje-prtljaga-beograd',
        'h1_sr'               => "Čuvanje prtljaga u Beogradu",
        'subtitle_sr'         => "Sigurno samouslužno čuvanje prtljaga i torbi 0–24 u centru Beograda — par minuta od Slavije i glavne autobuske stanice. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga Beograd — sigurni ormarići 0–24",
        'meta_description_sr' => "Čuvanje prtljaga u Beogradu 0–24. Sigurni pametni ormarići blizu Slavije i autobuske stanice. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

];
