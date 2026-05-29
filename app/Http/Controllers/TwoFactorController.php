<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TwoFactorController extends Controller
{
    public function __construct(protected TelegramBotService $telegram) {}

    /** Tampilkan halaman input OTP */
    public function showVerifyForm()
    {
        // Hanya bisa diakses jika sudah login tapi belum 2FA-verified
        if (!session('two_factor_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    /** Kirim ulang OTP ke Telegram */
    public function resend(Request $request)
    {
        $userId = session('two_factor_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->telegram_id) {
            return back()->with('error', 'Akun Anda belum terhubung ke Telegram. Hubungi admin.');
        }

        $this->sendOtp($user);
        return back()->with('status', 'Kode OTP baru telah dikirim ke Telegram Anda.');
    }

    /** Verifikasi OTP yang diinput user */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = session('two_factor_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->isTwoFactorCodeValid($request->code)) {
            return back()->withErrors(['code' => 'Kode OTP salah atau sudah kadaluarsa.']);
        }

        // OTP valid — login user dan hapus session sementara
        $user->clearTwoFactorCode();
        session()->forget('two_factor_user_id');

        Auth::login($user, session()->pull('two_factor_remember', false));
        $request->session()->regenerate();

        // Hapus semua session lama KECUALI session aktif saat ini (single device login)
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        // Mark 2FA as passed for this session
        session(['two_factor_passed' => true]);

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }

    /** Helper: generate OTP dan kirim ke Telegram */
    public function sendOtp(\App\Models\User $user): void
    {
        $code = $user->generateTwoFactorCode();

        $this->telegram->sendMessage(
            $user->telegram_id,
            "🔐 *Kode Verifikasi Login*\n\n" .
            "Kode OTP Anda:\n" .
            "`{$code}`\n\n" .
            "⏱ Berlaku 10 menit.\n" .
            "_Jangan berikan kode ini kepada siapapun!_"
        );
    }
}
