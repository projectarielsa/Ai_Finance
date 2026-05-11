@extends('layouts.app')
@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
        <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Kategori *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input-field" placeholder="Makan Siang, Netflix, Gaji..." required autofocus>
                </div>
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <option value="expense" {{ old('type')=='expense'?'selected':'' }}>💸 Pengeluaran</option>
                        <option value="income"  {{ old('type')=='income'?'selected':'' }}>💰 Pemasukan</option>
                        <option value="transfer"{{ old('type')=='transfer'?'selected':'' }}>🔄 Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Ikon Emoji</label>
                    <input type="text" name="icon" value="{{ old('icon') }}" class="input-field text-2xl" placeholder="🍔" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <input type="color" name="color" value="{{ old('color', '#3b82f6') }}" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
                </div>
                <div class="form-group">
                    <label class="input-label">Deskripsi</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="input-field" placeholder="Opsional...">
                </div>
                <div class="form-group col-span-2">
                    <label class="input-label">Kata Kunci AI (pisah koma)</label>
                    <input type="text" name="ai_keywords" value="{{ old('ai_keywords') }}" class="input-field" placeholder="makan, restoran, warteg, grab food, gofood">
                    <p class="text-dark-500 text-xs mt-1">AI akan otomatis mencocokkan transaksi ke kategori ini</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Kategori</button>
                <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
