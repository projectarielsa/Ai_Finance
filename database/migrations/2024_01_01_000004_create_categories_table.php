<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->default('tag');
            $table->string('color', 7)->default('#6366f1');
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system categories can't be deleted
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('ai_keywords')->nullable(); // keywords AI uses to auto-detect
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
