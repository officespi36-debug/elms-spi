<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || is_null($setting->value)) {
            return $default;
        }

        $value = $setting->value;
        if ($value === 'true' || $value === '1') return true;
        if ($value === 'false' || $value === '0') return false;

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $val = is_array($value) || is_object($value) 
            ? json_encode($value) 
            : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $val]
        );
    }
}
