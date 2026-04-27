<?php

namespace App\Services\Notification;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingNotifier
{
    public static function send(Booking $booking, string $templateKey, array $extraVars = []): void
    {
        $booking->loadMissing(['customer', 'location', 'lockers']);
        $locale = $booking->customer->locale ?? 'en';
        $vars = self::buildVars($booking) + $extraVars;

        self::sendEmail($booking, $templateKey, $locale, $vars);

        if ($booking->customer->whatsapp_opt_in && $booking->customer->phone) {
            self::sendWhatsApp($booking, $templateKey, $locale, $vars);
        }
    }

    private static function buildVars(Booking $booking): array
    {
        return [
            'customer_name' => $booking->customer->full_name,
            'location_name' => $booking->location->name,
            'location_address' => $booking->location->address.', Belgrade',
            'check_in' => $booking->check_in->format('d M Y, H:i'),
            'check_out' => $booking->check_out->format('d M Y, H:i'),
            'locker_qty' => $booking->locker_qty,
            'locker_size' => is_object($booking->locker_size) ? ucfirst($booking->locker_size->value) : ucfirst((string) $booking->locker_size),
            'total_eur' => number_format((float) $booking->total_eur, 2),
            'cancel_url' => url("/booking/{$booking->uuid}/cancel"),
            'directions_url' => $booking->location->google_maps_url ?: url('/'),
            'support_phone' => Setting::getValue('support_phone', '+381 65 332 2319'),
            'support_email' => Setting::getValue('support_email', 'info@belgradeluggagelocker.com'),
        ];
    }

    private static function sendEmail(Booking $booking, string $key, string $locale, array $vars): void
    {
        $rendered = NotificationTemplate::render($key, $locale, 'email', $vars);
        if (!$rendered) {
            Log::warning('Email template not found', ['key' => $key, 'locale' => $locale]);
            return;
        }

        try {
            Mail::html($rendered['body'], function ($m) use ($booking, $rendered) {
                $m->to($booking->customer->email, $booking->customer->full_name)
                    ->subject($rendered['subject'] ?? '(no subject)');
            });

            self::log($booking, 'email', $key, $booking->customer->email, 'sent');
        } catch (\Throwable $e) {
            Log::error('Booking email send failed', ['booking' => $booking->id, 'key' => $key, 'error' => $e->getMessage()]);
            self::log($booking, 'email', $key, $booking->customer->email, 'failed', $e->getMessage());
        }
    }

    private static function sendWhatsApp(Booking $booking, string $key, string $locale, array $vars): void
    {
        $rendered = NotificationTemplate::render($key, $locale, 'whatsapp', $vars);
        if (!$rendered) return;

        $token = config('services.whatsapp.api_token');
        $phoneId = config('services.whatsapp.phone_id');
        $phone = preg_replace('/[^0-9]/', '', $booking->customer->phone);

        if (!$token || !$phoneId) {
            Log::info('WhatsApp send (stub — credentials missing)', ['to' => $phone, 'template' => $key, 'body' => $rendered['body']]);
            self::log($booking, 'whatsapp', $key, $phone, 'sent');
            return;
        }

        try {
            Http::withToken($token)->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $rendered['body']],
            ]);
            self::log($booking, 'whatsapp', $key, $phone, 'sent');
        } catch (\Throwable $e) {
            self::log($booking, 'whatsapp', $key, $phone, 'failed', $e->getMessage());
        }
    }

    private static function log(Booking $booking, string $channel, string $key, string $recipient, string $status, ?string $error = null): void
    {
        try {
            NotificationLog::create([
                'booking_id' => $booking->id,
                'channel' => $channel,
                'template' => $key,
                'recipient' => $recipient,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $status === 'sent' ? now() : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) { /* don't block sends on logging issues */ }
    }
}
