@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
        <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Kategori *</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="input-field" required autofocus>
                </div>
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <option value="expense"  {{ $category->type=='expense'?'selected':'' }}>💸 Pengeluaran</option>
                        <option value="income"   {{ $category->type=='income'?'selected':'' }}>💰 Pemasukan</option>
                        <option value="transfer" {{ $category->type=='transfer'?'selected':'' }}>🔄 Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Ikon Emoji</label>
                    <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" class="input-field text-2xl" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <input type="color" name="color" value="{{ old('color', $category->color ?? '#3b82f6') }}" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
                </div>
                <div class="form-group">
                    <label class="input-label">Deskripsi</label>
                    <input type="text" name="description" value="{{ old('description', $category->description) }}" class="input-field">
                </div>
                <div class="form-group col-span-2">
                    <label class="input-label">Kata Kunci AI (pisah koma)</label>
                    <input type="text" name="ai_keywords"
                           value="{{ old('ai_keywords', is_array($category->ai_keywords) ? implode(', ', $category->ai_keywords) : '') }}"
                           class="input-field" placeholder="makan, restoran, warteg...">
                </div>
                <div class="col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span class="text-sm text-dark-200">Kategori aktif</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
