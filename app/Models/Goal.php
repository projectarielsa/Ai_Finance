<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'wallet_id', 'title', 'description',
        'target_amount', 'current_amount', 'target_date',
        'icon', 'color', 'status', 'completed_at', 'notify_on_milestone',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'        => 'decimal:2',
            'current_amount'       => 'decimal:2',
            'target_date'          => 'date',
            'completed_at'         => 'datetime',
            'notify_on_milestone'  => 'boolean',
        ];
    }

    public function user()   { return $this->belongsTo(User::class); }
    public function wallet() { return $this->belongsTo(Wallet::class); }

    public function getPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->current_amount / $this->target_amount) * 100, 1));
    }

    public function getRemainingAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->target_date) return null;
        $diff = now()->diffInDays($this->target_date, false);
        return (int) $diff;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active'    => 'blue',
            'completed' => 'green',
            'paused'    => 'yellow',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }

    /** Menambah dana ke goal (juga update wallet jika ada) */
    public function addFunds(float $amount): void
    {
        $this->increment('current_amount', $amount);
        $this->refresh();
        if ($this->current_amount >= $this->target_amount && $this->status === 'active') {
            $this->update(['status' => 'completed', 'completed_at' => now()]);
        }
    }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
