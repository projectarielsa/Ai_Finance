<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_gateway_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id')->nullable()->unique();
            $table->string('sender_phone', 20);
            $table->string('receiver_phone', 20)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->enum('type', ['text', 'image', 'audio', 'voice', 'document', 'sticker'])->default('text');
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_path')->nullable(); // local path after download
            $table->string('media_mime_type')->nullable();
            $table->bigInteger('media_size')->nullable();
            $table->json('raw_payload')->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'sent', 'delivered', 'read'])->default('received');
            $table->text('error_message')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sender_phone', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
