<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|unique:users,email,' . $user->id,
            'phone'                   => 'nullable|string|max:20',
            'timezone'                => 'nullable|string',
            'currency'                => 'nullable|string|max:5',
            'minimum_balance_warning' => 'nullable|numeric|min:0',
            'telegram_notifications'  => 'nullable|boolean',
            'avatar'                  => 'nullable|image|max:2048',
            // Reminder settings
            'daily_reminder_enabled'        => 'nullable|boolean',
            'daily_reminder_time'           => 'nullable|regex:/^\d{2}:\d{2}$/',
            'weekly_summary_enabled'        => 'nullable|boolean',
            'big_transaction_alert_enabled' => 'nullable|boolean',
            'big_transaction_threshold'     => 'nullable|numeric|min:10000',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Hanya update boolean fields yang memang ada di form yang di-submit
        // Ini mencegah form Profil me-reset reminder settings & sebaliknya
        if ($request->has('telegram_notifications') || $request->has('_telegram_notifications_field')) {
            $data['telegram_notifications'] = $request->boolean('telegram_notifications');
        }
        if ($request->has('daily_reminder_enabled') || $request->has('_reminder_field')) {
            $data['daily_reminder_enabled']        = $request->boolean('daily_reminder_enabled');
            $data['weekly_summary_enabled']        = $request->boolean('weekly_summary_enabled');
            $data['big_transaction_alert_enabled'] = $request->boolean('big_transaction_alert_enabled');
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password saat ini tidak benar.');
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password berhasil diubah!');
    }

    // ── 2FA Management ────────────────────────────────────────────────────────

    public function enableTwoFactor(Request $request)
    {
        $user = Auth::user();

        if (!$user->telegram_id) {
            return back()->with('error', '❌ Hubungkan akun Telegram dulu sebelum mengaktifkan 2FA. Buka bot dan ketik /link email@kamu.com');
        }

        $user->update(['two_factor_enabled' => true]);

        // Kirim notif ke Telegram
        app(\App\Services\TelegramBotService::class)->sendMessage(
            $user->telegram_id,
            "🔐 *Two-Factor Authentication Aktif*\n\n" .
            "Mulai sekarang, setiap login ke Finance AI akan membutuhkan kode OTP yang dikirim ke Telegram ini.\n\n" .
            "_Jika bukan kamu yang mengaktifkan ini, segera ubah password!_"
        );

        return back()->with('success', '✅ Verifikasi dua langkah berhasil diaktifkan!');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Password tidak benar.');
        }

        $user->update([
            'two_factor_enabled'    => false,
            'two_factor_code'       => null,
            'two_factor_expires_at' => null,
        ]);

        if ($user->telegram_id) {
            app(\App\Services\TelegramBotService::class)->sendMessage(
                $user->telegram_id,
                "⚠️ *Two-Factor Authentication Dinonaktifkan*\n\n" .
                "Login ke Finance AI sekarang hanya membutuhkan email & password.\n\n" .
                "_Jika bukan kamu yang melakukan ini, segera ubah password!_"
            );
        }

        return back()->with('success', 'Verifikasi dua langkah berhasil dinonaktifkan.');
    }
}
