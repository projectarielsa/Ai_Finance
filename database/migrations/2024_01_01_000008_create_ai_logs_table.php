<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('grok');
            $table->string('model')->nullable();
            $table->enum('type', ['transaction_parse', 'receipt_scan', 'voice_transcription', 'financial_insight', 'chat', 'other'])->default('other');
            $table->text('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable(); // response time
            $table->string('reference_type')->nullable(); // transaction, receipt_scan, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
