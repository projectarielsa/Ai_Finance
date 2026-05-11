<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Monthly reports — tanggal 1 jam 08:00 ─────────────────────────────────────
Schedule::command('finance:send-monthly-reports')->monthlyOn(1, '08:00');

// ── Recurring transactions — setiap hari jam 07:00 ───────────────────────────
Schedule::job(new \App\Jobs\ProcessRecurringTransactions)->dailyAt('07:00')->name('process-recurring');

// ── Budget alerts — setiap 4 jam ─────────────────────────────────────────────
Schedule::job(new \App\Jobs\CheckBudgetAlerts)->everyFourHours()->name('check-budget-alerts');

// ── Daily reminder — cek setiap jam, dispatch ke user yang jam reminder-nya = sekarang ──
// Jalankan setiap jam penuh; job sendiri yang filter berdasarkan daily_reminder_time user
Schedule::call(function () {
    $currentHour = now()->format('H'); // "21", "08", dst
    \App\Jobs\DailyReminderJob::dispatch($currentHour)->onQueue('notifications');
})->hourly()->name('daily-reminders');

// ── Weekly summary — setiap Senin jam 07:30 ──────────────────────────────────
Schedule::job(new \App\Jobs\WeeklySummaryJob)
    ->weeklyOn(1, '07:30') // 1 = Senin
    ->name('weekly-summary')
    ->onQueue('notifications');

// ── Minimum balance check — setiap hari jam 09:00 ────────────────────────────
Schedule::call(function () {
    \App\Models\User::where('is_active', true)
        ->where('telegram_notifications', true)
        ->whereNotNull('telegram_id')
        ->each(function ($user) {
            $lowBalanceWallets = $user->wallets()
                ->where('is_active', true)
                ->where('include_in_total', true)
                ->where('balance', '<', $user->minimum_balance_warning)
                ->get();

            if ($lowBalanceWallets->isNotEmpty()) {
                $telegram = app(\App\Services\TelegramBotService::class);
                foreach ($lowBalanceWallets as $wallet) {
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "⚠️ *Peringatan Saldo Rendah!*\nWallet *{$wallet->name}* hanya tersisa Rp" .
                        number_format($wallet->balance, 0, ',', '.')
                    );
                }
            }
        });
})->dailyAt('09:00')->name('check-minimum-balance');
