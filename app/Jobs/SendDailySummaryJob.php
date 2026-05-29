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
        $now   = now()->timezone($tz);

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

        // ── Daily average this month (for comparison) ─────────────────────
        $daysPassed = max($now->day, 1);
        $avgDaily   = $daysPassed > 1 ? round($monthExpense / $daysPassed) : $todayExpense;

        // ── 7-day average (spending pattern) ──────────────────────────────
        $weekAgo       = now()->timezone($tz)->subDays(7)->toDateString();
        $last7Expense  = $user->transactions()
            ->completed()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$weekAgo, $today])
            ->sum('amount');
        $avg7Day = round($last7Expense / 7);

        // ── Predicted end-of-month balance ────────────────────────────────
        $totalBalance = $user->wallets()->where('is_active', true)->where('include_in_total', true)->sum('balance');
        $daysLeft     = $now->daysInMonth - $now->day;
        $predictedEnd = $totalBalance - ($avgDaily * $daysLeft);

        // ── Budget warnings ───────────────────────────────────────────────
        $budgetWarnings = [];
        $budgets = \App\Models\Budget::where('user_id', $user->id)
            ->where(function ($q) use ($now) {
                $q->where(fn($q2) => $q2->where('year', $now->year)->where('month', $now->month))
                  ->orWhere('is_recurring', true);
            })
            ->with('category')
            ->get();

        foreach ($budgets as $budget) {
            $pct = $budget->percentage;
            if ($pct >= 80) {
                $budgetWarnings[] = [
                    'name' => $budget->category?->name ?? 'Lainnya',
                    'pct'  => $pct,
                ];
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // BUILD MESSAGE
        // ═══════════════════════════════════════════════════════════════════
        $dateLabel = $now->translatedFormat('l, d M Y');
        $msg  = "🌙 *Ringkasan Harian*\n";
        $msg .= "_{$dateLabel}_\n";
        $msg .= str_repeat("─", 25) . "\n\n";

        // ── Today's stats ─────────────────────────────────────────────────
        if ($todayExpense > 0) {
            $msg .= "💸 Pengeluaran: *Rp" . number_format($todayExpense, 0, ',', '.') . "*\n";
        }
        if ($todayIncome > 0) {
            $msg .= "💰 Pemasukan: *Rp" . number_format($todayIncome, 0, ',', '.') . "*\n";
        }
        $msg .= "📝 Total transaksi: {$todayCount}\n";

        // ── Smart comparison ──────────────────────────────────────────────
        if ($todayExpense > 0 && $avgDaily > 0) {
            $ratio = round(($todayExpense / $avgDaily) * 100);
            if ($ratio > 150) {
                $msg .= "🔴 _Hari ini {$ratio}% dari rata-rata harian — lebih boros dari biasanya!_\n";
            } elseif ($ratio > 110) {
                $msg .= "🟡 _Sedikit di atas rata-rata harian ({$ratio}%)_\n";
            } elseif ($ratio < 70) {
                $msg .= "🟢 _Hemat! Cuma {$ratio}% dari rata-rata harian_ 👏\n";
            }
        }

        // Yesterday vs today
        if ($yesterdayExpense > 0 && $todayExpense > 0) {
            $diff = $todayExpense - $yesterdayExpense;
            if ($diff > 0) {
                $msg .= "📈 _+" . number_format($diff, 0, ',', '.') . " dari kemarin_\n";
            } elseif ($diff < 0) {
                $msg .= "📉 _" . number_format(abs($diff), 0, ',', '.') . " lebih hemat dari kemarin_ 👍\n";
            }
        }

        // ── Top categories ────────────────────────────────────────────────
        if ($topCategories->isNotEmpty()) {
            $msg .= "\n*Top Pengeluaran:*\n";
            foreach ($topCategories as $i => $t) {
                $name = $t->category?->name ?? 'Lainnya';
                $msg .= "  " . ($i + 1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.') . " ({$t->count}x)\n";
            }
        }

        // ── Budget warnings ───────────────────────────────────────────────
        if (!empty($budgetWarnings)) {
            $msg .= "\n⚠️ *Budget hampir habis:*\n";
            foreach ($budgetWarnings as $bw) {
                $icon = $bw['pct'] >= 100 ? '🚨' : '⚠️';
                $msg .= "  {$icon} {$bw['name']}: {$bw['pct']}%\n";
            }
        }

        // ── Monthly progress ──────────────────────────────────────────────
        $msg .= "\n📅 *Progress bulan ini:*\n";
        $msg .= "  Pengeluaran: Rp" . number_format($monthExpense, 0, ',', '.') . "\n";
        $msg .= "  Pemasukan: Rp" . number_format($monthIncome, 0, ',', '.') . "\n";
        $net = $monthIncome - $monthExpense;
        $msg .= "  Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n";

        // ── Smart insights ────────────────────────────────────────────────
        $msg .= "\n💡 *Insight:*\n";
        $msg .= "  • Rata-rata harian: Rp" . number_format($avgDaily, 0, ',', '.') . "/hari\n";
        if ($avg7Day > 0) {
            $msg .= "  • Rata-rata 7 hari: Rp" . number_format($avg7Day, 0, ',', '.') . "/hari\n";
        }
        if ($daysLeft > 0) {
            $msg .= "  • Sisa " . $daysLeft . " hari lagi di bulan ini\n";
            if ($predictedEnd > 0) {
                $msg .= "  • Prediksi saldo akhir bulan: Rp" . number_format($predictedEnd, 0, ',', '.') . "\n";
            } else {
                $msg .= "  • ⚠️ Saldo diprediksi *minus* sebelum akhir bulan!\n";
            }
        }

        $telegram->sendMessage($user->telegram_id, $msg);
    }
}
