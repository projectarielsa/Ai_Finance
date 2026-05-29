@extends('layouts.app')
@section('title', 'Edit ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang'))
@section('page-title', 'Edit ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang'))
@section('page-subtitle', $debt->contact_name)

@section('header-actions')
<a href="{{ route('debts.show', $debt) }}" class="btn-secondary text-sm">← Kembali</a>
@endsection

@section('content')
<div class="max-w-2xl animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
        @endif

        <form action="{{ route('debts.update', $debt) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf @method('PUT')

            <div class="form-group">
                <label class="input-label">Nama *</label>
                <input type="text" name="contact_name" value="{{ old('contact_name', $debt->contact_name) }}" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">No. HP</label>
                <input type="text" name="contact_phone" value="{{ old('contact_phone', $debt->contact_phone) }}" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Nominal (Rp) *</label>
                <input type="number" name="amount" value="{{ old('amount', $debt->amount) }}" class="input-field" min="{{ $debt->paid_amount }}" step="1" required>
                @if($debt->paid_amount > 0)
                <p class="text-dark-500 text-xs mt-1">Minimal Rp {{ number_format($debt->paid_amount,0,',','.') }} (sudah terbayar)</p>
                @endif
            </div>
            <div class="form-group">
                <label class="input-label">Status</label>
                <select name="status" class="input-field">
                    <option value="active"    {{ $debt->status==='active'    ? 'selected' : '' }}>Aktif</option>
                    <option value="partial"   {{ $debt->status==='partial'   ? 'selected' : '' }}>Bayar Sebagian</option>
                    <option value="paid"      {{ $debt->status==='paid'      ? 'selected' : '' }}>Lunas</option>
                    <option value="cancelled" {{ $debt->status==='cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Tanggal</label>
                <input type="date" name="debt_date" value="{{ old('debt_date', $debt->debt_date->toDateString()) }}" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">Jatuh Tempo</label>
                <input type="date" name="due_date" value="{{ old('due_date', $debt->due_date?->toDateString()) }}" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Terkait</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Tidak ada</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ (old('wallet_id', $debt->wallet_id)==$w->id)?'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Keterangan</label>
                <input type="text" name="description" value="{{ old('description', $debt->description) }}" class="input-field">
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Catatan</label>
                <textarea name="notes" class="input-field" rows="2">{{ old('notes', $debt->notes) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_due" value="1" {{ $debt->notify_on_due ? 'checked' : '' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram saat mendekati jatuh tempo</span>
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('debts.show', $debt) }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
