<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'wallet_id', 'type', 'contact_name', 'contact_phone',
        'amount', 'paid_amount', 'description', 'due_date', 'debt_date',
        'status', 'notify_on_due', 'notified_due', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'paid_amount'    => 'decimal:2',
            'due_date'       => 'date',
            'debt_date'      => 'date',
            'notify_on_due'  => 'boolean',
            'notified_due'   => 'boolean',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────
    public function user()     { return $this->belongsTo(User::class); }
    public function wallet()   { return $this->belongsTo(Wallet::class); }
    public function payments() { return $this->hasMany(DebtPayment::class)->orderByDesc('payment_date'); }

    // ── Computed Attributes ───────────────────────────────────────────────────
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float)$this->amount - (float)$this->paid_amount);
    }

    public function getPercentageAttribute(): float
    {
        if ($this->amount <= 0) return 0;
        return min(100, round(($this->paid_amount / $this->amount) * 100, 1));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) return null;
        return (int) now()->diffInDays($this->due_date, false);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'receivable' ? 'Piutang' : 'Hutang';
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type === 'receivable' ? 'green' : 'red';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active'    => 'Aktif',
            'partial'   => 'Bayar Sebagian',
            'paid'      => 'Lunas',
            'cancelled' => 'Dibatalkan',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active'    => 'blue',
            'partial'   => 'yellow',
            'paid'      => 'green',
            'cancelled' => 'gray',
            default     => 'gray',
        };
    }

    // ── Business Logic ────────────────────────────────────────────────────────

    /** Tambah pembayaran dan update status otomatis */
    public function addPayment(float $amount): void
    {
        $newPaid = min((float)$this->amount, (float)$this->paid_amount + $amount);
        $status  = $newPaid >= (float)$this->amount ? 'paid' : 'partial';
        $this->update(['paid_amount' => $newPaid, 'status' => $status]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────
    public function scopeActive($q)      { return $q->whereIn('status', ['active', 'partial']); }
    public function scopePaid($q)        { return $q->where('status', 'paid'); }
    public function scopeReceivable($q)  { return $q->where('type', 'receivable'); }
    public function scopePayable($q)     { return $q->where('type', 'payable'); }
    public function scopeOverdue($q)     { return $q->whereIn('status', ['active', 'partial'])->whereNotNull('due_date')->where('due_date', '<', now()->toDateString()); }
    public function scopeDueSoon($q, int $days = 7) {
        return $q->whereIn('status', ['active', 'partial'])
                 ->whereNotNull('due_date')
                 ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
