<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Coba login tanpa persist dulu (tidak pakai Auth::login agar bisa intercept 2FA)
        if (!Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        // Cek akun aktif
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        // Jika 2FA aktif & Telegram terhubung → kirim OTP, jangan login dulu
        if ($user->two_factor_enabled && $user->telegram_id) {
            // Simpan state sementara di session
            session([
                'two_factor_user_id' => $user->id,
                'two_factor_remember' => $request->boolean('remember'),
            ]);

            // Kirim OTP via Telegram
            $twoFactor = app(TwoFactorController::class);
            $twoFactor->sendOtp($user);

            return redirect()->route('two-factor.verify');
        }

        // Tidak ada 2FA → login langsung
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        session(['two_factor_passed' => true]);

        // Hapus semua session lama KECUALI session aktif saat ini (single device login)
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
