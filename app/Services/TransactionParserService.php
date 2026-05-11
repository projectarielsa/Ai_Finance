<?php

namespace App\Services;

use App\Jobs\BigTransactionAlertJob;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class TransactionParserService
{
    public function __construct(
        protected GrokAIService $grokAI
    ) {}

    /**
     * Parse & save a transaction from any text message (Telegram/etc).
     *
     * @param string $source  Transaction source label, e.g. 'telegram_text' (default)
     */
    public function parseAndSave(string $message, User $user, ?string $messageId = null, string $source = 'telegram_text'): array
    {
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();

        // Call AI to parse
        $parsed = $this->grokAI->parseTransaction(
            $message, $user,
            $wallets->toArray(),
            $categories->toArray()
        );

        // AI failed / returned error
        if (!empty($parsed['error']) || empty($parsed['amount'])) {
            $reason = $parsed['error'] ?? 'Tidak terdeteksi sebagai transaksi';
            return [
                'success' => false,
                'message' => "❌ Tidak bisa diproses: {$reason}\n\nContoh format:\n• beli kopi 25rb gopay\n• gaji masuk 5jt bca\n• transfer 100rb dari bca ke gopay",
                'parsed'  => $parsed,
            ];
        }

        $amount = (float) ($parsed['amount'] ?? 0);
        $type   = $parsed['type'] ?? 'expense';

        // ── Find source wallet ────────────────────────────────────────────
        $walletName = $parsed['wallet'] ?? '';
        $wallet     = $this->findWallet($walletName, $wallets);

        // If wallet not found AND there's only 1 wallet, use it
        if (!$wallet && $wallets->count() === 1) {
            $wallet = $wallets->first();
        }

        // Still no wallet → ask user
        if (!$wallet) {
            $walletList = $wallets->pluck('name')->join(', ');
            return [
                'success'      => false,
                'needs_wallet' => true,
                'parsed'       => $parsed,
                'message'      => "⚠️ Wallet tidak ditemukan.\n\nWallet Anda: {$walletList}\n\nCoba ulangi dengan menyebutkan wallet, contoh:\n_beli kopi 25rb pakai *Gopay*_",
            ];
        }

        // ── Find target wallet (transfer) ─────────────────────────────────
        $targetWallet = null;
        if ($type === 'transfer') {
            $targetName   = $parsed['target_wallet'] ?? 'Cash';
            $targetWallet = $this->findWallet($targetName, $wallets);
            if (!$targetWallet) {
                $targetWallet = $wallets->first(fn ($w) => strtolower($w->name) === 'cash');
            }
            if (!$targetWallet) {
                return [
                    'success' => false,
                    'message' => "⚠️ Wallet tujuan \"{$targetName}\" tidak ditemukan.\nWallet Anda: " . $wallets->pluck('name')->join(', '),
                ];
            }
        }

        // ── Find category ─────────────────────────────────────────────────
        $category = $this->findCategory($parsed['category'] ?? '', $categories, $type);

        // ── Atomic: balance check + create + wallet update inside DB::transaction ──
        // Prevents race condition (TOCTOU) where two concurrent requests both pass
        // the balance check before either debit has been committed.
        try {
            $result = DB::transaction(function () use (
                $user, $wallet, $targetWallet, $category,
                $type, $amount, $parsed, $message, $source
            ) {
                // Re-fetch wallet with a row-level lock so no other request can
                // read/write the balance until this transaction commits.
                $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

                if ($targetWallet) {
                    $targetWallet = Wallet::lockForUpdate()->findOrFail($targetWallet->id);
                }

                // ── Balance check INSIDE the lock ─────────────────────────
                if (in_array($type, ['expense', 'transfer']) && !$wallet->hasSufficientBalance($amount)) {
                    return [
                        'success'       => false,
                        'balance_error' => true,
                        'message'       => "⚠️ Saldo *{$wallet->name}* tidak cukup.\n" .
                                           "Saldo: Rp" . number_format($wallet->balance, 0, ',', '.') . "\n" .
                                           "Dibutuhkan: Rp" . number_format($amount, 0, ',', '.') . "\n\n" .
                                           "Silakan top up wallet terlebih dahulu atau gunakan wallet lain.",
                    ];
                }

                // ── Create transaction ─────────────────────────────────────
                // ── Deteksi duplikat (transaksi sama dalam 5 menit terakhir) ──
                $duplicateOf = Transaction::where('user_id', $user->id)
                    ->where('type', $type)
                    ->where('amount', $amount)
                    ->where('wallet_id', $wallet->id)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->value('id');

                $transaction = Transaction::create([
                    'user_id'          => $user->id,
                    'wallet_id'        => $wallet->id,
                    'target_wallet_id' => $targetWallet?->id,
                    'category_id'      => $category?->id,
                    'type'             => $type,
                    'amount'           => $amount,
                    'description'      => $parsed['description'] ?? $message,
                    'merchant'         => $parsed['merchant'] ?? null,
                    'transaction_date' => now(),
                    'source'           => $source,
                    'ai_confidence'    => $parsed['confidence'] ?? null,
                    'ai_raw_response'  => json_encode($parsed),
                    'ai_parsed_data'   => $parsed,
                    'status'           => 'completed',
                    'is_duplicate'     => $duplicateOf !== null,
                    'duplicate_of'     => $duplicateOf,
                ]);

                // ── Update wallet balances ─────────────────────────────────
                match ($type) {
                    'income'   => $wallet->credit($amount),
                    'expense'  => $wallet->debit($amount),
                    'transfer' => (static function () use ($wallet, $targetWallet, $amount): void {
                        $wallet->debit($amount);
                        $targetWallet?->credit($amount);
                    })(),
                };

                return [
                    'success'     => true,
                    'transaction' => $transaction,
                    'wallet'      => $wallet,
                    'parsed'      => $parsed,
                    'message'     => $this->buildSuccessMessage($transaction, $wallet, $targetWallet, $category),
                    'is_duplicate'=> $duplicateOf !== null,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => '❌ Gagal menyimpan transaksi. Coba lagi.',
            ];
        }

        return $result;
    }

    /**
     * Dispatch BigTransactionAlertJob jika transaksi melebihi threshold user.
     * Dipanggil setelah transaksi berhasil disimpan.
     */
    public function maybeTriggerBigAlert(Transaction $transaction, User $user): void
    {
        if (!$user->big_transaction_alert_enabled) return;
        if (!$user->telegram_id)                   return;
        if (!in_array($transaction->type, ['expense', 'transfer'])) return;
        if ($transaction->amount < $user->big_transaction_threshold) return;

        BigTransactionAlertJob::dispatch($transaction->id, $user->id)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(2)); // delay 2s agar sukses message terkirim dulu
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function findWallet(string $name, $wallets): ?Wallet
    {
        if (empty(trim($name))) return null;

        $name = strtolower(trim($name));

        $found = $wallets->first(fn ($w) => strtolower($w->name) === $name);
        if ($found) return $found;

        $found = $wallets->first(fn ($w) => strtolower($w->provider ?? '') === $name);
        if ($found) return $found;

        foreach ($wallets as $w) {
            $aliases = array_map('strtolower', $w->ai_aliases ?? []);
            if (in_array($name, $aliases)) return $w;
        }

        $found = $wallets->first(fn ($w) => str_contains(strtolower($w->name), $name));
        if ($found) return $found;

        $found = $wallets->first(fn ($w) => str_contains($name, strtolower($w->name)));
        if ($found) return $found;

        return $wallets->first(fn ($w) => str_contains(strtolower($w->provider ?? ''), $name));
    }

    protected function findCategory(string $name, $categories, string $type): ?Category
    {
        if (empty(trim($name))) {
            return $categories->where('type', $type)->first();
        }
        $name = strtolower(trim($name));

        return $categories->first(fn ($c) =>
            strtolower($c->name) === $name ||
            str_contains(strtolower($c->name), $name) ||
            str_contains($name, strtolower($c->name))
        ) ?? $categories->where('type', $type)->first();
    }

    protected function buildSuccessMessage(Transaction $t, Wallet $wallet, ?Wallet $targetWallet, ?Category $category): string
    {
        $amount = 'Rp' . number_format($t->amount, 0, ',', '.');
        $date   = $t->transaction_date->format('d M Y');

        $dupWarning = $t->is_duplicate ? "\n\n⚠️ _Terdeteksi sebagai kemungkinan duplikat. Cek di web jika perlu dihapus._" : '';

        if ($t->type === 'transfer') {
            return "🔄 *Transfer berhasil dicatat!*\nDari: {$wallet->name}\nKe: {$targetWallet?->name}\nJumlah: {$amount}\nTanggal: {$date}{$dupWarning}";
        }

        $icon     = $t->type === 'income' ? '💰' : '💸';
        $typeText = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $msg      = "{$icon} *{$typeText} berhasil dicatat!*\nJumlah: {$amount}\nWallet: {$wallet->name}";
        if ($category) $msg .= "\nKategori: {$category->name}";
        if ($t->description) $msg .= "\nDeskripsi: {$t->description}";
        if ($t->merchant)    $msg .= "\nMerchant: {$t->merchant}";
        $msg .= "\nTanggal: {$date}";
        $msg .= $dupWarning;

        return $msg;
    }

    /** Parse nominal from Indonesian shorthand text. */
    public static function parseAmount(string $text): float
    {
        $text = strtolower(trim($text));
        $text = str_replace([',', '.'], ['', ''], $text);

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:juta|jt)/i', $text, $m)) {
            return (float) $m[1] * 1_000_000;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ribu|rb|k)/i', $text, $m)) {
            return (float) $m[1] * 1_000;
        }
        if (preg_match('/(\d+)/', $text, $m)) {
            return (float) $m[1];
        }
        return 0;
    }
}
