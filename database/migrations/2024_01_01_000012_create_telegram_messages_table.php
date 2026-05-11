<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telegram_user_id', 50)->nullable()->index();
            $table->string('chat_id', 50)->index();
            $table->string('message_id', 50)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->enum('type', ['text', 'photo', 'voice', 'audio', 'document', 'sticker', 'unknown'])->default('text');
            $table->text('content')->nullable();
            $table->string('media_path')->nullable();
            $table->json('raw_payload')->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'sent'])->default('received');
            $table->text('error_message')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['chat_id', 'created_at']);
        });

        // Note: telegram_id and telegram_username columns are already defined
        // in the create_users_table migration (0001_01_01_000000)
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
        // Note: telegram_id and telegram_username are dropped with the users table itself
    }
};
