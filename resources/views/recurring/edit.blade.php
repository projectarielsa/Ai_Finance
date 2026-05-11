@extends('layouts.app')
@section('title', 'Edit Transaksi Berulang')
@section('page-title', 'Edit Transaksi Berulang')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <form action="{{ route('recurring.update', $recurring) }}" method="POST" class="space-y-4"
              x-data="{ type: '{{ $recurring->type }}' }">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="input-label">Judul *</label>
                <input type="text" name="title" value="{{ old('title', $recurring->title) }}" class="input-field" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Tipe</label>
                    <input type="text" value="{{ ucfirst($recurring->type) }}" class="input-field bg-dark-800/50" disabled>
                </div>
                <div class="form-group">
                    <label class="input-label">Frekuensi *</label>
                    <select name="frequency" class="input-field">
                        @foreach(['monthly'=>'Bulanan','weekly'=>'Mingguan','yearly'=>'Tahunan','daily'=>'Harian'] as $val=>$label)
                        <option value="{{ $val }}" {{ $recurring->frequency==$val?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Jumlah (Rp) *</label>
                <input type="number" name="amount" value="{{ old('amount', $recurring->amount) }}" class="input-field" min="1">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Sumber *</label>
                <select name="wallet_id" class="input-field">
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ $recurring->wallet_id==$w->id?'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($recurring->type === 'transfer')
            <div class="form-group">
                <label class="input-label">Wallet Tujuan</label>
                <select name="target_wallet_id" class="input-field">
                    <option value="">-</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ $recurring->target_wallet_id==$w->id?'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group">
                <label class="input-label">Kategori</label>
                <select name="category_id" class="input-field">
                    <option value="">Tanpa kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $recurring->category_id==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Berakhir (opsional)</label>
                <input type="date" name="end_date" value="{{ old('end_date', $recurring->end_date?->toDateString()) }}" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description', $recurring->description) }}" class="input-field">
            </div>
            <div class="space-y-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $recurring->is_active?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Aktif</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_execute" value="1" {{ $recurring->auto_execute?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Jalankan Otomatis</span>
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
