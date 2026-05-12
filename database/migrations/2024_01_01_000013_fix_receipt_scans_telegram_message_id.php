<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Migration ini sebelumnya mencoba drop FK whatsapp_message_id dan rename ke message_id.
 * Namun migration 000009 & 000010 sudah langsung mendefinisikan kolom message_id (tanpa FK),
 * sehingga migration ini tidak perlu melakukan apa-apa pada fresh install.
 *
 * Dibiarkan kosong agar tidak crash pada environment baru maupun yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: kolom message_id sudah dibuat dengan benar di migration 000009 & 000010
    }

    public function down(): void
    {
        // No-op
    }
};
