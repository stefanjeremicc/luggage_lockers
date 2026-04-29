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
        $vars = self::buildVars($booking) + $extraVars;

        self::sendEmail($booking, $templateKey, $locale, $vars);

        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            self::sendWhatsApp($booking, $templateKey, $locale, $vars);
        }
    }

    /**
     * Send the per-locker PIN to the customer, after TTLock cloud has accepted it.
     */
    public static function sendPinDelivered(BookingLocker $bl): void
    {
        if (self::disabled()) {
            Log::info('Notifications disabled — skipping PIN delivery', ['booking_locker' => $bl->id]);
            return;
        }

        $booking = $bl->booking;
        $booking->loadMissing(['customer', 'location']);
        $locale = $booking->customer->locale ?? 'en';

        try {
            $pin = Crypt::decryptString($bl->pin_code_encrypted);
        } catch (\Throwable $e) {
            Log::error('Failed to decrypt PIN for delivery', ['booking_locker' => $bl->id, 'error' => $e->getMessage()]);
            return;
        }

        $vars = self::buildVars($booking) + [
            'pin_code' => $pin,
            'locker_number' => $bl->locker->number,
        ];

        self::sendEmail($booking, 'locker_pin_delivered', $locale, $vars);

        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            self::sendWhatsApp($booking, 'locker_pin_delivered', $locale, $vars);
        }
    }

    private static function buildVars(Booking $booking): array
    {
        $tz = config('app.display_timezone');
        $checkIn = $booking->check_in->copy()->setTimezone($tz);
        $checkOut = $booking->check_out->copy()->setTimezone($tz);

        // Build a multi-item summary like "2 x Large, 1 x Standard" from booking_items if present;
        // fall back to legacy single-size rendering when items table is empty.
        $items = $booking->relationLoaded('items') ? $booking->items : $booking->items()->get();
        if ($items && $items->count() > 0) {
            $itemsSummary = $items->map(fn($it) => $it->qty . ' x ' . ucfirst(is_object($it->locker_size) ? $it->locker_size->value : (string) $it->locker_size))->implode(', ');
            $totalQty = (int) $items->sum('qty');
            $sizeLabel = ucfirst(is_object($items->first()->locker_size) ? $items->first()->locker_size->value : (string) $items->first()->locker_size);
        } else {
            $sizeLabel = is_object($booking->locker_size) ? ucfirst($booking->locker_size->value) : ucfirst((string) $booking->locker_size);
            $itemsSummary = ($booking->locker_qty ?? 1) . ' x ' . $sizeLabel;
            $totalQty = (int) ($booking->locker_qty ?? 1);
        }

        return [
            'customer_name' => $booking->customer->full_name,
            'location_name' => $booking->location->name,
            'location_address' => $booking->location->address.', '.($booking->location->city ?: 'Belgrade'),
            'check_in' => $checkIn->format('M j, Y'),
            'check_in_time' => $checkIn->format('H:i'),
            'check_in_full' => $checkIn->format('M j, Y \a\t H:i'),
            'check_out' => $checkOut->format('M j, Y'),
            'check_out_time' => $checkOut->format('H:i'),
            'check_out_full' => $checkOut->format('M j, Y \a\t H:i'),
            'duration_label' => $booking->duration_label,
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
    }

    private static function sendEmail(Booking $booking, string $key, string $locale, array $vars): void
    {
        $rendered = NotificationTemplate::render($key, $locale, 'email', $vars);
        if (!$rendered) {
            Log::warning('Email template not found', ['key' => $key, 'locale' => $locale]);
            return;
        }

        $customerEmail = $booking->customer->email;
        $adminEmail = self::adminEmail();
        $isDevMode = self::devMode();
        $notifyAdmin = self::notifyAdmin();

        // Recipient policy:
        //  - Dev mode ON  → admin only (suppresses real customer).
        //  - Dev mode OFF → customer; if notify_admin is also ON, BCC the admin.
        $primary = $isDevMode ? ($adminEmail ?: $customerEmail) : $customerEmail;
        $bccs = (!$isDevMode && $notifyAdmin && $adminEmail && $adminEmail !== $customerEmail) ? [$adminEmail] : [];

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

    private static function adminEmail(): ?string
    {
        return Setting::getValue('notifications_admin_email', null) ?: env('ADMIN_EMAIL_TO') ?: env('DEV_EMAIL_TO');
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
