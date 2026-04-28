<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PrivacyTermsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $row) {
            Page::updateOrCreate(
                ['slug' => $row['slug'], 'locale' => $row['locale']],
                [
                    'title' => $row['title'],
                    'meta_title' => $row['meta_title'],
                    'meta_description' => $row['meta_description'],
                    'content' => $row['content'],
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }

    private function pages(): array
    {
        $companyEn = 'Belgrade Luggage Locker';
        $companySr = 'Belgrade Luggage Locker';
        $email = 'info@belgradeluggagelocker.com';
        $phone = '+381 65 294 1503';
        $address = 'Kralja Milana 20, Belgrade, Serbia';

        return [
            // ─── PRIVACY (EN) ─────────────────────────────────────────
            [
                'slug' => 'privacy', 'locale' => 'en',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy — Belgrade Luggage Locker',
                'meta_description' => 'How Belgrade Luggage Locker collects, uses, and protects your personal data — bookings, contact details, GDPR rights.',
                'content' => $this->privacyEn($companyEn, $email, $phone, $address),
            ],
            // ─── PRIVACY (SR) ─────────────────────────────────────────
            [
                'slug' => 'privacy', 'locale' => 'sr',
                'title' => 'Politika privatnosti',
                'meta_title' => 'Politika privatnosti — Belgrade Luggage Locker',
                'meta_description' => 'Kako Belgrade Luggage Locker prikuplja, koristi i štiti vaše lične podatke — rezervacije, kontakt, prava korisnika.',
                'content' => $this->privacySr($companySr, $email, $phone, $address),
            ],
            // ─── TERMS (EN) ───────────────────────────────────────────
            [
                'slug' => 'terms', 'locale' => 'en',
                'title' => 'Terms & Conditions',
                'meta_title' => 'Terms & Conditions — Belgrade Luggage Locker',
                'meta_description' => 'Terms governing use of Belgrade Luggage Locker self-service smart lockers — bookings, payment, prohibited items, liability.',
                'content' => $this->termsEn($companyEn, $email, $phone, $address),
            ],
            // ─── TERMS (SR) ───────────────────────────────────────────
            [
                'slug' => 'terms', 'locale' => 'sr',
                'title' => 'Uslovi korišćenja',
                'meta_title' => 'Uslovi korišćenja — Belgrade Luggage Locker',
                'meta_description' => 'Uslovi korišćenja samouslužnih pametnih ormarića Belgrade Luggage Locker — rezervacije, plaćanje, zabranjeni predmeti, odgovornost.',
                'content' => $this->termsSr($companySr, $email, $phone, $address),
            ],
        ];
    }

    private function privacyEn(string $company, string $email, string $phone, string $address): string
    {
        $effective = now()->format('F j, Y');
        return <<<HTML
<p class="text-sm text-white/60"><em>Effective date: {$effective}</em></p>

<h2>1. Who we are</h2>
<p>{$company} ("we", "us", "our") operates self-service smart luggage lockers in Belgrade, Serbia, and the website at belgradeluggagelocker.com. This policy explains what personal information we collect when you use our service, how we use it, and the choices you have.</p>
<p><strong>Contact:</strong> {$address} · <a href="mailto:{$email}">{$email}</a> · {$phone}</p>

<h2>2. What we collect</h2>
<p>We only collect what is needed to fulfil your booking and to support you if something goes wrong:</p>
<ul>
    <li><strong>Identity & contact:</strong> full name, email address, phone number, and (optionally) WhatsApp opt-in.</li>
    <li><strong>Booking data:</strong> selected location, locker size and quantity, check-in and check-out dates, total amount.</li>
    <li><strong>Access codes:</strong> a 4-digit PIN code per locker is generated and stored encrypted on our servers and on the smart lock for the duration of your booking.</li>
    <li><strong>Technical:</strong> IP address, browser user-agent, and approximate timezone — captured automatically when you submit a booking.</li>
    <li><strong>Communications:</strong> a log of confirmation, PIN-delivery, and reminder emails / WhatsApp messages we have sent you.</li>
</ul>
<p>We do <em>not</em> ask for ID documents, photos, or payment-card details. Payment is taken in cash at the locker location.</p>

<h2>3. Why we use your data (legal basis)</h2>
<ul>
    <li><strong>Performance of contract.</strong> To create your booking, deliver your PIN, send arrival reminders, allow cancellation, and provide customer support.</li>
    <li><strong>Legitimate interests.</strong> To prevent fraud and abuse, protect our infrastructure, and improve the service.</li>
    <li><strong>Legal obligation.</strong> Limited record-keeping for accounting, tax, and consumer-protection compliance.</li>
</ul>

<h2>4. Who we share with</h2>
<p>We share data only with carefully selected processors that act under written agreements:</p>
<ul>
    <li><strong>TTLock</strong> (Sciener Inc.) — receives your locker's PIN code and validity window so the smart lock can recognise it. Codes are deleted from TTLock when your booking ends or you cancel.</li>
    <li><strong>Email service provider</strong> — processes outbound emails (booking confirmation, PIN delivery, reminders, cancellation).</li>
    <li><strong>WhatsApp / Meta Platforms Ireland Ltd.</strong> — only if you opt in to WhatsApp messages. Messages are sent through the official WhatsApp Business API.</li>
    <li><strong>Hosting provider</strong> — stores the database; data resides in the EU.</li>
</ul>
<p>We do not sell your data and we do not use it for advertising or profiling.</p>

<h2>5. How long we keep it</h2>
<ul>
    <li><strong>Active booking data:</strong> from booking creation until 30 days after check-out, then archived in a reduced form.</li>
    <li><strong>Encrypted PIN codes:</strong> deleted from our servers and from TTLock within 24 hours of booking completion or cancellation.</li>
    <li><strong>Notification logs:</strong> 12 months for support / dispute resolution.</li>
    <li><strong>Accounting records:</strong> retained as required by Serbian tax law (currently 5 years for invoices and receipts).</li>
</ul>

<h2>6. Cookies</h2>
<p>We use only strictly necessary cookies — for example, the CSRF token that protects your booking form. We do not use third-party advertising or analytics cookies. The website does load Google Fonts; if you prefer to avoid that, you can install a font-blocker browser extension and the site will fall back to system fonts.</p>

<h2>7. Your rights</h2>
<p>Under the Serbian Law on Personal Data Protection ("ZZPL") and, where applicable, the EU GDPR, you have the right to:</p>
<ul>
    <li>access the personal data we hold about you;</li>
    <li>correct inaccurate data;</li>
    <li>request deletion of your data ("right to be forgotten") subject to legal retention;</li>
    <li>restrict or object to certain processing;</li>
    <li>receive a copy of your data in a portable format;</li>
    <li>withdraw any consent (e.g. WhatsApp opt-in) at any time;</li>
    <li>lodge a complaint with the Serbian Commissioner for Information of Public Importance and Personal Data Protection ("Poverenik").</li>
</ul>
<p>To exercise any of these rights, email <a href="mailto:{$email}">{$email}</a>. We respond within 30 days.</p>

<h2>8. Security</h2>
<p>Locker PIN codes are stored encrypted at rest. Access to the admin panel requires a password and a server-side role check. Communication with our servers is HTTPS only. We rotate access tokens regularly and log all administrative actions for accountability.</p>

<h2>9. Children</h2>
<p>Our service is not directed at children under 16 and we do not knowingly collect their data. If you believe a minor has booked through our service, please contact us so we can remove the record.</p>

<h2>10. Changes to this policy</h2>
<p>If we materially change how we use your data, we will update the "Effective date" above and notify active customers by email. Continued use of the service after a change constitutes acceptance of the updated policy.</p>

<h2>11. Contact</h2>
<p>For any privacy question or request: <a href="mailto:{$email}">{$email}</a> · {$phone}<br>
Postal address: {$address}.</p>
HTML;
    }

    private function privacySr(string $company, string $email, string $phone, string $address): string
    {
        $effective = now()->format('d.m.Y');
        return <<<HTML
<p class="text-sm text-white/60"><em>Datum stupanja na snagu: {$effective}</em></p>

<h2>1. Ko smo</h2>
<p>{$company} ("mi", "naš", "naša") upravlja samouslužnim pametnim ormarićima za prtljag u Beogradu, kao i veb-sajtom belgradeluggagelocker.com. Ova politika objašnjava koje lične podatke prikupljamo kada koristite našu uslugu, kako ih koristimo i koja prava imate.</p>
<p><strong>Kontakt:</strong> {$address} · <a href="mailto:{$email}">{$email}</a> · {$phone}</p>

<h2>2. Šta prikupljamo</h2>
<p>Prikupljamo samo ono što je neophodno da bismo izvršili vašu rezervaciju i pružili podršku ako bude potrebno:</p>
<ul>
    <li><strong>Identitet i kontakt:</strong> ime i prezime, e-mail adresa, broj telefona i (po izboru) saglasnost za WhatsApp.</li>
    <li><strong>Podaci o rezervaciji:</strong> izabrana lokacija, veličina i broj ormarića, vreme dolaska i odlaska, ukupan iznos.</li>
    <li><strong>Pristupne šifre:</strong> 4-cifreni PIN po ormariću, generisan i čuvan u kriptovanoj formi na našim serverima i na pametnoj bravi tokom trajanja rezervacije.</li>
    <li><strong>Tehnički podaci:</strong> IP adresa, podaci o pregledaču i približna vremenska zona — automatski se beleže prilikom slanja rezervacije.</li>
    <li><strong>Komunikacija:</strong> evidencija o poslatim e-mail / WhatsApp porukama (potvrda, dostava PIN-a, podsetnik, otkazivanje).</li>
</ul>
<p>Ne tražimo dokumenta, fotografije niti podatke o platnim karticama. Plaćanje se vrši gotovinom na lokaciji ormarića.</p>

<h2>3. Zašto koristimo vaše podatke (pravni osnov)</h2>
<ul>
    <li><strong>Izvršenje ugovora.</strong> Kreiranje rezervacije, slanje PIN-a, podsetnici, omogućavanje otkazivanja i korisnička podrška.</li>
    <li><strong>Legitimni interes.</strong> Sprečavanje zloupotreba, zaštita infrastrukture i poboljšanje usluge.</li>
    <li><strong>Zakonska obaveza.</strong> Ograničena evidencija u svrhe računovodstva, poreza i zaštite potrošača.</li>
</ul>

<h2>4. Sa kim delimo podatke</h2>
<p>Podatke delimo isključivo sa pažljivo odabranim obrađivačima koji rade po pisanim ugovorima:</p>
<ul>
    <li><strong>TTLock</strong> (Sciener Inc.) — prima PIN ormarića i vremenski opseg važenja kako bi pametna brava prepoznala kod. Šifre se brišu sa TTLock-a po isteku ili otkazivanju rezervacije.</li>
    <li><strong>Provajder e-pošte</strong> — obrađuje poslate poruke (potvrda, PIN, podsetnik, otkazivanje).</li>
    <li><strong>WhatsApp / Meta Platforms Ireland Ltd.</strong> — samo ako ste dali saglasnost. Poruke se šalju kroz zvanični WhatsApp Business API.</li>
    <li><strong>Hosting provajder</strong> — čuva bazu podataka; podaci se nalaze u EU.</li>
</ul>
<p>Ne prodajemo vaše podatke i ne koristimo ih za reklamiranje ili profilisanje.</p>

<h2>5. Koliko dugo čuvamo podatke</h2>
<ul>
    <li><strong>Aktivne rezervacije:</strong> od kreiranja do 30 dana posle odlaska, zatim arhivirano u smanjenoj formi.</li>
    <li><strong>Kriptovani PIN kodovi:</strong> brišu se sa naših servera i sa TTLock-a u roku od 24 sata po završetku ili otkazivanju.</li>
    <li><strong>Evidencija notifikacija:</strong> 12 meseci za potrebe podrške i rešavanja prigovora.</li>
    <li><strong>Računovodstvena dokumentacija:</strong> u skladu sa srpskim poreskim zakonima (trenutno 5 godina za fakture i potvrde).</li>
</ul>

<h2>6. Kolačići</h2>
<p>Koristimo isključivo neophodne kolačiće — npr. CSRF token koji štiti formu za rezervaciju. Ne koristimo kolačiće trećih strana za reklamiranje niti analitiku. Sajt učitava Google Fonts; ako to želite da izbegnete, koristite ekstenziju za blokiranje fontova i sajt će koristiti sistemske fontove.</p>

<h2>7. Vaša prava</h2>
<p>Po Zakonu o zaštiti podataka o ličnosti ("ZZPL") i, kada je primenljivo, evropskom GDPR-u, imate pravo da:</p>
<ul>
    <li>pristupite ličnim podacima koje čuvamo o vama;</li>
    <li>tražite ispravku netačnih podataka;</li>
    <li>tražite brisanje podataka ("pravo da budete zaboravljeni"), uz poštovanje obaveznog roka čuvanja;</li>
    <li>ograničite ili se usprotivite određenim obradama;</li>
    <li>dobijete kopiju podataka u prenosivom formatu;</li>
    <li>povučete saglasnost (npr. za WhatsApp) u bilo kom trenutku;</li>
    <li>podnesete pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti.</li>
</ul>
<p>Za bilo koje pravo, pišite na <a href="mailto:{$email}">{$email}</a>. Odgovaramo u roku od 30 dana.</p>

<h2>8. Bezbednost</h2>
<p>PIN kodovi se čuvaju u kriptovanoj formi. Pristup admin panelu zahteva lozinku i serversku proveru uloge. Komunikacija sa našim serverima ide isključivo preko HTTPS-a. Tokene rotiramo redovno i beležimo sve administrativne radnje.</p>

<h2>9. Maloletnici</h2>
<p>Naša usluga nije namenjena licima mlađim od 16 godina i ne prikupljamo svesno njihove podatke. Ako mislite da je maloletnik napravio rezervaciju kroz našu uslugu, javite nam se da uklonimo zapis.</p>

<h2>10. Izmene ove politike</h2>
<p>U slučaju značajnih izmena, ažuriraćemo "Datum stupanja na snagu" i obavestiti aktivne korisnike e-mailom. Nastavak korišćenja usluge nakon izmene smatra se prihvatanjem nove verzije.</p>

<h2>11. Kontakt</h2>
<p>Za sva pitanja u vezi privatnosti: <a href="mailto:{$email}">{$email}</a> · {$phone}<br>
Poštanska adresa: {$address}.</p>
HTML;
    }

    private function termsEn(string $company, string $email, string $phone, string $address): string
    {
        $effective = now()->format('F j, Y');
        return <<<HTML
<p class="text-sm text-white/60"><em>Effective date: {$effective}</em></p>

<h2>1. About these terms</h2>
<p>These Terms & Conditions ("Terms") govern the contractual relationship between you ("the Customer") and {$company} ("we", "us", "our") for the use of our self-service smart luggage lockers and related online booking service. By placing a booking, you confirm that you have read, understood and accepted these Terms.</p>

<h2>2. The service</h2>
<p>We provide short-term self-service luggage storage in Belgrade through smart lockers. Each locker is opened with a personal PIN code that we generate and send to you after booking. Lockers are accessible 24 hours a day, every day of the year, unless we publish a temporary closure on our website.</p>

<h2>3. Booking and payment</h2>
<ul>
    <li>You can book online by selecting a location, locker size, quantity and duration. The booking is confirmed only after you receive a confirmation email or WhatsApp message.</li>
    <li>The price displayed at the time of booking is the final price. Payment is made in <strong>cash</strong> on arrival, in EUR or RSD at the rate displayed in the confirmation. We do not currently accept card payments.</li>
    <li>You may arrive up to 20 minutes before your reserved start time without affecting your duration.</li>
</ul>

<h2>4. Your responsibilities</h2>
<ul>
    <li>You must be at least 16 years old to make a booking, or have explicit consent from a parent or legal guardian.</li>
    <li>You are responsible for keeping your PIN code confidential. Anyone in possession of the PIN can open the locker.</li>
    <li>You must collect your luggage before the check-out time. Late collection is treated as automatic extension and charged according to our pricing schedule. Lockers not emptied within 7 days of check-out may be opened by us in the presence of a witness and the contents stored at your cost.</li>
    <li>You must close the locker properly after each use; the lock engages automatically.</li>
</ul>

<h2>5. Prohibited items</h2>
<p>You agree not to store the following inside our lockers. Deposit of any of these items voids our liability and may be reported to authorities:</p>
<ul>
    <li>Live animals or perishable food.</li>
    <li>Cash or unmounted precious metals or stones in excess of EUR 1,000 in declared value.</li>
    <li>Firearms, ammunition, explosives, or fireworks.</li>
    <li>Illegal drugs, controlled substances, or counterfeit goods.</li>
    <li>Hazardous materials — flammable liquids, compressed gases, corrosives, radioactive items, biological waste.</li>
    <li>Stolen goods or items obtained through fraud.</li>
    <li>Items that emit strong odours or that may damage adjacent lockers.</li>
</ul>

<h2>6. Liability</h2>
<ul>
    <li>Our maximum liability for loss or damage to luggage stored in a locker is <strong>EUR 100 per locker per booking</strong>, unless declared higher value has been accepted by us in writing in advance.</li>
    <li>We are not liable for losses caused by failure to keep the PIN confidential, by force majeure, by acts of public authorities, or by the Customer's failure to comply with these Terms.</li>
    <li>Nothing in these Terms limits liability for death, personal injury caused by our negligence, or any liability that cannot be excluded under Serbian law.</li>
</ul>

<h2>7. Cancellation</h2>
<ul>
    <li>You may cancel your booking free of charge until check-in time using the cancel link in your confirmation email or by contacting us.</li>
    <li>Bookings cancelled after check-in time are not refundable, since the locker has been reserved and removed from availability.</li>
    <li>If we cancel a booking due to a system, hardware or location issue beyond our control, you receive a full refund or, if you prefer, a free rebooking at another available time.</li>
</ul>

<h2>8. Lost & found</h2>
<p>Items left after check-out are kept for 14 days. Contact <a href="mailto:{$email}">{$email}</a> with your booking UUID and a description of the item. Identity verification may be required before items are released.</p>

<h2>9. Force majeure</h2>
<p>We are not liable for delays or failures caused by events outside our reasonable control, including but not limited to power outages, network failures, supplier problems, civil unrest, government action, or natural disasters.</p>

<h2>10. Privacy</h2>
<p>Your personal data is processed in accordance with our <a href="/privacy">Privacy Policy</a>, which forms part of these Terms.</p>

<h2>11. Changes to the Terms</h2>
<p>We may update these Terms from time to time. The version that applies to your booking is the version published on the website at the time you make the booking.</p>

<h2>12. Governing law and disputes</h2>
<p>These Terms are governed by the laws of the Republic of Serbia. Any dispute arising from or in connection with these Terms shall be subject to the exclusive jurisdiction of the courts in Belgrade. Consumers retain any mandatory rights granted under the Consumer Protection Law of the Republic of Serbia.</p>

<h2>13. Contact</h2>
<p>{$company}<br>
{$address}<br>
<a href="mailto:{$email}">{$email}</a> · {$phone}</p>
HTML;
    }

    private function termsSr(string $company, string $email, string $phone, string $address): string
    {
        $effective = now()->format('d.m.Y');
        return <<<HTML
<p class="text-sm text-white/60"><em>Datum stupanja na snagu: {$effective}</em></p>

<h2>1. O ovim uslovima</h2>
<p>Ovi Uslovi korišćenja ("Uslovi") regulišu odnos između vas ("Korisnik") i {$company} ("mi", "naš") prilikom korišćenja samouslužnih pametnih ormarića i pripadajuće usluge online rezervacije. Slanjem rezervacije potvrđujete da ste pročitali, razumeli i prihvatili ove Uslove.</p>

<h2>2. Usluga</h2>
<p>Pružamo kratkoročno samouslužno čuvanje prtljaga u Beogradu putem pametnih ormarića. Svaki ormarić se otvara ličnim PIN-om koji generišemo i šaljemo nakon rezervacije. Ormarići su dostupni 24 sata dnevno, svakog dana u godini, osim u slučaju objavljenog privremenog zatvaranja.</p>

<h2>3. Rezervacija i plaćanje</h2>
<ul>
    <li>Rezervaciju vršite online izborom lokacije, veličine ormarića, broja komada i trajanja. Rezervacija je potvrđena tek po prijemu potvrde putem e-mail-a ili WhatsApp poruke.</li>
    <li>Cena prikazana u trenutku rezervacije je konačna. Plaćanje se vrši <strong>gotovinom</strong> po dolasku, u EUR ili RSD po kursu navedenom u potvrdi. Trenutno ne primamo kartično plaćanje.</li>
    <li>Možete doći i do 20 minuta ranije od rezervisanog vremena bez uticaja na trajanje.</li>
</ul>

<h2>4. Obaveze korisnika</h2>
<ul>
    <li>Morate imati najmanje 16 godina da napravite rezervaciju ili izričitu saglasnost roditelja / staratelja.</li>
    <li>Odgovorni ste za poverljivost svog PIN-a. Svako ko poseduje PIN može otvoriti ormarić.</li>
    <li>Prtljag morate preuzeti pre vremena odlaska. Kasno preuzimanje tretira se kao automatsko produženje i naplaćuje po važećem cenovniku. Ormarići koji nisu ispražnjeni u roku od 7 dana od vremena odlaska mogu biti otvoreni u prisustvu svedoka, a sadržaj uskladišten o trošku korisnika.</li>
    <li>Ormarić morate pravilno zatvoriti — brava se zaključava automatski.</li>
</ul>

<h2>5. Zabranjeni predmeti</h2>
<p>Saglasni ste da ne čuvate sledeće u našim ormarićima. Odlaganje ovakvih predmeta poništava našu odgovornost i može biti prijavljeno nadležnim organima:</p>
<ul>
    <li>Žive životinje ili kvarljiva hrana.</li>
    <li>Gotovina ili neugrađeni dragoceni metali / kamenje u prijavljenoj vrednosti većoj od 1.000 EUR.</li>
    <li>Vatreno oružje, municija, eksplozivi ili pirotehnika.</li>
    <li>Nelegalne droge, kontrolisane supstance ili krivotvorena roba.</li>
    <li>Opasni materijali — zapaljive tečnosti, gasovi pod pritiskom, korozivi, radioaktivni predmeti, biološki otpad.</li>
    <li>Ukradena dobra ili predmeti pribavljeni prevarom.</li>
    <li>Predmeti sa jakim mirisom ili oni koji mogu da oštete susedne ormariće.</li>
</ul>

<h2>6. Odgovornost</h2>
<ul>
    <li>Naša maksimalna odgovornost za gubitak ili oštećenje prtljaga u ormariću iznosi <strong>100 EUR po ormariću po rezervaciji</strong>, osim ako smo unapred pisanim putem prihvatili veću prijavljenu vrednost.</li>
    <li>Ne odgovaramo za štete prouzrokovane neuspehom u čuvanju PIN-a, višom silom, postupcima državnih organa ili nepoštovanjem ovih Uslova od strane korisnika.</li>
    <li>Ništa u ovim Uslovima ne ograničava odgovornost za smrt, telesnu povredu nastalu našom nepažnjom ili odgovornost koja se ne može isključiti po srpskom zakonu.</li>
</ul>

<h2>7. Otkazivanje</h2>
<ul>
    <li>Rezervaciju možete otkazati besplatno do vremena dolaska putem linka za otkazivanje u potvrdi ili kontaktiranjem naše podrške.</li>
    <li>Rezervacije otkazane posle vremena dolaska nisu povratljive, jer je ormarić već bio rezervisan i sklonjen iz dostupnosti.</li>
    <li>Ako mi otkažemo rezervaciju zbog tehničkog problema ili problema na lokaciji van naše kontrole, dobijate puni povraćaj ili, po izboru, besplatnu novu rezervaciju u dostupnom terminu.</li>
</ul>

<h2>8. Izgubljeno-nađeno</h2>
<p>Predmeti zaostali nakon odlaska čuvaju se 14 dana. Kontaktirajte <a href="mailto:{$email}">{$email}</a> sa UUID-om rezervacije i opisom predmeta. Pre vraćanja može biti potrebna provera identiteta.</p>

<h2>9. Viša sila</h2>
<p>Ne odgovaramo za kašnjenja ili neispunjenja prouzrokovana događajima van naše razumne kontrole, uključujući ali ne ograničavajući se na nestanak struje, padove mreže, probleme dobavljača, građanske nemire, postupke vlasti ili prirodne katastrofe.</p>

<h2>10. Privatnost</h2>
<p>Vaši lični podaci se obrađuju u skladu sa našom <a href="/sr/privatnost">Politikom privatnosti</a>, koja čini sastavni deo ovih Uslova.</p>

<h2>11. Izmene Uslova</h2>
<p>Možemo s vremena na vreme menjati ove Uslove. Verzija koja se primenjuje na vašu rezervaciju je verzija objavljena na sajtu u trenutku rezervacije.</p>

<h2>12. Merodavno pravo i sporovi</h2>
<p>Na ove Uslove primenjuje se pravo Republike Srbije. Sve sporove proistekle iz ili u vezi sa ovim Uslovima rešavaju isključivo nadležni sudovi u Beogradu. Potrošači zadržavaju sva obavezna prava priznata Zakonom o zaštiti potrošača Republike Srbije.</p>

<h2>13. Kontakt</h2>
<p>{$company}<br>
{$address}<br>
<a href="mailto:{$email}">{$email}</a> · {$phone}</p>
HTML;
    }
}
