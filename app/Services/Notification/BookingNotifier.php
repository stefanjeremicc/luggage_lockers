<?php

namespace App\Services\Notification;

use App\Models\Booking;
use App\Models\BookingLocker;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingNotifier
{
    public static function send(Booking $booking, string $templateKey, array $extraVars = []): void
    {
        if (self::disabled()) {
            Log::info('Notifications disabled — skipping send', ['booking' => $booking->id, 'template' => $templateKey]);
            return;
        }

        $booking->loadMissing(['customer', 'location', 'lockers']);
        $locale = $booking->customer->locale ?? 'en';
        $vars = self::buildVars($booking, $locale) + $extraVars;

        self::sendEmail($booking, $templateKey, $locale, $vars);

        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            self::sendWhatsApp($booking, $templateKey, $locale, $vars);
        }
    }

    /**
     * Send the single combined "booking confirmed" email to the customer,
     * AFTER every locker's PIN has been registered with TTLock cloud. This is
     * the only customer-facing confirmation now — the old two-step
     * (booking_confirmed → locker_pin_delivered) flow was consolidated.
     *
     * Idempotent: if some booking_lockers have a PIN registered and others
     * don't, we render what we have. Re-sends are guarded at the call site.
     */
    public static function sendBookingConfirmation(Booking $booking): void
    {
        if (self::disabled()) {
            Log::info('Notifications disabled — skipping booking_confirmed', ['booking' => $booking->id]);
            return;
        }

        $booking->loadMissing(['customer', 'location', 'bookingLockers.locker']);
        $locale = $booking->customer->locale ?? 'en';
        $vars = self::buildVars($booking, $locale) + self::pinVars($booking, $locale);

        self::sendEmail($booking, 'booking_confirmed', $locale, $vars);

        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            self::sendWhatsApp($booking, 'booking_confirmed', $locale, $vars);
        }
    }

    /**
     * Send the concise "new booking" alert to the admin inbox. Sent in parallel
     * with the customer confirmation, never BCCed onto the customer email.
     */
    public static function sendBookingConfirmedAdmin(Booking $booking): void
    {
        if (self::disabled()) return;

        $adminEmails = self::adminEmails();
        if (!$adminEmails) {
            Log::info('Admin booking confirmation skipped — no admin email configured', ['booking' => $booking->id]);
            return;
        }

        $booking->loadMissing(['customer', 'location', 'bookingLockers.locker']);
        $locale = 'en'; // Admin emails always English regardless of customer locale.
        $vars = self::buildVars($booking, $locale) + self::pinVars($booking, $locale) + [
            'customer_email' => $booking->customer->email ?? '—',
            'customer_phone' => $booking->customer->phone ?? '—',
        ];

        $rendered = NotificationTemplate::render('booking_confirmed_admin', $locale, 'email', $vars);
        if (!$rendered) {
            Log::warning('booking_confirmed_admin template not found', ['locale' => $locale]);
            return;
        }

        foreach ($adminEmails as $to) {
            try {
                Mail::html($rendered['body'], function ($m) use ($to, $rendered) {
                    $m->to($to)->subject($rendered['subject'] ?? 'New booking');
                });
                self::log($booking, 'email', 'booking_confirmed_admin', $to, 'sent', null, [
                    'subject' => $rendered['subject'], 'body_html' => $rendered['body'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Admin booking email send failed', ['booking' => $booking->id, 'to' => $to, 'error' => $e->getMessage()]);
                self::log($booking, 'email', 'booking_confirmed_admin', $to, 'failed', $e->getMessage(), null);
            }
        }
    }

    /**
     * Dispatch a "your PIN was just reissued" notice. Used by the admin
     * "Reissue PIN" flow — customer needs to know the previous PIN is dead
     * and the new one is now live. Goes to the customer (locale-aware) and
     * BCC every admin address for the audit trail.
     */
    public static function sendPinReissued(Booking $booking): void
    {
        if (self::disabled()) return;

        $booking->loadMissing(['customer', 'location', 'bookingLockers.locker']);
        $locale = $booking->customer->locale ?? 'en';
        $vars = self::buildVars($booking, $locale) + self::pinVars($booking, $locale);

        self::sendEmail($booking, 'pin_reissued', $locale, $vars);
    }

    /**
     * Build the lockers-with-PINs block for templates. Decrypts each
     * booking_locker's PIN and renders a single HTML chunk plus convenience
     * scalars (first PIN/number, used by single-locker templates).
     */
    private static function pinVars(Booking $booking, string $locale): array
    {
        $bls = $booking->bookingLockers ?? collect();
        $labelEntry = $locale === 'sr' ? 'Šifra ULAZNIH VRATA' : 'ENTRY DOOR access code';
        $labelLocker = $locale === 'sr' ? 'Šifra ORMARIĆA' : 'YOUR LOCKER';
        $entryCode = (string) Setting::getValue('entry_door_code', '0717#');

        $blocks = [];
        $firstPin = null;
        $firstNumber = null;
        foreach ($bls as $bl) {
            try {
                $pin = Crypt::decryptString($bl->pin_code_encrypted);
            } catch (\Throwable) {
                continue;
            }
            $firstPin ??= $pin;
            $firstNumber ??= $bl->locker->number ?? '';
            $number = $bl->locker->number ?? '';
            $blocks[] = '<div class="highlight">'
                .'<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">'.e($labelLocker).' ('.e($number).') access code</p>'
                .'<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">#'.e($pin).'</p>'
                .'</div>';
        }

        // Entry door block goes first, then one block per locker.
        $entryBlock = '<div class="highlight">'
            .'<p style="margin:0 0 6px;color:#A0A0A0;font-size:13px">'.e($labelEntry).'</p>'
            .'<p style="margin:0;font-size:28px;color:#F59E0B;font-weight:bold;letter-spacing:6px">#'.e($entryCode).'</p>'
            .'</div>';

        return [
            'pin_code' => $firstPin ? '#'.$firstPin : '',
            'locker_number' => $firstNumber ?? '',
            'codes_block' => $entryBlock . implode('', $blocks),
            'entry_door_code' => '#'.$entryCode,
        ];
    }

    /**
     * Render the "Booking Details" card used at the top of customer emails —
     * mirrors the confirmation-page card on the website: green CHECK-IN row,
     * red CHECK-OUT row, amber LOCKERS row, all stacked under the location.
     *
     * Pure HTML string; embedded in the template via {{ booking_details_block }}.
     */
    private static function bookingDetailsBlock(Booking $booking, string $locale, array $vars): string
    {
        $L = static fn (string $en, string $sr) => $locale === 'sr' ? $sr : $en;
        $name = e($vars['location_name'] ?? '');
        $addr = e($vars['location_address'] ?? '');
        $checkIn = e($vars['check_in_full'] ?? '');
        $checkOut = e($vars['check_out_full'] ?? '');
        $items = e($vars['items_summary'] ?? '');
        $duration = e($vars['duration_label'] ?? '');
        $total = e($vars['total_eur'] ?? '0.00');

        // Email-safe icon glyphs — single character per slot, sized + coloured
        // to match the SVG pills on the website. We avoid emojis (renders
        // differently per client) and inline SVG (Gmail strips it). The chars
        // here are HTML entities widely supported in mail clients.
        $iconPin    = '&#x2691;';   // ⚑  flag — for location
        $iconIn     = '&#x2193;';   // ↓  down-arrow — entering / check-in
        $iconOut    = '&#x2191;';   // ↑  up-arrow — leaving / check-out
        $iconBox    = '&#x25A3;';   // ▣  square with smaller square — locker
        $iconStyle  = 'font-size:18px;line-height:36px;font-weight:bold;display:block;text-align:center';

        $pill = static fn (string $bg, string $color, string $icon) =>
            '<div style="width:36px;height:36px;border-radius:10px;background:'.$bg.';color:'.$color.';'.$iconStyle.'">'.$icon.'</div>';

        $row = static fn (string $pillHtml, string $labelHtml, bool $border = true) =>
            '<tr><td style="padding:14px 0'.($border ? ';border-bottom:1px solid #2A2A2A' : '').'">'
            .'<table style="width:100%;border-collapse:collapse"><tr>'
            .'<td style="width:42px;vertical-align:top">'.$pillHtml.'</td>'
            .'<td style="vertical-align:top;padding-left:8px">'.$labelHtml.'</td>'
            .'</tr></table></td></tr>';

        $locationRow = $row(
            $pill('rgba(245,158,11,0.10)', '#F59E0B', $iconPin),
            '<div style="font-weight:600;color:#fff;font-size:14px">'.$name.'</div>'
            .'<div style="color:#A0A0A0;font-size:13px;margin-top:2px">'.$addr.'</div>'
        );

        $checkInRow = $row(
            $pill('rgba(16,185,129,0.10)', '#10B981', $iconIn),
            '<div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#10B981;font-weight:700">'.$L('Check-in', 'Dolazak').'</div>'
            .'<div class="no-link" style="font-weight:600;color:#fff;font-size:14px;margin-top:2px">'.$checkIn.'</div>'
        );

        $checkOutRow = $row(
            $pill('rgba(239,68,68,0.10)', '#EF4444', $iconOut),
            '<div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#EF4444;font-weight:700">'.$L('Check-out', 'Odlazak').'</div>'
            .'<div class="no-link" style="font-weight:600;color:#fff;font-size:14px;margin-top:2px">'.$checkOut.'</div>'
        );

        // Final row — no bottom border, plus right-aligned total cell.
        $lockersRow = '<tr><td style="padding:14px 0">'
            .'<table style="width:100%;border-collapse:collapse"><tr>'
            .'<td style="width:42px;vertical-align:top">'.$pill('rgba(245,158,11,0.10)', '#F59E0B', $iconBox).'</td>'
            .'<td style="vertical-align:top;padding-left:8px">'
            .'<div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#F59E0B;font-weight:700">'.$L('Lockers', 'Ormarići').'</div>'
            .'<div style="font-weight:600;color:#fff;font-size:14px;margin-top:2px">'.$items.' <span style="color:#A0A0A0;font-weight:400"> — '.$duration.'</span></div>'
            .'</td>'
            .'<td style="vertical-align:top;text-align:right;color:#F59E0B;font-weight:600;white-space:nowrap">€'.$total.'</td>'
            .'</tr></table></td></tr>';

        return '<div style="background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:0 18px;margin:16px 0">'
            .'<div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#A0A0A0;font-weight:700;padding:14px 0 6px;border-bottom:1px solid #2A2A2A">'
            .$L('Booking Details', 'Detalji rezervacije')
            .'</div>'
            .'<table style="width:100%;border-collapse:collapse">'
            .$locationRow.$checkInRow.$checkOutRow.$lockersRow
            .'</table></div>';
    }

    /**
     * Translate the booking's duration_label into the requested locale.
     * `duration_label` was rendered at booking-creation time using whatever
     * locale the customer's browser was in. To send a SR confirmation for an
     * EN-locale booking (or vice-versa), we map the stored text back to the
     * canonical key and re-translate from lang/{$locale}.json.
     */
    private static function localiseDurationLabel(?string $stored, string $locale): string
    {
        if (!$stored) return '';

        // Raw duration keys persisted on booking_items / older bookings, mapped
        // back to the English canonical key from lang files.
        $rawKeyMap = [
            '6h' => 'Up to 6 hours',
            '24h' => '24 hours',
            '2_days' => '2 days',
            '3_days' => '3 days',
            '4_days' => '4 days',
            '5_days' => '5 days',
            '1_week' => '1 week',
            '2_weeks' => '2 weeks',
            '1_month' => '1 month',
        ];
        $known = array_values($rawKeyMap);

        // Resolve to English canonical key, regardless of what was stored.
        $key = $rawKeyMap[$stored]
            ?? (in_array($stored, $known, true) ? $stored : null);

        if (!$key) {
            // Reverse-lookup: stored value might be a Serbian translation.
            $srTranslations = json_decode(@file_get_contents(base_path('lang/sr.json')) ?: '{}', true) ?: [];
            foreach ($srTranslations as $en => $sr) {
                if ($sr === $stored && in_array($en, $known, true)) { $key = $en; break; }
            }
        }
        $key = $key ?: $stored;

        if ($locale === 'sr') {
            $srTranslations ??= json_decode(@file_get_contents(base_path('lang/sr.json')) ?: '{}', true) ?: [];
            return $srTranslations[$key] ?? $key;
        }
        return $key;
    }

    private static function buildVars(Booking $booking, string $locale = 'en'): array
    {
        $tz = config('app.display_timezone');
        $checkIn = $booking->check_in->copy()->setTimezone($tz);
        $checkOut = $booking->check_out->copy()->setTimezone($tz);

        // Date/time formatting differs per locale: Serbian customers read
        // "13.05.2026. u 15:30" naturally, English customers "May 13, 2026 at 15:30".
        $fmtDate = $locale === 'sr' ? 'd.m.Y.' : 'M j, Y';
        $fmtFull = $locale === 'sr' ? 'd.m.Y. \u H:i' : 'M j, Y \a\t H:i';

        // Translate the duration_label per locale by looking up the original key.
        // `duration_label` on the booking row was rendered when the booking was
        // created (in whatever locale the customer was using), so we need to map
        // back to the canonical key first and re-translate from lang/{$locale}.json.
        $durationLabel = self::localiseDurationLabel($booking->duration_label, $locale);

        // Map internal size identifier to the customer-facing label. We kept the
        // DB enum as `standard`/`large` so existing rows + pricing rules keep
        // working, but every email/UI surface now reads "Regular" / "Big".
        $sizeDisplay = static function ($size): string {
            $key = is_object($size) ? $size->value : (string) $size;
            return $key === 'large' ? 'Big' : 'Regular';
        };

        // Build a multi-item summary like "2 x Big, 1 x Regular" from booking_items if present;
        // fall back to legacy single-size rendering when items table is empty.
        $items = $booking->relationLoaded('items') ? $booking->items : $booking->items()->get();
        if ($items && $items->count() > 0) {
            $itemsSummary = $items->map(fn($it) => $it->qty . ' x ' . $sizeDisplay($it->locker_size))->implode(', ');
            $totalQty = (int) $items->sum('qty');
            $sizeLabel = $sizeDisplay($items->first()->locker_size);
        } else {
            $sizeLabel = $sizeDisplay($booking->locker_size);
            $itemsSummary = ($booking->locker_qty ?? 1) . ' x ' . $sizeLabel;
            $totalQty = (int) ($booking->locker_qty ?? 1);
        }

        $baseVars = [
            'customer_name' => $booking->customer->full_name,
            'location_name' => $booking->location->name,
            'location_address' => $booking->location->address.', '.($booking->location->city ?: 'Belgrade'),
            'check_in' => $checkIn->format($fmtDate),
            'check_in_time' => $checkIn->format('H:i'),
            'check_in_full' => $checkIn->format($fmtFull),
            'check_out' => $checkOut->format($fmtDate),
            'check_out_time' => $checkOut->format('H:i'),
            'check_out_full' => $checkOut->format($fmtFull),
            'duration_label' => $durationLabel,
            'locker_qty' => $totalQty,
            'locker_number' => $booking->lockers->first()?->number ?? '',
            'locker_size' => $sizeLabel,
            'items_summary' => $itemsSummary,
            'total_eur' => number_format((float) $booking->total_eur, 2),
            'eur_rsd_rate' => Setting::getValue('eur_rsd_rate', 117),
            'entry_door_code' => Setting::getValue('entry_door_code', '0717#'),
            'tolerance_minutes' => Setting::getValue('booking_tolerance_minutes', 20),
            'cancel_url' => url("/booking/{$booking->uuid}/cancel?token=".hash_hmac('sha256', $booking->uuid.'|'.$booking->customer_id, config('app.key'))),
            'directions_url' => $booking->location->google_maps_url ?: url('/'),
            'support_phone' => Setting::getValue('support_phone', '+381 65 332 2319'),
            'support_email' => Setting::getValue('support_email', 'info@belgradeluggagelocker.com'),
            'site_name' => Setting::getValue('site_name', 'Belgrade Luggage Locker'),
        ];
        $baseVars['booking_details_block'] = self::bookingDetailsBlock($booking, $locale, $baseVars);
        return $baseVars;
    }

    private static function sendEmail(Booking $booking, string $key, string $locale, array $vars): void
    {
        $rendered = NotificationTemplate::render($key, $locale, 'email', $vars);
        if (!$rendered) {
            Log::warning('Email template not found', ['key' => $key, 'locale' => $locale]);
            return;
        }

        $customerEmail = $booking->customer->email;
        $adminEmails = self::adminEmails(); // array — supports multiple admin recipients
        $isDevMode = self::devMode();
        $notifyAdmin = self::notifyAdmin();

        // Recipient policy:
        //  - Dev mode ON  → first admin only (suppresses real customer).
        //  - Dev mode OFF → customer; if notify_admin is also ON, BCC every admin
        //    address (drop any that match the customer to avoid double-send).
        $primary = $isDevMode
            ? ($adminEmails[0] ?? $customerEmail)
            : $customerEmail;
        $bccs = (!$isDevMode && $notifyAdmin)
            ? array_values(array_filter($adminEmails, fn ($e) => $e && $e !== $customerEmail))
            : [];

        $subject = $rendered['subject'] ?? '(no subject)';
        $body = $rendered['body'];

        if ($isDevMode) {
            $subject = '[DEV → '.$customerEmail.'] '.$subject;
            $body = '<div style="background:#F59E0B;color:#000;padding:12px;font-family:monospace;font-size:13px">DEV REDIRECT — original recipient: '.e($customerEmail).' / customer phone: '.e($booking->customer->phone ?? '—').'</div>'.$body;
        }

        // Always persist the rendered HTML so admin can preview it later — works whether
        // outbound SMTP is wired up (real send) or running on Mailpit / log driver.
        $payload = ['subject' => $subject, 'body_html' => $body, 'bcc' => $bccs];

        try {
            Mail::html($body, function ($m) use ($primary, $bccs, $booking, $subject) {
                $m->to($primary, $booking->customer->full_name)->subject($subject);
                foreach ($bccs as $bcc) $m->bcc($bcc);
            });
            self::log($booking, 'email', $key, $primary, 'sent', null, $payload);
            foreach ($bccs as $bcc) self::log($booking, 'email', $key.':admin-bcc', $bcc, 'sent', null, $payload);
        } catch (\Throwable $e) {
            Log::error('Booking email send failed', ['booking' => $booking->id, 'key' => $key, 'error' => $e->getMessage()]);
            self::log($booking, 'email', $key, $primary, 'failed', $e->getMessage(), $payload);
        }
    }

    private static function sendWhatsApp(Booking $booking, string $key, string $locale, array $vars): void
    {
        $rendered = NotificationTemplate::render($key, $locale, 'whatsapp', $vars);
        if (!$rendered) return;

        $customerPhone = preg_replace('/[^0-9]/', '', $booking->customer->phone);
        $adminPhone = self::adminWhatsApp();
        $isDevMode = self::devMode();
        $notifyAdmin = self::notifyAdmin();

        // Same policy as email:
        //  - Dev mode ON  → admin only.
        //  - Dev mode OFF → customer; if notify_admin is also ON, also send to admin.
        $recipients = $isDevMode
            ? array_filter([$adminPhone ?: $customerPhone])
            : array_filter(array_unique([
                $customerPhone,
                ($notifyAdmin && $adminPhone && $adminPhone !== $customerPhone) ? $adminPhone : null,
            ]));

        foreach ($recipients as $phone) {
            $body = $rendered['body'];
            if ($isDevMode) {
                $body = "[DEV → +{$customerPhone}]\n".$body;
            } elseif ($phone === $adminPhone && $phone !== $customerPhone) {
                $body = "[admin copy — booking by ".($booking->customer->full_name ?: $customerPhone)."]\n".$body;
            }
            self::dispatchWhatsApp($booking, $key, $phone, $body, $rendered['body']);
        }
    }

    private static function dispatchWhatsApp(Booking $booking, string $key, string $phone, string $body, ?string $rawBody = null): void
    {
        $token = config('services.whatsapp.api_token');
        $phoneId = config('services.whatsapp.phone_id');
        $payload = ['body_text' => $body];

        if (!$token || !$phoneId) {
            Log::info('WhatsApp send (stub — credentials missing)', [
                'to' => $phone,
                'template' => $key,
                'body' => $body,
            ]);
            self::log($booking, 'whatsapp', $key, $phone, 'stub', 'WhatsApp credentials not configured', $payload);
            return;
        }

        try {
            Http::withToken($token)->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);
            self::log($booking, 'whatsapp', $key, $phone, 'sent', null, $payload);
        } catch (\Throwable $e) {
            self::log($booking, 'whatsapp', $key, $phone, 'failed', $e->getMessage(), $payload);
        }
    }

    /**
     * Settings UI is canonical — env value is only the bootstrap default.
     * If a setting row exists, its value wins (so admin can explicitly turn dev mode OFF).
     */
    private static function boolPref(string $settingKey, string $envKey): bool
    {
        $row = Setting::where('key', $settingKey)->first();
        if ($row) return filter_var($row->value, FILTER_VALIDATE_BOOLEAN);
        return (bool) env($envKey, false);
    }

    private static function disabled(): bool
    {
        return self::boolPref('notifications_disabled', 'NOTIFICATIONS_DISABLED');
    }

    private static function devMode(): bool
    {
        return self::boolPref('notifications_dev_mode', 'NOTIFICATIONS_DEV_MODE');
    }

    private static function notifyAdmin(): bool
    {
        return self::boolPref('notifications_notify_admin', 'NOTIFY_ADMIN');
    }

    /** First admin address — kept for callers that still expect a single string. */
    private static function adminEmail(): ?string
    {
        return self::adminEmails()[0] ?? null;
    }

    /**
     * Parse the admin-email setting into a list. Accepts a comma- or
     * semicolon-separated string so the dashboard can configure multiple
     * notification recipients without code changes.
     */
    private static function adminEmails(): array
    {
        $raw = Setting::getValue('notifications_admin_email', null)
            ?: env('ADMIN_EMAIL_TO')
            ?: env('DEV_EMAIL_TO')
            ?: '';
        if (!$raw) return [];
        $parts = preg_split('/[,;\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        // Keep only well-formed addresses; trim whitespace just in case.
        return array_values(array_filter(array_map('trim', $parts), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    private static function adminWhatsApp(): ?string
    {
        $raw = Setting::getValue('notifications_admin_whatsapp', null) ?: env('ADMIN_WHATSAPP_TO') ?: env('DEV_WHATSAPP_TO');
        return $raw ? preg_replace('/[^0-9]/', '', $raw) : null;
    }

    private static function log(Booking $booking, string $channel, string $key, string $recipient, string $status, ?string $error = null, ?array $payload = null): void
    {
        try {
            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => $channel,
                'template' => $key,
                'recipient' => $recipient,
                'payload' => $payload,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $status === 'sent' ? now() : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) { /* don't block sends on logging issues */ }
    }
}
