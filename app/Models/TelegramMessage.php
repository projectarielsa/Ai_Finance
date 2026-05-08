<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'telegram_user_id', 'chat_id', 'message_id',
        'direction', 'type', 'content', 'media_path', 'raw_payload',
        'status', 'error_message', 'transaction_id', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'sent_at'     => 'datetime',
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

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? asset('storage/' . $this->media_path) : null;
    }
}
