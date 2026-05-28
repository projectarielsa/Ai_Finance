@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<h2 class="text-xl font-bold text-white mb-1">Selamat Datang Kembali</h2>
<p class="text-dark-400 text-sm mb-6">Masuk ke akun Finance AI Anda</p>

@if($errors->any())
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
    {{ $errors->first() }}
</div>
@endif

@if(session('status'))
<div class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
    {{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div class="form-group">
        <label class="input-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="input-field" placeholder="email@example.com" required autofocus>
    </div>
    <div class="form-group">
        <label class="input-label">Password</label>
        <div class="relative" x-data="{ show: false }">
            <input :type="show ? 'text' : 'password'" name="password" class="input-field pr-11" placeholder="••••••••" required>
            <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-400 hover:text-white">
                <svg x-show="!show" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <svg x-show="show" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-dark-300 cursor-pointer">
            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
            Ingat saya
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-primary-400 hover:text-primary-300">Lupa password?</a>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-3">Masuk</button>
</form>

<p class="text-center text-dark-400 text-sm mt-5">
    Belum punya akun? <a href="{{ route('register') }}" class="text-primary-400 hover:text-primary-300 font-medium">Daftar sekarang</a>
</p>
@endsection
