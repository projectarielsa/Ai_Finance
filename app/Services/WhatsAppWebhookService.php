<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappMessage;
use App\Models\ReceiptScan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookService
{
    public function __construct(
        protected WhatsAppService $whatsApp,
        protected TransactionParserService $transactionParser,
        protected ReceiptScannerService $receiptScanner,
        protected VoiceNoteTranscriptionService $voiceService,
        protected GrokAIService $grokAI
    ) {}

    /**
     * Main entry point — process incoming webhook payload.
     */
    public function process(array $payload): void
    {
        try {
            $message = $this->extractMessage($payload);
            if (!$message) return;

            // Find user by phone
            $user = User::where('phone', $message['from'])->first();
            if (!$user) {
                $this->whatsApp->sendMessage($message['from'],
                    "❌ Nomor WhatsApp Anda belum terdaftar. Silakan daftar di " . config('app.url'));
                return;
            }

            // Save inbound message
            $waMessage = WhatsappMessage::create([
                'user_id'     => $user->id,
                'message_id'  => $message['id'],
                'sender_phone'=> $message['from'],
                'direction'   => 'inbound',
                'type'        => $message['type'],
                'content'     => $message['content'] ?? null,
                'media_url'   => $message['media_url'] ?? null,
                'raw_payload' => $payload,
                'status'      => 'received',
            ]);

            // Route by message type
            match($message['type']) {
                'text'  => $this->handleText($message, $user, $waMessage),
                'image' => $this->handleImage($message, $user, $waMessage),
                'audio', 'voice' => $this->handleAudio($message, $user, $waMessage),
                default => $this->whatsApp->sendMessage($message['from'],
                    "Tipe pesan ini belum didukung. Kirim teks, foto struk, atau voice note.")
            };

            $waMessage->update(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error('WhatsApp Webhook processing error: ' . $e->getMessage(), ['payload' => $payload]);
        }
    }

    protected function handleText(array $message, User $user, WhatsappMessage $waMessage): void
    {
        $text = trim($message['content']);

        // Handle commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $user);
            return;
        }

        // Handle pending receipt wallet confirmation
        $pendingScan = ReceiptScan::where('user_id', $user->id)
            ->where('needs_wallet_confirmation', true)
            ->where('status', 'processed')
            ->latest()
            ->first();

        if ($pendingScan) {
            $result = $this->receiptScanner->confirmWallet($pendingScan, $text, $user);
            $this->whatsApp->sendMessage($message['from'], $result['message']);
            return;
        }

        // Parse as transaction
        $result = $this->transactionParser->parseAndSave($text, $user, $waMessage->message_id);
        $reply  = $result['message'] ?? ($result['success'] ? '✅ Transaksi dicatat!' : '❌ ' . ($result['message'] ?? 'Gagal memproses pesan.'));

        if (!$result['success'] && ($result['balance_error'] ?? false)) {
            $reply = "⚠️ " . $result['message'];
        }

        $this->whatsApp->sendMessage($message['from'], $reply);

        if ($result['success'] ?? false) {
            $waMessage->update(['transaction_id' => $result['transaction']?->id ?? null]);
        }
    }

    protected function handleImage(array $message, User $user, WhatsappMessage $waMessage): void
    {
        $this->whatsApp->sendMessage($message['from'], "📸 Sedang memproses foto struk...");

        // Download media
        $filename = 'receipt_' . Str::uuid() . '.' . $this->getExtFromMime($message['media_mime'] ?? 'image/jpeg');
        $path     = $this->whatsApp->downloadMedia($message['media_url'], $filename);

        if (!$path) {
            $this->whatsApp->sendMessage($message['from'], "❌ Gagal mengunduh gambar. Coba lagi.");
            return;
        }

        $waMessage->update(['media_path' => $path]);

        $result = $this->receiptScanner->processReceipt($path, $user, $waMessage->id);
        $this->whatsApp->sendMessage($message['from'], $result['message']);

        if ($result['success'] ?? false) {
            $waMessage->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    protected function handleAudio(array $message, User $user, WhatsappMessage $waMessage): void
    {
        $this->whatsApp->sendMessage($message['from'], "🎤 Sedang memproses voice note...");

        $ext      = $message['type'] === 'voice' ? 'ogg' : 'mp3';
        $filename = 'voice_' . Str::uuid() . '.' . $ext;
        $path     = $this->whatsApp->downloadMedia($message['media_url'], $filename);

        if (!$path) {
            $this->whatsApp->sendMessage($message['from'], "❌ Gagal mengunduh audio. Coba lagi.");
            return;
        }

        $waMessage->update(['media_path' => $path]);

        $result = $this->voiceService->processVoiceNote($path, $user, $waMessage->id);
        $this->whatsApp->sendMessage($message['from'], $result['message']);

        if ($result['success'] ?? false) {
            $waMessage->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    protected function handleCommand(string $command, User $user): void
    {
        $cmd  = strtolower(explode(' ', $command)[0]);
        $from = $user->phone;

        switch ($cmd) {
            case '/saldo':
                $wallets = $user->wallets()->where('is_active', true)->get();
                $lines   = ["💰 *Saldo Wallet Anda:*"];
                foreach ($wallets as $w) {
                    $lines[] = "• {$w->name}: Rp" . number_format($w->balance, 0, ',', '.');
                }
                $lines[] = "\n*Total:* Rp" . number_format($user->total_balance, 0, ',', '.');
                $this->whatsApp->sendMessage($from, implode("\n", $lines));
                break;

            case '/laporan':
            case '/bulanini':
                $now = now();
                $income  = $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type','income')->sum('amount');
                $expense = $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type','expense')->sum('amount');
                $msg = "📊 *Laporan Bulan {$now->translatedFormat('F Y')}*\n";
                $msg .= "✅ Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "❌ Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                $msg .= "📈 Cashflow: Rp" . number_format($income - $expense, 0, ',', '.');
                $this->whatsApp->sendMessage($from, $msg);
                break;

            case '/topkategori':
                $top = $user->transactions()->completed()
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', now()->month)
                    ->selectRaw('category_id, sum(amount) as total')
                    ->groupBy('category_id')
                    ->orderByDesc('total')
                    ->with('category')
                    ->limit(5)
                    ->get();
                $lines = ["🏆 *Top Kategori Pengeluaran Bulan Ini:*"];
                foreach ($top as $i => $t) {
                    $name = $t->category?->name ?? 'Lainnya';
                    $lines[] = ($i+1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.');
                }
                $this->whatsApp->sendMessage($from, implode("\n", $lines));
                break;

            case '/wallet':
                $wallets = $user->wallets()->where('is_active', true)->get();
                $lines   = ["💳 *Daftar Wallet Anda:*"];
                foreach ($wallets as $w) {
                    $lines[] = "• {$w->name} ({$w->type}): Rp" . number_format($w->balance, 0, ',', '.');
                }
                $this->whatsApp->sendMessage($from, implode("\n", $lines));
                break;

            case '/help':
                $help = "🤖 *Finance AI Assistant*\n\n";
                $help .= "*Cara input transaksi:*\n";
                $help .= "• Teks: _beli kopi 25rb pakai gopay_\n";
                $help .= "• Foto struk: Kirim foto nota/struk\n";
                $help .= "• Voice note: Rekam pengeluaran Anda\n\n";
                $help .= "*Commands:*\n";
                $help .= "/saldo - Lihat saldo semua wallet\n";
                $help .= "/laporan - Laporan bulan ini\n";
                $help .= "/topkategori - Top pengeluaran\n";
                $help .= "/wallet - Daftar wallet\n";
                $help .= "/help - Bantuan ini";
                $this->whatsApp->sendMessage($from, $help);
                break;

            default:
                // Let AI handle it as a question
                $context = $this->buildUserContext($user);
                $answer  = $this->grokAI->answerFinancialQuestion(ltrim($command, '/'), $user, $context);
                $this->whatsApp->sendMessage($from, $answer);
        }
    }

    protected function buildUserContext(User $user): array
    {
        $now = now();
        return [
            'month'          => $now->format('F Y'),
            'total_balance'  => $user->total_balance,
            'monthly_income' => $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type','income')->sum('amount'),
            'monthly_expense'=> $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type','expense')->sum('amount'),
            'wallets'        => $user->wallets()->where('is_active', true)->get(['name','balance'])->toArray(),
        ];
    }

    /**
     * Normalize payload from different gateway providers.
     */
    protected function extractMessage(array $payload): ?array
    {
        // Try Fonnte format
        if (isset($payload['sender'])) {
            return [
                'id'         => $payload['id'] ?? Str::uuid(),
                'from'       => $payload['sender'],
                'type'       => $payload['file'] ? 'image' : 'text',
                'content'    => $payload['message'] ?? null,
                'media_url'  => $payload['file'] ?? null,
                'media_mime' => $payload['mimetype'] ?? null,
            ];
        }

        // Try Wablas format
        if (isset($payload['data']['message'])) {
            $msg = $payload['data']['message'];
            return [
                'id'         => $msg['id'] ?? Str::uuid(),
                'from'       => $msg['phone'] ?? '',
                'type'       => $msg['message_type'] ?? 'text',
                'content'    => $msg['message'] ?? null,
                'media_url'  => $msg['file_url'] ?? null,
                'media_mime' => $msg['mime_type'] ?? null,
            ];
        }

        // Try custom/generic format
        if (isset($payload['from']) || isset($payload['phone'])) {
            return [
                'id'         => $payload['message_id'] ?? $payload['id'] ?? Str::uuid(),
                'from'       => $payload['from'] ?? $payload['phone'] ?? '',
                'type'       => $payload['type'] ?? 'text',
                'content'    => $payload['message'] ?? $payload['text'] ?? null,
                'media_url'  => $payload['media_url'] ?? $payload['file_url'] ?? null,
                'media_mime' => $payload['mime_type'] ?? null,
            ];
        }

        return null;
    }

    protected function getExtFromMime(string $mime): string
    {
        return match($mime) {
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'audio/ogg'     => 'ogg',
            'audio/mpeg'    => 'mp3',
            default         => 'jpg',
        };
    }
}
