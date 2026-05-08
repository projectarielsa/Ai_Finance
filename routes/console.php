<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly reports on the 1st of each month at 08:00
Schedule::command('finance:send-monthly-reports')->monthlyOn(1, '08:00');

// Check minimum balance daily at 09:00
Schedule::call(function () {
    \App\Models\User::where('is_active', true)
        ->where('whatsapp_notifications', true)
        ->whereNotNull('phone')
        ->each(function ($user) {
            $lowBalanceWallets = $user->wallets()
                ->where('is_active', true)
                ->where('include_in_total', true)
                ->where('balance', '<', $user->minimum_balance_warning)
                ->get();
            if ($lowBalanceWallets->isNotEmpty()) {
                $service = app(\App\Services\WhatsAppService::class);
                foreach ($lowBalanceWallets as $wallet) {
                    $service->sendMessage($user->phone,
                        "⚠️ *Peringatan Saldo Rendah!*\nWallet {$wallet->name} hanya tersisa Rp" .
                        number_format($wallet->balance, 0, ',', '.'));
                }
            }
        });
})->dailyAt('09:00')->name('check-minimum-balance');
