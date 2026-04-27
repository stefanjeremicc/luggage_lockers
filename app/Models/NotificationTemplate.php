<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'key', 'channel', 'locale', 'subject', 'body', 'variables', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function render(string $key, string $locale, string $channel, array $vars = []): ?array
    {
        $tpl = static::where('key', $key)
            ->where('channel', $channel)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first()
            ?? static::where('key', $key)
                ->where('channel', $channel)
                ->where('locale', 'en')
                ->where('is_active', true)
                ->first();

        if (!$tpl) {
            return null;
        }

        $subject = $tpl->subject ? self::interpolate($tpl->subject, $vars) : null;
        $body = self::interpolate($tpl->body, $vars);

        return compact('subject', 'body');
    }

    private static function interpolate(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{{ '.$k.' }}', (string) $v, $text);
            $text = str_replace('{{'.$k.'}}', (string) $v, $text);
        }
        return $text;
    }
}
