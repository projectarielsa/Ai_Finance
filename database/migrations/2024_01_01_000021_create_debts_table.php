<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete(); // wallet terkait pembayaran

            // Tipe: 'receivable' = piutang (orang hutang ke kita), 'payable' = hutang (kita hutang ke orang)
            $table->enum('type', ['receivable', 'payable']);

            $table->string('contact_name');           // nama orang yang berhutang / kita hutang ke
            $table->string('contact_phone')->nullable();
            $table->decimal('amount', 15, 2);          // total pokok hutang/piutang
            $table->decimal('paid_amount', 15, 2)->default(0); // sudah dibayar
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();       // jatuh tempo
            $table->date('debt_date');                 // tanggal hutang dibuat
            $table->enum('status', ['active', 'partial', 'paid', 'cancelled'])->default('active');
            $table->boolean('notify_on_due')->default(true);
            $table->boolean('notified_due')->default(false); // sudah kirim notif jatuh tempo?
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
