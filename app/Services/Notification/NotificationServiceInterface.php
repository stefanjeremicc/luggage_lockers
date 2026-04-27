<?php

namespace App\Services\Notification;

interface NotificationServiceInterface
{
    /**
     * Send a notification using a named template.
     *
     * @param  string  $to         Recipient (email address or E.164 phone number).
     * @param  string  $templateKey Identifier matching NotificationTemplate.key.
     * @param  array   $vars       Interpolation variables for the template.
     * @param  string  $locale     Preferred locale; falls back to 'en' if unavailable.
     * @return bool                True if the message was dispatched successfully.
     */
    public function send(string $to, string $templateKey, array $vars = [], string $locale = 'en'): bool;

    /** Channel identifier — used to look up the correct template. */
    public function channel(): string;
}
