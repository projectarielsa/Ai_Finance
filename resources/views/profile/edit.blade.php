@extends('layouts.app')
@section('title', 'Profil')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Kelola informasi akun dan preferensi Anda')

@section('content')
<div class="max-w-2xl mx-auto space-y-5 animate-fade-in">

    {{-- Profile Info --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-5">Informasi Profil</h3>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')

            <div class="flex items-center gap-4 mb-4">
                <img src="{{ $user->avatar_url }}" alt="avatar" class="w-16 h-16 rounded-2xl object-cover ring-2 ring-primary-500/30">
                <div>
                    <label class="btn-secondary text-sm cursor-pointer">
                        Ganti Foto
                        <input type="file" name="avatar" accept="image/*" class="hidden">
                    </label>
                    <p class="text-dark-500 text-xs mt-1">JPG, PNG. Maks 2MB</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name',$user->name) }}" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Email</label>
                    <input type="email" name="email" value="{{ old('email',$user->email) }}" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Nomor Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone',$user->phone) }}" class="input-field" placeholder="08123456789">
                </div>
                <div class="form-group">
                    <label class="input-label">Timezone</label>
                    <select name="timezone" class="input-field">
                        <option value="Asia/Jakarta" {{ $user->timezone=='Asia/Jakarta'?'selected':'' }}>WIB (Jakarta)</option>
                        <option value="Asia/Makassar" {{ $user->timezone=='Asia/Makassar'?'selected':'' }}>WITA (Makassar)</option>
                        <option value="Asia/Jayapura" {{ $user->timezone=='Asia/Jayapura'?'selected':'' }}>WIT (Jayapura)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Peringatan Saldo Minimum (Rp)</label>
                    <input type="number" name="minimum_balance_warning" value="{{ old('minimum_balance_warning',$user->minimum_balance_warning) }}" class="input-field" min="0">
                </div>
                <div class="col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="telegram_notifications" value="1" {{ $user->telegram_notifications ? 'checked' : '' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <div>
                            <span class="text-sm text-dark-200">Notifikasi Telegram</span>
                            <p class="text-xs text-dark-500">Terima laporan bulanan dan peringatan saldo lewat Telegram Bot</p>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-primary">Simpan Profil</button>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-5">Ubah Password</h3>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="input-label">Password Saat Ini</label>
                <input type="password" name="current_password" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">Password Baru</label>
                <input type="password" name="password" class="input-field" required minlength="8">
            </div>
            <div class="form-group">
                <label class="input-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="input-field" required>
            </div>
            <button type="submit" class="btn-primary">Ubah Password</button>
        </form>
    </div>

    {{-- Account Info --}}
    <div class="glass-card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-dark-400 text-sm">Role: <span class="text-white font-medium">{{ ucfirst($user->role) }}</span></p>
                <p class="text-dark-400 text-sm mt-0.5">Member sejak: <span class="text-white font-medium">{{ $user->created_at->format('d M Y') }}</span></p>
            </div>
            @if($user->phone)
            <div class="text-right">
                <p class="text-dark-400 text-xs">Nomor telepon</p>
                <p class="text-white text-sm font-medium">{{ $user->phone }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Telegram Connection --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-2">🤖 Hubungkan Telegram</h3>
        @if($user->telegram_id)
        <div class="flex items-center gap-3 p-3 rounded-xl bg-green-500/10 border border-green-500/20 mb-4">
            <span class="text-green-400 text-xl">✅</span>
            <div>
                <p class="text-green-300 text-sm font-medium">Telegram sudah terhubung</p>
                @if($user->telegram_username)<p class="text-dark-400 text-xs">{{'@'}}{{ $user->telegram_username }}</p>@endif
            </div>
        </div>
        @else
        <div class="p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/20 mb-4">
            <p class="text-yellow-300 text-sm">Telegram belum terhubung. Ikuti langkah berikut:</p>
        </div>
        @endif

        <ol class="text-dark-300 text-sm space-y-3 list-decimal list-inside mb-4">
            <li>Buka bot Telegram:
                <a href="https://t.me/{{ config('services.telegram.bot_username', 'FinanceAIBot') }}" target="_blank" class="inline-flex items-center gap-1 text-primary-400 font-medium hover:text-primary-300 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    {{'@'}}{{ config('services.telegram.bot_username', 'FinanceAIBot') }}
                </a>
            </li>
            <li>Kirim perintah ini ke bot:
                <code class="text-xs bg-dark-700 text-blue-300 px-2 py-1 rounded inline-block mt-1">/link {{ $user->email }}</code>
            </li>
            <li>Selesai! Bot akan otomatis terhubung ke akun Anda</li>
        </ol>

        <a href="https://t.me/{{ config('services.telegram.bot_username', 'FinanceAIBot') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-sm font-medium hover:bg-blue-500/20 hover:text-blue-300 transition-all">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            Buka Bot di Telegram
        </a>
    </div>

    {{-- Reminder & Notifikasi Telegram --}}
    @if($user->telegram_id)
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-1">🔔 Pengaturan Reminder</h3>
        <p class="text-dark-400 text-xs mb-5">Notifikasi otomatis via Telegram Bot</p>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf @method('PUT')
            <input type="hidden" name="name"  value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">

            {{-- Reminder Harian --}}
            <div class="p-4 rounded-xl bg-dark-800/40 border border-dark-600/30 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-white text-sm font-medium">📝 Reminder Harian</p>
                        <p class="text-dark-400 text-xs mt-0.5">Bot mengingatkan untuk catat transaksi. Jika sudah ada transaksi hari ini, bot kirim ringkasan.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                        <input type="checkbox" name="daily_reminder_enabled" value="1"
                               {{ $user->daily_reminder_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-6 bg-dark-600 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer
                                    peer-checked:bg-primary-500 transition-colors"></div>
                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform
                                    peer-checked:translate-x-4"></div>
                    </label>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-dark-400 text-xs flex-shrink-0">Jam reminder:</label>
                    <select name="daily_reminder_time" class="input-field py-1.5 text-sm w-32">
                        @foreach(['07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00'] as $t)
                        <option value="{{ $t }}" {{ ($user->daily_reminder_time ?? '21:00') === $t ? 'selected' : '' }}>
                            {{ $t }} WIB
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Summary Mingguan --}}
            <div class="p-4 rounded-xl bg-dark-800/40 border border-dark-600/30">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-white text-sm font-medium">📅 Summary Mingguan</p>
                        <p class="text-dark-400 text-xs mt-0.5">Setiap Senin pagi, bot kirim ringkasan pengeluaran 7 hari terakhir beserta perbandingan minggu sebelumnya.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                        <input type="checkbox" name="weekly_summary_enabled" value="1"
                               {{ $user->weekly_summary_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-6 bg-dark-600 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer
                                    peer-checked:bg-primary-500 transition-colors"></div>
                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform
                                    peer-checked:translate-x-4"></div>
                    </label>
                </div>
            </div>

            {{-- Alert Transaksi Besar --}}
            <div class="p-4 rounded-xl bg-dark-800/40 border border-dark-600/30 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-white text-sm font-medium">⚠️ Alert Transaksi Besar</p>
                        <p class="text-dark-400 text-xs mt-0.5">Bot langsung kirim notifikasi setiap ada pengeluaran melebihi batas yang ditentukan.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                        <input type="checkbox" name="big_transaction_alert_enabled" value="1"
                               {{ $user->big_transaction_alert_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-10 h-6 bg-dark-600 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer
                                    peer-checked:bg-primary-500 transition-colors"></div>
                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform
                                    peer-checked:translate-x-4"></div>
                    </label>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-dark-400 text-xs flex-shrink-0">Alert jika &gt;</label>
                    <div class="relative flex-1 max-w-[180px]">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 text-sm">Rp</span>
                        <input type="number" name="big_transaction_threshold"
                               value="{{ old('big_transaction_threshold', (int)$user->big_transaction_threshold) }}"
                               class="input-field py-1.5 text-sm pl-8" min="10000" step="10000">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">Simpan Pengaturan Reminder</button>
        </form>

        {{-- Cara pakai quick stats di Telegram --}}
        <div class="mt-5 pt-5 border-t border-dark-700/30">
            <p class="text-dark-400 text-xs font-semibold uppercase tracking-wider mb-2">💬 Quick Stats di Telegram</p>
            <p class="text-dark-400 text-xs mb-3">Ketik langsung ke bot tanpa command:</p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-2 rounded-lg bg-dark-800/50 text-dark-300"><code>hari ini berapa</code></div>
                <div class="p-2 rounded-lg bg-dark-800/50 text-dark-300"><code>kemarin</code></div>
                <div class="p-2 rounded-lg bg-dark-800/50 text-dark-300"><code>minggu ini</code></div>
                <div class="p-2 rounded-lg bg-dark-800/50 text-dark-300"><code>bulan lalu</code></div>
                <div class="p-2 rounded-lg bg-dark-800/50 text-dark-300 col-span-2"><code>pengeluaran bulan ini</code></div>
            </div>
        </div>
    </div>
    @endif
</div>

    {{-- ── 2FA SECTION ──────────────────────────────── --}}
    <div class="glass-card p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-9 h-9 rounded-xl {{ $user->two_factor_enabled ? 'bg-green-500/15 text-green-400' : 'bg-dark-700/50 text-dark-400' }} flex items-center justify-center text-lg">
                🔐
            </div>
            <div>
                <h3 class="text-white font-semibold">Verifikasi Dua Langkah (2FA)</h3>
                <p class="text-dark-400 text-xs mt-0.5">Tambah lapisan keamanan ekstra saat login</p>
            </div>
            @if($user->two_factor_enabled)
            <span class="ml-auto badge bg-green-500/15 text-green-400 border-green-500/25 text-xs">✓ Aktif</span>
            @else
            <span class="ml-auto badge bg-dark-600/30 text-dark-400 border-dark-600/30 text-xs">Nonaktif</span>
            @endif
        </div>

        @if($user->two_factor_enabled)
        {{-- 2FA AKTIF --}}
        <div class="p-4 rounded-xl bg-green-500/8 border border-green-500/20 mb-5">
            <p class="text-green-400 text-sm font-medium mb-1">✅ 2FA sedang aktif</p>
            <p class="text-dark-400 text-xs">Setiap login akan membutuhkan kode OTP yang dikirim ke Telegram <strong class="text-dark-300">@{{ $user->telegram_username ?? $user->telegram_id }}</strong>.</p>
        </div>
        <form method="POST" action="{{ route('profile.2fa.disable') }}" x-data="{ show: false }"
              onsubmit="return confirm('Nonaktifkan 2FA? Login akan lebih rentan tanpa verifikasi tambahan.')">
            @csrf
            <div class="form-group mb-4">
                <label class="input-label">Konfirmasi dengan Password</label>
                <div class="relative" x-data="{ showPw: false }">
                    <input :type="showPw ? 'text' : 'password'" name="password"
                           class="input-field pr-11" placeholder="Masukkan password Anda" required>
                    <button type="button" @click="showPw=!showPw"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" x-show="!showPw"
                                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" x-show="showPw"
                                  d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-secondary border-red-500/30 text-red-400 hover:bg-red-500/10 w-full justify-center">
                Nonaktifkan 2FA
            </button>
        </form>

        @else
        {{-- 2FA TIDAK AKTIF --}}
        @if(!$user->telegram_id)
        <div class="p-4 rounded-xl bg-yellow-500/8 border border-yellow-500/20 mb-5">
            <p class="text-yellow-400 text-sm font-medium mb-1">⚠️ Telegram belum terhubung</p>
            <p class="text-dark-400 text-xs">2FA membutuhkan Telegram. Buka bot dan ketik:</p>
            <code class="text-primary-400 text-xs mt-1 block">/link {{ $user->email }}</code>
        </div>
        <button class="btn-primary w-full justify-center opacity-50 cursor-not-allowed" disabled>
            Aktifkan 2FA (Hubungkan Telegram dulu)
        </button>
        @else
        <div class="p-4 rounded-xl bg-dark-800/40 border border-dark-600/30 mb-5 space-y-2">
            <p class="text-white text-sm font-medium">Cara kerja 2FA:</p>
            <ol class="text-dark-400 text-xs space-y-1 list-decimal list-inside">
                <li>Masukkan email & password seperti biasa</li>
                <li>Bot Telegram kirim kode OTP 6 digit</li>
                <li>Masukkan kode OTP untuk masuk</li>
            </ol>
            <p class="text-dark-500 text-xs pt-1">Telegram terhubung: <span class="text-primary-400">@{{ $user->telegram_username ?? 'ID '.$user->telegram_id }}</span></p>
        </div>
        <form method="POST" action="{{ route('profile.2fa.enable') }}">
            @csrf
            <button type="submit" class="btn-primary w-full justify-center">
                🔐 Aktifkan Verifikasi Dua Langkah
            </button>
        </form>
        @endif
        @endif
    </div>

</div>
@endsection
