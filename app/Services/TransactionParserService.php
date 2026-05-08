<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

class TransactionParserService
{
    public function __construct(
        protected GrokAIService $grokAI,
        protected WhatsAppService $whatsApp
    ) {}

    /**
     * Parse & save a transaction from a WhatsApp text message.
     */
    public function parseAndSave(string $message, User $user, ?string $messageId = null): array
    {
        $wallets    = $user->wallets()->where('is_active', true)->get();
        $categories = Category::where(function($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();

        $parsed = $this->grokAI->parseTransaction(
            $message, $user,
            $wallets->toArray(),
            $categories->toArray()
        );

        if (!empty($parsed['error']) || empty($parsed['amount'])) {
            return ['success' => false, 'message' => 'Pesan tidak dikenali sebagai transaksi keuangan.', 'parsed' => $parsed];
        }

        // Find wallet
        $wallet = $this->findWallet($parsed['wallet'] ?? '', $wallets);
        if (!$wallet) {
            return ['success' => false, 'message' => "Wallet \"{$parsed['wallet']}\" tidak ditemukan. Wallet Anda: " . $wallets->pluck('name')->join(', ')];
        }

        $targetWallet = null;
        if ($parsed['type'] === 'transfer') {
            // Auto-detect: "tarik tunai dari BRI" => target = Cash
            $targetName  = $parsed['target_wallet'] ?? 'Cash';
            $targetWallet = $this->findWallet($targetName, $wallets);
            if (!$targetWallet) {
                return ['success' => false, 'message' => "Wallet tujuan \"{$targetName}\" tidak ditemukan."];
            }
        }

        $amount = (float) ($parsed['amount'] ?? 0);

        // Validate balance
        if (in_array($parsed['type'], ['expense', 'transfer']) && !$wallet->hasSufficientBalance($amount)) {
            return [
                'success'  => false,
                'message'  => "Saldo {$wallet->name} tidak cukup untuk transaksi Rp" . number_format($amount, 0, ',', '.') . ". Saldo saat ini: Rp" . number_format($wallet->balance, 0, ',', '.'),
                'balance_error' => true,
            ];
        }

        $category = $this->findCategory($parsed['category'] ?? '', $categories, $parsed['type']);

        // Create transaction
        $transaction = $this->createTransaction([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'target_wallet_id' => $targetWallet?->id,
            'category_id'      => $category?->id,
            'type'             => $parsed['type'],
            'amount'           => $amount,
            'description'      => $parsed['description'] ?? $message,
            'merchant'         => $parsed['merchant'] ?? null,
            'transaction_date' => now(),
            'source'           => 'whatsapp_text',
            'ai_confidence'    => $parsed['confidence'] ?? null,
            'ai_raw_response'  => json_encode($parsed),
            'ai_parsed_data'   => $parsed,
            'status'           => 'completed',
            'whatsapp_message_id' => $messageId,
        ]);

        // Update wallet balances
        $this->updateWalletBalance($transaction, $wallet, $targetWallet);

        return [
            'success'     => true,
            'transaction' => $transaction,
            'wallet'      => $wallet,
            'parsed'      => $parsed,
            'message'     => $this->buildSuccessMessage($transaction, $wallet, $targetWallet, $category),
        ];
    }

    protected function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    protected function updateWalletBalance(Transaction $t, Wallet $wallet, ?Wallet $targetWallet): void
    {
        match($t->type) {
            'income'   => $wallet->credit($t->amount),
            'expense'  => $wallet->debit($t->amount),
            'transfer' => (function() use ($wallet, $targetWallet, $t) {
                $wallet->debit($t->amount);
                $targetWallet?->credit($t->amount);
            })(),
        };
    }

    protected function findWallet(string $name, $wallets): ?Wallet
    {
        if (empty($name)) return $wallets->first();
        $name = strtolower(trim($name));

        // Exact match
        $found = $wallets->first(fn($w) => strtolower($w->name) === $name
            || strtolower($w->provider ?? '') === $name);
        if ($found) return $found;

        // Alias match
        foreach ($wallets as $w) {
            $aliases = $w->ai_aliases ?? [];
            if (in_array($name, array_map('strtolower', $aliases))) {
                return $w;
            }
        }

        // Partial match
        return $wallets->first(fn($w) =>
            str_contains(strtolower($w->name), $name) ||
            str_contains($name, strtolower($w->name))
        );
    }

    protected function findCategory(string $name, $categories, string $type): ?Category
    {
        if (empty($name)) return $categories->where('type', $type)->first();
        $name = strtolower(trim($name));
        return $categories->first(fn($c) =>
            strtolower($c->name) === $name ||
            str_contains(strtolower($c->name), $name) ||
            str_contains($name, strtolower($c->name))
        );
    }

    protected function buildSuccessMessage(Transaction $t, Wallet $wallet, ?Wallet $targetWallet, ?Category $category): string
    {
        $amount = 'Rp' . number_format($t->amount, 0, ',', '.');
        $date   = $t->transaction_date->format('d M Y');
        $icon   = match($t->type) { 'income' => '✅', 'expense' => '✅', 'transfer' => '🔄' };

        if ($t->type === 'transfer') {
            return "{$icon} Transfer berhasil dicatat!\nDari: {$wallet->name}\nKe: {$targetWallet?->name}\nJumlah: {$amount}\nTanggal: {$date}";
        }

        $typeText = $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $msg = "{$icon} {$typeText} berhasil dicatat!\nJumlah: {$amount}\nWallet: {$wallet->name}";
        if ($category) $msg .= "\nKategori: {$category->name}";
        if ($t->description) $msg .= "\nDeskripsi: {$t->description}";
        $msg .= "\nTanggal: {$date}";
        return $msg;
    }

    /**
     * Parse nominal from Indonesian text.
     */
    public static function parseAmount(string $text): float
    {
        $text = strtolower(trim($text));
        $text = str_replace([',', '.'], ['', ''], $text);

        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:juta|jt)/i', $text, $m)) {
            return (float)$m[1] * 1_000_000;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ribu|rb|k)/i', $text, $m)) {
            return (float)$m[1] * 1_000;
        }
        if (preg_match('/(\d+)/', $text, $m)) {
            return (float)$m[1];
        }
        return 0;
    }
}
