<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename whatsapp_message_id → telegram_message_id in transactions.
     * Remove whatsapp_* values from source enum (replaced by telegram_* values).
     */
    public function up(): void
    {
        // 1. Rename column whatsapp_message_id → telegram_message_id
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('whatsapp_message_id', 'telegram_message_id');
        });

        // 2. Migrate any existing whatsapp_* source values to telegram_* equivalents
        DB::statement("UPDATE transactions SET source = 'telegram_text'  WHERE source = 'whatsapp_text'");
        DB::statement("UPDATE transactions SET source = 'telegram_image' WHERE source = 'whatsapp_image'");
        DB::statement("UPDATE transactions SET source = 'telegram_voice' WHERE source = 'whatsapp_voice'");

        // 3. Drop whatsapp_* values from the enum — keep only telegram + manual + import
        DB::statement("ALTER TABLE transactions MODIFY COLUMN source ENUM(
            'manual',
            'telegram_text',
            'telegram_image',
            'telegram_voice',
            'import'
        ) NOT NULL DEFAULT 'manual'");
    }

    public function down(): void
    {
        // Restore whatsapp_* enum values
        DB::statement("ALTER TABLE transactions MODIFY COLUMN source ENUM(
            'manual',
            'whatsapp_text',
            'whatsapp_image',
            'whatsapp_voice',
            'telegram_text',
            'telegram_image',
            'telegram_voice',
            'import'
        ) NOT NULL DEFAULT 'manual'");

        // Rename column back
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('telegram_message_id', 'whatsapp_message_id');
        });
    }
};
