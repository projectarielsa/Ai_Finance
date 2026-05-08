<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider'); // wablas, fonnte, whacenter, custom
            $table->string('base_url');
            $table->text('api_key'); // encrypted
            $table->string('device_id')->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['connected', 'disconnected', 'unknown'])->default('unknown');
            $table->timestamp('last_connected_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_gateways');
    }
};
