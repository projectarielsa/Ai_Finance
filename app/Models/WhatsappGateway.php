<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class WhatsappGateway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'provider', 'base_url', 'api_key', 'device_id', 'sender_number',
        'webhook_secret', 'webhook_url', 'is_active', 'is_default', 'status',
        'last_connected_at', 'meta', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_connected_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function setApiKeyAttribute(string $value): void
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    public function getApiKeyAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getMaskedApiKeyAttribute(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) return str_repeat('*', strlen($key));
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first()
               ?? static::where('is_active', true)->first();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'connected' => 'bg-green-500/20 text-green-400',
            'disconnected' => 'bg-red-500/20 text-red-400',
            default => 'bg-yellow-500/20 text-yellow-400',
        };
    }
}
