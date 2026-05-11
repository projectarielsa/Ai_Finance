<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WeeklySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TelegramBotService $telegram): void
    {
        $users = User::where('is_active', true)
            ->where('weekly_summary_enabled', true)
            ->where('telegram_notifications', true)
            ->whereNotNull('telegram_id')
            ->get();

        foreach ($users as $user) {
            try {
                $this->sendWeeklySummary($user, $telegram);
            } catch (\Throwable $e) {
                Log::error("WeeklySummaryJob failed for user #{$user->id}: " . $e->getMessage());
            }
        }
    }

    protected function sendWeeklySummary(User $user, TelegramBotService $telegram): void
    {
        $tz        = $user->timezone ?? 'Asia/Jakarta';
        $endDate   = now()->timezone($tz)->startOfDay();           // Senin hari ini (awal hari)
        $startDate = $endDate->copy()->subDays(7);                 // Senin minggu lalu

        $transactions = $user->transactions()
            ->completed()
            ->whereBetween('transaction_date', [$startDate, $endDate->copy()->endOfDay()->subDay()])
            ->with(['category', 'wallet'])
            ->get();

        if ($transactions->isEmpty()) {
            $telegram->sendMessage(
                $user->telegram_id,
                "📊 *Summary Mingguan*\n\nTidak ada transaksi minggu lalu.\nMulai catat dari sekarang yuk! 💪"
            );
            return;
        }

        $income   = $transactions->where('type', 'income')->sum('amount');
        $expense  = $transactions->where('type', 'expense')->sum('amount');
        $net      = $income - $expense;
        $totalTx  = $transactions->count();

        // Top 5 pengeluaran per kategori minggu ini
        $byCategory = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn($t) => [
                'name'  => $t->first()->category?->name ?? 'Lainnya',
                'total' => $t->sum('amount'),
                'count' => $t->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->take(5);

        $weekLabel = $startDate->format('d M') . ' – ' . $endDate->copy()->subDay()->format('d M Y');

        // ── Pesan ringkasan ───────────────────────────────────────────────
        $msg  = "📅 *SUMMARY MINGGUAN*\n";
        $msg .= "_" . $weekLabel . "_\n";
        $msg .= str_repeat("─", 28) . "\n\n";
        $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
        $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
        $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n";
        $msg .= "📝 Total Transaksi: {$totalTx}\n\n";

        // Pengeluaran per hari
        $perDay = $transactions->where('type', 'expense')
            ->groupBy(fn($t) => $t->transaction_date->format('D'))
            ->map(fn($t) => $t->sum('amount'))
            ->sortDesc();

        if ($perDay->isNotEmpty()) {
            $maxDay   = $perDay->keys()->first();
            $maxAmt   = $perDay->first();
            $dayNames = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
            $msg .= "📆 Paling boros: *" . ($dayNames[$maxDay] ?? $maxDay) . "* (Rp" . number_format($maxAmt, 0, ',', '.') . ")\n\n";
        }

        if ($byCategory->isNotEmpty()) {
            $msg .= "*Top Pengeluaran:*\n";
            foreach ($byCategory as $i => $cat) {
                $pct    = $expense > 0 ? round($cat['total'] / $expense * 100) : 0;
                $msg .= ($i + 1) . ". {$cat['name']}: Rp" . number_format($cat['total'], 0, ',', '.') . " ({$pct}%)\n";
            }
        }

        // Perbandingan dengan minggu sebelumnya
        $prevEnd   = $startDate->copy();
        $prevStart = $prevEnd->copy()->subDays(7);
        $prevExpense = $user->transactions()
            ->completed()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd->copy()->endOfDay()])
            ->sum('amount');

        if ($prevExpense > 0) {
            $diff    = $expense - $prevExpense;
            $diffPct = round(abs($diff) / $prevExpense * 100, 1);
            $arrow   = $diff > 0 ? "📈 naik" : "📉 turun";
            $msg .= "\n_Dibanding minggu lalu: {$arrow} {$diffPct}%_";
        }

        $msg .= "\n\n_Ketik /rekap untuk laporan bulanan lengkap_";

        $telegram->sendMessage($user->telegram_id, $msg);
    }
}
