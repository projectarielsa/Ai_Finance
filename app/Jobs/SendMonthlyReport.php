<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected User $user, protected int $year, protected int $month) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        if (!$this->user->phone || !$this->user->whatsapp_notifications) return;

        $income  = $this->user->transactions()->completed()->byMonth($this->year, $this->month)->where('type','income')->sum('amount');
        $expense = $this->user->transactions()->completed()->byMonth($this->year, $this->month)->where('type','expense')->sum('amount');
        $balance = $this->user->total_balance;
        $period  = now()->setYear($this->year)->setMonth($this->month)->format('F Y');

        $msg = "📊 *Laporan Keuangan Bulan {$period}*\n\n";
        $msg .= "✅ Total Pemasukan: Rp" . number_format($income,0,',','.') . "\n";
        $msg .= "❌ Total Pengeluaran: Rp" . number_format($expense,0,',','.') . "\n";
        $msg .= "📈 Cashflow: Rp" . number_format($income - $expense,0,',','.') . "\n";
        $msg .= "💰 Total Saldo: Rp" . number_format($balance,0,',','.') . "\n\n";
        $msg .= "Lihat laporan lengkap di: " . config('app.url');

        $whatsApp->sendMessage($this->user->phone, $msg);
    }
}
