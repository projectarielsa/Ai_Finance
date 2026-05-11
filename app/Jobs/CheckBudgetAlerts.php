<?php

namespace App\Jobs;

use App\Models\Budget;
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
        $budgets = Budget::where('year', $now->year)
            ->where('month', $now->month)
            ->with(['user', 'category'])
            ->get();

        foreach ($budgets as $budget) {
            $user = $budget->user;
            if (!$user->telegram_id || !$user->telegram_notifications) continue;

            $percentage = $budget->percentage;
            $cat        = $budget->category?->name ?? 'Semua';
            $limit      = 'Rp' . number_format($budget->limit_amount, 0, ',', '.');
            $spent      = 'Rp' . number_format($budget->spent, 0, ',', '.');

            // Alert 80%
            if ($budget->alert_at_80 && !$budget->alert_sent_80 && $percentage >= 80 && $percentage < 100) {
                try {
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "⚠️ *Peringatan Budget 80%*\n\n" .
                        "Kategori: *{$cat}*\n" .
                        "Terpakai: {$spent} ({$percentage}%)\n" .
                        "Batas: {$limit}\n\n" .
                        "_Sisa: Rp" . number_format($budget->remaining, 0, ',', '.') . "_\n" .
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
                    $over = 'Rp' . number_format(max(0, $budget->spent - $budget->limit_amount), 0, ',', '.');
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "🚨 *Budget Terlampaui!*\n\n" .
                        "Kategori: *{$cat}*\n" .
                        "Terpakai: {$spent} ({$percentage}%)\n" .
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
