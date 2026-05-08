@extends('layouts.app')
@section('title', 'Edit Transaksi')
@section('page-title', 'Edit Transaksi')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <div class="mb-5 p-4 rounded-xl bg-dark-700/30 border border-dark-600/30">
            <p class="text-dark-400 text-xs mb-1">Transaksi (tidak bisa diubah)</p>
            <p class="text-white font-bold text-xl">
                {{ $transaction->type==='income'?'+':'-' }}Rp {{ number_format($transaction->amount,0,',','.') }}
            </p>
            <div class="flex items-center gap-2 mt-1">
                <span class="badge badge-{{ $transaction->type }}">{{ ucfirst($transaction->type) }}</span>
                <span class="text-dark-400 text-xs">{{ $transaction->wallet->name }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('transactions.update',$transaction) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="form-group">
                <label class="input-label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description',$transaction->description) }}" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Merchant</label>
                <input type="text" name="merchant" value="{{ old('merchant',$transaction->merchant) }}" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Kategori</label>
                <select name="category_id" class="input-field">
                    <option value="">Pilih Kategori</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ $transaction->category_id==$c->id?'selected':'' }}>{{ $c->name }} ({{ $c->type }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="input-label">Tanggal Transaksi</label>
                <input type="datetime-local" name="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d\TH:i')) }}" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Catatan</label>
                <textarea name="notes" rows="2" class="input-field">{{ old('notes',$transaction->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('transactions.show',$transaction) }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
