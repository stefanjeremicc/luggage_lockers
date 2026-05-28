<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SEO blog posts targeting informational Search Console queries
 * (e.g. "where to find cheap storage solutions"). Idempotent: each post is
 * keyed by slug via updateOrCreate, so re-running only updates these rows and
 * never touches existing content. Safe to run on production.
 */
class SeoBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = BlogCategory::firstOrCreate(
            ['slug' => 'storage-guide'],
            ['name' => 'Storage Guide', 'name_sr' => 'Vodič za čuvanje prtljaga', 'sort_order' => 10]
        )->id;

        $posts = [
            [
                'slug'        => 'cheap-luggage-storage-belgrade',
                'slug_sr'     => 'jeftino-cuvanje-prtljaga-beograd',
                'title'       => 'Where to Find Cheap Luggage Storage in Belgrade (2026 Guide)',
                'title_sr'    => 'Gde naći jeftino čuvanje prtljaga u Beogradu (vodič 2026)',
                'excerpt'     => 'Looking for affordable luggage storage in Belgrade? Compare your options — lockers, hotels, cafes — and learn how to store bags safely from just a few euros.',
                'excerpt_sr'  => 'Tražiš povoljno čuvanje prtljaga u Beogradu? Uporedi opcije — ormarići, hoteli, kafići — i saznaj kako da bezbedno ostaviš torbe već od par evra.',
                'content'     => <<<'HTML'
<p>Belgrade is a fantastic city to explore on foot — but heavy suitcases get in the way fast. Whether you've just checked out of your hotel, have a long layover, or arrived before check-in, you'll want somewhere cheap and safe to leave your bags. Here's how to find the best-value luggage storage in Belgrade.</p>

<h2>Your Luggage Storage Options in Belgrade</h2>
<p>There are a few common ways to store bags in the city, each with trade-offs in price, convenience, and security:</p>
<ul>
<li><strong>Smart self-service lockers</strong> — Modern lockers with a one-time PIN, available 24/7. Usually the best mix of price, security, and flexibility.</li>
<li><strong>Hotel bag storage</strong> — Many hotels hold luggage for guests, sometimes for non-guests too. Convenient if you're staying nearby, but availability and security vary.</li>
<li><strong>Cafe or shop "left luggage"</strong> — Some businesses watch bags informally for a small fee. Cheapest on paper, but there's no real security guarantee.</li>
</ul>

<h2>How Much Should Luggage Storage Cost?</h2>
<p>Fair pricing in Belgrade typically runs from just a few euros for a few hours up to a flat daily rate. Watch out for services that charge per-bag per-hour with no cap — those add up fast for a full day. The best value comes from clear, capped pricing where you pay one predictable amount no matter how full your day gets.</p>

<h2>What to Look For Beyond the Price</h2>
<p>"Cheap" only matters if your belongings stay safe. Before you book, check that the service offers:</p>
<ul>
<li><strong>Individual PIN-locked lockers</strong> — only you can open your compartment.</li>
<li><strong>24/7 access</strong> — so an early flight or late train isn't a problem.</li>
<li><strong>A central location</strong> — near where you actually want to be (Slavija Square, the main bus and train station, Knez Mihailova).</li>
<li><strong>Real reviews</strong> — verified Google ratings from actual travelers.</li>
<li><strong>Online booking</strong> — reserve in advance so you're not stuck if it's full.</li>
</ul>

<h2>Tips to Store Your Bags for Less</h2>
<ul>
<li>Book online ahead of time — walk-up rates and availability are less predictable.</li>
<li>Pick a service with a daily cap if you'll be out exploring all day.</li>
<li>Choose a central spot so you don't spend your savings on extra taxis.</li>
<li>Keep valuables (passport, cash, electronics) with you regardless of where you store the rest.</li>
</ul>

<h2>The Easy, Affordable Choice in Central Belgrade</h2>
<p>Our smart self-service lockers sit in the heart of the city, minutes from Slavija Square and the main bus station. You get a one-time PIN, 24/7 access, and simple capped pricing with no surprises. Book online in 60 seconds and pay cash on arrival — then go enjoy Belgrade with empty hands.</p>
HTML,
                'content_sr'  => <<<'HTML'
<p>Beograd je sjajan grad za pešačenje — ali teški koferi brzo postanu teret. Bilo da si se upravo odjavio iz hotela, imaš dugo presedanje ili si stigao pre prijave, trebaće ti jeftino i bezbedno mesto za torbe. Evo kako da nađeš čuvanje prtljaga u Beogradu sa najboljim odnosom cene i kvaliteta.</p>

<h2>Opcije za čuvanje prtljaga u Beogradu</h2>
<p>Postoji nekoliko uobičajenih načina da ostaviš torbe u gradu, svaki sa svojim prednostima u ceni, praktičnosti i bezbednosti:</p>
<ul>
<li><strong>Pametni samouslužni ormarići</strong> — moderni ormarići sa jednokratnim PIN-om, dostupni 0–24. Obično najbolji spoj cene, bezbednosti i fleksibilnosti.</li>
<li><strong>Čuvanje u hotelu</strong> — mnogi hoteli čuvaju prtljag gostima, ponekad i onima koji ne odsedaju. Praktično ako si u blizini, ali dostupnost i bezbednost variraju.</li>
<li><strong>Kafić ili radnja</strong> — neki lokali neformalno pričuvaju torbe za malu nadoknadu. Najjeftinije na papiru, ali bez prave garancije bezbednosti.</li>
</ul>

<h2>Koliko bi čuvanje prtljaga trebalo da košta?</h2>
<p>Poštene cene u Beogradu obično idu od par evra za nekoliko sati do fiksne dnevne tarife. Pazi na servise koji naplaćuju po torbi po satu bez gornje granice — to brzo naraste za ceo dan. Najbolju vrednost daje jasna, ograničena cena gde plaćaš jedan predvidiv iznos bez obzira na to koliko ti dan bude pun.</p>

<h2>Na šta da obratiš pažnju osim cene</h2>
<p>„Jeftino" ima smisla samo ako su ti stvari bezbedne. Pre rezervacije proveri da li servis nudi:</p>
<ul>
<li><strong>Pojedinačne ormariće sa PIN-om</strong> — samo ti otvaraš svoj odeljak.</li>
<li><strong>Pristup 0–24</strong> — da rani let ili kasni voz ne budu problem.</li>
<li><strong>Centralnu lokaciju</strong> — blizu mesta gde stvarno želiš da budeš (Slavija, glavna autobuska i železnička stanica, Knez Mihailova).</li>
<li><strong>Prave recenzije</strong> — proverene Google ocene od stvarnih putnika.</li>
<li><strong>Online rezervaciju</strong> — rezerviši unapred da ne ostaneš bez mesta.</li>
</ul>

<h2>Saveti kako da ostaviš torbe jeftinije</h2>
<ul>
<li>Rezerviši online unapred — cene i dostupnost „na licu mesta" su manje predvidive.</li>
<li>Izaberi servis sa dnevnim limitom ako ćeš biti napolju ceo dan.</li>
<li>Biraj centralno mesto da ne potrošiš uštedu na dodatne taksije.</li>
<li>Vredne stvari (pasoš, novac, elektronika) drži uvek uz sebe.</li>
</ul>

<h2>Jednostavan i povoljan izbor u centru Beograda</h2>
<p>Naši pametni samouslužni ormarići su u srcu grada, par minuta od Slavije i glavne autobuske stanice. Dobijaš jednokratni PIN, pristup 0–24 i jednostavnu ograničenu cenu bez iznenađenja. Rezerviši online za 60 sekundi i plati gotovinom na licu mesta — pa uživaj u Beogradu praznih ruku.</p>
HTML,
                'featured_image'   => '/images/blog/cheap-luggage-storage-belgrade.jpg',
                'meta_title'       => 'Cheap Luggage Storage in Belgrade — 2026 Money-Saving Guide',
                'meta_title_sr'    => 'Jeftino čuvanje prtljaga u Beogradu — vodič za uštedu 2026',
                'meta_description' => 'Find cheap, safe luggage storage in Belgrade. Compare lockers vs hotels, see fair pricing, and learn how to store your bags 24/7 from just a few euros.',
                'meta_description_sr' => 'Nađi jeftino i bezbedno čuvanje prtljaga u Beogradu. Uporedi ormariće i hotele, pogledaj poštene cene i saznaj kako da ostaviš torbe 0–24 već od par evra.',
                'tags'             => ['cheap luggage storage', 'belgrade', 'budget travel', 'luggage storage'],
            ],
            [
                'slug'        => 'luggage-storage-belgrade-prices-guide',
                'slug_sr'     => 'cene-cuvanja-prtljaga-beograd-vodic',
                'title'       => 'Luggage Storage Prices in Belgrade: What You Should Actually Pay',
                'title_sr'    => 'Cene čuvanja prtljaga u Beogradu: koliko zaista treba da platiš',
                'excerpt'     => 'How much does it cost to store luggage in Belgrade? A clear breakdown of hourly vs daily pricing, hidden fees to avoid, and how to get the best value.',
                'excerpt_sr'  => 'Koliko košta čuvanje prtljaga u Beogradu? Jasan pregled cena po satu i po danu, skrivenih troškova i kako da dobiješ najbolju vrednost.',
                'content'     => <<<'HTML'
<p>If you're planning a trip to Belgrade, knowing what luggage storage really costs helps you budget — and avoid overpaying. Here's a straightforward guide to luggage storage prices in the city and how to get the most for your money.</p>

<h2>Hourly vs Daily Pricing</h2>
<p>Most services charge either by the hour or with a flat daily rate. Hourly pricing is great for short stops — a couple of hours between a checkout and a flight. For a full day of sightseeing, a capped daily rate almost always works out cheaper than open-ended hourly charges that keep climbing.</p>

<h2>What Affects the Price</h2>
<ul>
<li><strong>Duration</strong> — the longer you store, the more you pay, though daily caps protect you.</li>
<li><strong>Locker size</strong> — a small bag costs less than a large suitcase or several pieces.</li>
<li><strong>Location</strong> — central, well-secured spots may cost slightly more but save you time and transport money.</li>
<li><strong>Security level</strong> — PIN-locked smart lockers and CCTV are worth a small premium over an informal "watch your bag" arrangement.</li>
</ul>

<h2>Hidden Fees to Watch For</h2>
<ul>
<li>Per-bag surcharges on top of a base rate.</li>
<li>No daily cap, so an all-day visit costs far more than expected.</li>
<li>Card-only payment with processing fees — paying cash on arrival avoids this.</li>
<li>Penalties for picking up a little late.</li>
</ul>

<h2>How to Get the Best Value</h2>
<p>Choose a service with transparent, capped pricing, central placement, and the option to book online and pay on arrival. That combination keeps the cost predictable and the experience stress-free.</p>

<h2>Simple, Honest Pricing in Belgrade</h2>
<p>Our lockers use clear, capped pricing — you know what you'll pay before you book. Pick your size, reserve online in 60 seconds, and pay cash on arrival at a central location minutes from Slavija Square and the main bus station. No hidden fees, no surprises.</p>
HTML,
                'content_sr'  => <<<'HTML'
<p>Ako planiraš put u Beograd, dobro je da znaš koliko čuvanje prtljaga zaista košta — da isplaniraš budžet i izbegneš preplaćivanje. Evo jasnog vodiča kroz cene čuvanja prtljaga u gradu i kako da dobiješ najviše za svoj novac.</p>

<h2>Cena po satu naspram cene po danu</h2>
<p>Većina servisa naplaćuje ili po satu ili po fiksnoj dnevnoj tarifi. Naplata po satu je odlična za kratke pauze — par sati između odjave i leta. Za ceo dan razgledanja, ograničena dnevna tarifa skoro uvek ispadne jeftinija od otvorene satnice koja stalno raste.</p>

<h2>Šta utiče na cenu</h2>
<ul>
<li><strong>Trajanje</strong> — što duže čuvaš, više plaćaš, mada te dnevni limit štiti.</li>
<li><strong>Veličina ormarića</strong> — mala torba košta manje od velikog kofera ili više komada.</li>
<li><strong>Lokacija</strong> — centralna, dobro obezbeđena mesta mogu koštati malo više, ali štede vreme i novac na prevoz.</li>
<li><strong>Nivo bezbednosti</strong> — pametni ormarići sa PIN-om i video-nadzor vrede male doplate u odnosu na neformalno „pričuvaću ti torbu".</li>
</ul>

<h2>Skriveni troškovi na koje treba paziti</h2>
<ul>
<li>Doplata po torbi povrh osnovne cene.</li>
<li>Bez dnevnog limita, pa celodnevna poseta košta mnogo više nego što očekuješ.</li>
<li>Plaćanje samo karticom uz proviziju — plaćanje gotovinom na licu mesta to izbegava.</li>
<li>Kazne za malo kasnije preuzimanje.</li>
</ul>

<h2>Kako da dobiješ najbolju vrednost</h2>
<p>Izaberi servis sa transparentnom, ograničenom cenom, centralnom lokacijom i mogućnošću online rezervacije uz plaćanje na licu mesta. Ta kombinacija drži trošak predvidivim, a iskustvo bez stresa.</p>

<h2>Jednostavne i poštene cene u Beogradu</h2>
<p>Naši ormarići koriste jasnu, ograničenu cenu — znaš koliko plaćaš pre rezervacije. Izaberi veličinu, rezerviši online za 60 sekundi i plati gotovinom na licu mesta, na centralnoj lokaciji par minuta od Slavije i glavne autobuske stanice. Bez skrivenih troškova, bez iznenađenja.</p>
HTML,
                'featured_image'   => '/images/blog/luggage-storage-prices-belgrade.jpg',
                'meta_title'       => 'Luggage Storage Prices Belgrade — Hourly & Daily Cost Guide',
                'meta_title_sr'    => 'Cene čuvanja prtljaga Beograd — vodič po satu i po danu',
                'meta_description' => 'How much does luggage storage cost in Belgrade? Hourly vs daily rates, hidden fees to avoid, and how to get the best value with capped, transparent pricing.',
                'meta_description_sr' => 'Koliko košta čuvanje prtljaga u Beogradu? Cene po satu i po danu, skriveni troškovi i kako do najbolje vrednosti uz jasnu, ograničenu cenu.',
                'tags'             => ['luggage storage prices', 'belgrade', 'cost', 'travel tips'],
            ],
        ];

        foreach ($posts as $i => $p) {
            BlogPost::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, [
                    'blog_category_id' => $categoryId,
                    'author_name'      => 'Belgrade Luggage Locker',
                    'is_published'     => true,
                    'is_featured'      => false,
                    'published_at'     => now()->subDays(2 + $i),
                ])
            );
        }
    }
}
