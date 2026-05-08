<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add telegram_text, telegram_image, telegram_voice to transactions.source enum.
     * Also renames whatsapp_message_id column to message_id for generic use.
     */
    public function up(): void
    {
        // Modify the enum on MySQL/MariaDB
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
    }

    public function down(): void
    {
        // Revert to original enum (Telegram values become 'manual' via default)
        DB::statement("ALTER TABLE transactions MODIFY COLUMN source ENUM(
            'manual',
            'whatsapp_text',
            'whatsapp_image',
            'whatsapp_voice',
            'import'
        ) NOT NULL DEFAULT 'manual'");
    }
};
