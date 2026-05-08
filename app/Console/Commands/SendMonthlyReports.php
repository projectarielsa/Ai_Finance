<?php

namespace App\Console\Commands;

use App\Jobs\SendMonthlyReport;
use App\Models\User;
use Illuminate\Console\Command;

class SendMonthlyReports extends Command
{
    protected $signature   = 'finance:send-monthly-reports {--month=} {--year=}';
    protected $description = 'Send monthly financial reports via WhatsApp to all users';

    public function handle(): void
    {
        $month = (int)($this->option('month') ?? now()->subMonth()->month);
        $year  = (int)($this->option('year') ?? now()->subMonth()->year);

        $users = User::where('is_active', true)
                     ->where('whatsapp_notifications', true)
                     ->whereNotNull('phone')
                     ->get();

        foreach ($users as $user) {
            SendMonthlyReport::dispatch($user, $year, $month)->onQueue('notifications');
        }

        $this->info("Dispatched {$users->count()} monthly report jobs for {$month}/{$year}");
    }
}
