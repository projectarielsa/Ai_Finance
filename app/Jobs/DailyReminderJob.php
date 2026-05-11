<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $reminderHour  Format "HH" — jalankan untuk user dengan jam ini
     */
    public function __construct(public readonly string $reminderHour) {}

    public function handle(TelegramBotService $telegram): void
    {
        $now = now();

        // Ambil semua user yang punya Telegram aktif + reminder enabled + jam reminder = sekarang
        $users = User::where('is_active', true)
            ->where('daily_reminder_enabled', true)
            ->where('telegram_notifications', true)
            ->whereNotNull('telegram_id')
            ->get()
            ->filter(fn($u) => substr($u->daily_reminder_time ?? '21:00', 0, 2) === $this->reminderHour);

        foreach ($users as $user) {
            try {
                $today = now()->timezone($user->timezone ?? 'Asia/Jakarta')->toDateString();

                // Hitung transaksi yang dicatat hari ini
                $todayCount = $user->transactions()
                    ->whereDate('transaction_date', $today)
                    ->where('status', 'completed')
                    ->count();

                $todayExpense = $user->transactions()
                    ->whereDate('transaction_date', $today)
                    ->where('type', 'expense')
                    ->where('status', 'completed')
                    ->sum('amount');

                $todayIncome = $user->transactions()
                    ->whereDate('transaction_date', $today)
                    ->where('type', 'income')
                    ->where('status', 'completed')
                    ->sum('amount');

                if ($todayCount === 0) {
                    // Belum ada transaksi sama sekali hari ini
                    $msg  = "📝 *Reminder Harian*\n\n";
                    $msg .= "Hai *{$user->name}*! Kamu belum mencatat transaksi hari ini.\n\n";
                    $msg .= "Ada pengeluaran yang terlupakan? Kirim sekarang, contoh:\n";
                    $msg .= "_makan siang 35rb gopay_\n";
                    $msg .= "_isi bensin 50rb cash_\n\n";
                    $msg .= "_Ketik /saldo untuk cek saldo terkini_";
                } else {
                    // Sudah ada transaksi — beri ringkasan harian
                    $msg  = "📊 *Ringkasan Hari Ini*\n\n";
                    $msg .= "Kamu sudah mencatat *{$todayCount} transaksi* hari ini 👍\n\n";
                    if ($todayExpense > 0) {
                        $msg .= "💸 Pengeluaran: Rp" . number_format($todayExpense, 0, ',', '.') . "\n";
                    }
                    if ($todayIncome > 0) {
                        $msg .= "💰 Pemasukan: Rp" . number_format($todayIncome, 0, ',', '.') . "\n";
                    }
                    $msg .= "\n_Ada yang belum tercatat? Langsung kirim ke sini!_ 😊";
                }

                $telegram->sendMessage($user->telegram_id, $msg);

            } catch (\Throwable $e) {
                Log::error("DailyReminderJob failed for user #{$user->id}: " . $e->getMessage());
            }
        }
    }
}
