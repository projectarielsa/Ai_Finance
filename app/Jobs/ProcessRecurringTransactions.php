<?php

namespace App\Jobs;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRecurringTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TelegramBotService $telegram): void
    {
        $due = RecurringTransaction::where('is_active', true)
            ->where('auto_execute', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->with(['user', 'wallet', 'category'])
            ->get();

        foreach ($due as $recurring) {
            try {
                DB::transaction(function () use ($recurring) {
                    $wallet = Wallet::lockForUpdate()->findOrFail($recurring->wallet_id);

                    if (in_array($recurring->type, ['expense', 'transfer'])) {
                        if (!$wallet->hasSufficientBalance((float)$recurring->amount)) {
                            Log::warning("Recurring #{$recurring->id}: insufficient balance, skipping.");
                            return;
                        }
                    }

                    Transaction::create([
                        'user_id'          => $recurring->user_id,
                        'wallet_id'        => $recurring->wallet_id,
                        'target_wallet_id' => $recurring->target_wallet_id,
                        'category_id'      => $recurring->category_id,
                        'type'             => $recurring->type,
                        'amount'           => $recurring->amount,
                        'description'      => $recurring->title . ' (otomatis)',
                        'merchant'         => $recurring->merchant,
                        'transaction_date' => now(),
                        'source'           => 'manual',
                        'status'           => 'completed',
                    ]);

                    match ($recurring->type) {
                        'income'   => $wallet->credit($recurring->amount),
                        'expense'  => $wallet->debit($recurring->amount),
                        'transfer' => (function () use ($recurring, $wallet): void {
                            $wallet->debit($recurring->amount);
                            $targetWallet = Wallet::lockForUpdate()->find($recurring->target_wallet_id);
                            $targetWallet?->credit($recurring->amount);
                        })(),
                    };

                    $nextDate = $recurring->calculateNextRunDate()->toDateString();
                    $updates  = ['last_run_date' => now()->toDateString(), 'next_run_date' => $nextDate];

                    // Nonaktifkan jika sudah melewati end_date
                    if ($recurring->end_date && $nextDate > $recurring->end_date->toDateString()) {
                        $updates['is_active'] = false;
                    }

                    $recurring->update($updates);
                });

                // Kirim notifikasi Telegram
                $user = $recurring->user;
                if ($user->telegram_id && $user->telegram_notifications) {
                    $icon      = match ($recurring->type) { 'income' => '💰', 'expense' => '💸', default => '🔄' };
                    $amount    = 'Rp' . number_format($recurring->amount, 0, ',', '.');
                    $freshData = $recurring->fresh();
                    $nextLabel = $freshData?->next_run_date?->format('d M Y') ?? '-';
                    $telegram->sendMessage(
                        $user->telegram_id,
                        "{$icon} *Transaksi Berulang Dicatat*\n" .
                        "{$recurring->title}\n" .
                        "Jumlah: {$amount}\n" .
                        "Wallet: {$recurring->wallet->name}\n" .
                        "_Jadwal berikutnya: {$nextLabel}_"
                    );
                }
            } catch (\Throwable $e) {
                Log::error("ProcessRecurring #{$recurring->id} failed: " . $e->getMessage());
            }
        }
    }
}
