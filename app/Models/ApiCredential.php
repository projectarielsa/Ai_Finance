<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class ApiCredential extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'provider', 'key_name', 'key_value', 'endpoint_url', 'model',
        'is_active', 'is_default', 'meta', 'last_tested_at',
        'last_test_success', 'last_test_message', 'updated_by',
    ];

    protected $hidden = ['key_value'];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'is_default'       => 'boolean',
            'last_test_success'=> 'boolean',
            'last_tested_at'   => 'datetime',
            'meta'             => 'array',
            // key_value is encrypted via mutator/accessor below (Crypt facade),
            // NOT the Laravel 'encrypted:' cast, so we keep it as plain string here.
        ];
    }

    /**
     * Always encrypt API key when storing.
     */
    public function setKeyValueAttribute(string $value): void
    {
        // Avoid double-encrypting: if the value is already encrypted, store as-is
        try {
            Crypt::decryptString($value);
            // Already encrypted — store directly
            $this->attributes['key_value'] = $value;
        } catch (\Exception) {
            // Plain text — encrypt it
            $this->attributes['key_value'] = Crypt::encryptString($value);
        }
    }

    /**
     * Always decrypt API key when reading.
     */
    public function getKeyValueAttribute(?string $value): string
    {
        if (empty($value)) return '';
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            // Value was stored plain (legacy) — return as-is
            return $value;
        }
    }

    public function getMaskedKeyAttribute(): string
    {
        $key = $this->key_value;
        if (strlen($key) <= 8) return str_repeat('*', strlen($key));
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getDefault(string $provider): ?self
    {
        return static::where('provider', $provider)
                     ->where('is_active', true)
                     ->where('is_default', true)
                     ->first()
               ?? static::where('provider', $provider)
                     ->where('is_active', true)
                     ->first();
    }
}
