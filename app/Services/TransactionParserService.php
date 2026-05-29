<?php

namespace App\Services;

use App\Jobs\BigTransactionAlertJob;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
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
        $confidence = (int) ($parsed['confidence'] ?? 0);

        // ── Low confidence → return for confirmation ──────────────────────
        // If AI is not confident enough, don't save directly — ask user first
        if ($confidence > 0 && $confidence < 70) {
            return [
                'success'            => false,
                'needs_confirmation' => true,
                'parsed'             => $parsed,
                'amount'             => $amount,
                'type'               => $type,
                'confidence'         => $confidence,
                'message'            => null, // will be built by the caller
            ];
        }

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
                    'transaction_date' => $this->resolveTransactionDate($parsed),
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
     * Parse a message WITHOUT saving — returns parsed data for confirmation flow.
     * Used when confidence is low and we need user confirmation first.
     */
    public function parseOnly(string $message, User $user): array
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
            return [
                'success'    => false,
                'is_transaction' => false,
                'parsed'     => $parsed,
            ];
        }

        $amount = (float) ($parsed['amount'] ?? 0);
        $type   = $parsed['type'] ?? 'expense';

        // Find wallet
        $walletName = $parsed['wallet'] ?? '';
        $wallet     = $this->findWallet($walletName, $wallets);
        if (!$wallet && $wallets->count() === 1) {
            $wallet = $wallets->first();
        }

        // Find target wallet (transfer)
        $targetWallet = null;
        if ($type === 'transfer') {
            $targetName   = $parsed['target_wallet'] ?? 'Cash';
            $targetWallet = $this->findWallet($targetName, $wallets);
            if (!$targetWallet) {
                $targetWallet = $wallets->first(fn ($w) => strtolower($w->name) === 'cash');
            }
        }

        // Find category
        $category = $this->findCategory($parsed['category'] ?? '', $categories, $type);

        return [
            'success'        => true,
            'is_transaction' => true,
            'parsed'         => $parsed,
            'amount'         => $amount,
            'type'           => $type,
            'wallet'         => $wallet,
            'target_wallet'  => $targetWallet,
            'category'       => $category,
            'confidence'     => (int) ($parsed['confidence'] ?? 0),
        ];
    }

    /**
     * Save a transaction from previously parsed data (after user confirmation).
     * Called when user confirms a low-confidence transaction.
     */
    public function saveFromParsed(array $pendingData, User $user, string $source = 'telegram_text'): array
    {
        $parsed       = $pendingData['parsed'];
        $amount       = (float) ($pendingData['amount'] ?? $parsed['amount'] ?? 0);
        $type         = $pendingData['type'] ?? $parsed['type'] ?? 'expense';
        $walletId     = $pendingData['wallet_id'] ?? null;
        $targetWalletId = $pendingData['target_wallet_id'] ?? null;
        $categoryId   = $pendingData['category_id'] ?? null;
        $message      = $parsed['original_message'] ?? $parsed['description'] ?? '';

        $wallets = $user->wallets()->where('is_active', true)->get();

        // Resolve wallet
        $wallet = $walletId ? $wallets->firstWhere('id', $walletId) : null;
        if (!$wallet) {
            $wallet = $this->findWallet($parsed['wallet'] ?? '', $wallets);
        }
        if (!$wallet && $wallets->count() === 1) {
            $wallet = $wallets->first();
        }
        if (!$wallet) {
            return ['success' => false, 'message' => '❌ Wallet tidak ditemukan.'];
        }

        // Resolve target wallet
        $targetWallet = null;
        if ($type === 'transfer') {
            $targetWallet = $targetWalletId ? $wallets->firstWhere('id', $targetWalletId) : null;
            if (!$targetWallet) {
                $targetWallet = $this->findWallet($parsed['target_wallet'] ?? 'Cash', $wallets);
            }
            if (!$targetWallet) {
                $targetWallet = $wallets->first(fn ($w) => strtolower($w->name) === 'cash');
            }
        }

        // Resolve category
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();
        $category = $categoryId ? $categories->firstWhere('id', $categoryId) : null;
        if (!$category) {
            $category = $this->findCategory($parsed['category'] ?? '', $categories, $type);
        }

        try {
            $result = DB::transaction(function () use (
                $user, $wallet, $targetWallet, $category,
                $type, $amount, $parsed, $message, $source
            ) {
                $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);
                if ($targetWallet) {
                    $targetWallet = Wallet::lockForUpdate()->findOrFail($targetWallet->id);
                }

                if (in_array($type, ['expense', 'transfer']) && !$wallet->hasSufficientBalance($amount)) {
                    return [
                        'success'       => false,
                        'balance_error' => true,
                        'message'       => "⚠️ Saldo *{$wallet->name}* tidak cukup.\n" .
                                           "Saldo: Rp" . number_format($wallet->balance, 0, ',', '.') . "\n" .
                                           "Dibutuhkan: Rp" . number_format($amount, 0, ',', '.'),
                    ];
                }

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
                    'transaction_date' => $this->resolveTransactionDate($parsed),
                    'source'           => $source,
                    'ai_confidence'    => $parsed['confidence'] ?? null,
                    'ai_raw_response'  => json_encode($parsed),
                    'ai_parsed_data'   => $parsed,
                    'status'           => 'completed',
                    'is_duplicate'     => $duplicateOf !== null,
                    'duplicate_of'     => $duplicateOf,
                ]);

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
                    'message'     => $this->buildSuccessMessage($transaction, $wallet, $targetWallet, $category),
                ];
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '❌ Gagal menyimpan transaksi. Coba lagi.'];
        }

        return $result;
    }

    /**
     * Store pending transaction data in cache for confirmation flow.
     * Returns a unique key to reference the pending data.
     */
    public function storePendingConfirmation(int $userId, array $data): string
    {
        $key = "pending_tx:{$userId}:" . uniqid();
        Cache::put($key, $data, now()->addMinutes(10)); // expires in 10 minutes
        return $key;
    }

    /**
     * Retrieve and delete pending transaction data from cache.
     */
    public function getPendingConfirmation(string $key): ?array
    {
        $data = Cache::get($key);
        if ($data) {
            Cache::forget($key);
        }
        return $data;
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

    /**
     * Resolve transaction date/time from AI parsed data.
     * If AI detected a specific date, use it. Otherwise default to now().
     */
    protected function resolveTransactionDate(array $parsed): string
    {
        $date = $parsed['transaction_date'] ?? null;
        $time = $parsed['transaction_time'] ?? null;

        // No date detected → use current datetime
        if (empty($date)) {
            return now()->toDateTimeString();
        }

        // Validate date format
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return now()->toDateTimeString();
        }

        // Combine date + time if available
        if ($time && preg_match('/^\d{1,2}[:.]\d{2}$/', $time)) {
            $time = str_replace('.', ':', $time);
            $dateTime = date('Y-m-d', $timestamp) . ' ' . $time . ':00';
            if (strtotime($dateTime) !== false) {
                return $dateTime;
            }
        }

        // Date only → use current time of day
        return date('Y-m-d', $timestamp) . ' ' . now()->format('H:i:s');
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
        $time   = $t->transaction_date->format('H:i');

        // Tampilkan tanggal + jam jika bukan hari ini
        $dateDisplay = $t->transaction_date->isToday()
            ? "Hari ini, {$time}"
            : "{$date} {$time}";

        $dupWarning = $t->is_duplicate ? "\n\n⚠️ _Terdeteksi sebagai kemungkinan duplikat. Cek di web jika perlu dihapus._" : '';

        if ($t->type === 'transfer') {
            return "🔄 *Transfer berhasil dicatat!*\nDari: {$wallet->name}\nKe: {$targetWallet?->name}\nJumlah: {$amount}\nTanggal: {$dateDisplay}{$dupWarning}";
        }

        $icon     = $t->type === 'income' ? '💰' : '💸';
        $typeText = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $msg      = "{$icon} *{$typeText} berhasil dicatat!*\nJumlah: {$amount}\nWallet: {$wallet->name}";
        if ($category) $msg .= "\nKategori: {$category->name}";
        if ($t->description) $msg .= "\nDeskripsi: {$t->description}";
        if ($t->merchant)    $msg .= "\nMerchant: {$t->merchant}";
        $msg .= "\nTanggal: {$dateDisplay}";
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
