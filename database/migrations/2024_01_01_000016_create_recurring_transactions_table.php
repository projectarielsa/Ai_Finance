<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->string('description')->nullable();
            $table->string('merchant')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_execute')->default(true); // otomatis catat
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
