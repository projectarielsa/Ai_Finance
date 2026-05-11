@extends('layouts.app')
@section('title', 'Kategori')
@section('page-title', 'Kategori')
@section('page-subtitle', 'Kelola kategori transaksi Anda')

@section('header-actions')
<a href="{{ route('categories.create') }}" class="btn-secondary text-sm">+ Kategori Baru</a>
@endsection

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- Kategori Pengguna --}}
    @if($userCategories->count())
    <div class="glass-card overflow-hidden">
        <div class="px-5 py-4 border-b border-dark-700/30">
            <h3 class="text-white font-semibold">Kategori Saya ({{ $userCategories->count() }})</h3>
        </div>
        <div class="divide-y divide-dark-700/30">
            @foreach($userCategories->groupBy('type') as $type => $cats)
            <div class="px-5 py-3">
                <p class="text-dark-500 text-xs font-semibold uppercase tracking-wider mb-2">
                    {{ $type === 'income' ? '💰 Pemasukan' : ($type === 'expense' ? '💸 Pengeluaran' : '🔄 Transfer') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($cats as $cat)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-dark-800/40 hover:bg-dark-700/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="text-lg w-8 text-center">{{ $cat->icon ?? '📁' }}</span>
                            <div>
                                <p class="text-white text-sm font-medium">{{ $cat->name }}</p>
                                @if($cat->ai_keywords)
                                <p class="text-dark-500 text-xs">{{ implode(', ', array_slice($cat->ai_keywords, 0, 3)) }}...</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @if(!$cat->is_active)
                                <span class="badge badge-danger text-xs">Nonaktif</span>
                            @endif
                            <a href="{{ route('categories.edit', $cat) }}" class="btn-icon text-dark-400 hover:text-white">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </a>
                            <form action="{{ route('categories.destroy', $cat) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="btn-icon text-dark-500 hover:text-red-400"
                                        onclick="return confirm('Hapus kategori {{ $cat->name }}?')">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Kategori Sistem --}}
    <div class="glass-card overflow-hidden">
        <div class="px-5 py-4 border-b border-dark-700/30">
            <h3 class="text-white font-semibold">Kategori Sistem ({{ $systemCategories->count() }})</h3>
            <p class="text-dark-400 text-xs mt-0.5">Kategori bawaan, tidak bisa diedit</p>
        </div>
        <div class="divide-y divide-dark-700/30">
            @foreach($systemCategories->groupBy('type') as $type => $cats)
            <div class="px-5 py-3">
                <p class="text-dark-500 text-xs font-semibold uppercase tracking-wider mb-2">
                    {{ $type === 'income' ? '💰 Pemasukan' : ($type === 'expense' ? '💸 Pengeluaran' : '🔄 Transfer') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($cats as $cat)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-dark-800/40 text-dark-200 text-sm">
                        <span>{{ $cat->icon ?? '📁' }}</span>
                        {{ $cat->name }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
