<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('message_id')->nullable(); // telegram_message record id (no FK — table renamed)
            $table->string('image_path');
            $table->string('merchant_name')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->datetime('receipt_date')->nullable();
            $table->json('items')->nullable();
            $table->string('detected_category')->nullable();
            $table->string('detected_wallet')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->text('ai_raw_response')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed', 'confirmed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('needs_wallet_confirmation')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_scans');
    }
};
