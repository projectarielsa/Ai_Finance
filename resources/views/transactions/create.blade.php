@extends('layouts.app')
@section('title', 'Tambah Transaksi')
@section('page-title', 'Tambah Transaksi')
@section('page-subtitle', 'Catat transaksi keuangan secara manual')

@section('content')
<div class="max-w-2xl mx-auto animate-fade-in" x-data="{ type: '{{ old('type','expense') }}' }">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('transactions.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Type selector --}}
            <div class="form-group">
                <label class="input-label">Jenis Transaksi *</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['expense'=>['Pengeluaran','text-red-400','bg-red-500/15 border-red-500/40'],'income'=>['Pemasukan','text-green-400','bg-green-500/15 border-green-500/40'],'transfer'=>['Transfer','text-blue-400','bg-blue-500/15 border-blue-500/40']] as $val=>[$label,$color,$activeCls])
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="{{ $val }}" x-model="type" class="sr-only">
                        <div :class="type === '{{ $val }}' ? '{{ $activeCls }} border' : 'bg-dark-700/30 border border-dark-600/30'"
                             class="p-3 rounded-xl text-center transition-all duration-200">
                            <p class="{{ $color }} font-semibold text-sm">{{ $label }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Jumlah (Rp) *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 font-medium">Rp</span>
                        <input type="number" name="amount" value="{{ old('amount') }}" class="input-field pl-10" placeholder="0" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Wallet *</label>
                    <select name="wallet_id" class="input-field" required>
                        <option value="">Pilih Wallet</option>
                        @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ old('wallet_id')==$w->id?'selected':'' }}>{{ $w->name }} (Rp{{ number_format($w->balance,0,',','.') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" x-show="type === 'transfer'" x-transition>
                    <label class="input-label">Wallet Tujuan *</label>
                    <select name="target_wallet_id" class="input-field">
                        <option value="">Pilih Wallet Tujuan</option>
                        @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ old('target_wallet_id')==$w->id?'selected':'' }}>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" x-show="type !== 'transfer'" x-transition>
                    <label class="input-label">Kategori</label>
                    <select name="category_id" class="input-field">
                        <option value="">Pilih Kategori</option>
                        @foreach($categories->where('type','expense') as $c)
                        @if($c->type !== 'transfer')
                        <option value="{{ $c->id }}" {{ old('category_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="input-field" placeholder="Contoh: Makan siang, Bayar listrik...">
                </div>

                <div class="form-group">
                    <label class="input-label">Merchant / Toko</label>
                    <input type="text" name="merchant" value="{{ old('merchant') }}" class="input-field" placeholder="Nama toko/merchant">
                </div>

                <div class="form-group">
                    <label class="input-label">Tanggal Transaksi *</label>
                    <input type="datetime-local" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" class="input-field" required>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Catatan</label>
                    <textarea name="notes" rows="2" class="input-field" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Lampiran Struk</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="input-field py-2.5 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-500/20 file:text-primary-300 hover:file:bg-primary-500/30">
                    <p class="text-xs text-dark-500 mt-1">Format: JPG, PNG, PDF. Maks 5MB</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Transaksi</button>
                <a href="{{ route('transactions.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
