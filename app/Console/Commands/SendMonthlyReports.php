<?php

namespace App\Console\Commands;

use App\Jobs\SendMonthlyReport;
use App\Models\User;
use Illuminate\Console\Command;

class SendMonthlyReports extends Command
{
    protected $signature   = 'finance:send-monthly-reports {--month=} {--year=}';
    protected $description = 'Send monthly financial reports via Telegram to all linked users';

    public function handle(): void
    {
        $month = (int)($this->option('month') ?? now()->subMonth()->month);
        $year  = (int)($this->option('year')  ?? now()->subMonth()->year);

        // Only dispatch for users who have linked their Telegram account
        $users = User::where('is_active', true)
                     ->whereNotNull('telegram_id')
                     ->get();

        foreach ($users as $user) {
            SendMonthlyReport::dispatch($user, $year, $month)->onQueue('notifications');
        }

        $this->info("Dispatched {$users->count()} monthly report jobs via Telegram for {$month}/{$year}");
    }
}
