<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = [
        'debt_id', 'wallet_id', 'transaction_id',
        'amount', 'payment_date', 'notes', 'source',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function debt()        { return $this->belongsTo(Debt::class); }
    public function wallet()      { return $this->belongsTo(Wallet::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
