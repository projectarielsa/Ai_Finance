<?php

namespace App\Services;

use App\Models\TelegramMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookService
{
    public function __construct(
        protected TelegramBotService $telegram,
        protected TransactionParserService $transactionParser,
        protected ReceiptScannerService $receiptScanner,
        protected VoiceNoteTranscriptionService $voiceService,
        protected GrokAIService $grokAI
    ) {}

    /**
     * Process update — also auto-register telegram_id if user not linked yet.
     */

    /**
     * Main entry point — process Telegram update payload.
     */
    public function process(array $update): void
    {
        try {
            $message = $update['message'] ?? $update['edited_message'] ?? null;
            if (!$message) return;

            $chatId         = $message['chat']['id'];
            $from           = $message['from'] ?? [];
            $telegramUserId = (string)($from['id'] ?? $chatId);
            $username       = $from['username'] ?? null;
            $text           = $message['text'] ?? '';

            // ── Handle /link & /unlink BEFORE user check ──────────────────
            // Allows unlinked users to connect their account
            if (str_starts_with(trim($text), '/link') || str_starts_with(trim($text), '/unlink')) {
                $this->handleLinkCommand(trim($text), $telegramUserId, $username, $chatId);
                return;
            }

            // ── Find user linked to this Telegram ID ───────────────────────
            $user = User::where('telegram_id', $telegramUserId)->first();
            if (!$user) {
                $botUsername = config('services.telegram.bot_username', 'FinanceAIBot');
                $this->telegram->sendMessage($chatId,
                    "❌ *Akun belum terhubung*\n\n" .
                    "Hubungkan akun Anda dengan perintah:\n" .
                    "`/link email@anda.com`\n\n" .
                    "Contoh:\n`/link john@gmail.com`\n\n" .
                    "Atau login ke web: " . config('app.url') . "/profile"
                );
                return;
            }

            // Update username jika berubah
            if ($username && $user->telegram_username !== $username) {
                $user->update(['telegram_username' => $username]);
            }

            // Save inbound message
            $msgRecord = TelegramMessage::create([
                'user_id'          => $user->id,
                'telegram_user_id' => $telegramUserId,
                'chat_id'          => (string)$chatId,
                'message_id'       => (string)($message['message_id'] ?? ''),
                'direction'        => 'inbound',
                'type'             => $this->detectType($message),
                'content'          => $message['text'] ?? $message['caption'] ?? null,
                'raw_payload'      => $update,
                'status'           => 'received',
            ]);

            // Route by message type
            match(true) {
                isset($message['text'])  => $this->handleText($message, $user, $chatId, $msgRecord),
                isset($message['photo']) => $this->handlePhoto($message, $user, $chatId, $msgRecord),
                isset($message['document']) && $this->isImage($message['document']) => $this->handleDocument($message, $user, $chatId, $msgRecord),
                isset($message['voice']) => $this->handleVoice($message, $user, $chatId, $msgRecord),
                isset($message['audio']) => $this->handleVoice($message, $user, $chatId, $msgRecord),
                default => $this->telegram->sendMessage($chatId, "Tipe pesan ini belum didukung. Kirim teks, foto struk, atau voice note."),
            };

            $msgRecord->update(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error('TelegramWebhook processing error: ' . $e->getMessage(), [
                'update' => $update,
                'trace'  => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    protected function handleText(array $message, User $user, int|string $chatId, TelegramMessage $msgRecord): void
    {
        $text = trim($message['text']);

        // Telegram bot commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $user, $chatId);
            return;
        }

        // Check pending receipt waiting for wallet confirmation
        $pendingScan = \App\Models\ReceiptScan::where('user_id', $user->id)
            ->where('needs_wallet_confirmation', true)
            ->where('status', 'processed')
            ->latest()->first();

        if ($pendingScan) {
            $result = $this->receiptScanner->confirmWallet($pendingScan, $text, $user);
            $this->telegram->sendMessage($chatId, $result['message']);
            return;
        }

        // Parse as financial transaction
        $result = $this->transactionParser->parseAndSave($text, $user);
        $reply  = $result['message'] ?? ($result['success'] ? '✅ Transaksi dicatat!' : '❌ Tidak dikenali sebagai transaksi keuangan.');

        if (!$result['success'] && ($result['balance_error'] ?? false)) {
            $reply = "⚠️ " . $result['message'];
        }

        $this->telegram->sendMessage($chatId, $reply);

        if ($result['success'] ?? false) {
            $msgRecord->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    protected function handlePhoto(array $message, User $user, int|string $chatId, TelegramMessage $msgRecord): void
    {
        $this->telegram->sendMessage($chatId, "📸 Sedang memproses foto struk...");

        // Get largest photo size
        $photo  = end($message['photo']);
        $fileId = $photo['file_id'];

        $path = $this->telegram->downloadFile($fileId, 'receipt_' . Str::uuid());
        if (!$path) {
            $this->telegram->sendMessage($chatId, "❌ Gagal mengunduh gambar. Coba lagi.");
            return;
        }

        $msgRecord->update(['media_path' => $path]);

        $result = $this->receiptScanner->processReceipt($path, $user, $msgRecord->id);
        $this->telegram->sendMessage($chatId, $result['message']);

        if ($result['success'] ?? false) {
            $msgRecord->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    protected function handleDocument(array $message, User $user, int|string $chatId, TelegramMessage $msgRecord): void
    {
        $doc    = $message['document'];
        $path   = $this->telegram->downloadFile($doc['file_id'], 'receipt_doc_' . Str::uuid());

        if (!$path) {
            $this->telegram->sendMessage($chatId, "❌ Gagal mengunduh file. Coba lagi.");
            return;
        }

        $msgRecord->update(['media_path' => $path]);
        $result = $this->receiptScanner->processReceipt($path, $user, $msgRecord->id);
        $this->telegram->sendMessage($chatId, $result['message']);
    }

    protected function handleVoice(array $message, User $user, int|string $chatId, TelegramMessage $msgRecord): void
    {
        $this->telegram->sendMessage($chatId, "🎤 Sedang memproses voice note...");

        $media  = $message['voice'] ?? $message['audio'];
        $fileId = $media['file_id'];

        $path = $this->telegram->downloadFile($fileId, 'voice_' . Str::uuid());
        if (!$path) {
            $this->telegram->sendMessage($chatId, "❌ Gagal mengunduh audio. Coba lagi.");
            return;
        }

        $msgRecord->update(['media_path' => $path]);

        $result = $this->voiceService->processVoiceNote($path, $user, $msgRecord->id);
        $this->telegram->sendMessage($chatId, $result['message']);

        if ($result['success'] ?? false) {
            $msgRecord->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    // ── Commands ──────────────────────────────────────────────────────────────

    /**
     * Handle /link email@user.com — link Telegram ID to web account.
     * This runs BEFORE user authentication check.
     * Supports re-linking to a different account.
     */
    protected function handleLinkCommand(string $command, string $telegramUserId, ?string $username, int|string $chatId): void
    {
        $parts = explode(' ', $command, 2);

        // /unlink — disconnect current account
        if (strtolower(trim($parts[0])) === '/unlink') {
            $existing = User::where('telegram_id', $telegramUserId)->first();
            if (!$existing) {
                $this->telegram->sendMessage($chatId, "ℹ️ Telegram Anda belum terhubung ke akun manapun.");
                return;
            }
            $existing->update(['telegram_id' => null, 'telegram_username' => null]);
            $this->telegram->sendMessage($chatId,
                "✅ Akun *{$existing->name}* berhasil diputuskan.\n\n" .
                "Untuk menghubungkan akun baru, kirim:\n`/link email@baru.com`"
            );
            Log::info("Telegram unlinked: user #{$existing->id} ({$existing->email})");
            return;
        }

        // /link email@example.com
        $email = trim($parts[1] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->telegram->sendMessage($chatId,
                "⚠️ *Format salah!*\n\n" .
                "Gunakan: `/link email@anda.com`\n\n" .
                "Contoh: `/link john@gmail.com`\n\n" .
                "Untuk ganti akun: `/unlink` dulu, lalu `/link` email baru."
            );
            return;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->telegram->sendMessage($chatId,
                "❌ Email *{$email}* tidak ditemukan.\n\n" .
                "Pastikan email sudah terdaftar di " . config('app.url')
            );
            return;
        }

        // If this Telegram is already linked to a DIFFERENT account → auto unlink old first
        $oldUser = User::where('telegram_id', $telegramUserId)
                       ->where('id', '!=', $user->id)
                       ->first();
        if ($oldUser) {
            // Auto-unlink from old account
            $oldUser->update(['telegram_id' => null, 'telegram_username' => null]);
            Log::info("Telegram auto-unlinked from user #{$oldUser->id} to re-link to #{$user->id}");
        }

        // Link to new account
        $user->update([
            'telegram_id'       => $telegramUserId,
            'telegram_username' => $username,
        ]);

        $prefix = $oldUser ? "🔄 Akun berhasil *diganti*!" : "✅ Akun berhasil *dihubungkan*!";

        $this->telegram->sendMessage($chatId,
            "{$prefix}\n\n" .
            "Halo *{$user->name}*! 👋\n\n" .
            ($oldUser ? "Sebelumnya: _{$oldUser->name}_ → Sekarang: _{$user->name}_\n\n" : "") .
            "Sekarang Anda bisa:\n" .
            "💬 Kirim teks transaksi: _beli kopi 25rb gopay_\n" .
            "📸 Kirim foto struk\n" .
            "🎤 Kirim voice note\n\n" .
            "Ketik /help untuk panduan lengkap."
        );

        Log::info("Telegram linked: user #{$user->id} ({$user->email}) → Telegram ID {$telegramUserId}");
    }

    protected function handleCommand(string $command, User $user, int|string $chatId): void
    {
        // Strip bot username suffix e.g. /start@MyBot → /start
        $cmd = strtolower(explode('@', explode(' ', $command)[0])[0]);

        switch ($cmd) {
            case '/start':
                $this->telegram->sendMessage($chatId,
                    "👋 Halo *{$user->name}*!\n\n" .
                    "Selamat datang di *Finance AI Bot* 🤖\n\n" .
                    "Saya siap membantu mencatat keuangan Anda!\n\n" .
                    "Ketik /help untuk panduan lengkap."
                );
                break;

            case '/saldo':
                $wallets = $user->wallets()->where('is_active', true)->get();
                $lines   = ["💰 *Saldo Wallet Anda:*"];
                foreach ($wallets as $w) {
                    $lines[] = "• {$w->name}: Rp" . number_format($w->balance, 0, ',', '.');
                }
                $lines[] = "\n*Total:* Rp" . number_format($user->total_balance, 0, ',', '.');
                $this->telegram->sendMessage($chatId, implode("\n", $lines));
                break;

            case '/laporan':
            case '/bulanini':
                $now     = now();
                $income  = $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type', 'income')->sum('amount');
                $expense = $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type', 'expense')->sum('amount');
                $net     = $income - $expense;
                $msg  = "📊 *Laporan " . $now->translatedFormat('F Y') . "*\n";
                $msg .= "✅ Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "❌ Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: Rp" . number_format($net, 0, ',', '.');
                $this->telegram->sendMessage($chatId, $msg);
                break;

            case '/pengeluaran':
                $now     = now();
                $expense = $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type', 'expense')->sum('amount');
                $this->telegram->sendMessage($chatId, "❌ *Total Pengeluaran " . $now->format('F Y') . ":*\nRp" . number_format($expense, 0, ',', '.'));
                break;

            case '/topkategori':
                $top = $user->transactions()->completed()
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', now()->month)
                    ->selectRaw('category_id, sum(amount) as total')
                    ->groupBy('category_id')
                    ->orderByDesc('total')
                    ->with('category')
                    ->limit(5)->get();
                $lines = ["🏆 *Top Kategori Pengeluaran Bulan Ini:*"];
                foreach ($top as $i => $t) {
                    $name    = $t->category?->name ?? 'Lainnya';
                    $lines[] = ($i + 1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.');
                }
                if ($top->isEmpty()) $lines[] = "Belum ada data.";
                $this->telegram->sendMessage($chatId, implode("\n", $lines));
                break;

            case '/wallet':
                $wallets = $user->wallets()->where('is_active', true)->get();
                $lines   = ["💳 *Daftar Wallet:*"];
                foreach ($wallets as $w) {
                    $type    = ucfirst(str_replace('_', ' ', $w->type));
                    $lines[] = "• *{$w->name}* ({$type})\n  Rp" . number_format($w->balance, 0, ',', '.');
                }
                $this->telegram->sendMessage($chatId, implode("\n", $lines));
                break;

            case '/help':
                $help  = "🤖 *Finance AI Bot — Panduan*\n\n";
                $help .= "*Cara input transaksi:*\n";
                $help .= "💬 Teks: _beli kopi 25rb gopay_\n";
                $help .= "📸 Foto struk: Kirim foto nota/struk\n";
                $help .= "🎤 Voice note: Rekam ucapan transaksi\n\n";
                $help .= "*Commands:*\n";
                $help .= "/saldo — Lihat semua saldo wallet\n";
                $help .= "/laporan — Laporan bulan ini\n";
                $help .= "/topkategori — Top pengeluaran\n";
                $help .= "/wallet — Daftar wallet\n";
                $help .= "/link email — Hubungkan/ganti akun\n";
                $help .= "/unlink — Putuskan akun dari bot\n";
                $help .= "/help — Bantuan ini\n\n";
                $help .= "*Contoh pesan:*\n";
                $help .= "• beli makan siang 35rb cash\n";
                $help .= "• gaji masuk 5jt bca\n";
                $help .= "• transfer 200rb dari bca ke gopay\n";
                $help .= "• tarik tunai 500rb bri";
                $this->telegram->sendMessage($chatId, $help);
                break;

            default:
                // Let AI answer as a financial question
                $context = $this->buildUserContext($user);
                $answer  = $this->grokAI->answerFinancialQuestion(ltrim($command, '/'), $user, $context);
                $this->telegram->sendMessage($chatId, $answer);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function detectType(array $message): string
    {
        if (isset($message['text']))     return 'text';
        if (isset($message['photo']))    return 'photo';
        if (isset($message['voice']))    return 'voice';
        if (isset($message['audio']))    return 'audio';
        if (isset($message['document'])) return 'document';
        if (isset($message['sticker']))  return 'sticker';
        return 'unknown';
    }

    protected function isImage(array $document): bool
    {
        $mime = $document['mime_type'] ?? '';
        return str_starts_with($mime, 'image/');
    }

    protected function buildUserContext(User $user): array
    {
        $now = now();
        return [
            'month'           => $now->format('F Y'),
            'total_balance'   => $user->total_balance,
            'monthly_income'  => $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type', 'income')->sum('amount'),
            'monthly_expense' => $user->transactions()->completed()->byMonth($now->year, $now->month)->where('type', 'expense')->sum('amount'),
            'wallets'         => $user->wallets()->where('is_active', true)->get(['name', 'balance'])->toArray(),
        ];
    }
}
