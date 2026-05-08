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
     * Process a receipt image from WhatsApp.
     */
    public function processReceipt(string $imagePath, User $user, ?int $whatsappMessageId = null): array
    {
        // Read image and encode to base64
        $imageContent = Storage::disk('public')->get($imagePath);
        if (!$imageContent) {
            return ['success' => false, 'message' => 'Gambar tidak dapat dibaca'];
        }

        $mimeType    = $this->detectMimeType($imagePath);
        $imageBase64 = base64_encode($imageContent);

        // Call AI to scan receipt
        $scanned = $this->grokAI->scanReceipt($imageBase64, $mimeType, $user);

        // Create receipt scan record
        $receiptScan = ReceiptScan::create([
            'user_id'               => $user->id,
            'whatsapp_message_id'   => $whatsappMessageId,
            'image_path'            => $imagePath,
            'merchant_name'         => $scanned['merchant_name'] ?? null,
            'total_amount'          => $scanned['total_amount'] ?? null,
            'receipt_date'          => isset($scanned['receipt_date']) ? date('Y-m-d', strtotime($scanned['receipt_date'])) : now(),
            'items'                 => $scanned['items'] ?? null,
            'detected_category'     => $scanned['category'] ?? null,
            'detected_wallet'       => $scanned['detected_wallet'] ?? null,
            'confidence_score'      => $scanned['confidence'] ?? 0,
            'ai_raw_response'       => json_encode($scanned),
            'status'                => 'processed',
            'needs_wallet_confirmation' => empty($scanned['detected_wallet']),
        ]);

        if (!empty($scanned['error']) || empty($scanned['total_amount'])) {
            $receiptScan->update(['status' => 'failed', 'error_message' => $scanned['error'] ?? 'Could not read receipt']);
            return ['success' => false, 'message' => 'Struk tidak dapat dibaca dengan jelas. Pastikan foto jelas dan tidak buram.'];
        }

        // If wallet not detected, ask user
        if (empty($scanned['detected_wallet'])) {
            $amount = number_format($scanned['total_amount'], 0, ',', '.');
            return [
                'success'       => false,
                'needs_wallet'  => true,
                'receipt_scan'  => $receiptScan,
                'message'       => "Saya berhasil membaca total Rp{$amount} dari struk.\nWallet yang digunakan apa? Contoh: Cash, BRI, Gopay",
                'amount'        => $scanned['total_amount'],
            ];
        }

        // Try to create transaction automatically
        $wallets  = $user->wallets()->where('is_active', true)->get();
        $wallet   = $this->findWallet($scanned['detected_wallet'], $wallets);

        if (!$wallet) {
            $receiptScan->update(['needs_wallet_confirmation' => true]);
            return [
                'success'      => false,
                'needs_wallet' => true,
                'receipt_scan' => $receiptScan,
                'message'      => "Struk berhasil dibaca! Total: Rp" . number_format($scanned['total_amount'], 0, ',', '.') . "\nWallet yang digunakan apa?",
                'amount'       => $scanned['total_amount'],
            ];
        }

        $transaction = $this->createTransactionFromScan($scanned, $user, $wallet, $receiptScan);

        if (!$transaction) {
            return ['success' => false, 'message' => 'Gagal menyimpan transaksi.'];
        }

        $amount    = number_format($scanned['total_amount'], 0, ',', '.');
        $merchant  = $scanned['merchant_name'] ?? 'Unknown';
        $category  = $scanned['category'] ?? 'Belanja';
        $date      = $receiptScan->receipt_date ? date('d M Y', strtotime($receiptScan->receipt_date)) : now()->format('d M Y');

        return [
            'success'     => true,
            'transaction' => $transaction,
            'receipt_scan'=> $receiptScan,
            'message'     => "✅ Transaksi berhasil dicatat!\nMerchant: {$merchant}\nTotal: Rp{$amount}\nKategori: {$category}\nWallet: {$wallet->name}\nTanggal: {$date}",
        ];
    }

    /**
     * Confirm receipt with wallet (when AI couldn't detect wallet).
     */
    public function confirmWallet(ReceiptScan $receiptScan, string $walletName, User $user): array
    {
        $wallets = $user->wallets()->where('is_active', true)->get();
        $wallet  = $this->findWallet($walletName, $wallets);

        if (!$wallet) {
            return ['success' => false, 'message' => "Wallet \"{$walletName}\" tidak ditemukan. Wallet Anda: " . $wallets->pluck('name')->join(', ')];
        }

        $scanned     = json_decode($receiptScan->ai_raw_response, true);
        $transaction = $this->createTransactionFromScan($scanned, $user, $wallet, $receiptScan);

        $amount = number_format($receiptScan->total_amount, 0, ',', '.');
        return [
            'success'     => true,
            'transaction' => $transaction,
            'message'     => "✅ Transaksi dicatat!\nTotal: Rp{$amount}\nWallet: {$wallet->name}",
        ];
    }

    protected function createTransactionFromScan(array $scanned, User $user, Wallet $wallet, ReceiptScan $receiptScan): ?Transaction
    {
        $amount = (float)($scanned['total_amount'] ?? 0);
        if (!$wallet->hasSufficientBalance($amount)) {
            return null;
        }

        $categories = Category::where(function($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('is_active', true)->get();

        $category = $categories->first(fn($c) =>
            str_contains(strtolower($c->name), strtolower($scanned['category'] ?? '')) ||
            str_contains(strtolower($scanned['category'] ?? ''), strtolower($c->name))
        ) ?? $categories->where('type', 'expense')->first();

        $transaction = Transaction::create([
            'user_id'          => $user->id,
            'wallet_id'        => $wallet->id,
            'category_id'      => $category?->id,
            'type'             => 'expense',
            'amount'           => $amount,
            'description'      => 'Belanja di ' . ($scanned['merchant_name'] ?? 'unknown'),
            'merchant'         => $scanned['merchant_name'] ?? null,
            'attachment'       => $receiptScan->image_path,
            'transaction_date' => $receiptScan->receipt_date ?? now(),
            'source'           => 'whatsapp_image',
            'ai_confidence'    => $scanned['confidence'] ?? null,
            'ai_raw_response'  => json_encode($scanned),
            'status'           => 'completed',
        ]);

        $wallet->debit($amount);
        $receiptScan->update(['transaction_id' => $transaction->id, 'status' => 'confirmed']);

        return $transaction;
    }

    protected function findWallet(string $name, $wallets): ?Wallet
    {
        $name = strtolower(trim($name));
        return $wallets->first(fn($w) =>
            strtolower($w->name) === $name ||
            str_contains(strtolower($w->name), $name) ||
            str_contains($name, strtolower($w->name))
        );
    }

    protected function detectMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/jpeg',
        };
    }
}
