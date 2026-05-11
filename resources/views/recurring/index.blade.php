@extends('layouts.app')
@section('title', 'Transaksi Berulang')
@section('page-title', 'Transaksi Berulang')
@section('page-subtitle', 'Otomatiskan transaksi yang terjadi rutin')

@section('header-actions')
<a href="{{ route('recurring.create') }}" class="btn-secondary text-sm">+ Tambah</a>
@endsection

@section('content')
<div class="space-y-4 animate-fade-in">
    @forelse($recurring as $r)
    @php
        $isDue    = $r->isDue();
        $typeIcon = match($r->type) { 'income' => '💰', 'expense' => '💸', default => '🔄' };
        $typeColor= match($r->type) { 'income' => 'text-green-400', 'expense' => 'text-red-400', default => 'text-blue-400' };
    @endphp
    <div class="glass-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-3 flex-1">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl
                {{ $r->is_active ? 'bg-dark-700/50' : 'bg-dark-800/50 opacity-50' }}">
                {{ $typeIcon }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-white font-semibold truncate">{{ $r->title }}</p>
                    @if(!$r->is_active)
                        <span class="badge badge-danger text-xs">Nonaktif</span>
                    @endif
                    @if($isDue)
                        <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">Jatuh Tempo</span>
                    @endif
                </div>
                <p class="text-dark-400 text-sm">
                    {{ $r->frequency_label }} · {{ $r->wallet->name }}
                    @if($r->category) · {{ $r->category->name }}@endif
                </p>
                <p class="text-dark-500 text-xs mt-0.5">
                    Berikutnya: {{ $r->next_run_date->format('d M Y') }}
                    @if($r->end_date) · Berakhir: {{ $r->end_date->format('d M Y') }}@endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <p class="{{ $typeColor }} font-bold text-lg">Rp{{ number_format($r->amount,0,',','.') }}</p>

            @if($isDue && $r->is_active)
            <form action="{{ route('recurring.execute', $r) }}" method="POST">
                @csrf
                <button class="btn-primary text-xs py-1.5 px-3">▶ Jalankan</button>
            </form>
            @endif

            <a href="{{ route('recurring.edit', $r) }}" class="btn-secondary text-xs py-1.5 px-3">Edit</a>

            <form action="{{ route('recurring.destroy', $r) }}" method="POST"
                  onsubmit="return confirm('Hapus transaksi berulang ini?')">
                @csrf @method('DELETE')
                <button class="btn-icon text-dark-500 hover:text-red-400">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="glass-card p-12 text-center">
        <p class="text-4xl mb-4">🔄</p>
        <p class="text-white font-semibold">Belum ada transaksi berulang</p>
        <p class="text-dark-400 text-sm mt-1 mb-4">Otomatiskan gaji, tagihan, langganan, cicilan</p>
        <a href="{{ route('recurring.create') }}" class="btn-primary">Tambah Sekarang</a>
    </div>
    @endforelse
</div>
@endsection
