@extends('layouts.app')
@section('title', 'Edit Wallet')
@section('page-title', 'Edit Wallet')
@section('page-subtitle', $wallet->name)

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('wallets.update', $wallet) }}" class="space-y-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Wallet *</label>
                    <input type="text" name="name" value="{{ old('name', $wallet->name) }}" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        @foreach(['bank','e_wallet','cash','investment','credit_card','other'] as $t)
                        <option value="{{ $t }}" {{ $wallet->type==$t?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Provider</label>
                    <input type="text" name="provider" value="{{ old('provider', $wallet->provider) }}" class="input-field">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="{{ old('color', $wallet->color) }}" class="w-11 h-11 rounded-lg border border-dark-600 bg-dark-800 cursor-pointer p-1">
                        <span class="text-dark-400 text-sm">Pilih warna</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="input-label">Nomor Rekening</label>
                    <input type="text" name="account_number" value="{{ old('account_number', $wallet->account_number) }}" class="input-field">
                </div>
                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <textarea name="description" rows="2" class="input-field">{{ old('description', $wallet->description) }}</textarea>
                </div>
                <div class="col-span-2 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="include_in_total" value="1" {{ $wallet->include_in_total?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span class="text-sm text-dark-200">Hitung dalam total saldo</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $wallet->is_active?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span class="text-sm text-dark-200">Wallet aktif</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('wallets.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
