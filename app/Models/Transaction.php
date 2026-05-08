<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'user_id', 'wallet_id', 'target_wallet_id', 'category_id',
        'type', 'amount', 'fee', 'currency', 'description', 'notes',
        'merchant', 'reference_number', 'tags', 'attachment',
        'transaction_date', 'source', 'ai_confidence', 'ai_raw_response',
        'ai_parsed_data', 'status', 'whatsapp_message_id',
        'is_duplicate', 'duplicate_of',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'ai_confidence' => 'decimal:2',
            'transaction_date' => 'datetime',
            'tags' => 'array',
            'ai_parsed_data' => 'json',
            'is_duplicate' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function targetWallet()
    {
        return $this->belongsTo(Wallet::class, 'target_wallet_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function whatsappMessage()
    {
        return $this->belongsTo(WhatsappMessage::class, 'whatsapp_message_id', 'message_id');
    }

    public function receiptScan()
    {
        return $this->hasOne(ReceiptScan::class);
    }

    public function voiceNoteTranscription()
    {
        return $this->hasOne(VoiceNoteTranscription::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? asset('storage/' . $this->attachment) : null;
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByMonth($query, int $year, int $month)
    {
        return $query->whereYear('transaction_date', $year)
                     ->whereMonth('transaction_date', $month);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'income' => 'green',
            'expense' => 'red',
            'transfer' => 'blue',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'income' => '↑',
            'expense' => '↓',
            'transfer' => '⇄',
            default => '•',
        };
    }
}
