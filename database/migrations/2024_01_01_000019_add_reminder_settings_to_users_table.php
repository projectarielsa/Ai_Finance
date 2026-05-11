<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reminder harian — "Sudah catat hari ini?"
            $table->boolean('daily_reminder_enabled')->default(false)->after('telegram_notifications');
            $table->string('daily_reminder_time', 5)->default('21:00')->after('daily_reminder_enabled'); // HH:MM

            // Summary mingguan — setiap Senin pagi
            $table->boolean('weekly_summary_enabled')->default(false)->after('daily_reminder_time');

            // Alert transaksi besar
            $table->boolean('big_transaction_alert_enabled')->default(true)->after('weekly_summary_enabled');
            $table->decimal('big_transaction_threshold', 15, 2)->default(200000)->after('big_transaction_alert_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_reminder_enabled',
                'daily_reminder_time',
                'weekly_summary_enabled',
                'big_transaction_alert_enabled',
                'big_transaction_threshold',
            ]);
        });
    }
};
