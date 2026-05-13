<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Jika 2FA aktif tapi session belum verified → redirect ke verifikasi
        if ($user->two_factor_enabled && !session('two_factor_passed')) {
            // Jangan redirect jika sudah di halaman 2FA atau logout
            if (!$request->routeIs('two-factor.*') && !$request->routeIs('logout')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Sesi tidak valid. Silakan login ulang.');
            }
        }

        return $next($request);
    }
}
