<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Monthly reports — tanggal 1 jam 08:00 ─────────────────────────────────────
Schedule::command('finance:send-monthly-reports')->monthlyOn(1, '08:00');

// ── Reset recurring budget alerts — tanggal 1 jam 00:05 ──────────────────────
Schedule::call(function () {
    \App\Models\Budget::where('is_recurring', true)->update([
        'alert_sent_80'  => false,
        'alert_sent_100' => false,
    ]);
})->monthlyOn(1, '00:05')->name('reset-recurring-budget-alerts');

// ── Recurring transactions — setiap hari jam 07:00 ───────────────────────────
Schedule::job(new \App\Jobs\ProcessRecurringTransactions)->dailyAt('07:00')->name('process-recurring');

// ── Budget alerts — setiap 4 jam ─────────────────────────────────────────────
Schedule::job(new \App\Jobs\CheckBudgetAlerts)->everyFourHours()->name('check-budget-alerts');

// ── Daily reminder — cek setiap jam ──────────────────────────────────────────
Schedule::call(function () {
    $currentHour = now()->format('H');
    \App\Jobs\DailyReminderJob::dispatch($currentHour);
})->hourly()->name('daily-reminders');

// ── Daily summary — setiap malam jam 21:00 WIB ───────────────────────────────
Schedule::job(new \App\Jobs\SendDailySummaryJob)
    ->dailyAt('21:00')
    ->timezone('Asia/Jakarta')
    ->name('daily-summary');

// ── Weekly summary — setiap Senin jam 07:30 ──────────────────────────────────
Schedule::job(new \App\Jobs\WeeklySummaryJob)
    ->weeklyOn(1, '07:30')
    ->name('weekly-summary');

// ── Minimum balance check — setiap hari jam 09:00 ────────────────────────────
Schedule::call(function () {
    \App\Models\User::where('is_active', true)
        ->where('telegram_notifications', true) // Menggunakan Telegram sesuai logic di bawahnya
        ->whereNotNull('telegram_id')
        ->where('minimum_balance_warning', '>', 0)
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