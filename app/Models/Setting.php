<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'int' => (int) $setting->value,
            'float' => (float) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $group = null): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue, 'type' => $type, 'group' => $group]
        );

    }
}
