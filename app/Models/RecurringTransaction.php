<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'wallet_id', 'target_wallet_id', 'category_id',
        'title', 'type', 'amount', 'frequency',
        'start_date', 'end_date', 'next_run_date', 'last_run_date',
        'description', 'merchant', 'is_active', 'auto_execute',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'start_date'    => 'date',
            'end_date'      => 'date',
            'next_run_date' => 'date',
            'last_run_date' => 'date',
            'is_active'     => 'boolean',
            'auto_execute'  => 'boolean',
        ];
    }

    public function user()         { return $this->belongsTo(User::class); }
    public function wallet()        { return $this->belongsTo(Wallet::class); }
    public function targetWallet()  { return $this->belongsTo(Wallet::class, 'target_wallet_id'); }
    public function category()      { return $this->belongsTo(Category::class); }

    /** Hitung next_run_date berikutnya setelah run */
    public function calculateNextRunDate(): \Carbon\Carbon
    {
        $base = $this->next_run_date->copy();
        return match ($this->frequency) {
            'daily'   => $base->addDay(),
            'weekly'  => $base->addWeek(),
            'monthly' => $base->addMonth(),
            'yearly'  => $base->addYear(),
        };
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'daily'   => 'Harian',
            'weekly'  => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly'  => 'Tahunan',
        };
    }

    public function isDue(): bool
    {
        return $this->is_active && $this->next_run_date->lte(now()->toDateString());
    }
}
