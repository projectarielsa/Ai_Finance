<?php

namespace App\Services;

use App\Models\ReceiptScan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ReceiptScannerService
{
    public function __construct(protected GrokAIService $grokAI) {}

    /**
     * Process a receipt image from Telegram or WhatsApp.
     * $telegramMsgId / $whatsappMsgId — pass whichever is available.
     */
    public function processReceipt(string $imagePath, User $user, ?int $telegramMsgId = null): array
    {
        // Read image
        $imageContent = Storage::disk('public')->get($imagePath);
        if (!$imageContent) {
            return ['success' => false, 'message' => '❌ Gambar tidak dapat dibaca. Coba kirim ulang.'];
        }

        $mimeType    = $this->detectMimeType($imagePath);
        $imageBase64 = base64_encode($imageContent);

        // Call Vision AI
        $scanned = $this->grokAI->scanReceipt($imageBase64, $mimeType, $user);

        // Save receipt scan record
        $receiptScan = ReceiptScan::create([
            'user_id'                   => $user->id,
            'whatsapp_message_id'       => $telegramMsgId, // reuse column for both platforms
            'image_path'                => $imagePath,
            'merchant_name'             => $scanned['merchant_name'] ?? null,
            'total_amount'              => $scanned['total_amount'] ?? null,
            'receipt_date'              => !empty($scanned['receipt_date'])
                                            ? date('Y-m-d', strtotime($scanned['receipt_date']))
                                            : now()->toDateString(),
            'items'                     => $scanned['items'] ?? null,
            'detected_category'         => $scanned['category'] ?? null,
            'detected_wallet'           => $scanned['detected_wallet'] ?? null,
            'confidence_score'          => $scanned['confidence'] ?? 0,
            'ai_raw_response'           => json_encode($scanned),
            'status'                    => 'processed',
            'needs_wallet_confirmation' => empty($scanned['detected_wallet']),
        ]);

        // AI error or no amount detected
        if (!empty($scanned['error']) || empty($scanned['total_amount'])) {
            $receiptScan->update([
                'status'        => 'failed',
                'error_message' => $scanned['error'] ?? 'Jumlah tidak terdeteksi',
            ]);
            return [
                'success' => false,
                'message' => "❌ Struk tidak dapat dibaca.\n\nPastikan:\n• Foto jelas & tidak buram\n• Angka total terlihat\n• Bukan foto screenshot",
            ];
        }

        $amount    = (float) $scanned['total_amount'];
        $amountFmt = number_format($amount, 0, ',', '.');
        $merchant  = $scanned['merchant_name'] ?? null;
        $category  = $scanned['category'] ?? 'Belanja';

        // ── SELALU tampilkan keyboard pilih wallet ─────────────────────────
        // Apapun kondisinya (wallet terdeteksi atau tidak), kita selalu
        // minta user konfirmasi via tombol. Ini mencegah salah wallet.
        $receiptScan->update(['needs_wallet_confirmation' => true]);

        $merchantText = $merchant ? "\nMerchant: *{$merchant}*" : '';
        $dateText     = !empty($scanned['receipt_date'])
                        ? "\nTanggal: " . date('d M Y', strtotime($scanned['receipt_date']))
                        : '';

        return [
            'success'      => false,
            'needs_wallet' => true,
            'receipt_scan' => $receiptScan,
            'amount'       => $amount,
            'message'      => "📋 *Struk berhasil dibaca!*" .
                              $merchantText .
                              "\nTotal: *Rp{$amountFmt}*" .
                              "\nKategori: {$category}" .
                              $dateText .
                              "\n\n💳 *Pembayaran menggunakan wallet apa?*",
        ];
    }

    /**
     * Confirm wallet choice for a pending receipt scan.
     * Called when user picks a wallet (inline keyboard or text reply).
     */
    public function confirmWallet(ReceiptScan $receiptScan, string $walletName, User $user): array
    {
        $wallets = $user->wallets()->where('is_active', true)->get();
        $wallet  = $this->findWallet($walletName, $wallets);

        if (!$wallet) {
            $list = $wallets->pluck('name')->join(', ');
            return [
                'success' => false,
                'message' => "❌ Wallet *\"{$walletName}\"* tidak ditemukan.\n\nWallet Anda: {$list}",
            ];
        }

        $scanned     = json_decode($receiptScan->ai_raw_response, true) ?? [];
        $transaction = $this->createTransactionFromScan($scanned, $user, $wallet, $receiptScan);

        if (!$transaction) {
            $amount = number_format($receiptScan->total_amount, 0, ',', '.');
            return [
                'success' => false,
                'message' => "⚠️ Saldo *{$wallet->name}* tidak cukup untuk transaksi Rp{$amount}.",
            ];
        }

        $amount   = number_format($receiptScan->total_amount, 0, ',', '.');
        $merchant = $receiptScan->merchant_name;
        $date     = $receiptScan->receipt_date
            ? date('d M Y', strtotime($receiptScan->receipt_date))
            : now()->format('d M Y');

        return [
            'success'     => true,
            'transaction' => $transaction,
            'message'     => "✅ *Transaksi berhasil dicatat!*" .
                             ($merchant ? "\nMerchant: {$merchant}" : '') .
                             "\nTotal: Rp{$amount}" .
                             "\nWallet: {$wallet->name}" .
                             "\nTanggal: {$date}",
        ];
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    protected function createTransactionFromScan(
        array $scanned,
        User $user,
        Wallet $wallet,
        ReceiptScan $receiptScan
    ): ?Transaction {
        $amount = (float) ($scanned['total_amount'] ?? 0);

        if ($amount <= 0) return null;

        if (!$wallet->hasSufficientBalance($amount)) {
            return null;
        }

        // Find best-matching category
        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();

        $catName  = strtolower($scanned['category'] ?? '');
        $category = $categories->first(function ($c) use ($catName) {
            return str_contains(strtolower($c->name), $catName) ||
                   str_contains($catName, strtolower($c->name));
        }) ?? $categories->where('type', 'expense')->first();

        $transaction = Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'category_id'      => $category?->id,
            'type'             => 'expense',
            'amount'           => $amount,
            'description'      => 'Belanja di ' . ($scanned['merchant_name'] ?? 'Unknown'),
            'merchant'         => $scanned['merchant_name'] ?? null,
            'attachment'       => $receiptScan->image_path,
            'transaction_date' => $receiptScan->receipt_date ?? now(),
            'source'           => 'whatsapp_image', // covers both Telegram & WA image
            'ai_confidence'    => $scanned['confidence'] ?? null,
            'ai_raw_response'  => json_encode($scanned),
            'status'           => 'completed',
        ]);

        $wallet->debit($amount);
        $receiptScan->update([
            'transaction_id' => $transaction->id,
            'status'         => 'confirmed',
        ]);

        return $transaction;
    }

    protected function findWallet(string $name, $wallets): ?Wallet
    {
        $name = strtolower(trim($name));
        if (empty($name)) return null;

        // Exact match
        $w = $wallets->first(fn ($w) => strtolower($w->name) === $name);
        if ($w) return $w;

        // Provider match
        $w = $wallets->first(fn ($w) => strtolower($w->provider ?? '') === $name);
        if ($w) return $w;

        // Alias match
        foreach ($wallets as $w) {
            if (in_array($name, array_map('strtolower', $w->ai_aliases ?? []))) return $w;
        }

        // Contains
        $w = $wallets->first(fn ($w) => str_contains(strtolower($w->name), $name));
        if ($w) return $w;

        return $wallets->first(fn ($w) => str_contains($name, strtolower($w->name)));
    }

    protected function detectMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/jpeg',
        };
    }
}
