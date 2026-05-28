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

    'belgrade-luggage-storage' => [
        'h1'               => "Belgrade Luggage Storage",
        'subtitle'         => "Drop your bags in seconds with secure, self-service luggage storage in central Belgrade — open 24/7, minutes from Slavija Square and the main bus station. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Belgrade Luggage Storage — Secure Bag Lockers 24/7",
        'meta_description' => "Belgrade luggage storage made easy. Secure self-service lockers in the city centre, open 24/7. Book in 60 seconds, pay on arrival, and explore Belgrade bag-free.",
        'hero_image'       => '/images/landing/belgrade-luggage-storage.webp',
        'hero_alt'         => "Secure self-service luggage storage lockers in central Belgrade",

        'slug_sr'             => 'cuvanje-prtljaga-beograd-centar',
        'h1_sr'               => "Čuvanje prtljaga Beograd",
        'subtitle_sr'         => "Ostavi torbe za par sekundi uz sigurno samouslužno čuvanje prtljaga u centru Beograda — otvoreno 0–24, par minuta od Slavije i glavne autobuske stanice. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga Beograd — sigurni ormarići 0–24",
        'meta_description_sr' => "Jednostavno čuvanje prtljaga u Beogradu. Sigurni samouslužni ormarići u centru grada, otvoreni 0–24. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

    'airport-luggage-storage' => [
        'h1'               => "Airport Luggage Storage in Belgrade",
        'subtitle'         => "Arriving early or flying out late? Store your bags safely on the way to or from Belgrade Nikola Tesla Airport. Secure 24/7 lockers in the city centre — book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Airport Luggage Storage Belgrade — 24/7 Secure Lockers",
        'meta_description' => "Luggage storage for Belgrade airport travellers. Secure 24/7 lockers in the city centre near the airport shuttle. Book in 60 seconds, pay on arrival, travel light.",
        'hero_image'       => '/images/landing/airport-luggage-storage.webp',
        'hero_alt'         => "Secure airport luggage storage lockers in Belgrade city centre",

        'slug_sr'             => 'cuvanje-prtljaga-aerodrom-beograd',
        'h1_sr'               => "Čuvanje prtljaga — aerodrom Beograd",
        'subtitle_sr'         => "Stižeš ranije ili letiš kasno? Ostavi torbe na sigurnom na putu do ili od aerodroma Nikola Tesla. Sigurni ormarići 0–24 u centru grada — rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga aerodrom Beograd — ormarići 0–24",
        'meta_description_sr' => "Čuvanje prtljaga za putnike sa beogradskog aerodroma. Sigurni ormarići 0–24 u centru grada. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

    'bus-station-luggage-storage' => [
        'h1'               => "Bus Station Luggage Storage in Belgrade",
        'subtitle'         => "Just minutes from Belgrade's main bus and train station — leave your luggage in a secure self-service locker and explore the city between connections. Open 24/7, book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Bus Station Luggage Storage Belgrade — 24/7 Lockers",
        'meta_description' => "Luggage storage minutes from Belgrade's main bus station. Secure self-service lockers, open 24/7. Book in 60 seconds, pay on arrival, explore between buses.",
        'hero_image'       => '/images/landing/bus-station-luggage-storage.webp',
        'hero_alt'         => "Luggage storage lockers near Belgrade main bus station",

        'slug_sr'             => 'cuvanje-prtljaga-autobuska-stanica-beograd',
        'h1_sr'               => "Čuvanje prtljaga — autobuska stanica Beograd",
        'subtitle_sr'         => "Par minuta od glavne autobuske i železničke stanice u Beogradu — ostavi prtljag u sigurnom samouslužnom ormariću i razgledaj grad između polazaka. Otvoreno 0–24, rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga autobuska stanica Beograd — 0–24",
        'meta_description_sr' => "Čuvanje prtljaga par minuta od glavne autobuske stanice u Beogradu. Sigurni samouslužni ormarići 0–24. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

    'luggage-storage' => [
        'h1'               => "Luggage Storage",
        'subtitle'         => "Secure, self-service luggage storage in central Belgrade — drop your bags in a smart locker and travel hands-free. Open 24/7, book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Luggage Storage in Belgrade — Secure Smart Lockers 24/7",
        'meta_description' => "Self-service luggage storage in central Belgrade. Secure smart lockers, open 24/7. Book in 60 seconds, pay on arrival, and explore the city bag-free.",
        'hero_image'       => '/images/landing/luggage-storage.webp',
        'hero_alt'         => "Self-service luggage storage smart lockers in Belgrade",

        'slug_sr'             => 'cuvanje-prtljaga',
        'h1_sr'               => "Čuvanje prtljaga",
        'subtitle_sr'         => "Sigurno samouslužno čuvanje prtljaga u centru Beograda — ostavi torbe u pametnom ormariću i kreći se bez tereta. Otvoreno 0–24, rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga Beograd — pametni ormarići 0–24",
        'meta_description_sr' => "Samouslužno čuvanje prtljaga u centru Beograda. Sigurni pametni ormarići, otvoreni 0–24. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

    'storage-belgrade' => [
        'h1'               => "Storage in Belgrade",
        'subtitle'         => "Need somewhere to keep your bags in Belgrade? Use our secure, self-service smart lockers in the city centre — open 24/7, minutes from Slavija Square and the bus station. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Storage in Belgrade — Secure 24/7 Luggage Lockers",
        'meta_description' => "Secure storage in central Belgrade. Self-service smart lockers for luggage and bags, open 24/7. Book in 60 seconds, pay on arrival, explore the city.",
        'hero_image'       => '/images/landing/storage-belgrade.webp',
        'hero_alt'         => "Secure storage lockers in central Belgrade",

        'slug_sr'             => 'ostava-beograd',
        'h1_sr'               => "Ostava u Beogradu",
        'subtitle_sr'         => "Treba ti mesto za torbe u Beogradu? Koristi naše sigurne samouslužne pametne ormariće u centru grada — otvoreno 0–24, par minuta od Slavije i autobuske stanice. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Ostava Beograd — sigurni ormarići za prtljag 0–24",
        'meta_description_sr' => "Sigurna ostava u centru Beograda. Samouslužni pametni ormarići za prtljag i torbe, otvoreni 0–24. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

    'belgrade-luggage-locker' => [
        'h1'               => "Belgrade Luggage Lockers",
        'subtitle'         => "Smart self-service luggage lockers in central Belgrade — choose your size, get a one-time PIN, and your bags are safe 24/7. Minutes from Slavija Square and the bus station. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Belgrade Luggage Lockers — Secure Smart Lockers 24/7",
        'meta_description' => "Belgrade luggage lockers, open 24/7. Smart self-service lockers with one-time PIN access in the city centre. Book in 60 seconds, pay on arrival.",
        'hero_image'       => '/images/landing/belgrade-luggage-locker.webp',
        'hero_alt'         => "Smart self-service luggage lockers in central Belgrade",

        'slug_sr'             => 'ormarici-za-prtljag-beograd',
        'h1_sr'               => "Ormarići za prtljag u Beogradu",
        'subtitle_sr'         => "Pametni samouslužni ormarići za prtljag u centru Beograda — izaberi veličinu, dobij jednokratni PIN i tvoje torbe su bezbedne 0–24. Par minuta od Slavije i autobuske stanice. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Ormarići za prtljag Beograd — sigurni ormarići 0–24",
        'meta_description_sr' => "Ormarići za prtljag u Beogradu, otvoreni 0–24. Pametni samouslužni ormarići sa jednokratnim PIN-om u centru grada. Rezerviši za 60 sekundi, plati na licu mesta.",
    ],

];
