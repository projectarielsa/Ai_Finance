@extends('layouts.app')
@section('title', 'Tambah Transaksi Berulang')
@section('page-title', 'Tambah Transaksi Berulang')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
        <form action="{{ route('recurring.store') }}" method="POST" class="space-y-4" x-data="{ type: '{{ old('type','expense') }}' }">
            @csrf
            <div class="form-group">
                <label class="input-label">Judul *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="input-field" placeholder="Gaji Bulanan, Netflix, Cicilan KPR..." required autofocus>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" x-model="type" required>
                        <option value="expense">💸 Pengeluaran</option>
                        <option value="income">💰 Pemasukan</option>
                        <option value="transfer">🔄 Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Frekuensi *</label>
                    <select name="frequency" class="input-field" required>
                        <option value="monthly" selected>Bulanan</option>
                        <option value="weekly">Mingguan</option>
                        <option value="yearly">Tahunan</option>
                        <option value="daily">Harian</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Jumlah (Rp) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" class="input-field" min="1" step="1" required>
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Sumber *</label>
                <select name="wallet_id" class="input-field" required>
                    <option value="">Pilih wallet...</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ old('wallet_id')==$w->id?'selected':'' }}>
                        {{ $w->name }} — Rp{{ number_format($w->balance,0,',','.') }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" x-show="type === 'transfer'">
                <label class="input-label">Wallet Tujuan</label>
                <select name="target_wallet_id" class="input-field">
                    <option value="">Pilih wallet tujuan...</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Kategori</label>
                <select name="category_id" class="input-field">
                    <option value="">Tanpa kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Mulai Tanggal *</label>
                    <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Berakhir (opsional)</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="input-field">
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Deskripsi (opsional)</label>
                <input type="text" name="description" value="{{ old('description') }}" class="input-field">
            </div>
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_execute" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <div>
                        <span class="text-sm text-dark-200">Jalankan Otomatis</span>
                        <p class="text-xs text-dark-500">Transaksi akan dicatat otomatis oleh sistem setiap jadwal</p>
                    </div>
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('recurring.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
