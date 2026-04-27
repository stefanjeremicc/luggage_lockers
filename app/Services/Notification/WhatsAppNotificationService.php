<?php

namespace App\Services\Notification;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService implements NotificationServiceInterface
{
    public function send(string $to, string $templateKey, array $vars = [], string $locale = 'en'): bool
    {
        $rendered = NotificationTemplate::render($templateKey, $locale, 'whatsapp', $vars);

        if (!$rendered) {
            Log::warning('WhatsApp template not found', ['key' => $templateKey, 'locale' => $locale]);
            return false;
        }

        // Integration stub — replace with Twilio / WhatsApp Cloud API / Infobip when configured.
        Log::info('WhatsApp send (stub)', [
            'to' => $to,
            'template' => $templateKey,
            'body' => $rendered['body'],
        ]);

        return true;
    }

    public function channel(): string
    {
        return 'whatsapp';
    }
}
