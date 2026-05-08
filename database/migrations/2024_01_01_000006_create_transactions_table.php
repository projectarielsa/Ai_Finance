<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('merchant')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('tags')->nullable();
            $table->string('attachment')->nullable(); // receipt image path
            $table->datetime('transaction_date');
            $table->enum('source', ['manual', 'whatsapp_text', 'whatsapp_image', 'whatsapp_voice', 'import'])->default('manual');
            $table->decimal('ai_confidence', 5, 2)->nullable(); // 0-100
            $table->text('ai_raw_response')->nullable();
            $table->json('ai_parsed_data')->nullable();
            $table->enum('status', ['completed', 'pending', 'failed', 'cancelled'])->default('completed');
            $table->string('whatsapp_message_id')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->foreignId('duplicate_of')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type']);
            $table->index(['wallet_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
