<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BigTransactionAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $transactionId,
        public readonly int $userId
    ) {}

    public function handle(TelegramBotService $telegram): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user || !$user->telegram_id || !$user->telegram_notifications) return;
            if (!$user->big_transaction_alert_enabled) return;

            $tx = Transaction::with(['wallet', 'category'])->find($this->transactionId);
            if (!$tx) return;

            // Hanya alert untuk expense & transfer di atas threshold
            if (!in_array($tx->type, ['expense', 'transfer'])) return;
            if ($tx->amount < $user->big_transaction_threshold) return;

            $amount   = 'Rp' . number_format($tx->amount, 0, ',', '.');
            $wallet   = $tx->wallet->name;
            $category = $tx->category?->name ?? 'Tidak ada kategori';
            $desc     = $tx->description ?? $tx->merchant ?? $category;
            $balance  = 'Rp' . number_format($tx->wallet->balance, 0, ',', '.');
            $typeIcon = $tx->type === 'transfer' ? '🔄' : '💸';
            $typeText = $tx->type === 'transfer' ? 'Transfer' : 'Pengeluaran';

            $msg  = "⚠️ *Alert Transaksi Besar!*\n\n";
            $msg .= "{$typeIcon} *{$typeText}*: {$amount}\n";
            $msg .= "📁 Kategori: {$category}\n";
            $msg .= "💳 Wallet: {$wallet}\n";
            $msg .= "📝 Deskripsi: " . \Illuminate\Support\Str::limit($desc, 40) . "\n";
            $msg .= "💰 Sisa saldo {$wallet}: {$balance}\n\n";
            $msg .= "_Bukan transaksimu? Cek di web untuk hapus._";

            $telegram->sendMessage($user->telegram_id, $msg);

        } catch (\Throwable $e) {
            Log::error("BigTransactionAlertJob failed for tx #{$this->transactionId}: " . $e->getMessage());
        }
    }
}
