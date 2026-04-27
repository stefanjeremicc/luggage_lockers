<?php

namespace App\Services\Notification;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService implements NotificationServiceInterface
{
    public function send(string $to, string $templateKey, array $vars = [], string $locale = 'en'): bool
    {
        $rendered = NotificationTemplate::render($templateKey, $locale, 'email', $vars);

        if (!$rendered) {
            Log::warning('Email template not found', ['key' => $templateKey, 'locale' => $locale]);
            return false;
        }

        try {
            Mail::html($rendered['body'], function ($m) use ($to, $rendered) {
                $m->to($to)->subject($rendered['subject'] ?? '(no subject)');
            });
            $this->logSuccess($to, $templateKey, $locale);
            return true;
        } catch (\Throwable $e) {
            Log::error('Email send failed', ['to' => $to, 'key' => $templateKey, 'error' => $e->getMessage()]);
            $this->logFailure($to, $templateKey, $locale, $e->getMessage());
            return false;
        }
    }

    public function channel(): string
    {
        return 'email';
    }

    private function logSuccess(string $to, string $key, string $locale): void
    {
        if (!class_exists(NotificationLog::class)) return;
        try {
            NotificationLog::create([
                'channel' => 'email', 'recipient' => $to, 'template_key' => $key,
                'locale' => $locale, 'status' => 'sent', 'sent_at' => now(),
            ]);
        } catch (\Throwable) { /* log table shape may differ — don't block sends */ }
    }

    private function logFailure(string $to, string $key, string $locale, string $error): void
    {
        if (!class_exists(NotificationLog::class)) return;
        try {
            NotificationLog::create([
                'channel' => 'email', 'recipient' => $to, 'template_key' => $key,
                'locale' => $locale, 'status' => 'failed', 'error' => $error,
            ]);
        } catch (\Throwable) { /* ignore */ }
    }
}
