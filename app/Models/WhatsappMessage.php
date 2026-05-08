<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'whatsapp_gateway_id', 'message_id', 'sender_phone',
        'receiver_phone', 'direction', 'type', 'content', 'media_url',
        'media_path', 'media_mime_type', 'media_size', 'raw_payload',
        'status', 'error_message', 'transaction_id', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(WhatsappGateway::class, 'whatsapp_gateway_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function receiptScans()
    {
        return $this->hasMany(ReceiptScan::class);
    }

    public function voiceNoteTranscription()
    {
        return $this->hasOne(VoiceNoteTranscription::class);
    }

    public function getMediaUrlFullAttribute(): ?string
    {
        if ($this->media_path) {
            return asset('storage/' . $this->media_path);
        }
        return $this->media_url;
    }
}
