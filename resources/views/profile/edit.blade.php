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
                    <label class="input-label">Nomor WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone',$user->phone) }}" class="input-field" placeholder="628123456789">
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
                        <input type="checkbox" name="whatsapp_notifications" value="1" {{ $user->whatsapp_notifications?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <div>
                            <span class="text-sm text-dark-200">Notifikasi WhatsApp</span>
                            <p class="text-xs text-dark-500">Terima laporan bulanan dan peringatan saldo lewat WhatsApp</p>
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
                <p class="text-dark-400 text-xs">WhatsApp terdaftar</p>
                <p class="text-white text-sm font-medium">+{{ $user->phone }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
