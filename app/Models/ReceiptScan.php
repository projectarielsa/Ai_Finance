<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'transaction_id', 'whatsapp_message_id', 'image_path',
        'merchant_name', 'total_amount', 'receipt_date', 'items',
        'detected_category', 'detected_wallet', 'confidence_score',
        'ai_raw_response', 'status', 'error_message', 'needs_wallet_confirmation',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'receipt_date' => 'datetime',
            'items' => 'array',
            'needs_wallet_confirmation' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function whatsappMessage()
    {
        return $this->belongsTo(WhatsappMessage::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
