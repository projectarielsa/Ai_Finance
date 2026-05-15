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

class SendDailySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(TelegramBotService $telegram): void
    {
        // Get all users with Telegram linked and notifications enabled
        $users = User::where('is_active', true)
            ->where('telegram_notifications', true)
            ->whereNotNull('telegram_id')
            ->get();

        foreach ($users as $user) {
            try {
                $this->sendSummaryToUser($user, $telegram);
            } catch (\Throwable $e) {
                Log::error("SendDailySummaryJob failed for user #{$user->id}: " . $e->getMessage());
            }
        }
    }

    protected function sendSummaryToUser(User $user, TelegramBotService $telegram): void
    {
        $tz    = $user->timezone ?? 'Asia/Jakarta';
        $today = now()->timezone($tz)->toDateString();

        // Today's transactions
        $todayExpense = $user->transactions()
            ->whereDate('transaction_date', $today)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        $todayIncome = $user->transactions()
            ->whereDate('transaction_date', $today)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $todayCount = $user->transactions()
            ->whereDate('transaction_date', $today)
            ->where('status', 'completed')
            ->count();

        // Skip if user had zero activity today (no spam)
        if ($todayCount === 0) {
            return;
        }

        // Top categories today
        $topCategories = $user->transactions()
            ->whereDate('transaction_date', $today)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->limit(3)
            ->get();

        // Monthly totals so far
        $now          = now()->timezone($tz);
        $monthExpense = $user->transactions()
            ->completed()
            ->where('type', 'expense')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->sum('amount');

        $monthIncome = $user->transactions()
            ->completed()
            ->where('type', 'income')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->sum('amount');

        // Yesterday comparison
        $yesterday        = now()->timezone($tz)->subDay()->toDateString();
        $yesterdayExpense = $user->transactions()
            ->whereDate('transaction_date', $yesterday)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        // Build message
        $dateLabel = now()->timezone($tz)->translatedFormat('l, d M Y');
        $msg  = "🌙 *Ringkasan Harian*\n";
        $msg .= "_{$dateLabel}_\n";
        $msg .= str_repeat("─", 25) . "\n\n";

        // Today's stats
        if ($todayExpense > 0) {
            $msg .= "💸 Pengeluaran: *Rp" . number_format($todayExpense, 0, ',', '.') . "*\n";
        }
        if ($todayIncome > 0) {
            $msg .= "💰 Pemasukan: *Rp" . number_format($todayIncome, 0, ',', '.') . "*\n";
        }
        $msg .= "📝 Total transaksi: {$todayCount}\n";

        // Comparison with yesterday
        if ($yesterdayExpense > 0 && $todayExpense > 0) {
            $diff = $todayExpense - $yesterdayExpense;
            if ($diff > 0) {
                $msg .= "📈 _+" . number_format($diff, 0, ',', '.') . " dari kemarin_\n";
            } elseif ($diff < 0) {
                $msg .= "📉 _" . number_format($diff, 0, ',', '.') . " dari kemarin_ 👍\n";
            }
        }

        // Top categories
        if ($topCategories->isNotEmpty()) {
            $msg .= "\n*Top Pengeluaran:*\n";
            foreach ($topCategories as $i => $t) {
                $name = $t->category?->name ?? 'Lainnya';
                $msg .= "  " . ($i + 1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.') . " ({$t->count}x)\n";
            }
        }

        // Monthly progress
        $msg .= "\n📅 *Bulan ini:*\n";
        $msg .= "  Pengeluaran: Rp" . number_format($monthExpense, 0, ',', '.') . "\n";
        $msg .= "  Pemasukan: Rp" . number_format($monthIncome, 0, ',', '.') . "\n";
        $net = $monthIncome - $monthExpense;
        $msg .= "  Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n";

        // Days remaining in month
        $daysLeft = $now->daysInMonth - $now->day;
        if ($daysLeft > 0 && $monthExpense > 0) {
            $avgDaily = round($monthExpense / $now->day);
            $msg .= "\n💡 _Rata-rata harian: Rp" . number_format($avgDaily, 0, ',', '.') . "/hari_";
        }

        $telegram->sendMessage($user->telegram_id, $msg);
    }
}
