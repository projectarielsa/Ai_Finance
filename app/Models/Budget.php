<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'limit_amount', 'month', 'year',
        'alert_at_80', 'alert_at_100', 'alert_sent_80', 'alert_sent_100', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'limit_amount'   => 'decimal:2',
            'alert_at_80'    => 'boolean',
            'alert_at_100'   => 'boolean',
            'alert_sent_80'  => 'boolean',
            'alert_sent_100' => 'boolean',
        ];
    }

    public function user()    { return $this->belongsTo(User::class); }
    public function category(){ return $this->belongsTo(Category::class); }

    /** Berapa yang sudah dipakai bulan ini */
    public function getSpentAttribute(): float
    {
        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereYear('transaction_date', $this->year)
            ->whereMonth('transaction_date', $this->month)
            ->sum('amount');
    }

    /** Persentase penggunaan (0–100+) */
    public function getPercentageAttribute(): float
    {
        if ($this->limit_amount <= 0) return 0;
        return round(($this->spent / $this->limit_amount) * 100, 1);
    }

    /** Sisa budget */
    public function getRemainingAttribute(): float
    {
        return max(0, $this->limit_amount - $this->spent);
    }

    public function scopeForMonth($q, int $year, int $month)
    {
        return $q->where('year', $year)->where('month', $month);
    }
}
