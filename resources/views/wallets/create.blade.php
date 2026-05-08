@extends('layouts.app')
@section('title', 'Tambah Wallet')
@section('page-title', 'Tambah Wallet')
@section('page-subtitle', 'Tambahkan rekening atau dompet digital baru')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('wallets.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Wallet *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input-field" placeholder="Contoh: BCA, Gopay, Cash" required>
                </div>

                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <option value="bank" {{ old('type')=='bank'?'selected':'' }}>Bank</option>
                        <option value="e_wallet" {{ old('type')=='e_wallet'?'selected':'' }}>E-Wallet</option>
                        <option value="cash" {{ old('type')=='cash'?'selected':'' }}>Cash</option>
                        <option value="investment" {{ old('type')=='investment'?'selected':'' }}>Investasi</option>
                        <option value="credit_card" {{ old('type')=='credit_card'?'selected':'' }}>Kartu Kredit</option>
                        <option value="other" {{ old('type')=='other'?'selected':'' }}>Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="input-label">Provider</label>
                    <input type="text" name="provider" value="{{ old('provider') }}" class="input-field" placeholder="BCA, BRI, Gopay, dll">
                </div>

                <div class="form-group">
                    <label class="input-label">Saldo Awal (Rp)</label>
                    <input type="number" name="initial_balance" value="{{ old('initial_balance', 0) }}" class="input-field currency-input" placeholder="0" min="0">
                </div>

                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="{{ old('color','#3b82f6') }}" class="w-11 h-11 rounded-lg border border-dark-600 bg-dark-800 cursor-pointer p-1">
                        <span class="text-dark-400 text-sm">Pilih warna wallet</span>
                    </div>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Nomor Rekening</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" class="input-field" placeholder="Optional">
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <textarea name="description" rows="2" class="input-field" placeholder="Catatan tambahan (opsional)">{{ old('description') }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="include_in_total" value="1" {{ old('include_in_total',1) ? 'checked' : '' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <div>
                            <span class="text-sm text-dark-200">Hitung dalam total saldo</span>
                            <p class="text-xs text-dark-500">Jika dicentang, saldo wallet ini akan dihitung dalam total keseluruhan</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Wallet</button>
                <a href="{{ route('wallets.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
