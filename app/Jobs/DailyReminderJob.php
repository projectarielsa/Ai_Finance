<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramBotService;
use Carbon\Carbon;
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
        $serverHour = $this->reminderHour; // jam server (UTC atau timezone server)

        // Ambil semua user yang punya Telegram aktif + reminder enabled
        $users = User::where('is_active', true)
            ->where('daily_reminder_enabled', true)
            ->where('telegram_notifications', true)
            ->whereNotNull('telegram_id')
            ->get()
            ->filter(function ($u) use ($serverHour) {
                // Konversi jam reminder user ke timezone server untuk perbandingan yang benar
                $userTz       = $u->timezone ?? 'Asia/Jakarta';
                $reminderTime = $u->daily_reminder_time ?? '21:00';
                $userHour     = substr($reminderTime, 0, 2); // "21"

                // Buat Carbon di timezone user dengan jam reminder, lalu konversi ke server time
                try {
                    $today = now()->timezone($userTz);
                    $reminderInServerTz = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today->format('Y-m-d') . ' ' . $reminderTime, $userTz)
                        ->timezone(config('app.timezone', 'UTC'))
                        ->format('H');
                } catch (\Throwable $e) {
                    // Fallback jika timezone tidak valid: bandingkan langsung (tidak akurat)
                    $reminderInServerTz = $userHour;
                }

                return $reminderInServerTz === $serverHour;
            });

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
