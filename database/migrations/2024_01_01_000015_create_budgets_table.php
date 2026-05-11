<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('limit_amount', 15, 2);
            $table->tinyInteger('month');        // 1–12, null = berlaku semua bulan
            $table->year('year');
            $table->boolean('alert_at_80')->default(true);
            $table->boolean('alert_at_100')->default(true);
            $table->boolean('alert_sent_80')->default(false);
            $table->boolean('alert_sent_100')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
