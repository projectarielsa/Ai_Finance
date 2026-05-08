<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['bank', 'e_wallet', 'cash', 'investment', 'credit_card', 'other'])->default('bank');
            $table->string('provider')->nullable(); // BCA, BRI, Mandiri, Gopay, etc.
            $table->string('icon')->nullable();
            $table->string('logo')->nullable(); // path to logo image
            $table->string('color', 7)->default('#3b82f6');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('account_number')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_total')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('ai_aliases')->nullable(); // alternative names AI can detect
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
