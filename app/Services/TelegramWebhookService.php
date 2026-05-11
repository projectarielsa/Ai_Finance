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
     * Main entry point — process Telegram update payload.
     */
    public function process(array $update): void
    {
        try {
            // ── Handle callback_query (inline keyboard button clicks) ──────
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

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

        // ── Cek apakah ini pertanyaan/permintaan analisa DULU ────────────────
        // Jika ya, langsung jawab via AI tanpa coba parse sebagai transaksi
        // Ini mencegah AI membuang token untuk parseTransaction yang pasti gagal
        if ($this->looksLikeQuestion($text)) {
            $context = $this->buildUserContext($user);
            $reply   = $this->grokAI->answerFinancialQuestion($text, $user, $context);
            $this->telegram->sendMessage($chatId, $reply);
            return;
        }

        // ── Parse as financial transaction ────────────────────────────────
        $result = $this->transactionParser->parseAndSave($text, $user, null, 'telegram_text');
        $reply  = $result['message'] ?? ($result['success'] ? '✅ Transaksi dicatat!' : '❌ Tidak dikenali sebagai transaksi keuangan.');

        if (!$result['success'] && ($result['balance_error'] ?? false)) {
            $reply = "⚠️ " . $result['message'];
        }

        $this->telegram->sendMessage($chatId, $reply);

        if (($result['success'] ?? false) && isset($result['transaction'])) {
            $msgRecord->update(['transaction_id' => $result['transaction']?->id]);
        }
    }

    protected function handlePhoto(array $message, User $user, int|string $chatId, TelegramMessage $msgRecord): void
    {
        $this->telegram->sendMessage($chatId, "📸 Sedang memproses foto struk...");

        // Get largest photo size
        $photo  = end($message['photo']);
        $fileId = $photo['file_id'];

        Log::info('TG handlePhoto: downloading file', ['file_id' => $fileId, 'user_id' => $user->id]);

        $path = $this->telegram->downloadFile($fileId, 'receipt_' . Str::uuid());

        Log::info('TG handlePhoto: download result', ['path' => $path]);

        if (!$path) {
            $this->telegram->sendMessage($chatId, "❌ Gagal mengunduh gambar. Coba lagi.");
            return;
        }

        $msgRecord->update(['media_path' => $path]);

        Log::info('TG handlePhoto: calling processReceipt', ['path' => $path]);

        try {
            $result = $this->receiptScanner->processReceipt($path, $user, null); // pass null to avoid FK issue
        } catch (\Throwable $e) {
            Log::error('TG handlePhoto: processReceipt exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->telegram->sendMessage($chatId, "❌ Gagal memproses struk: " . $e->getMessage());
            return;
        }

        Log::info('TG handlePhoto: processReceipt result', [
            'success'      => $result['success'],
            'needs_wallet' => $result['needs_wallet'] ?? false,
            'has_scan'     => isset($result['receipt_scan']),
            'message'      => substr($result['message'] ?? '', 0, 100),
        ]);

        // If needs wallet confirmation → send inline keyboard with wallet choices
        if (!empty($result['needs_wallet']) && isset($result['receipt_scan'])) {
            $this->sendWalletKeyboard($chatId, $user, $result['receipt_scan']->id, $result['message']);
            return;
        }

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

        // Same as photo — show wallet keyboard if needed
        if (!empty($result['needs_wallet']) && isset($result['receipt_scan'])) {
            $this->sendWalletKeyboard($chatId, $user, $result['receipt_scan']->id, $result['message']);
            return;
        }

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
                $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: Rp" . number_format($net, 0, ',', '.') . "\n\n";
                $msg .= "Untuk rekap lengkap, ketik /rekap";
                $this->telegram->sendMessage($chatId, $msg);
                break;

            case '/rekap':
                // Cek apakah ada parameter bulan: /rekap 3 2026 atau /rekap (default bulan ini)
                $parts   = explode(' ', $command);
                $reqMonth = isset($parts[1]) ? (int)$parts[1] : now()->month;
                $reqYear  = isset($parts[2]) ? (int)$parts[2] : now()->year;
                $this->sendMonthlyRecap($user, $chatId, $reqYear, $reqMonth);
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
                $help .= "/laporan — Ringkasan bulan ini\n";
                $help .= "/rekap — Rekapan lengkap bulan ini\n";
                $help .= "/rekap 4 2026 — Rekap bulan April 2026\n";
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
                // Let AI answer as a financial question with full context
                $context = $this->buildUserContext($user);
                $answer  = $this->grokAI->answerFinancialQuestion($command, $user, $context);
                $this->telegram->sendMessage($chatId, $answer);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Handle inline keyboard button clicks (callback_query).
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId   = $callbackQuery['message']['chat']['id'];
        $msgId    = $callbackQuery['message']['message_id'];
        $data     = $callbackQuery['data'] ?? '';
        $fromId   = (string)($callbackQuery['from']['id'] ?? '');

        // Answer callback to remove loading spinner
        $this->telegram->answerCallbackQuery($callbackQuery['id']);

        // Find user
        $user = User::where('telegram_id', $fromId)->first();
        if (!$user) {
            $this->telegram->editMessageText($chatId, $msgId, "❌ Akun tidak ditemukan.");
            return;
        }

        // Parse callback data: wallet_confirm:{receipt_scan_id}:{wallet_name}
        if (str_starts_with($data, 'wallet_confirm:')) {
            $parts       = explode(':', $data, 3);
            $scanId      = $parts[1] ?? null;
            $walletName  = $parts[2] ?? null;

            if (!$scanId || !$walletName) return;

            $receiptScan = \App\Models\ReceiptScan::find($scanId);
            if (!$receiptScan || $receiptScan->user_id !== $user->id) {
                $this->telegram->editMessageText($chatId, $msgId, "❌ Data struk tidak ditemukan.");
                return;
            }

            // Process with chosen wallet
            $result = $this->receiptScanner->confirmWallet($receiptScan, $walletName, $user);

            // Edit original message to remove keyboard and show result
            $this->telegram->editMessageText($chatId, $msgId, $result['message']);
            return;
        }
    }

    /**
     * Send wallet selection as inline keyboard buttons.
     */
    protected function sendWalletKeyboard(int|string $chatId, User $user, int $receiptScanId, string $promptText): void
    {
        $wallets = $user->wallets()->where('is_active', true)->get();

        // Build inline keyboard rows (2 per row)
        $buttons = [];
        $row     = [];
        foreach ($wallets as $i => $wallet) {
            $row[] = [
                'text'          => $wallet->name,
                'callback_data' => "wallet_confirm:{$receiptScanId}:{$wallet->name}",
            ];
            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) {
            $buttons[] = $row;
        }

        $this->telegram->sendMessage($chatId, $promptText, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /**
     * Send a full monthly financial recap to the user.
     */
    protected function sendMonthlyRecap(User $user, int|string $chatId, int $year, int $month): void
    {
        // Validate
        if ($month < 1 || $month > 12) {
            $this->telegram->sendMessage($chatId, "⚠️ Format bulan salah.\nContoh: `/rekap 5 2026` untuk Mei 2026");
            return;
        }

        $periodName = \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        $this->telegram->sendMessage($chatId, "⏳ Membuat rekapan *{$periodName}*...");

        $transactions = $user->transactions()
            ->completed()
            ->byMonth($year, $month)
            ->with(['category', 'wallet'])
            ->orderBy('transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            $this->telegram->sendMessage($chatId, "📭 Tidak ada transaksi di bulan *{$periodName}*.");
            return;
        }

        $income       = $transactions->where('type', 'income')->sum('amount');
        $expense      = $transactions->where('type', 'expense')->sum('amount');
        $transfer     = $transactions->where('type', 'transfer')->sum('amount');
        $net          = $income - $expense;
        $totalTx      = $transactions->count();

        // ── Pesan 1: Ringkasan Utama ──────────────────────────────────────
        $msg  = "📊 *REKAP KEUANGAN {$periodName}*\n";
        $msg .= str_repeat("─", 30) . "\n\n";
        $msg .= "💰 *Pemasukan:* Rp" . number_format($income, 0, ',', '.') . "\n";
        $msg .= "💸 *Pengeluaran:* Rp" . number_format($expense, 0, ',', '.') . "\n";
        if ($transfer > 0) {
            $msg .= "🔄 *Transfer:* Rp" . number_format($transfer, 0, ',', '.') . "\n";
        }
        $msg .= "\n";
        $msg .= ($net >= 0 ? "📈" : "📉") . " *Cashflow:* ";
        $msg .= ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n";
        $msg .= "📝 *Total Transaksi:* {$totalTx}\n\n";

        // Saldo wallet saat ini
        $wallets = $user->wallets()->where('is_active', true)->where('include_in_total', true)->get();
        $totalBalance = $wallets->sum('balance');
        $msg .= "💳 *Saldo Sekarang:* Rp" . number_format($totalBalance, 0, ',', '.') . "\n";

        $this->telegram->sendMessage($chatId, $msg);

        // ── Pesan 2: Top Pengeluaran per Kategori ─────────────────────────
        $expByCategory = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn($t) => [
                'name'  => $t->first()->category?->name ?? 'Lainnya',
                'total' => $t->sum('amount'),
                'count' => $t->count(),
            ])
            ->sortByDesc('total')
            ->values();

        if ($expByCategory->isNotEmpty()) {
            $catMsg = "🏆 *TOP PENGELUARAN PER KATEGORI*\n\n";
            $maxExp = $expByCategory->max('total');
            foreach ($expByCategory->take(8) as $i => $cat) {
                $pct     = $expense > 0 ? round($cat['total'] / $expense * 100) : 0;
                $bar     = str_repeat('█', (int)($pct / 10)) . str_repeat('░', 10 - (int)($pct / 10));
                $catMsg .= ($i + 1) . ". *{$cat['name']}*\n";
                $catMsg .= "   Rp" . number_format($cat['total'], 0, ',', '.') . " ({$pct}%) · {$cat['count']}x\n";
                $catMsg .= "   `{$bar}`\n\n";
            }
            $this->telegram->sendMessage($chatId, $catMsg);
        }

        // ── Pesan 3: Pengeluaran Terbesar ─────────────────────────────────
        $bigExpenses = $transactions
            ->where('type', 'expense')
            ->sortByDesc('amount')
            ->take(5);

        if ($bigExpenses->isNotEmpty()) {
            $bigMsg = "💸 *PENGELUARAN TERBESAR*\n\n";
            foreach ($bigExpenses as $i => $tx) {
                $desc    = $tx->description ?? $tx->merchant ?? ($tx->category?->name ?? 'Pengeluaran');
                $wallet  = $tx->wallet->name;
                $date    = $tx->transaction_date->format('d M');
                $bigMsg .= ($i + 1) . ". *" . \Str::limit($desc, 30) . "*\n";
                $bigMsg .= "   Rp" . number_format($tx->amount, 0, ',', '.') . " · {$wallet} · {$date}\n\n";
            }
            $this->telegram->sendMessage($chatId, $bigMsg);
        }

        // ── Pesan 4: Saldo per Wallet ─────────────────────────────────────
        if ($wallets->count() > 1) {
            $walletMsg = "💳 *SALDO PER WALLET*\n\n";
            foreach ($wallets as $w) {
                $walletMsg .= "• *{$w->name}*: Rp" . number_format($w->balance, 0, ',', '.') . "\n";
            }
            $this->telegram->sendMessage($chatId, $walletMsg);
        }

        // ── Pesan 5: AI Insight ────────────────────────────────────────────
        try {
            $topCatName   = $expByCategory->first()['name'] ?? 'Lainnya';
            $prevMonth    = \Carbon\Carbon::createFromDate($year, $month, 1)->subMonth();
            $prevExpense  = $user->transactions()->completed()
                               ->byMonth($prevMonth->year, $prevMonth->month)
                               ->where('type', 'expense')->sum('amount');
            $compPct      = $prevExpense > 0
                ? round(($expense - $prevExpense) / $prevExpense * 100, 1)
                : 0;
            $comparison   = $compPct >= 0
                ? "naik {$compPct}% dari bulan lalu"
                : "turun " . abs($compPct) . "% dari bulan lalu";

            $insight = $this->grokAI->generateFinancialInsight($user, [
                'income'       => number_format($income, 0, ',', '.'),
                'expense'      => number_format($expense, 0, ',', '.'),
                'top_category' => $topCatName,
                'comparison'   => $comparison,
            ]);

            $insightMsg = "🤖 *AI INSIGHT*\n\n{$insight}\n\n";
            $insightMsg .= "_Lihat laporan lengkap: " . config('app.url') . "/reports_";
            $this->telegram->sendMessage($chatId, $insightMsg);
        } catch (\Throwable $e) {
            // AI insight opsional, tidak perlu error jika gagal
            Log::warning('AI insight gagal: ' . $e->getMessage());
        }
    }

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
        $now  = now();
        $year = $now->year;
        $month = $now->month;

        // Top categories this month
        $topCategories = $user->transactions()
            ->completed()
            ->where('type', 'expense')
            ->byMonth($year, $month)
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'kategori' => $t->category?->name ?? 'Lainnya',
                'total'    => (float) $t->total,
            ])
            ->toArray();

        // Last 10 transactions
        $recentTransactions = $user->transactions()
            ->completed()
            ->with(['category', 'wallet'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'tanggal'  => $t->transaction_date->format('d M Y'),
                'tipe'     => $t->type,
                'jumlah'   => (float) $t->amount,
                'kategori' => $t->category?->name ?? '-',
                'wallet'   => $t->wallet->name,
                'deskripsi'=> $t->description ?? '-',
            ])
            ->toArray();

        // Prev month
        $prevMonth   = $now->copy()->subMonth();
        $prevIncome  = $user->transactions()->completed()->byMonth($prevMonth->year, $prevMonth->month)->where('type','income')->sum('amount');
        $prevExpense = $user->transactions()->completed()->byMonth($prevMonth->year, $prevMonth->month)->where('type','expense')->sum('amount');

        $monthlyIncome  = $user->transactions()->completed()->byMonth($year, $month)->where('type','income')->sum('amount');
        $monthlyExpense = $user->transactions()->completed()->byMonth($year, $month)->where('type','expense')->sum('amount');

        return [
            'user_name'                      => $user->name,
            'bulan_ini'                      => $now->translatedFormat('F Y'),
            'total_saldo'                    => (float) $user->total_balance,
            'pemasukan_bulan_ini'            => (float) $monthlyIncome,
            'pengeluaran_bulan_ini'          => (float) $monthlyExpense,
            'cashflow_bulan_ini'             => (float) ($monthlyIncome - $monthlyExpense),
            'pemasukan_bulan_lalu'           => (float) $prevIncome,
            'pengeluaran_bulan_lalu'         => (float) $prevExpense,
            'top_kategori_pengeluaran'       => $topCategories,
            '10_transaksi_terakhir'          => $recentTransactions,
            'wallets'                        => $user->wallets()->where('is_active', true)->get(['name', 'balance', 'type'])
                                                     ->map(fn($w) => ['nama' => $w->name, 'saldo' => (float)$w->balance, 'tipe' => $w->type])
                                                     ->toArray(),
        ];
    }

    /**
     * Detect if text looks like a question/request rather than a transaction.
     * Check BEFORE trying to parse as transaction to avoid wasting AI tokens.
     *
     * PENTING: Jangan masukkan kata yang ada di konteks transaksi seperti:
     * "tambah", "masuk", "keluar", "catat", "simpan", "bayar"
     * karena itu semua adalah transaksi, bukan pertanyaan!
     */
    protected function looksLikeQuestion(string $text): bool
    {
        $lower = strtolower(trim($text));

        // ── Cek tanda tanya eksplisit ─────────────────────────────────────
        if (str_contains($lower, '?')) return true;

        // ── BLACKLIST: jika mengandung kata-kata transaksi ini, BUKAN pertanyaan ──
        // Kata-kata ini menandakan aksi pencatatan transaksi
        $transactionIndicators = [
            'beli', 'bayar', 'bayarin', 'transfer', 'tarik', 'setor', 'top up', 'topup',
            'isi', 'beli', 'jajan', 'makan', 'minum', 'bensin', 'parkir',
            'gaji', 'pemasukan', 'penghasilan', 'dapat uang', 'terima',
            'keluar', 'pengeluaran', 'habis', 'abis',
            'tambah', 'tambahkan', 'masukin', 'simpen', 'catat', 'input',
            'masuk ke', 'masuk dari', 'ke wallet', 'ke krom', 'ke bri', 'ke bca',
            'ke gopay', 'ke dana', 'ke ovo', 'ke cash',
        ];
        foreach ($transactionIndicators as $ti) {
            if (str_contains($lower, $ti)) return false;
        }

        // ── WHITELIST: kata-kata yang jelas pertanyaan/analisa ─────────────
        $definitelyQuestion = [
            // Rekap/laporan eksplisit
            'rekap', 'rekapan', 'rangkum', 'rangkuman', 'ringkasan', 'resume',
            'laporan keuangan', 'laporan bulanan', 'laporan pengeluaran',
            // Analisa
            'analisa', 'analisis', 'evaluasi',
            'boros', 'hemat', 'irit', 'saran keuangan', 'rekomendasi',
            // Pertanyaan murni
            'berapa total', 'berapa saldo', 'berapa pengeluaran', 'berapa pemasukan',
            'gimana kondisi', 'bagaimana kondisi', 'bagaimana keuangan',
            'paling boros', 'paling sering', 'terbesar', 'terbanyak',
            'bulan lalu', 'bulan ini lebih', 'dibanding',
            'trend', 'perbandingan', 'statistik',
        ];
        foreach ($definitelyQuestion as $kw) {
            if (str_contains($lower, $kw)) return true;
        }

        // ── Kata tanya di awal kalimat ────────────────────────────────────
        $startsWithQuestion = ['berapa', 'gimana', 'bagaimana', 'kenapa', 'mengapa',
                               'kapan', 'dimana', 'di mana', 'siapa', 'apa saja',
                               'apa yang', 'coba cerita', 'tolong analisa', 'tolong rekap',
                               'tolong buatkan', 'buatkan rekap', 'buatkan laporan'];
        foreach ($startsWithQuestion as $kw) {
            if (str_starts_with($lower, $kw)) return true;
        }

        return false;
    }
}
