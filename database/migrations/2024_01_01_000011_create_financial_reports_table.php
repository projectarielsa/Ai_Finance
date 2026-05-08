<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly', 'custom']);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('total_transfer', 15, 2)->default(0);
            $table->decimal('net_cashflow', 15, 2)->default(0);
            $table->json('income_by_category')->nullable();
            $table->json('expense_by_category')->nullable();
            $table->json('wallet_balances')->nullable();
            $table->json('top_merchants')->nullable();
            $table->text('ai_insight')->nullable();
            $table->text('ai_recommendation')->nullable();
            $table->string('file_path')->nullable(); // exported PDF/Excel
            $table->timestamps();

            $table->index(['user_id', 'period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
