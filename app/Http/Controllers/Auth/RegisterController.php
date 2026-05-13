<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\Category;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Step 1: Validasi data, simpan ke pending_registrations, kirim OTP via email.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Hapus pending registration sebelumnya untuk email ini (jika ada)
        PendingRegistration::where('email', $data['email'])->delete();

        // Simpan data registrasi sementara
        $pending = PendingRegistration::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'password'       => Hash::make($data['password']),
            'otp_code'       => '000000', // placeholder, akan di-generate
            'otp_expires_at' => now(),
        ]);

        // Generate OTP
        $otpCode = $pending->generateOtp();

        // Kirim OTP via Email
        Mail::to($pending->email)->send(new RegistrationOtpMail($otpCode, $pending->name));

        // Simpan ID ke session untuk verifikasi
        session(['pending_registration_id' => $pending->id]);

        return redirect()->route('register.verify', ['ref' => $pending->id]);
    }

    /**
     * Tampilkan halaman input OTP.
     */
    public function showVerifyForm(Request $request)
    {
        $pendingId = session('pending_registration_id') ?? $request->query('ref');
        if (!$pendingId) {
            return redirect()->route('register')->with('error', 'Silakan daftar terlebih dahulu.');
        }

        $pending = PendingRegistration::find($pendingId);
        if (!$pending) {
            session()->forget('pending_registration_id');
            return redirect()->route('register')->with('error', 'Data pendaftaran tidak ditemukan. Silakan daftar ulang.');
        }

        // Re-set session in case it was lost
        session(['pending_registration_id' => $pending->id]);

        return view('auth.register-verify', [
            'email'     => $pending->email,
            'pendingId' => $pending->id,
        ]);
    }

    /**
     * Step 2: Verifikasi OTP, jika valid buat user sesungguhnya.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $pendingId = session('pending_registration_id') ?? $request->input('pending_id');
        if (!$pendingId) {
            return redirect()->route('register')->with('error', 'Session kedaluwarsa. Silakan daftar ulang.');
        }

        $pending = PendingRegistration::find($pendingId);
        if (!$pending) {
            session()->forget('pending_registration_id');
            return redirect()->route('register')->with('error', 'Data pendaftaran tidak ditemukan. Silakan daftar ulang.');
        }

        // Cek max attempts
        if ($pending->hasExceededAttempts()) {
            $pending->delete();
            session()->forget('pending_registration_id');
            return redirect()->route('register')->with('error', 'Terlalu banyak percobaan salah. Silakan daftar ulang.');
        }

        // Verifikasi OTP
        if (!$pending->isOtpValid($request->code)) {
            $pending->increment('attempts');
            return back()->withErrors(['code' => 'Kode OTP salah atau sudah kadaluarsa.']);
        }

        // OTP Valid — Buat user sesungguhnya
        $user = User::create([
            'name'     => $pending->name,
            'email'    => $pending->email,
            'phone'    => $pending->phone,
            'password' => $pending->password, // sudah di-hash
        ]);

        // Create default wallet (Cash)
        Wallet::create([
            'user_id'         => $user->id,
            'name'            => 'Cash',
            'slug'            => 'cash',
            'type'            => 'cash',
            'provider'        => 'Cash',
            'icon'            => 'banknotes',
            'color'           => '#22c55e',
            'balance'         => 0,
            'initial_balance' => 0,
        ]);

        // Bersihkan data sementara
        $pending->delete();
        session()->forget('pending_registration_id');

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard'));
    }

    /**
     * Kirim ulang OTP ke email.
     */
    public function resendOtp(Request $request)
    {
        $pendingId = session('pending_registration_id') ?? $request->input('pending_id');
        if (!$pendingId) {
            return redirect()->route('register')->with('error', 'Session kedaluwarsa. Silakan daftar ulang.');
        }

        $pending = PendingRegistration::find($pendingId);
        if (!$pending) {
            session()->forget('pending_registration_id');
            return redirect()->route('register')->with('error', 'Data pendaftaran tidak ditemukan.');
        }

        // Generate OTP baru
        $otpCode = $pending->generateOtp();

        // Kirim ulang via email
        Mail::to($pending->email)->send(new RegistrationOtpMail($otpCode, $pending->name));

        return back()->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }
}
