@extends('layouts.auth')
@section('title', 'Daftar')

@section('content')
<h2 class="text-xl font-bold text-white mb-1">Buat Akun Baru</h2>
<p class="text-dark-400 text-sm mb-6">Mulai kelola keuangan Anda dengan AI</p>

@if(session('error'))
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div class="form-group">
        <label class="input-label">Nama Lengkap</label>
        <input type="text" name="name" value="{{ old('name') }}" class="input-field" placeholder="John Doe" required autofocus>
    </div>
    <div class="form-group">
        <label class="input-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="input-field" placeholder="email@example.com" required>
    </div>
    <div class="form-group">
        <label class="input-label">Nomor Telepon <span class="text-dark-500">(opsional)</span></label>
        <input type="text" name="phone" value="{{ old('phone') }}" class="input-field" placeholder="08123456789">
    </div>
    <div class="form-group">
        <label class="input-label">Password</label>
        <input type="password" name="password" class="input-field" placeholder="Minimal 8 karakter" required>
    </div>
    <div class="form-group">
        <label class="input-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="input-field" placeholder="Ulangi password" required>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-3">Daftar Sekarang</button>
</form>

<p class="text-center text-dark-400 text-sm mt-5">
    Sudah punya akun? <a href="{{ route('login') }}" class="text-primary-400 hover:text-primary-300 font-medium">Masuk</a>
</p>
@endsection
