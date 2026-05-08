<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_scans', function (Blueprint $table) {
            // Drop the FK constraint so we can store telegram_message_id too
            $table->dropForeign(['whatsapp_message_id']);
            // Rename to generic message_id (nullable unsignedBigInteger — no FK)
            $table->renameColumn('whatsapp_message_id', 'message_id');
        });

        Schema::table('voice_note_transcriptions', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_message_id']);
            $table->renameColumn('whatsapp_message_id', 'message_id');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_scans', function (Blueprint $table) {
            $table->renameColumn('message_id', 'whatsapp_message_id');
            $table->foreign('whatsapp_message_id')
                  ->references('id')->on('whatsapp_messages')
                  ->nullOnDelete();
        });

        Schema::table('voice_note_transcriptions', function (Blueprint $table) {
            $table->renameColumn('message_id', 'whatsapp_message_id');
            $table->foreign('whatsapp_message_id')
                  ->references('id')->on('whatsapp_messages')
                  ->nullOnDelete();
        });
    }
};
