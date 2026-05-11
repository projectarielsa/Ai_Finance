@extends('layouts.app')
@section('title', 'Edit Tujuan Keuangan')
@section('page-title', 'Edit Tujuan Keuangan')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <form action="{{ route('goals.update', $goal) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="input-label">Nama Tujuan *</label>
                <input type="text" name="title" value="{{ old('title', $goal->title) }}" class="input-field" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Target Nominal (Rp) *</label>
                    <input type="number" name="target_amount" value="{{ old('target_amount', $goal->target_amount) }}" class="input-field" min="1000" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Target Tanggal</label>
                    <input type="date" name="target_date" value="{{ old('target_date', $goal->target_date?->toDateString()) }}" class="input-field">
                </div>
                <div class="form-group">
                    <label class="input-label">Ikon</label>
                    <input type="text" name="icon" value="{{ old('icon', $goal->icon) }}" class="input-field text-2xl" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <input type="color" name="color" value="{{ old('color', $goal->color) }}" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Tabungan</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Tidak terkait wallet</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ $goal->wallet_id==$w->id?'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description', $goal->description) }}" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Status</label>
                <select name="status" class="input-field">
                    <option value="active"     {{ $goal->status==='active'?'selected':'' }}>Aktif</option>
                    <option value="paused"     {{ $goal->status==='paused'?'selected':'' }}>Dijeda</option>
                    <option value="cancelled"  {{ $goal->status==='cancelled'?'selected':'' }}>Dibatalkan</option>
                </select>
            </div>
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_milestone" value="1" {{ $goal->notify_on_milestone?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram per milestone 25%</span>
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('goals.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
