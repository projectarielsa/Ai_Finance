<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

class TransactionParserService
{
    public function __construct(
        protected GrokAIService $grokAI
    ) {}

    /**
     * Parse & save a transaction from any text message (Telegram/WhatsApp).
     */
    public function parseAndSave(string $message, User $user, ?string $messageId = null): array
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
                // Try fallback to Cash
                $targetWallet = $wallets->first(fn ($w) => strtolower($w->name) === 'cash');
            }
            if (!$targetWallet) {
                return [
                    'success' => false,
                    'message' => "⚠️ Wallet tujuan \"{$targetName}\" tidak ditemukan.\nWallet Anda: " . $wallets->pluck('name')->join(', '),
                ];
            }
        }

        // ── Balance check ─────────────────────────────────────────────────
        if (in_array($type, ['expense', 'transfer']) && !$wallet->hasSufficientBalance($amount)) {
            return [
                'success'       => false,
                'balance_error' => true,
                'message'       => "⚠️ Saldo *{$wallet->name}* tidak cukup.\nSaldo: Rp" . number_format($wallet->balance, 0, ',', '.') . "\nDibutuhkan: Rp" . number_format($amount, 0, ',', '.'),
            ];
        }

        // ── Find category ─────────────────────────────────────────────────
        $category = $this->findCategory($parsed['category'] ?? '', $categories, $type);

        // ── Create transaction ────────────────────────────────────────────
        $transaction = Transaction::create([
            'user_id'             => $user->id,
            'wallet_id'           => $wallet->id,
            'target_wallet_id'    => $targetWallet?->id,
            'category_id'         => $category?->id,
            'type'                => $type,
            'amount'              => $amount,
            'description'         => $parsed['description'] ?? $message,
            'merchant'            => $parsed['merchant'] ?? null,
            'transaction_date'    => now(),
            'source'              => 'whatsapp_text', // generic — covers both Telegram & WA
            'ai_confidence'       => $parsed['confidence'] ?? null,
            'ai_raw_response'     => json_encode($parsed),
            'ai_parsed_data'      => $parsed,
            'status'              => 'completed',
            'whatsapp_message_id' => $messageId,
        ]);

        // ── Update wallet balances ────────────────────────────────────────
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
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function findWallet(string $name, $wallets): ?Wallet
    {
        if (empty(trim($name))) return null;

        $name = strtolower(trim($name));

        // 1. Exact name match
        $found = $wallets->first(fn ($w) => strtolower($w->name) === $name);
        if ($found) return $found;

        // 2. Exact provider match
        $found = $wallets->first(fn ($w) => strtolower($w->provider ?? '') === $name);
        if ($found) return $found;

        // 3. Alias match
        foreach ($wallets as $w) {
            $aliases = array_map('strtolower', $w->ai_aliases ?? []);
            if (in_array($name, $aliases)) return $w;
        }

        // 4. Name contains search term
        $found = $wallets->first(fn ($w) => str_contains(strtolower($w->name), $name));
        if ($found) return $found;

        // 5. Search term contains name (e.g. "gopay saya" → "gopay")
        $found = $wallets->first(fn ($w) => str_contains($name, strtolower($w->name)));
        if ($found) return $found;

        // 6. Provider contains search term
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

        if ($t->type === 'transfer') {
            return "🔄 *Transfer berhasil dicatat!*\nDari: {$wallet->name}\nKe: {$targetWallet?->name}\nJumlah: {$amount}\nTanggal: {$date}";
        }

        $icon     = $t->type === 'income' ? '💰' : '💸';
        $typeText = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $msg      = "{$icon} *{$typeText} berhasil dicatat!*\nJumlah: {$amount}\nWallet: {$wallet->name}";
        if ($category) $msg .= "\nKategori: {$category->name}";
        if ($t->description) $msg .= "\nDeskripsi: {$t->description}";
        if ($t->merchant) $msg .= "\nMerchant: {$t->merchant}";
        $msg .= "\nTanggal: {$date}";

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
