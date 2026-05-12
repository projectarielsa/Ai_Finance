<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_note_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('message_id')->nullable(); // telegram_message record id (no FK — table renamed)
            $table->string('audio_path');
            $table->string('audio_format')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('transcription')->nullable();
            $table->string('transcription_provider')->default('grok'); // grok, openai, google
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->enum('status', ['pending', 'transcribed', 'parsed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_note_transcriptions');
    }
};
