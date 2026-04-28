<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
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
        $commonVars = ['customer_name', 'location_name', 'location_address', 'check_in', 'check_out', 'locker_qty', 'locker_size', 'total_eur', 'cancel_url', 'directions_url', 'support_phone', 'support_email'];

        return [
            // ─── booking_confirmed ─────────────────────────────────────
            [
                'key' => 'booking_confirmed', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your Belgrade Luggage Locker Booking is Confirmed',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Confirmed!', '#10B981',
                    '<div class="highlight"><p style="color:#F59E0B;font-size:14px;margin:0">You will receive your PIN code shortly via email or WhatsApp.</p></div>'.
                    '<h2>Booking Details</h2><div class="info">'.
                    '<p><strong>Location:</strong> {{ location_name }}</p>'.
                    '<p><strong>Address:</strong> {{ location_address }}</p>'.
                    '<p><strong>Check-in:</strong> {{ check_in }}</p>'.
                    '<p><strong>Check-out:</strong> {{ check_out }}</p>'.
                    '<p><strong>Lockers:</strong> {{ locker_qty }} x {{ locker_size }}</p>'.
                    '<p><strong>Total:</strong> €{{ total_eur }} — Pay cash on arrival</p></div>'.
                    '<h2>How to Use</h2><div class="info"><ol>'.
                    '<li>Go to {{ location_name }} ({{ location_address }})</li>'.
                    '<li>You will receive your PIN code before your check-in time</li>'.
                    '<li>Enter your PIN on the locker keypad</li>'.
                    '<li>Store your luggage and close the door</li>'.
                    '<li>Use the same PIN to open when you return</li></ol></div>'.
                    '<div style="text-align:center"><a href="{{ directions_url }}" class="btn">Get Directions</a></div>'.
                    '<div style="margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A;text-align:center">'.
                    '<p class="info">Need to cancel? <a href="{{ cancel_url }}" style="color:#F59E0B">Click here</a></p>'.
                    '<p class="info">Questions? Contact us: {{ support_phone }}</p></div>'),
            ],
            [
                'key' => 'booking_confirmed', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija potvrđena!', '#10B981',
                    '<div class="highlight"><p style="color:#F59E0B;font-size:14px;margin:0">PIN kod ćete dobiti uskoro putem e-maila ili WhatsApp-a.</p></div>'.
                    '<h2>Detalji rezervacije</h2><div class="info">'.
                    '<p><strong>Lokacija:</strong> {{ location_name }}</p>'.
                    '<p><strong>Adresa:</strong> {{ location_address }}</p>'.
                    '<p><strong>Dolazak:</strong> {{ check_in }}</p>'.
                    '<p><strong>Odlazak:</strong> {{ check_out }}</p>'.
                    '<p><strong>Ormarići:</strong> {{ locker_qty }} x {{ locker_size }}</p>'.
                    '<p><strong>Ukupno:</strong> €{{ total_eur }} — Plaćanje gotovinom</p></div>'.
                    '<h2>Kako da koristite</h2><div class="info"><ol>'.
                    '<li>Dođite na {{ location_name }} ({{ location_address }})</li>'.
                    '<li>PIN kod ćete dobiti pre vremena dolaska</li>'.
                    '<li>Unesite PIN na tastaturu ormarića</li>'.
                    '<li>Odložite stvari i zatvorite vrata</li>'.
                    '<li>Koristite isti PIN za otvaranje pri povratku</li></ol></div>'.
                    '<div style="text-align:center"><a href="{{ directions_url }}" class="btn">Uputstva</a></div>'.
                    '<div style="margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A;text-align:center">'.
                    '<p class="info">Treba da otkažete? <a href="{{ cancel_url }}" style="color:#F59E0B">Kliknite ovde</a></p>'.
                    '<p class="info">Pitanja? Kontakt: {{ support_phone }}</p></div>'),
            ],
            [
                'key' => 'booking_confirmed', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => $commonVars,
                'body' => "Your locker is booked!\n\nLocation: {{ location_name }}\nAddress: {{ location_address }}\nCheck-in: {{ check_in }}\nCheck-out: {{ check_out }}\nTotal: €{{ total_eur }} — Pay cash on arrival\n\nYou will receive your PIN code shortly.\n\nDirections: {{ directions_url }}",
            ],
            [
                'key' => 'booking_confirmed', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => $commonVars,
                'body' => "Vaš ormarić je rezervisan!\n\nLokacija: {{ location_name }}\nAdresa: {{ location_address }}\nDolazak: {{ check_in }}\nOdlazak: {{ check_out }}\nUkupno: €{{ total_eur }} — Plaćanje gotovinom\n\nPIN kod sledi uskoro.\n\nUputstva: {{ directions_url }}",
            ],

            // ─── expiry_reminder ─────────────────────────────────────
            [
                'key' => 'expiry_reminder', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your locker time ends soon — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Your Locker Time Ends Soon', '#F59E0B',
                    '<div class="info" style="text-align:center">'.
                    '<p>Your booking at <strong style="color:#fff">{{ location_name }}</strong> ends at:</p>'.
                    '<p style="font-size:24px;color:#F59E0B;font-weight:bold">{{ check_out }}</p>'.
                    '<p>Please collect your belongings before this time.</p>'.
                    '<p style="margin-top:16px">Need more time? Contact us:<br><strong style="color:#fff">{{ support_phone }}</strong></p></div>'),
            ],
            [
                'key' => 'expiry_reminder', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vreme zakupa ormarića se bliži kraju — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Vreme zakupa se bliži kraju', '#F59E0B',
                    '<div class="info" style="text-align:center">'.
                    '<p>Vaša rezervacija na <strong style="color:#fff">{{ location_name }}</strong> se završava u:</p>'.
                    '<p style="font-size:24px;color:#F59E0B;font-weight:bold">{{ check_out }}</p>'.
                    '<p>Molimo pokupite svoje stvari pre tog vremena.</p>'.
                    '<p style="margin-top:16px">Treba više vremena? Kontakt:<br><strong style="color:#fff">{{ support_phone }}</strong></p></div>'),
            ],
            [
                'key' => 'expiry_reminder', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => $commonVars,
                'body' => "Reminder: Your locker at {{ location_name }} ends at {{ check_out }}. Please collect your belongings. Need more time? {{ support_phone }}",
            ],
            [
                'key' => 'expiry_reminder', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => $commonVars,
                'body' => "Podsetnik: Vaš ormarić na {{ location_name }} se završava u {{ check_out }}. Pokupite stvari. Treba više vremena? {{ support_phone }}",
            ],

            // ─── booking_expired ─────────────────────────────────────
            [
                'key' => 'booking_expired', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your booking has expired — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Expired', '#EF4444',
                    '<div class="info" style="text-align:center">'.
                    '<p>Your booking at <strong style="color:#fff">{{ location_name }}</strong> has expired.</p>'.
                    '<p>Please pick up your belongings as soon as possible.</p>'.
                    '<p style="margin-top:16px">Contact us if you need assistance:<br><strong style="color:#fff">{{ support_phone }}</strong><br>{{ support_email }}</p></div>'),
            ],
            [
                'key' => 'booking_expired', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija je istekla — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija istekla', '#EF4444',
                    '<div class="info" style="text-align:center">'.
                    '<p>Vaša rezervacija na <strong style="color:#fff">{{ location_name }}</strong> je istekla.</p>'.
                    '<p>Molimo pokupite svoje stvari što pre.</p>'.
                    '<p style="margin-top:16px">Kontakt za pomoć:<br><strong style="color:#fff">{{ support_phone }}</strong><br>{{ support_email }}</p></div>'),
            ],
            [
                'key' => 'booking_expired', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => $commonVars,
                'body' => "Your booking at {{ location_name }} has EXPIRED. Please collect your belongings ASAP. Contact: {{ support_phone }}",
            ],
            [
                'key' => 'booking_expired', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => $commonVars,
                'body' => "Vaša rezervacija na {{ location_name }} je ISTEKLA. Pokupite stvari što pre. Kontakt: {{ support_phone }}",
            ],

            // ─── booking_cancelled ─────────────────────────────────────
            [
                'key' => 'booking_cancelled', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Booking cancelled — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Booking Cancelled', '#A0A0A0',
                    '<div class="info" style="text-align:center">'.
                    '<p>Your booking at <strong style="color:#fff">{{ location_name }}</strong> has been cancelled.</p>'.
                    '<p>No charges have been made.</p>'.
                    '<p style="margin-top:16px">Want to book again?<br><a href="{{ directions_url }}" style="color:#F59E0B">Visit our website</a></p></div>'),
            ],
            [
                'key' => 'booking_cancelled', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Rezervacija otkazana — Belgrade Luggage Locker',
                'variables' => $commonVars,
                'body' => $this->emailShell('Rezervacija otkazana', '#A0A0A0',
                    '<div class="info" style="text-align:center">'.
                    '<p>Vaša rezervacija na <strong style="color:#fff">{{ location_name }}</strong> je otkazana.</p>'.
                    '<p>Nema naplaćenih troškova.</p>'.
                    '<p style="margin-top:16px">Želite ponovo da rezervišete?<br><a href="{{ directions_url }}" style="color:#F59E0B">Posetite naš sajt</a></p></div>'),
            ],
            [
                'key' => 'booking_cancelled', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => $commonVars,
                'body' => "Your booking at {{ location_name }} has been cancelled. No charges. Book again any time.",
            ],
            [
                'key' => 'booking_cancelled', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => $commonVars,
                'body' => "Vaša rezervacija na {{ location_name }} je otkazana. Nema naplate. Slobodno rezervišite ponovo.",
            ],

            // ─── payment_received ─────────────────────────────────────
            [
                'key' => 'payment_received', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Payment received — Belgrade Luggage Locker',
                'variables' => array_merge($commonVars, ['amount_eur', 'payment_method']),
                'body' => $this->emailShell('Payment Received', '#10B981',
                    '<div class="info" style="text-align:center">'.
                    '<p>Thank you, {{ customer_name }}!</p>'.
                    '<p>We received your payment of <strong style="color:#10B981">€{{ amount_eur }}</strong>.</p>'.
                    '<p>Method: {{ payment_method }}</p>'.
                    '<p>Booking: {{ location_name }} — {{ check_in }}</p></div>'),
            ],
            [
                'key' => 'payment_received', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Plaćanje primljeno — Belgrade Luggage Locker',
                'variables' => array_merge($commonVars, ['amount_eur', 'payment_method']),
                'body' => $this->emailShell('Plaćanje primljeno', '#10B981',
                    '<div class="info" style="text-align:center">'.
                    '<p>Hvala, {{ customer_name }}!</p>'.
                    '<p>Primili smo vašu uplatu od <strong style="color:#10B981">€{{ amount_eur }}</strong>.</p>'.
                    '<p>Način: {{ payment_method }}</p>'.
                    '<p>Rezervacija: {{ location_name }} — {{ check_in }}</p></div>'),
            ],

            // ─── refund_processed ─────────────────────────────────────
            [
                'key' => 'refund_processed', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Refund processed — Belgrade Luggage Locker',
                'variables' => array_merge($commonVars, ['amount_eur']),
                'body' => $this->emailShell('Refund Processed', '#10B981',
                    '<div class="info" style="text-align:center">'.
                    '<p>A refund of <strong style="color:#10B981">€{{ amount_eur }}</strong> has been processed.</p>'.
                    '<p>It should appear in your account within 5–10 business days depending on your bank.</p>'.
                    '<p style="margin-top:16px">Questions? {{ support_phone }}</p></div>'),
            ],
            [
                'key' => 'refund_processed', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Povraćaj sredstava obrađen — Belgrade Luggage Locker',
                'variables' => array_merge($commonVars, ['amount_eur']),
                'body' => $this->emailShell('Povraćaj obrađen', '#10B981',
                    '<div class="info" style="text-align:center">'.
                    '<p>Povraćaj od <strong style="color:#10B981">€{{ amount_eur }}</strong> je obrađen.</p>'.
                    '<p>Sredstva će biti vidljiva na računu u roku od 5–10 radnih dana, u zavisnosti od banke.</p>'.
                    '<p style="margin-top:16px">Pitanja? {{ support_phone }}</p></div>'),
            ],

            // ─── locker_pin_delivered (full booking confirmation with PIN) ───────────────
            [
                'key' => 'locker_pin_delivered', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Your Booking is Confirmed — {{ site_name }}',
                'variables' => array_merge($commonVars, ['pin_code', 'locker_number', 'entry_door_code', 'duration_label', 'eur_rsd_rate', 'tolerance_minutes']),
                'body' => $this->emailShell('Booking Confirmed!', '#10B981',
                    '<p class="info">Hello <strong style="color:#fff">{{ customer_name }}</strong>, your booking is confirmed.</p>'.
                    '<div class="highlight" style="text-align:left">'.
                    '<p style="margin:0;color:#fff;font-size:16px"><strong>{{ locker_number }} — {{ locker_size }} Locker — {{ duration_label }}</strong></p>'.
                    '<p style="margin:8px 0 0;color:#A0A0A0;font-size:14px">{{ check_in_full }} to {{ check_out_full }}</p>'.
                    '<p style="margin:8px 0 0;color:#A0A0A0;font-size:13px"><em>You\'re welcome to arrive up to {{ tolerance_minutes }} minutes early.</em></p>'.
                    '<p style="margin:4px 0 0;color:#A0A0A0;font-size:13px"><em>Luggage is fully secured and you can access it 24/7.</em></p>'.
                    '</div>'.

                    '<h2>Pay Station Information</h2>'.
                    '<div class="info">'.
                    '<p>Our pay station is located on the right-hand wall.</p>'.
                    '<p>Upon your arrival, please place your payment inside the box — our team will securely process it and take care of the rest.</p>'.
                    '<p><strong style="color:#fff">Total: €{{ total_eur }}</strong></p>'.
                    '<p style="margin-top:12px"><em>Please note:</em> We accept payments in Euros (€) and Serbian Dinars (RSD) only.<br>The exchange rate is <strong style="color:#fff">1 Euro = {{ eur_rsd_rate }} RSD</strong>.</p>'.
                    '</div>'.

                    '<h2>Entry Instructions</h2>'.
                    '<div class="highlight">'.
                    '<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">ENTRY DOOR access code</p>'.
                    '<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">{{ entry_door_code }}</p>'.
                    '</div>'.
                    '<div class="highlight">'.
                    '<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">YOUR LOCKER ({{ locker_number }}) access code</p>'.
                    '<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">{{ pin_code }}</p>'.
                    '</div>'.

                    '<h2>How to use lockers</h2>'.
                    '<div class="info"><ol style="padding-left:20px;margin:8px 0">'.
                    '<li style="margin-bottom:8px">Locate your locker number ({{ locker_number }}).</li>'.
                    '<li style="margin-bottom:8px">Press any button to activate the lock — a light will appear to confirm it\'s engaged.</li>'.
                    '<li style="margin-bottom:8px">Enter your access code — locker will unlock.</li>'.
                    '<li style="margin-bottom:8px">Place your belongings inside and close the door — the lock will automatically secure without needing to enter a code.</li>'.
                    '<li>Check if the locker is locked.</li>'.
                    '</ol></div>'.

                    '<div style="text-align:center;margin:24px 0"><a href="{{ directions_url }}" class="btn">Get Directions</a></div>'.

                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p>You are all set!</p>'.
                    '<p>If you have any questions or need help checking in, please let us know.</p>'.
                    '<p style="margin-top:16px"><strong style="color:#fff">{{ support_phone }}</strong> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">Enjoy Belgrade!<br>— {{ site_name }}</p>'.
                    '<p style="margin-top:12px;font-size:12px"><a href="{{ cancel_url }}" style="color:#6B7280">Need to cancel?</a></p>'.
                    '</div>'),
            ],
            [
                'key' => 'locker_pin_delivered', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Vaša rezervacija je potvrđena — {{ site_name }}',
                'variables' => array_merge($commonVars, ['pin_code', 'locker_number', 'entry_door_code', 'duration_label', 'eur_rsd_rate', 'tolerance_minutes']),
                'body' => $this->emailShell('Rezervacija potvrđena!', '#10B981',
                    '<p class="info">Zdravo <strong style="color:#fff">{{ customer_name }}</strong>, vaša rezervacija je potvrđena.</p>'.
                    '<div class="highlight" style="text-align:left">'.
                    '<p style="margin:0;color:#fff;font-size:16px"><strong>{{ locker_number }} — {{ locker_size }} ormarić — {{ duration_label }}</strong></p>'.
                    '<p style="margin:8px 0 0;color:#A0A0A0;font-size:14px">{{ check_in_full }} do {{ check_out_full }}</p>'.
                    '<p style="margin:8px 0 0;color:#A0A0A0;font-size:13px"><em>Možete doći i do {{ tolerance_minutes }} minuta ranije.</em></p>'.
                    '<p style="margin:4px 0 0;color:#A0A0A0;font-size:13px"><em>Prtljag je potpuno bezbedan i pristup imate 24/7.</em></p>'.
                    '</div>'.

                    '<h2>Informacije o plaćanju</h2>'.
                    '<div class="info">'.
                    '<p>Naš pay station se nalazi na desnom zidu.</p>'.
                    '<p>Prilikom dolaska, molimo vas da plaćanje stavite u kutiju — naš tim će bezbedno obraditi i pobrinuti se za sve ostalo.</p>'.
                    '<p><strong style="color:#fff">Ukupno: €{{ total_eur }}</strong></p>'.
                    '<p style="margin-top:12px"><em>Napomena:</em> Prihvatamo plaćanje u evrima (€) i srpskim dinarima (RSD).<br>Kurs: <strong style="color:#fff">1 Euro = {{ eur_rsd_rate }} RSD</strong>.</p>'.
                    '</div>'.

                    '<h2>Uputstva za ulaz</h2>'.
                    '<div class="highlight">'.
                    '<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">Šifra ULAZNIH VRATA</p>'.
                    '<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">{{ entry_door_code }}</p>'.
                    '</div>'.
                    '<div class="highlight">'.
                    '<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">Šifra VAŠEG ORMARIĆA ({{ locker_number }})</p>'.
                    '<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">{{ pin_code }}</p>'.
                    '</div>'.

                    '<h2>Kako se koriste ormarići</h2>'.
                    '<div class="info"><ol style="padding-left:20px;margin:8px 0">'.
                    '<li style="margin-bottom:8px">Pronađite vaš ormarić ({{ locker_number }}).</li>'.
                    '<li style="margin-bottom:8px">Pritisnite bilo koje dugme da aktivirate bravu — zasvetleće lampica.</li>'.
                    '<li style="margin-bottom:8px">Unesite šifru — ormarić će se otključati.</li>'.
                    '<li style="margin-bottom:8px">Stavite stvari unutra i zatvorite vrata — brava se automatski zaključava.</li>'.
                    '<li>Proverite da li je ormarić zaključan.</li>'.
                    '</ol></div>'.

                    '<div style="text-align:center;margin:24px 0"><a href="{{ directions_url }}" class="btn">Uputstva do nas</a></div>'.

                    '<div class="info" style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A">'.
                    '<p>Spremno!</p>'.
                    '<p>Ako imate bilo kakvo pitanje, javite nam se.</p>'.
                    '<p style="margin-top:16px"><strong style="color:#fff">{{ support_phone }}</strong> · <a href="mailto:{{ support_email }}" style="color:#F59E0B">{{ support_email }}</a></p>'.
                    '<p style="margin-top:16px;color:#6B7280">Uživajte u Beogradu!<br>— {{ site_name }}</p>'.
                    '<p style="margin-top:12px;font-size:12px"><a href="{{ cancel_url }}" style="color:#6B7280">Treba da otkažete?</a></p>'.
                    '</div>'),
            ],
            [
                'key' => 'locker_pin_delivered', 'channel' => 'whatsapp', 'locale' => 'en',
                'variables' => array_merge($commonVars, ['pin_code', 'locker_number', 'entry_door_code']),
                'body' => "Hello {{ customer_name }}, your booking is confirmed!\n\n📍 {{ location_name }} — {{ location_address }}\n🗄 Locker {{ locker_number }} ({{ locker_size }})\n🕒 {{ check_in_full }} → {{ check_out_full }}\n\n🔑 Entry door: *{{ entry_door_code }}*\n🔐 Your locker: *{{ pin_code }}*\n\n💶 Total €{{ total_eur }} — pay cash on arrival (1€ = {{ eur_rsd_rate }} RSD).\n\nNeed help? {{ support_phone }}",
            ],
            [
                'key' => 'locker_pin_delivered', 'channel' => 'whatsapp', 'locale' => 'sr',
                'variables' => array_merge($commonVars, ['pin_code', 'locker_number', 'entry_door_code']),
                'body' => "Zdravo {{ customer_name }}, rezervacija potvrđena!\n\n📍 {{ location_name }} — {{ location_address }}\n🗄 Ormarić {{ locker_number }} ({{ locker_size }})\n🕒 {{ check_in_full }} → {{ check_out_full }}\n\n🔑 Ulazna vrata: *{{ entry_door_code }}*\n🔐 Vaš ormarić: *{{ pin_code }}*\n\n💶 Ukupno €{{ total_eur }} — gotovinom na licu mesta (1€ = {{ eur_rsd_rate }} RSD).\n\nPomoć: {{ support_phone }}",
            ],

            // ─── password_reset ─────────────────────────────────────
            [
                'key' => 'password_reset', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'Reset your password — Belgrade Luggage Locker',
                'variables' => ['user_name', 'reset_url', 'expires_minutes', 'support_phone'],
                'body' => $this->emailShell('Reset Your Password', '#F59E0B',
                    '<div class="info">'.
                    '<p>Hi {{ user_name }},</p>'.
                    '<p>You requested to reset your password. Click the button below to set a new one.</p>'.
                    '<div style="text-align:center;margin:24px 0"><a href="{{ reset_url }}" class="btn">Reset Password</a></div>'.
                    '<p>This link expires in {{ expires_minutes }} minutes.</p>'.
                    '<p style="margin-top:16px;color:#A0A0A0;font-size:13px">If you did not request this, ignore this email — your password will not change.</p></div>'),
            ],
            [
                'key' => 'password_reset', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Resetovanje lozinke — Belgrade Luggage Locker',
                'variables' => ['user_name', 'reset_url', 'expires_minutes', 'support_phone'],
                'body' => $this->emailShell('Resetovanje lozinke', '#F59E0B',
                    '<div class="info">'.
                    '<p>Zdravo {{ user_name }},</p>'.
                    '<p>Zatražili ste resetovanje lozinke. Kliknite dugme ispod da postavite novu.</p>'.
                    '<div style="text-align:center;margin:24px 0"><a href="{{ reset_url }}" class="btn">Resetuj lozinku</a></div>'.
                    '<p>Link ističe za {{ expires_minutes }} minuta.</p>'.
                    '<p style="margin-top:16px;color:#A0A0A0;font-size:13px">Ako niste tražili ovo, ignorišite e-mail — lozinka neće biti promenjena.</p></div>'),
            ],

            // ─── contact_form_submitted ─────────────────────────────────────
            [
                'key' => 'contact_form_submitted', 'channel' => 'email', 'locale' => 'en',
                'subject' => 'New contact form submission',
                'variables' => ['name', 'email', 'phone', 'message', 'submitted_at'],
                'body' => $this->emailShell('New Contact Form Submission', '#F59E0B',
                    '<div class="info">'.
                    '<p><strong>From:</strong> {{ name }} &lt;{{ email }}&gt;</p>'.
                    '<p><strong>Phone:</strong> {{ phone }}</p>'.
                    '<p><strong>Submitted:</strong> {{ submitted_at }}</p>'.
                    '<h2 style="color:#F59E0B;font-size:14px;margin-top:20px">Message</h2>'.
                    '<div style="background:#0A0A0A;border:1px solid #2A2A2A;border-radius:8px;padding:16px;white-space:pre-wrap">{{ message }}</div></div>'),
            ],
            [
                'key' => 'contact_form_submitted', 'channel' => 'email', 'locale' => 'sr',
                'subject' => 'Nova poruka sa kontakt forme',
                'variables' => ['name', 'email', 'phone', 'message', 'submitted_at'],
                'body' => $this->emailShell('Nova poruka sa kontakt forme', '#F59E0B',
                    '<div class="info">'.
                    '<p><strong>Od:</strong> {{ name }} &lt;{{ email }}&gt;</p>'.
                    '<p><strong>Telefon:</strong> {{ phone }}</p>'.
                    '<p><strong>Poslato:</strong> {{ submitted_at }}</p>'.
                    '<h2 style="color:#F59E0B;font-size:14px;margin-top:20px">Poruka</h2>'.
                    '<div style="background:#0A0A0A;border:1px solid #2A2A2A;border-radius:8px;padding:16px;white-space:pre-wrap">{{ message }}</div></div>'),
            ],
        ];
    }

    private function emailShell(string $title, string $titleColor, string $inner): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'.
            'body{font-family:Arial,sans-serif;background:#0A0A0A;color:#fff;padding:20px}'.
            '.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}'.
            '.info{color:#A0A0A0;font-size:14px;line-height:1.6}'.
            '.btn{display:inline-block;background:#F59E0B;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin-top:16px}'.
            '.highlight{background:#111;border:1px solid #2A2A2A;border-radius:8px;padding:16px;text-align:center;margin:16px 0}'.
            'h1{color:'.$titleColor.';text-align:center}h2{color:#F59E0B;font-size:16px;margin-top:20px}'.
            '</style></head><body><div class="card">'.
            '<h1>'.$title.'</h1>'.
            $inner.
            '</div></body></html>';
    }
}
