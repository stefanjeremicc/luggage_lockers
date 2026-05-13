<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Active template set (Apr 2026 onwards). Anything not in this list
        // is purged on every seed so retired templates can't accidentally re-emit.
        $activeKeys = [
            'booking_confirmed',
            'booking_confirmed_admin',
            'booking_cancelled_by_customer',
            'booking_cancelled_by_admin',
            'pin_reissued',
            'review_request',
        ];
        NotificationTemplate::whereNotIn('key', $activeKeys)->delete();

        foreach ($this->templates() as $row) {
            NotificationTemplate::updateOrCreate(
                ['key' => $row['key'], 'channel' => $row['channel'], 'locale' => $row['locale']],
                [
                    'subject' => $row['subject'] ?? null,
                    'body' => $row['body'],
                    'variables' => $row['variables'] ?? [],
                    'is_active' => true,
                ]
            );
        }
    }

    private function templates(): array
    {
        $commonVars = ['customer_name', 'location_name', 'location_address', 'check_in', 'check_in_time', 'check_in_full', 'check_out', 'check_out_time', 'check_out_full', 'locker_qty', 'locker_size', 'items_summary', 'duration_label', 'total_eur', 'eur_rsd_rate', 'cancel_url', 'directions_url', 'support_phone', 'support_email', 'site_name', 'pin_code', 'locker_number', 'codes_block', 'entry_door_code', 'tolerance_minutes'];
        $reviewUrl = 'https://search.google.com/local/writereview?placeid=ChIJq3Y86Jl7WkcRJRP0r-8Tg5M';

        return [
            // ─── booking_confirmed (customer, combined) ───────────────
            [
                'key' => 'booking_confirmed', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your Booking is Confirmed — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Confirmed!', '#10B981',
                    '<p class="info">Hello <strong style="color:#fff">{{ customer_name }}</strong>, your booking is confirmed.</p>'.

                    '{{ booking_details_block }}'.
                    '<p style="color:#A0A0A0;font-size:13px;font-style:italic;margin:8px 0 0">You\'re welcome to arrive up to {{ tolerance_minutes }} minutes early. Luggage is fully secured and you can access it 24/7.</p>'.

                    '<h2>Pay Station Information</h2>'.
                    '<div class="info">'.
                    '<p>Our pay station is located on the right-hand wall.</p>'.
                    '<p>Upon your arrival, please place your payment inside the box — our team will securely process it and take care of the rest.</p>'.
                    '<p><strong style="color:#fff">Total: €{{ total_eur }}</strong></p>'.
                    '<p style="margin-top:12px"><em>Please note:</em> We accept payments in Euros (€) and Serbian Dinars (RSD) only.<br>The exchange rate is <strong style="color:#fff">1 Euro = {{ eur_rsd_rate }} RSD</strong>.</p>'.
                    '</div>'.

                    '<h2>Entry Instructions</h2>'.
                    '{{ codes_block }}'.

                    '<h2>How to use lockers</h2>'.
                    '<div class="info"><ol style="padding-left:20px;margin:8px 0">'.
                    '<li style="margin-bottom:8px">Locate your locker number (shown above).</li>'.
                    '<li style="margin-bottom:8px">Press any button to activate the lock — a light will appear to confirm it\'s engaged.</li>'.
                    '<li style="margin-bottom:8px">Enter your access code — locker will unlock.</li>'.
                    '<li style="margin-bottom:8px">Place your belongings inside and close the door — the lock will automatically secure without needing to enter a code.</li>'.
                    '<li>Check if the locker is locked.</li>'.
                    '</ol></div>'.

                    '<div style="text-align:center;margin:24px 0"><a href="{{ directions_url }}" class="btn">Get Directions</a></div>'.

                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p>You are all set!</p>'.
                    '<p>If you have any questions or need help checking in, please let us know.</p>'.
                    '<p style="margin-top:16px"><a href="tel:{{ support_phone }}" style="color:#F59E0B;text-decoration:none;font-weight:bold">{{ support_phone }}</a> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">Enjoy Belgrade!<br>— {{ site_name }}</p>'.
                    '<p style="margin-top:12px;font-size:12px"><a href="{{ cancel_url }}" style="color:#6B7280">Need to cancel?</a></p>'.
                    '</div>'),
            ],
            [
                'key' => 'booking_confirmed', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija je potvrđena — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija potvrđena!', '#10B981',
                    '<p class="info">Zdravo <strong style="color:#fff">{{ customer_name }}</strong>, vaša rezervacija je potvrđena.</p>'.

                    '{{ booking_details_block }}'.
                    '<p style="color:#A0A0A0;font-size:13px;font-style:italic;margin:8px 0 0">Možete doći i do {{ tolerance_minutes }} minuta ranije. Prtljag je potpuno bezbedan i pristup imate 24/7.</p>'.

                    '<h2>Informacije o plaćanju</h2>'.
                    '<div class="info">'.
                    '<p>Naš pay station se nalazi na desnom zidu.</p>'.
                    '<p>Prilikom dolaska, molimo vas da plaćanje stavite u kutiju — naš tim će bezbedno obraditi i pobrinuti se za sve ostalo.</p>'.
                    '<p><strong style="color:#fff">Ukupno: €{{ total_eur }}</strong></p>'.
                    '<p style="margin-top:12px"><em>Napomena:</em> Prihvatamo plaćanje u evrima (€) i srpskim dinarima (RSD).<br>Kurs: <strong style="color:#fff">1 Euro = {{ eur_rsd_rate }} RSD</strong>.</p>'.
                    '</div>'.

                    '<h2>Uputstva za ulaz</h2>'.
                    '{{ codes_block }}'.

                    '<h2>Kako se koriste ormarići</h2>'.
                    '<div class="info"><ol style="padding-left:20px;margin:8px 0">'.
                    '<li style="margin-bottom:8px">Pronađite vaš ormarić (prikazan iznad).</li>'.
                    '<li style="margin-bottom:8px">Pritisnite bilo koje dugme da aktivirate bravu — zasvetleće lampica.</li>'.
                    '<li style="margin-bottom:8px">Unesite šifru — ormarić će se otključati.</li>'.
                    '<li style="margin-bottom:8px">Stavite stvari unutra i zatvorite vrata — brava se automatski zaključava.</li>'.
                    '<li>Proverite da li je ormarić zaključan.</li>'.
                    '</ol></div>'.

                    '<div style="text-align:center;margin:24px 0"><a href="{{ directions_url }}" class="btn">Uputstva do nas</a></div>'.

                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p>Spremno!</p>'.
                    '<p>Ako imate bilo kakvo pitanje, javite nam se.</p>'.
                    '<p style="margin-top:16px"><a href="tel:{{ support_phone }}" style="color:#F59E0B;text-decoration:none;font-weight:bold">{{ support_phone }}</a> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">Uživajte u Beogradu!<br>— {{ site_name }}</p>'.
                    '<p style="margin-top:12px;font-size:12px"><a href="{{ cancel_url }}" style="color:#6B7280">Treba da otkažete?</a></p>'.
                    '</div>'),
            ],
            // WhatsApp variant kept for when the WhatsApp UI is re-enabled.
            [
                'key' => 'booking_confirmed', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => $commonVars,
                'body' => "Hello {{ customer_name }}, your booking is confirmed!\n\n📍 {{ location_name }} — {{ location_address }}\n🕒 {{ check_in_full }} → {{ check_out_full }}\n\n🔑 Entry door: *{{ entry_door_code }}*\n🔐 Your locker {{ locker_number }}: *{{ pin_code }}*\n\n💶 Total €{{ total_eur }} — pay cash on arrival (1€ = {{ eur_rsd_rate }} RSD).\n\nNeed help? {{ support_phone }}",
            ],
            [
                'key' => 'booking_confirmed', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => $commonVars,
                'body' => "Zdravo {{ customer_name }}, rezervacija potvrđena!\n\n📍 {{ location_name }} — {{ location_address }}\n🕒 {{ check_in_full }} → {{ check_out_full }}\n\n🔑 Ulazna vrata: *{{ entry_door_code }}*\n🔐 Vaš ormarić {{ locker_number }}: *{{ pin_code }}*\n\n💶 Ukupno €{{ total_eur }} — gotovinom na licu mesta (1€ = {{ eur_rsd_rate }} RSD).\n\nPomoć: {{ support_phone }}",
            ],

            // ─── booking_confirmed_admin (concise alert for admin inbox) ───────────────
            [
                'key' => 'booking_confirmed_admin', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'New booking — {{ items_summary }} @ {{ location_name }} — {{ check_in_full }}',
                'variables' => array_merge($commonVars, ['customer_email', 'customer_phone']),
                'body' => $this->emailShell('New Booking', '#F59E0B',
                    '<div class="info">'.
                    '<table style="width:100%;border-collapse:collapse;font-size:14px">'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0;width:40%">Customer</td><td style="padding:6px 0;color:#fff"><strong>{{ customer_name }}</strong></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Email</td><td style="padding:6px 0"><a href="mailto:{{ customer_email }}" style="color:#F59E0B">{{ customer_email }}</a></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Phone</td><td style="padding:6px 0"><a href="tel:{{ customer_phone }}" style="color:#F59E0B">{{ customer_phone }}</a></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Location</td><td style="padding:6px 0;color:#fff">{{ location_name }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Locker</td><td style="padding:6px 0;color:#fff"><strong>{{ locker_number }}</strong></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">PIN</td><td style="padding:6px 0;color:#F59E0B;font-family:monospace;font-weight:bold">{{ pin_code }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Type</td><td style="padding:6px 0;color:#fff">{{ items_summary }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Duration</td><td style="padding:6px 0;color:#fff">{{ duration_label }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Check-in</td><td style="padding:6px 0;color:#fff">{{ check_in_full }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Check-out</td><td style="padding:6px 0;color:#fff">{{ check_out_full }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Total</td><td style="padding:6px 0;color:#fff"><strong>€{{ total_eur }}</strong></td></tr>'.
                    '</table></div>'),
            ],
            [
                'key' => 'booking_confirmed_admin', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Nova rezervacija — {{ items_summary }} @ {{ location_name }} — {{ check_in_full }}',
                'variables' => array_merge($commonVars, ['customer_email', 'customer_phone']),
                'body' => $this->emailShell('Nova rezervacija', '#F59E0B',
                    '<div class="info">'.
                    '<table style="width:100%;border-collapse:collapse;font-size:14px">'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0;width:40%">Kupac</td><td style="padding:6px 0;color:#fff"><strong>{{ customer_name }}</strong></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Email</td><td style="padding:6px 0"><a href="mailto:{{ customer_email }}" style="color:#F59E0B">{{ customer_email }}</a></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Telefon</td><td style="padding:6px 0"><a href="tel:{{ customer_phone }}" style="color:#F59E0B">{{ customer_phone }}</a></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Lokacija</td><td style="padding:6px 0;color:#fff">{{ location_name }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Ormarić</td><td style="padding:6px 0;color:#fff"><strong>{{ locker_number }}</strong></td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">PIN</td><td style="padding:6px 0;color:#F59E0B;font-family:monospace;font-weight:bold">{{ pin_code }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Tip</td><td style="padding:6px 0;color:#fff">{{ items_summary }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Trajanje</td><td style="padding:6px 0;color:#fff">{{ duration_label }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Dolazak</td><td style="padding:6px 0;color:#fff">{{ check_in_full }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Odlazak</td><td style="padding:6px 0;color:#fff">{{ check_out_full }}</td></tr>'.
                    '<tr><td style="padding:6px 0;color:#A0A0A0">Ukupno</td><td style="padding:6px 0;color:#fff"><strong>€{{ total_eur }}</strong></td></tr>'.
                    '</table></div>'),
            ],

            // ─── booking_cancelled_by_customer (customer initiated) ───────────────
            [
                'key' => 'booking_cancelled_by_customer', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your booking has been cancelled — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Cancelled', '#6B7280',
                    '<p class="info">Hi {{ customer_name }},</p>'.
                    '<p class="info">Your booking at <strong style="color:#fff">{{ location_name }}</strong> for {{ check_in_full }} has been cancelled at your request. No charges have been made.</p>'.
                    '<p class="info">We hope to see you next time you\'re in Belgrade. If you need to store luggage again, you can book in 60 seconds:</p>'.
                    '<div style="text-align:center;margin:20px 0"><a href="{{ directions_url }}" class="btn">Book again</a></div>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">— {{ site_name }}</p>'),
            ],
            [
                'key' => 'booking_cancelled_by_customer', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija je otkazana — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija otkazana', '#6B7280',
                    '<p class="info">Zdravo {{ customer_name }},</p>'.
                    '<p class="info">Vaša rezervacija na <strong style="color:#fff">{{ location_name }}</strong> za {{ check_in_full }} je otkazana na vaš zahtev. Nema naplate.</p>'.
                    '<p class="info">Nadamo se da se vidimo sledeći put kad budete u Beogradu. Ako vam ponovo zatreba odlaganje prtljaga, rezervišite za 60 sekundi:</p>'.
                    '<div style="text-align:center;margin:20px 0"><a href="{{ directions_url }}" class="btn">Rezerviši ponovo</a></div>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">— {{ site_name }}</p>'),
            ],

            // ─── booking_cancelled_by_admin (admin initiated) ───────────────
            [
                'key' => 'booking_cancelled_by_admin', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your booking was cancelled — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Cancelled', '#EF4444',
                    '<p class="info">Hi {{ customer_name }},</p>'.
                    '<p class="info">We have cancelled your booking at <strong style="color:#fff">{{ location_name }}</strong> scheduled for {{ check_in_full }}. No charges have been made.</p>'.
                    '<p class="info">If this was unexpected or you would like more details, please reach out to us — we\'re happy to help find another slot or location.</p>'.
                    '<div style="text-align:center;margin:20px 0">'.
                    '<p style="margin:0;color:#fff;font-size:14px"><strong>{{ support_phone }}</strong></p>'.
                    '<p style="margin:6px 0 0"><a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '</div>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">— {{ site_name }}</p>'),
            ],
            [
                'key' => 'booking_cancelled_by_admin', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija je otkazana — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija otkazana', '#EF4444',
                    '<p class="info">Zdravo {{ customer_name }},</p>'.
                    '<p class="info">Otkazali smo vašu rezervaciju na <strong style="color:#fff">{{ location_name }}</strong> zakazanu za {{ check_in_full }}. Nema naplate.</p>'.
                    '<p class="info">Ako je ovo neočekivano ili želite više detalja, javite nam se — drago nam je da pomognemo da nađemo drugi termin ili lokaciju.</p>'.
                    '<div style="text-align:center;margin:20px 0">'.
                    '<p style="margin:0;color:#fff;font-size:14px"><strong>{{ support_phone }}</strong></p>'.
                    '<p style="margin:6px 0 0"><a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '</div>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">— {{ site_name }}</p>'),
            ],

            // ─── pin_reissued (admin reissued the locker PIN) ───────────────
            [
                'key' => 'pin_reissued', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your locker PIN has been updated — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('PIN Updated', '#F59E0B',
                    '<p class="info">Hi <strong style="color:#fff">{{ customer_name }}</strong>,</p>'.
                    '<p class="info">Your previous locker PIN is no longer valid. We have issued a new one — please use the access codes below from now on.</p>'.
                    '{{ booking_details_block }}'.
                    '<h2>Updated Access Codes</h2>'.
                    '{{ codes_block }}'.
                    '<p class="info" style="font-size:13px;font-style:italic;margin-top:12px">If you did not expect this change, please get in touch right away.</p>'.
                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p style="margin-top:8px"><a href="tel:{{ support_phone }}" style="color:#F59E0B;text-decoration:none;font-weight:bold">{{ support_phone }}</a> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">— {{ site_name }}</p>'.
                    '</div>'),
            ],
            [
                'key' => 'pin_reissued', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaš PIN je promenjen — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('PIN je promenjen', '#F59E0B',
                    '<p class="info">Zdravo <strong style="color:#fff">{{ customer_name }}</strong>,</p>'.
                    '<p class="info">Vaš prethodni PIN za ormarić više nije važeći. Izdali smo novi — molimo koristite šifre ispod od sada.</p>'.
                    '{{ booking_details_block }}'.
                    '<h2>Nove pristupne šifre</h2>'.
                    '{{ codes_block }}'.
                    '<p class="info" style="font-size:13px;font-style:italic;margin-top:12px">Ako niste očekivali ovu izmenu, molimo javite nam se odmah.</p>'.
                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p style="margin-top:8px"><a href="tel:{{ support_phone }}" style="color:#F59E0B;text-decoration:none;font-weight:bold">{{ support_phone }}</a> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">— {{ site_name }}</p>'.
                    '</div>'),
            ],

            // ─── review_request (30 min after check_out) ───────────────
            [
                'key' => 'review_request', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'How was your stay? — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Thanks for stopping by! 🙌', '#F59E0B',
                    '<p class="info">Hi {{ customer_name }},</p>'.
                    '<p class="info">Thank you for storing your stuff with Belgrade Luggage Locker — we hope you had an awesome (and luggage-free) time in the city!</p>'.
                    '<p class="info">If you were happy with our service, we\'d love a quick review:</p>'.
                    '<div style="text-align:center;margin:24px 0">'.
                    '<a href="'.$reviewUrl.'" class="btn">⭐ Leave A Review</a>'.
                    '<p style="margin:12px 0 0;color:#6B7280;font-size:12px">Just one click — your review in less than 30 seconds :)</p>'.
                    '</div>'.
                    '<p class="info">Your feedback helps other travelers find us — and totally makes our day!</p>'.
                    '<p class="info">We appreciate your support! ❤️</p>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">The {{ site_name }} Team ✌️</p>'),
            ],
            [
                'key' => 'review_request', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Kako je prošlo? — {{ site_name }}',
                'variables' => $commonVars,
                'body' => $this->emailShell('Hvala što ste svratili! 🙌', '#F59E0B',
                    '<p class="info">Zdravo {{ customer_name }},</p>'.
                    '<p class="info">Hvala što ste odložili stvari kod Belgrade Luggage Locker-a — nadamo se da ste imali sjajan (i bezbrižan) dan u gradu!</p>'.
                    '<p class="info">Ako ste zadovoljni našom uslugom, ostavite nam kratku recenziju:</p>'.
                    '<div style="text-align:center;margin:24px 0">'.
                    '<a href="'.$reviewUrl.'" class="btn">⭐ Ostavi recenziju</a>'.
                    '<p style="margin:12px 0 0;color:#6B7280;font-size:12px">Jedan klik — recenzija za manje od 30 sekundi :)</p>'.
                    '</div>'.
                    '<p class="info">Vaš utisak pomaže drugim putnicima da nas pronađu — i mnogo nam znači!</p>'.
                    '<p class="info">Hvala na podršci! ❤️</p>'.
                    '<p class="info" style="text-align:center;margin-top:20px;color:#6B7280">Tim {{ site_name }} ✌️</p>'),
            ],
        ];
    }

    private function emailShell(string $title, string $titleColor, string $inner): string
    {
        // Anti-auto-link rules below: Gmail / iOS Mail / Outlook auto-detect
        // phone numbers, addresses and emails and wrap them in <a> with their
        // own blue colour. The `x-apple-data-detectors` selector + the
        // format-detection meta tag stop that, so prose never goes blue.
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            .'<meta name="format-detection" content="telephone=no, address=no, email=no, date=no">'
            .'<meta name="x-apple-disable-message-reformatting">'
            .'<style>'
            .'body{font-family:Arial,sans-serif;background:#0A0A0A;color:#fff;padding:20px}'
            .'.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}'
            .'.info{color:#A0A0A0;font-size:14px;line-height:1.6}'
            .'.btn{display:inline-block;background:#F59E0B;color:#000 !important;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin-top:16px}'
            .'.highlight{background:#111;border:1px solid #2A2A2A;border-radius:8px;padding:16px;text-align:center;margin:16px 0}'
            .'h1{color:'.$titleColor.';text-align:center}h2{color:#F59E0B;font-size:16px;margin-top:20px}'
            // Override the blue auto-detected links on Apple Mail / iOS:
            .'a[x-apple-data-detectors]{color:inherit !important;text-decoration:none !important;font-size:inherit !important;font-family:inherit !important;font-weight:inherit !important;line-height:inherit !important}'
            // Generic anchor — every link in our emails is intentionally amber
            // or gray, never the browser/client default blue.
            .'a{color:#F59E0B;text-decoration:none}'
            .'</style></head><body><div class="card">'
            .'<h1>'.$title.'</h1>'
            .$inner
            .'</div></body></html>';
    }
}
