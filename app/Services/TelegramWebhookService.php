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

        // ── 1. Bot commands (/saldo, /rekap, dll) ────────────────────────
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $user, $chatId);
            return;
        }

        // ── 2. Pending receipt waiting for wallet confirmation ────────────
        $pendingScan = \App\Models\ReceiptScan::where('user_id', $user->id)
            ->where('needs_wallet_confirmation', true)
            ->where('status', 'processed')
            ->latest()->first();

        if ($pendingScan) {
            $result = $this->receiptScanner->confirmWallet($pendingScan, $text, $user);
            $this->telegram->sendMessage($chatId, $result['message']);
            return;
        }

        // ── 3. Fast-path: cek saldo / info tanpa AI ───────────────────────
        // Tangani permintaan umum sehari-hari langsung dari kode — cepat & hemat token
        $fastReply = $this->handleFastQuery($text, $user);
        if ($fastReply !== null) {
            $this->telegram->sendMessage($chatId, $fastReply);
            return;
        }

        // ── 4. Coba parse sebagai transaksi keuangan ──────────────────────
        $result = $this->transactionParser->parseAndSave($text, $user, null, 'telegram_text');

        if ($result['success']) {
            $this->telegram->sendMessage($chatId, $result['message']);
            $msgRecord->update(['transaction_id' => $result['transaction']?->id]);

            // Trigger alert jika transaksi besar (async, tidak delay respons)
            if (isset($result['transaction'])) {
                $this->transactionParser->maybeTriggerBigAlert($result['transaction'], $user);
            }
            return;
        }

        if ($result['balance_error'] ?? false) {
            $this->telegram->sendMessage($chatId, $result['message']);
            return;
        }

        // ── 5. Fallback: jawab via AI chat (bukan error) ──────────────────
        // Pesan apapun yang tidak dikenali sebagai transaksi → tanya AI
        // Ini membuat bot responsif terhadap segala bentuk pertanyaan natural
        $context = $this->buildUserContext($user);
        $reply   = $this->grokAI->answerFinancialQuestion($text, $user, $context);
        $this->telegram->sendMessage($chatId, $reply);
    }

    /**
     * Fast-path handler: jawab permintaan umum TANPA memanggil AI.
     * Hemat token & respons lebih cepat untuk query yang sering dipakai.
     * Return null jika tidak cocok (lanjut ke flow berikutnya).
     */
    protected function handleFastQuery(string $text, User $user): ?string
    {
        $lower = strtolower(trim($text));

        // ── Cek saldo ─────────────────────────────────────────────────────
        $saldoKeywords = [
            'saldo', 'balance', 'duit', 'uang', 'tabungan',
            'cek saldo', 'lihat saldo', 'berapa saldo', 'saldo ku', 'saldoku',
            'berapa uang', 'ada berapa', 'berapa duit', 'berapa tabungan',
        ];
        foreach ($saldoKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $wallets = $user->wallets()->where('is_active', true)->orderBy('sort_order')->get();
                if ($wallets->isEmpty()) {
                    return "💳 Kamu belum punya wallet aktif. Tambahkan di " . config('app.url') . "/wallets";
                }
                $lines = ["💰 *Saldo Wallet Kamu:*\n"];
                foreach ($wallets as $w) {
                    $lines[] = "• *{$w->name}*: Rp" . number_format($w->balance, 0, ',', '.');
                }
                $total = $wallets->where('include_in_total', true)->sum('balance');
                $lines[] = "\n*Total:* Rp" . number_format($total, 0, ',', '.');
                return implode("\n", $lines);
            }
        }

        // ── Cek pengeluaran / pemasukan bulan ini ─────────────────────────
        $expenseKeywords = [
            'pengeluaran', 'pengeluaran bulan ini', 'habis berapa', 'keluar berapa',
            'udah keluar berapa', 'sudah keluar', 'sudah habis',
        ];
        foreach ($expenseKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $now     = now();
                $expense = $user->transactions()->completed()
                               ->byMonth($now->year, $now->month)->where('type', 'expense')->sum('amount');
                $income  = $user->transactions()->completed()
                               ->byMonth($now->year, $now->month)->where('type', 'income')->sum('amount');
                $net     = $income - $expense;
                $msg  = "📊 *Bulan " . $now->translatedFormat('F Y') . ":*\n\n";
                $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n\n";
                $msg .= "_Ketik /rekap untuk laporan lengkap_";
                return $msg;
            }
        }

        $incomeKeywords = [
            'pemasukan', 'pemasukan bulan ini', 'penghasilan', 'masuk berapa',
            'dapat berapa', 'gaji berapa',
        ];
        foreach ($incomeKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $now    = now();
                $income = $user->transactions()->completed()
                              ->byMonth($now->year, $now->month)->where('type', 'income')->sum('amount');
                return "💰 *Pemasukan " . $now->translatedFormat('F Y') . ":*\nRp" . number_format($income, 0, ',', '.') .
                       "\n\n_Ketik /rekap untuk laporan lengkap_";
            }
        }

        // ── Laporan / rekap singkat ────────────────────────────────────────
        $laporanKeywords = [
            'laporan', 'rekap', 'rekapan', 'ringkasan', 'summary',
            'laporan bulan ini', 'rekap bulan ini', 'bulan ini gimana',
            'bulan ini bagaimana', 'gimana keuangan', 'bagaimana keuangan',
        ];
        foreach ($laporanKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $now     = now();
                $income  = $user->transactions()->completed()
                               ->byMonth($now->year, $now->month)->where('type', 'income')->sum('amount');
                $expense = $user->transactions()->completed()
                               ->byMonth($now->year, $now->month)->where('type', 'expense')->sum('amount');
                $net     = $income - $expense;
                $msg  = "📊 *Ringkasan " . $now->translatedFormat('F Y') . ":*\n\n";
                $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                $msg .= ($net >= 0 ? "📈" : "📉") . " Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n\n";
                $msg .= "_Ketik /rekap untuk rekapan lengkap dengan detail & AI insight_";
                return $msg;
            }
        }

        // ── Daftar wallet ─────────────────────────────────────────────────
        $walletKeywords = ['wallet', 'dompet', 'rekening', 'akun', 'daftar wallet', 'list wallet'];
        foreach ($walletKeywords as $kw) {
            if ($lower === $kw || str_starts_with($lower, 'cek ' . $kw) || str_starts_with($lower, 'lihat ' . $kw)) {
                $wallets = $user->wallets()->where('is_active', true)->orderBy('sort_order')->get();
                $lines   = ["💳 *Daftar Wallet:*\n"];
                foreach ($wallets as $w) {
                    $type    = ucfirst(str_replace('_', ' ', $w->type));
                    $lines[] = "• *{$w->name}* ({$type})\n  Rp" . number_format($w->balance, 0, ',', '.');
                }
                return implode("\n", $lines);
            }
        }

        // ── Bantuan / help ─────────────────────────────────────────────────
        $helpKeywords = ['help', 'bantuan', 'cara pakai', 'cara penggunaan', 'bisa apa', 'apa yang bisa', 'fitur'];
        foreach ($helpKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $help  = "🤖 *Finance AI Bot — Panduan*\n\n";
                $help .= "*Cara input transaksi (cukup ketik natural):*\n";
                $help .= "💬 _beli kopi 25rb gopay_\n";
                $help .= "💬 _gaji masuk 5jt bca_\n";
                $help .= "💬 _transfer 100rb dari bca ke gopay_\n";
                $help .= "📸 Foto struk → otomatis dicatat\n";
                $help .= "🎤 Voice note → otomatis dicatat\n\n";
                $help .= "*Cek info (bisa tulis bebas):*\n";
                $help .= "💬 _saldo_ atau _cek saldo_\n";
                $help .= "💬 _pengeluaran bulan ini_\n";
                $help .= "💬 _laporan_ atau _rekap_\n";
                $help .= "💬 _bulan ini boros gak?_\n\n";
                $help .= "*Commands:*\n";
                $help .= "/saldo /laporan /rekap /topkategori /wallet /help\n\n";
                $help .= "_Tanya apa saja seputar keuanganmu, bot akan jawab!_ 😊";
                return $help;
            }
        }

        // ── Quick stats: kemarin ──────────────────────────────────────────
        $kemarinKeywords = ['kemarin', 'yesterday', 'kemarin habis', 'kemarin keluar', 'kemarin berapa'];
        foreach ($kemarinKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $tz        = $user->timezone ?? 'Asia/Jakarta';
                $yesterday = now()->timezone($tz)->subDay()->toDateString();
                $expense   = $user->transactions()->completed()
                                 ->whereDate('transaction_date', $yesterday)
                                 ->where('type', 'expense')->sum('amount');
                $income    = $user->transactions()->completed()
                                 ->whereDate('transaction_date', $yesterday)
                                 ->where('type', 'income')->sum('amount');
                $count     = $user->transactions()->completed()
                                 ->whereDate('transaction_date', $yesterday)->count();
                $dateLabel = now()->timezone($tz)->subDay()->format('d M Y');

                if ($count === 0) {
                    return "📅 *Kemarin ({$dateLabel})*\n\nTidak ada transaksi yang dicatat.";
                }

                $msg  = "📅 *Kemarin ({$dateLabel})*\n\n";
                if ($expense > 0) $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                if ($income > 0)  $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "📝 Total transaksi: {$count}";
                return $msg;
            }
        }

        // ── Quick stats: minggu ini ───────────────────────────────────────
        $mingguKeywords = [
            'minggu ini', 'this week', 'minggu ini habis', 'minggu ini keluar',
            'seminggu ini', '7 hari', '7 hari terakhir', 'sepekan',
        ];
        foreach ($mingguKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $tz        = $user->timezone ?? 'Asia/Jakarta';
                $startDate = now()->timezone($tz)->startOfWeek()->toDateString();
                $endDate   = now()->timezone($tz)->toDateString();

                $expense = $user->transactions()->completed()
                               ->whereBetween('transaction_date', [$startDate, $endDate])
                               ->where('type', 'expense')->sum('amount');
                $income  = $user->transactions()->completed()
                               ->whereBetween('transaction_date', [$startDate, $endDate])
                               ->where('type', 'income')->sum('amount');
                $count   = $user->transactions()->completed()
                               ->whereBetween('transaction_date', [$startDate, $endDate])->count();

                // Top 3 kategori minggu ini
                $topCats = $user->transactions()->completed()
                    ->where('type', 'expense')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->selectRaw('category_id, SUM(amount) as total')
                    ->groupBy('category_id')
                    ->orderByDesc('total')
                    ->with('category')
                    ->limit(3)->get();

                $msg  = "📅 *Minggu Ini*\n\n";
                if ($expense > 0) $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                if ($income > 0)  $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "📝 Total transaksi: {$count}\n";

                if ($topCats->isNotEmpty()) {
                    $msg .= "\n*Top Pengeluaran:*\n";
                    foreach ($topCats as $i => $t) {
                        $name = $t->category?->name ?? 'Lainnya';
                        $msg .= ($i + 1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.') . "\n";
                    }
                }
                $msg .= "\n_Ketik /rekap untuk laporan bulanan_";
                return $msg;
            }
        }

        // ── Quick stats: bulan lalu ───────────────────────────────────────
        $bulanLaluKeywords = [
            'bulan lalu', 'last month', 'bulan kemarin', 'bulan sebelumnya',
        ];
        foreach ($bulanLaluKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $now       = now();
                $prevMonth = $now->copy()->subMonth();
                $income    = $user->transactions()->completed()
                                 ->byMonth($prevMonth->year, $prevMonth->month)->where('type', 'income')->sum('amount');
                $expense   = $user->transactions()->completed()
                                 ->byMonth($prevMonth->year, $prevMonth->month)->where('type', 'expense')->sum('amount');
                $net       = $income - $expense;

                // Top 5 kategori bulan lalu
                $topCats = $user->transactions()->completed()
                    ->where('type', 'expense')
                    ->byMonth($prevMonth->year, $prevMonth->month)
                    ->selectRaw('category_id, SUM(amount) as total')
                    ->groupBy('category_id')
                    ->orderByDesc('total')
                    ->with('category')
                    ->limit(5)->get();

                $label = $prevMonth->translatedFormat('F Y');
                $msg   = "📊 *Bulan Lalu ({$label})*\n\n";
                $msg  .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg  .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                $msg  .= ($net >= 0 ? "📈" : "📉") . " Cashflow: " . ($net >= 0 ? "+" : "") . "Rp" . number_format($net, 0, ',', '.') . "\n";

                if ($topCats->isNotEmpty()) {
                    $msg .= "\n*Top Pengeluaran:*\n";
                    foreach ($topCats as $i => $t) {
                        $name = $t->category?->name ?? 'Lainnya';
                        $pct  = $expense > 0 ? round($t->total / $expense * 100) : 0;
                        $msg .= ($i + 1) . ". {$name}: Rp" . number_format($t->total, 0, ',', '.') . " ({$pct}%)\n";
                    }
                }
                $msg .= "\n_Ketik /rekap untuk rekap lengkap bulan ini_";
                return $msg;
            }
        }

        // ── Quick stats: hari ini ─────────────────────────────────────────
        $hariIniKeywords = [
            'hari ini berapa', 'hari ini habis', 'hari ini keluar', 'pengeluaran hari ini',
            'today', 'udah habis berapa hari ini', 'sudah keluar berapa hari ini',
        ];
        foreach ($hariIniKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $tz      = $user->timezone ?? 'Asia/Jakarta';
                $today   = now()->timezone($tz)->toDateString();
                $expense = $user->transactions()->completed()
                               ->whereDate('transaction_date', $today)
                               ->where('type', 'expense')->sum('amount');
                $income  = $user->transactions()->completed()
                               ->whereDate('transaction_date', $today)
                               ->where('type', 'income')->sum('amount');
                $count   = $user->transactions()->completed()
                               ->whereDate('transaction_date', $today)->count();

                if ($count === 0) {
                    return "📅 *Hari Ini*\n\nBelum ada transaksi yang dicatat hari ini.\nCatat sekarang sebelum lupa! 💪";
                }

                $msg  = "📅 *Hari Ini*\n\n";
                if ($expense > 0) $msg .= "💸 Pengeluaran: Rp" . number_format($expense, 0, ',', '.') . "\n";
                if ($income > 0)  $msg .= "💰 Pemasukan: Rp" . number_format($income, 0, ',', '.') . "\n";
                $msg .= "📝 Total transaksi: {$count}";
                return $msg;
            }
        }

        // ── Hutang / Piutang ──────────────────────────────────────────────
        $debtKeywords = ['hutang', 'piutang', 'utang', 'cek hutang', 'cek piutang', 'lihat hutang', 'lihat piutang'];
        foreach ($debtKeywords as $kw) {
            if ($lower === $kw || str_contains($lower, $kw)) {
                return null; // biarkan command handler /hutang yang handle, atau fallback ke AI
            }
        }

        return null; // tidak ada yang cocok → lanjut ke flow berikutnya
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

            case '/hutang':
            case '/piutang':
            case '/utang':
                $this->sendDebtSummary($user, $chatId);
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
                $help .= "/hutang — Cek hutang & piutang\n";
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

        // ── wallet_confirm:{scan_id}:{wallet_name} ────────────────────────
        if (str_starts_with($data, 'wallet_confirm:')) {
            $parts      = explode(':', $data, 3);
            $scanId     = $parts[1] ?? null;
            $walletName = $parts[2] ?? null;

            if (!$scanId || !$walletName) return;

            $receiptScan = \App\Models\ReceiptScan::find($scanId);
            if (!$receiptScan || $receiptScan->user_id !== $user->id) {
                $this->telegram->editMessageText($chatId, $msgId, "❌ Data struk tidak ditemukan.");
                return;
            }

            // Process with chosen wallet
            $result = $this->receiptScanner->confirmWallet($receiptScan, $walletName, $user);

            // Edit original message — remove keyboard and show result
            $this->telegram->editMessageText($chatId, $msgId, $result['message']);
            return;
        }

        // ── receipt_cancel:{scan_id} ───────────────────────────────────────
        if (str_starts_with($data, 'receipt_cancel:')) {
            $scanId = explode(':', $data, 2)[1] ?? null;

            if (!$scanId) return;

            $receiptScan = \App\Models\ReceiptScan::find($scanId);
            if (!$receiptScan || $receiptScan->user_id !== $user->id) {
                $this->telegram->editMessageText($chatId, $msgId, "❌ Data struk tidak ditemukan.");
                return;
            }

            // cancelScan bisa return false jika ENUM cancelled belum ada di DB,
            // tapi tetap tampilkan pesan berhasil ke user karena needs_wallet_confirmation sudah false
            $this->receiptScanner->cancelScan($receiptScan);

            $this->telegram->editMessageText(
                $chatId,
                $msgId,
                "Transaksi dibatalkan.\n\nStruk tidak dicatat. Kirim ulang foto jika ingin mencoba lagi."
            );
            return;
        }
    }

    /**
     * Send wallet selection as inline keyboard buttons.
     * Includes a ❌ Batalkan button at the bottom.
     */
    protected function sendWalletKeyboard(int|string $chatId, User $user, int $receiptScanId, string $promptText): void
    {
        $wallets = $user->wallets()->where('is_active', true)->get();

        // Build wallet button rows (2 buttons per row)
        $buttons = [];
        $row     = [];
        foreach ($wallets as $wallet) {
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

        // Tombol batalkan — selalu di baris tersendiri paling bawah
        $buttons[] = [
            [
                'text'          => '❌ Batalkan Transaksi',
                'callback_data' => "receipt_cancel:{$receiptScanId}",
            ],
        ];

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
            'hutang_piutang_aktif'           => $this->buildDebtContext($user),
        ];
    }

    protected function buildDebtContext(User $user): array
    {
        $debts = \App\Models\Debt::where('user_id', $user->id)->active()->with('wallet')->get();
        if ($debts->isEmpty()) return ['total_piutang' => 0, 'total_hutang' => 0, 'items' => []];

        return [
            'total_piutang' => (float) $debts->where('type', 'receivable')->sum('remaining_amount'),
            'total_hutang'  => (float) $debts->where('type', 'payable')->sum('remaining_amount'),
            'items' => $debts->map(fn($d) => [
                'tipe'    => $d->type === 'receivable' ? 'piutang' : 'hutang',
                'nama'    => $d->contact_name,
                'total'   => (float)$d->amount,
                'sisa'    => (float)$d->remaining_amount,
                'status'  => $d->status,
                'jatuh_tempo' => $d->due_date?->format('d M Y'),
            ])->toArray(),
        ];
    }

    /** Tampilkan ringkasan hutang/piutang via Telegram */
    protected function sendDebtSummary(User $user, int|string $chatId): void
    {
        $debts = \App\Models\Debt::where('user_id', $user->id)->active()->orderBy('due_date')->get();

        if ($debts->isEmpty()) {
            $this->telegram->sendMessage($chatId,
                "✅ *Hutang & Piutang*\n\nTidak ada hutang atau piutang aktif saat ini! 🎉\n\n" .
                "_Catat hutang/piutang baru di: " . config('app.url') . "/debts_"
            );
            return;
        }

        $receivables = $debts->where('type', 'receivable');
        $payables    = $debts->where('type', 'payable');
        $totalR = $receivables->sum('remaining_amount');
        $totalP = $payables->sum('remaining_amount');

        $msg = "🤝 *HUTANG & PIUTANG AKTIF*\n\n";

        if ($receivables->isNotEmpty()) {
            $msg .= "💰 *Piutang (orang hutang ke kamu):*\n";
            foreach ($receivables as $d) {
                $sisa  = 'Rp' . number_format($d->remaining_amount, 0, ',', '.');
                $due   = $d->due_date ? ' · jatuh tempo ' . $d->due_date->format('d M Y') : '';
                $over  = $d->is_overdue ? ' ⚠️' : '';
                $msg  .= "• *{$d->contact_name}*: {$sisa}{$due}{$over}\n";
            }
            $msg .= "_Total piutang: Rp" . number_format($totalR, 0, ',', '.') . "_\n\n";
        }

        if ($payables->isNotEmpty()) {
            $msg .= "💸 *Hutang (kamu hutang ke orang):*\n";
            foreach ($payables as $d) {
                $sisa  = 'Rp' . number_format($d->remaining_amount, 0, ',', '.');
                $due   = $d->due_date ? ' · jatuh tempo ' . $d->due_date->format('d M Y') : '';
                $over  = $d->is_overdue ? ' ⚠️' : '';
                $msg  .= "• *{$d->contact_name}*: {$sisa}{$due}{$over}\n";
            }
            $msg .= "_Total hutang: Rp" . number_format($totalP, 0, ',', '.') . "_\n\n";
        }

        $overdue = $debts->filter(fn($d) => $d->is_overdue)->count();
        if ($overdue > 0) {
            $msg .= "⚠️ *{$overdue} item sudah melewati jatuh tempo!*\n\n";
        }

        $msg .= "_Kelola semua di: " . config('app.url') . "/debts_";
        $this->telegram->sendMessage($chatId, $msg);
    }
