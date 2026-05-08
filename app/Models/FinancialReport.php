<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'period_type', 'period_start', 'period_end',
        'total_income', 'total_expense', 'total_transfer', 'net_cashflow',
        'income_by_category', 'expense_by_category', 'wallet_balances',
        'top_merchants', 'ai_insight', 'ai_recommendation', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'total_income' => 'decimal:2',
            'total_expense' => 'decimal:2',
            'total_transfer' => 'decimal:2',
            'net_cashflow' => 'decimal:2',
            'income_by_category' => 'array',
            'expense_by_category' => 'array',
            'wallet_balances' => 'array',
            'top_merchants' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
