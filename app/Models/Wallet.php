<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'slug', 'type', 'provider', 'icon', 'logo',
        'color', 'balance', 'initial_balance', 'currency', 'account_number',
        'description', 'is_active', 'include_in_total', 'sort_order', 'ai_aliases',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'initial_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'include_in_total' => 'boolean',
            'ai_aliases' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($wallet) {
            if (empty($wallet->slug)) {
                $wallet->slug = Str::slug($wallet->name);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers()
    {
        return $this->hasMany(Transaction::class, 'target_wallet_id');
    }

    public function getIconUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return $this->getDefaultIcon();
    }

    public function getDefaultIcon(): string
    {
        $icons = [
            'BCA'       => '🏦',
            'BRI'       => '🏦',
            'Mandiri'   => '🏦',
            'BNI'       => '🏦',
            'Cash'      => '💵',
            'Dana'      => '💙',
            'OVO'       => '💜',
            'Gopay'     => '💚',
            'Shopeepay' => '🧡',
        ];
        return $icons[$this->provider] ?? '💳';
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    public function getTotalIncomeAttribute(): float
    {
        return $this->transactions()->where('type', 'income')->where('status', 'completed')->sum('amount');
    }

    public function getTotalExpenseAttribute(): float
    {
        return $this->transactions()->where('type', 'expense')->where('status', 'completed')->sum('amount');
    }

    /** Credit wallet (add money) */
    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /** Debit wallet (subtract money) */
    public function debit(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
