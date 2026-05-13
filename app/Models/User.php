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
        'name', 'email', 'password', 'phone', 'telegram_id', 'telegram_username',
        'avatar', 'role', 'currency', 'language', 'telegram_notifications',
        'minimum_balance_warning', 'is_active', 'timezone',
        // Reminder settings
        'daily_reminder_enabled', 'daily_reminder_time',
        'weekly_summary_enabled',
        'big_transaction_alert_enabled', 'big_transaction_threshold',
        // 2FA
        'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at'               => 'datetime',
            'password'                        => 'hashed',
            'telegram_notifications'          => 'boolean',
            'is_active'                       => 'boolean',
            'minimum_balance_warning'         => 'decimal:2',
            'daily_reminder_enabled'          => 'boolean',
            'weekly_summary_enabled'          => 'boolean',
            'big_transaction_alert_enabled'   => 'boolean',
            'big_transaction_threshold'       => 'decimal:2',
            // 2FA
            'two_factor_enabled'              => 'boolean',
            'two_factor_expires_at'           => 'datetime',
        ];
    }

    // ── 2FA Helpers ───────────────────────────────────────────────────────────

    /** Generate & simpan OTP 6 digit, berlaku 10 menit */
    public function generateTwoFactorCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'two_factor_code'       => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);
        return $code;
    }

    /** Cek apakah OTP masih valid */
    public function isTwoFactorCodeValid(string $code): bool
    {
        return $this->two_factor_code === $code
            && $this->two_factor_expires_at
            && $this->two_factor_expires_at->isFuture();
    }

    /** Hapus OTP setelah berhasil diverifikasi */
    public function clearTwoFactorCode(): void
    {
        $this->update([
            'two_factor_code'       => null,
            'two_factor_expires_at' => null,
        ]);
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

    public function telegramMessages()
    {
        return $this->hasMany(TelegramMessage::class);
    }

    public function budgets()
    {
        return $this->hasMany(\App\Models\Budget::class);
    }

    public function goals()
    {
        return $this->hasMany(\App\Models\Goal::class);
    }

    public function recurringTransactions()
    {
        return $this->hasMany(\App\Models\RecurringTransaction::class);
    }

    public function debts()
    {
        return $this->hasMany(\App\Models\Debt::class);
    }

    public function hasTelegram(): bool
    {
        return !empty($this->telegram_id);
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
