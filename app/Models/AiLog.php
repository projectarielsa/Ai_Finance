<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'provider', 'model', 'type', 'prompt', 'response',
        'prompt_tokens', 'completion_tokens', 'total_tokens', 'cost',
        'success', 'error_message', 'duration_ms', 'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'cost' => 'decimal:6',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
