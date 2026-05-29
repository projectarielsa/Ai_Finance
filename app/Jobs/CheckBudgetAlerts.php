<?php

namespace App\Jobs;

use App\Models\Budget;
use App\Models\Transaction;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckBudgetAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TelegramBotService $telegram): void
    {
        $now     = now();
        $budgets = Budget::where(function ($q) use ($now) {
                // Budget spesifik bulan ini
                $q->where('year', $now->year)->where('month', $now->month);
            })
            ->orWhere('is_recurring', true) // Budget recurring berlaku setiap bulan
            ->with(['user', 'category'])
            ->get();

        if ($budgets->isEmpty()) return;

        // Aggregate spent amounts in a single query — avoids N+1
        $userIds     = $budgets->pluck('user_id')->unique()->all();
        $categoryIds = $budgets->pluck('category_id')->filter()->unique()->all();

        $spentMap = Transaction::whereIn('user_id', $userIds)
            ->whereIn('category_id', $categoryIds)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->selectRaw('user_id, category_id, SUM(amount) as total')
            ->groupBy('user_id', 'category_id')
            ->get()
            ->mapWithKeys(fn($row) => ["{$row->user_id}_{$row->category_id}" => (float)$row->total]);

        foreach ($budgets as $budget) {
            $user = $budget->user;
            if (!$user->telegram_id || !$user->telegram_notifications) continue;

            $spent      = $spentMap["{$budget->user_id}_{$budget->category_id}"] ?? 0.0;
            $percentage = $budget->limit_amount > 0
                ? round(($spent / $budget->limit_amount) * 100, 1)
                : 0;

            $cat   = $budget->category?->name ?? 'Semua';
            $limit = 'Rp' . number_format($budget->limit_amount, 0, ',', '.');
            $spentFmt = 'Rp' . number_format($spent, 0, ',', '.');

            // Alert 80%
            if ($budget->alert_at_80 && !$budget->alert_sent_80 && $percentage >= 80 && $percentage < 100) {
                try {
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "⚠️ *Peringatan Budget 80%*\n\n" .
                        "Kategori: *{$cat}*\n" .
                        "Terpakai: {$spentFmt} ({$percentage}%)\n" .
                        "Batas: {$limit}\n\n" .
                        "_Sisa: Rp" . number_format(max(0, $budget->limit_amount - $spent), 0, ',', '.') . "_\n" .
                        "Hemat pengeluaran agar tidak melebihi budget! 💪"
                    );
                    $budget->update(['alert_sent_80' => true]);
                } catch (\Throwable $e) {
                    Log::warning("BudgetAlert 80% user#{$user->id}: " . $e->getMessage());
                }
            }

            // Alert 100%
            if ($budget->alert_at_100 && !$budget->alert_sent_100 && $percentage >= 100) {
                try {
                    $over = 'Rp' . number_format(max(0, $spent - $budget->limit_amount), 0, ',', '.');
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "🚨 *Budget Terlampaui!*\n\n" .
                        "Kategori: *{$cat}*\n" .
                        "Terpakai: {$spentFmt} ({$percentage}%)\n" .
                        "Batas: {$limit}\n" .
                        "Kelebihan: *{$over}*\n\n" .
                        "Pertimbangkan untuk meninjau pengeluaran Anda."
                    );
                    $budget->update(['alert_sent_100' => true]);
                } catch (\Throwable $e) {
                    Log::warning("BudgetAlert 100% user#{$user->id}: " . $e->getMessage());
                }
            }
        }
    }
}
