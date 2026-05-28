<?php

/*
|--------------------------------------------------------------------------
| SEO landing pages
|--------------------------------------------------------------------------
| Distinct-intent, query-targeted pages that render the homepage layout
| (HomeController@landing) with a unique H1, subtitle, hero, meta tags AND
| unique body content (`intro` + `faqs`) so they are NOT thin homepage clones.
|
| Word-order synonyms of "luggage storage Belgrade" are intentionally NOT
| separate pages — they would cannibalise the homepage, which already ranks
| for the whole cluster. Those slugs 301-redirect to the homepage (see
| routes/web.php). Only genuinely different search intents live here.
|
| URL = root-level slug (EN) and /sr/{slug_sr} (SR). Both languages routed.
*/

return [

    'airport-luggage-storage' => [
        'h1'               => "Airport Luggage Storage in Belgrade",
        'subtitle'         => "Flying in early or out late from Nikola Tesla Airport? Store your bags in secure 24/7 lockers in central Belgrade, minutes from the A1 airport shuttle. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Airport Luggage Storage Belgrade — 24/7 Secure Lockers",
        'meta_description' => "Luggage storage for Belgrade Nikola Tesla Airport travellers. Secure 24/7 lockers in the city centre by the A1 shuttle. Book in 60 seconds, pay on arrival, travel light.",
        'hero_image'       => '/images/landing/airport-luggage-storage.webp',
        'hero_alt'         => "Secure airport luggage storage lockers in Belgrade city centre",

        'intro' => "<h2>Luggage storage for Belgrade airport travellers</h2>"
            . "<p>Belgrade Nikola Tesla Airport (BEG) sits about 18 km west of the city centre, roughly a 30–40 minute ride on the <strong>A1 airport shuttle</strong> or a 25-minute taxi. If you land early before hotel check-in, or check out hours before a late flight, you don't have to drag your suitcase around the city — leave it in one of our secure self-service lockers and explore hands-free.</p>"
            . "<p>Both of our locations are in <strong>central Belgrade</strong>, minutes from Slavija Square and the A1 shuttle stop, so dropping off and picking up your bags fits naturally into your route to or from the airport. Lockers are open <strong>24/7</strong> with one-time PIN access, so an early-morning departure or a midnight arrival is never a problem.</p>"
            . "<h2>How it works</h2>"
            . "<p>Pick your locker size, book online in about 60 seconds, and get a PIN by email. Drop your bags, head to the airport or into town, and collect them whenever you're ready. You pay cash on arrival — no card needed, no hidden fees.</p>",
        'faqs' => [
            ['q' => "Is there luggage storage at Belgrade Airport?",
             'a' => "Belgrade Nikola Tesla Airport has limited on-site options. Most travellers use secure luggage storage in the city centre instead — our 24/7 lockers are minutes from the A1 airport shuttle stop and far cheaper for a full day."],
            ['q' => "How do I get from Nikola Tesla Airport to the city centre?",
             'a' => "The A1 airport shuttle bus runs regularly to Slavija Square in about 30–40 minutes; a taxi takes around 25 minutes. Our lockers are a short walk from the central stops."],
            ['q' => "Can I store my bags before an early flight or after checkout?",
             'a' => "Yes. Our lockers are open 24/7 with one-time PIN access, so you can drop off or collect your luggage at any hour, including very early mornings and late nights."],
            ['q' => "How much does airport luggage storage in Belgrade cost?",
             'a' => "Pricing is clear and capped — you see the exact amount before you book, and pay cash on arrival. A full day costs far less than per-hour airport lockers."],
        ],

        'slug_sr'             => 'cuvanje-prtljaga-aerodrom-beograd',
        'h1_sr'               => "Čuvanje prtljaga — aerodrom Beograd",
        'subtitle_sr'         => "Stižeš rano ili letiš kasno sa aerodroma Nikola Tesla? Ostavi torbe u sigurnim ormarićima 0–24 u centru Beograda, par minuta od A1 aerodromskog busa. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga aerodrom Beograd — ormarići 0–24",
        'meta_description_sr' => "Čuvanje prtljaga za putnike sa beogradskog aerodroma Nikola Tesla. Sigurni ormarići 0–24 u centru grada kod A1 busa. Rezerviši za 60 sekundi, plati na licu mesta.",
        'intro_sr' => "<h2>Čuvanje prtljaga za putnike sa beogradskog aerodroma</h2>"
            . "<p>Aerodrom Nikola Tesla (BEG) je oko 18 km zapadno od centra, otprilike 30–40 minuta vožnje <strong>A1 aerodromskim busom</strong> ili 25 minuta taksijem. Ako sletiš rano pre prijave u hotel ili se odjaviš satima pre kasnog leta, ne moraš da vučeš kofer po gradu — ostavi ga u našem sigurnom samouslužnom ormariću i razgledaj bez tereta.</p>"
            . "<p>Obe lokacije su u <strong>centru Beograda</strong>, par minuta od Slavije i stajališta A1 busa, pa se ostavljanje i preuzimanje torbi prirodno uklapa u put do ili od aerodroma. Ormarići rade <strong>0–24</strong> uz pristup jednokratnim PIN-om, pa ni rani polazak ni dolazak u ponoć nisu problem.</p>"
            . "<h2>Kako funkcioniše</h2>"
            . "<p>Izaberi veličinu ormarića, rezerviši online za oko 60 sekundi i dobij PIN na mejl. Ostavi torbe, idi na aerodrom ili u grad, i preuzmi ih kad poželiš. Plaćaš gotovinom na licu mesta — bez kartice, bez skrivenih troškova.</p>",
        'faqs_sr' => [
            ['q' => "Postoji li čuvanje prtljaga na aerodromu u Beogradu?",
             'a' => "Aerodrom Nikola Tesla ima ograničene opcije na licu mesta. Većina putnika koristi sigurno čuvanje prtljaga u centru grada — naši ormarići rade 0–24, par minuta su od stajališta A1 busa i znatno su jeftiniji za ceo dan."],
            ['q' => "Kako da stignem od aerodroma do centra Beograda?",
             'a' => "A1 aerodromski bus redovno vozi do Slavije za oko 30–40 minuta; taksi stiže za oko 25 minuta. Naši ormarići su na par koraka od centralnih stajališta."],
            ['q' => "Mogu li da ostavim torbe pre ranog leta ili posle odjave?",
             'a' => "Da. Ormarići rade 0–24 uz jednokratni PIN, pa torbe možeš da ostaviš ili preuzmeš u bilo koje doba, uključujući rano jutro i kasnu noć."],
            ['q' => "Koliko košta čuvanje prtljaga blizu aerodroma?",
             'a' => "Cena je jasna i ograničena — vidiš tačan iznos pre rezervacije i plaćaš gotovinom na licu mesta. Ceo dan košta znatno manje od aerodromskih ormarića po satu."],
        ],
    ],

    'bus-station-luggage-storage' => [
        'h1'               => "Bus Station Luggage Storage in Belgrade",
        'subtitle'         => "Minutes from Belgrade's main bus station (BAS) and Prokop railway station — leave your luggage in a secure 24/7 locker and explore the city between connections. Book online in 60 seconds, pay cash on arrival.",
        'meta_title'       => "Bus Station Luggage Storage Belgrade — 24/7 Lockers",
        'meta_description' => "Luggage storage minutes from Belgrade's main bus station (BAS) and Prokop railway station. Secure 24/7 self-service lockers. Book in 60 seconds, pay on arrival.",
        'hero_image'       => '/images/landing/bus-station-luggage-storage.webp',
        'hero_alt'         => "Luggage storage lockers near Belgrade main bus station",

        'intro' => "<h2>Luggage storage near Belgrade bus & train station</h2>"
            . "<p>Belgrade's main bus station (BAS) and the <strong>Prokop railway station</strong> are the arrival point for travellers coming from across Serbia and the region. If you have hours between connections, you don't need to sit on a bench guarding your bags — store them in a secure self-service locker in the city centre and use the time to actually see Belgrade.</p>"
            . "<p>Our central locations are a short ride or walk from the stations, close to Slavija Square, Knez Mihailova street and Kalemegdan Fortress. Lockers are open <strong>24/7</strong> with one-time PIN access, so late buses and early trains are never a problem.</p>"
            . "<h2>How it works</h2>"
            . "<p>Choose your locker size, book online in about 60 seconds, and receive a PIN by email. Drop your luggage, explore the city, and pick it up before your next departure. Pay cash on arrival — no card, no hidden fees.</p>",
        'faqs' => [
            ['q' => "Is there luggage storage at Belgrade bus station?",
             'a' => "On-site storage at the bus station is limited. Our secure 24/7 lockers in the city centre are a short hop away and let you store bags for the whole day at a clear, capped price."],
            ['q' => "Where is the main bus station in Belgrade?",
             'a' => "Belgrade's main bus station (BAS) is near the Prokop railway station, a short ride from the city centre where our lockers are located, close to Slavija Square."],
            ['q' => "Can I store luggage near Prokop railway station?",
             'a' => "Yes. Our central locations are easy to reach from Prokop, so you can drop your bags after arriving by train and collect them before your next connection."],
            ['q' => "How long can I leave my bags?",
             'a' => "From a couple of hours to a full day or more. Pricing is capped, so a long layover stays affordable, and you collect your bags whenever you're ready."],
        ],

        'slug_sr'             => 'cuvanje-prtljaga-autobuska-stanica-beograd',
        'h1_sr'               => "Čuvanje prtljaga — autobuska stanica Beograd",
        'subtitle_sr'         => "Par minuta od glavne autobuske stanice (BAS) i železničke stanice Prokop — ostavi prtljag u sigurnom ormariću 0–24 i razgledaj grad između polazaka. Rezerviši online za 60 sekundi, plati gotovinom na licu mesta.",
        'meta_title_sr'       => "Čuvanje prtljaga autobuska stanica Beograd — 0–24",
        'meta_description_sr' => "Čuvanje prtljaga par minuta od glavne autobuske stanice (BAS) i stanice Prokop u Beogradu. Sigurni samouslužni ormarići 0–24. Rezerviši za 60 sekundi, plati na licu mesta.",
        'intro_sr' => "<h2>Čuvanje prtljaga blizu autobuske i železničke stanice</h2>"
            . "<p>Glavna autobuska stanica (BAS) i <strong>železnička stanica Prokop</strong> su mesto dolaska putnika iz cele Srbije i regiona. Ako imaš sati između polazaka, ne moraš da sediš na klupi i čuvaš torbe — ostavi ih u sigurnom samouslužnom ormariću u centru grada i iskoristi vreme da stvarno vidiš Beograd.</p>"
            . "<p>Naše centralne lokacije su na kratkoj vožnji ili šetnji od stanica, blizu Slavije, Knez Mihailove i Kalemegdana. Ormarići rade <strong>0–24</strong> uz jednokratni PIN, pa kasni autobusi i rani vozovi nisu problem.</p>"
            . "<h2>Kako funkcioniše</h2>"
            . "<p>Izaberi veličinu ormarića, rezerviši online za oko 60 sekundi i dobij PIN na mejl. Ostavi prtljag, razgledaj grad i preuzmi ga pre sledećeg polaska. Plaćaš gotovinom na licu mesta — bez kartice, bez skrivenih troškova.</p>",
        'faqs_sr' => [
            ['q' => "Postoji li čuvanje prtljaga na autobuskoj stanici u Beogradu?",
             'a' => "Čuvanje na samoj stanici je ograničeno. Naši sigurni ormarići 0–24 u centru grada su na kratkom putu i omogućavaju da ostaviš torbe za ceo dan po jasnoj, ograničenoj ceni."],
            ['q' => "Gde se nalazi glavna autobuska stanica u Beogradu?",
             'a' => "Glavna autobuska stanica (BAS) je blizu železničke stanice Prokop, na kratkoj vožnji od centra grada gde su naši ormarići, u blizini Slavije."],
            ['q' => "Mogu li da ostavim prtljag blizu stanice Prokop?",
             'a' => "Da. Naše centralne lokacije se lako dostižu sa Prokopa, pa torbe možeš da ostaviš po dolasku vozom i preuzmeš pre sledećeg polaska."],
            ['q' => "Koliko dugo mogu da ostavim torbe?",
             'a' => "Od par sati do celog dana i duže. Cena je ograničena, pa dugo čekanje između polazaka ostaje povoljno, a torbe preuzimaš kad poželiš."],
        ],
    ],

];
