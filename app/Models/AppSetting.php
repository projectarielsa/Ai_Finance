<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });
        if (!$setting) return $default;
        return static::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'type' => $type]
        );
        Cache::forget("setting_{$key}");
    }

    protected static function castValue(mixed $value, string $type): mixed
    {
        return match($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float'   => (float) $value,
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
