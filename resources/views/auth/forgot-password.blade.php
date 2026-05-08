@extends('layouts.auth')
@section('title', 'Lupa Password')
@section('content')
<h2 class="text-xl font-bold text-white mb-1">Lupa Password</h2>
<p class="text-dark-400 text-sm mb-6">Masukkan email Anda untuk reset password.</p>

@if(session('status'))
<div class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div class="form-group">
        <label class="input-label">Email</label>
        <input type="email" name="email" class="input-field" placeholder="email@example.com" required>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-3">Kirim Link Reset</button>
</form>
<p class="text-center text-dark-400 text-sm mt-4"><a href="{{ route('login') }}" class="text-primary-400 hover:text-primary-300">← Kembali ke Login</a></p>
@endsection
