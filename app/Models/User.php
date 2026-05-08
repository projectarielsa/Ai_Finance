<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar', 'role',
        'currency', 'language', 'whatsapp_notifications',
        'minimum_balance_warning', 'is_active', 'timezone',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'whatsapp_notifications' => 'boolean',
            'is_active' => 'boolean',
            'minimum_balance_warning' => 'decimal:2',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class)->orderBy('sort_order');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public function aiLogs()
    {
        return $this->hasMany(AiLog::class);
    }

    public function financialReports()
    {
        return $this->hasMany(FinancialReport::class);
    }

    public function getTotalBalanceAttribute(): float
    {
        return $this->wallets()
            ->where('is_active', true)
            ->where('include_in_total', true)
            ->sum('balance');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=3b82f6&color=fff&size=128";
    }
}
