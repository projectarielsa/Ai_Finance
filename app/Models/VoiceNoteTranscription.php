<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceNoteTranscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'transaction_id', 'message_id', 'audio_path',
        'audio_format', 'duration_seconds', 'transcription',
        'transcription_provider', 'confidence_score', 'status', 'error_message',
    ];

    protected function casts(): array
    {
        return ['confidence_score' => 'decimal:2'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function telegramMessage()
    {
        return $this->belongsTo(TelegramMessage::class, 'message_id');
    }
}
