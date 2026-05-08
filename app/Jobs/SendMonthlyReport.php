<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected User $user, protected int $year, protected int $month) {}

    public function handle(TelegramBotService $telegram): void
    {
        if (!$this->user->telegram_id) return;

        $income  = $this->user->transactions()->completed()->byMonth($this->year, $this->month)->where('type', 'income')->sum('amount');
        $expense = $this->user->transactions()->completed()->byMonth($this->year, $this->month)->where('type', 'expense')->sum('amount');
        $balance = $this->user->total_balance;
        $period  = now()->setYear($this->year)->setMonth($this->month)->format('F Y');
        $net     = $income - $expense;

        $msg  = "📊 *Laporan Keuangan {$period}*\n\n";
        $msg .= "✅ Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
        $msg .= "❌ Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
        $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: Rp" . number_format($net, 0, ',', '.') . "\n";
        $msg .= "💰 Total Saldo: Rp" . number_format($balance, 0, ',', '.') . "\n\n";
        $msg .= "Lihat laporan lengkap: " . config('app.url');

        $telegram->sendMessage($this->user->telegram_id, $msg);
    }
}
