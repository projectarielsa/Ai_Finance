<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send monthly reports on the 1st of each month at 08:00 via Telegram
Schedule::command('finance:send-monthly-reports')->monthlyOn(1, '08:00');

// Process recurring transactions — run every day at 07:00
Schedule::job(new \App\Jobs\ProcessRecurringTransactions)->dailyAt('07:00')->name('process-recurring');

// Check budget alerts — run every 4 hours
Schedule::job(new \App\Jobs\CheckBudgetAlerts)->everyFourHours()->name('check-budget-alerts');

// Check minimum balance daily at 09:00 — send Telegram notification
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
