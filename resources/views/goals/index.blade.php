@extends('layouts.app')
@section('title', 'Tujuan Keuangan')
@section('page-title', 'Tujuan Keuangan')
@section('page-subtitle', 'Tetapkan target dan pantau progress tabungan Anda')

@section('content')
<div class="space-y-6 animate-fade-in" x-data="{ showAddForm: false }">

    {{-- Header action --}}
    <div class="flex justify-end">
        <button @click="showAddForm = !showAddForm" class="btn-primary text-sm">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Tujuan
        </button>
    </div>

    {{-- Add Form --}}
    <div x-show="showAddForm" x-transition class="glass-card p-6">
        <h3 class="text-white font-semibold mb-4">Tujuan Baru</h3>
        @if($errors->any())
        <div class="mb-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
        @endif
        <form action="{{ route('goals.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div class="form-group sm:col-span-2">
                <label class="input-label">Nama Tujuan *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="input-field" placeholder="Beli Motor, Liburan Eropa, Dana Darurat..." required>
            </div>
            <div class="form-group">
                <label class="input-label">Target Nominal (Rp) *</label>
                <input type="number" name="target_amount" value="{{ old('target_amount') }}" class="input-field" min="1000" step="1" required>
            </div>
            <div class="form-group">
                <label class="input-label">Dana Awal (Rp)</label>
                <input type="number" name="current_amount" value="{{ old('current_amount', 0) }}" class="input-field" min="0" step="1">
            </div>
            <div class="form-group">
                <label class="input-label">Target Tanggal</label>
                <input type="date" name="target_date" value="{{ old('target_date') }}" class="input-field" min="{{ now()->addDay()->toDateString() }}">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Tabungan (opsional)</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Tidak terkait wallet</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Ikon</label>
                <input type="text" name="icon" value="{{ old('icon', '🎯') }}" class="input-field text-2xl" maxlength="10">
            </div>
            <div class="form-group">
                <label class="input-label">Warna</label>
                <input type="color" name="color" value="{{ old('color', '#3b82f6') }}" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Deskripsi (opsional)</label>
                <input type="text" name="description" value="{{ old('description') }}" class="input-field" placeholder="Motivasi atau detail...">
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_milestone" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram setiap 25% tercapai</span>
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="btn-primary">Buat Tujuan</button>
                <button type="button" @click="showAddForm=false" class="btn-secondary">Batal</button>
            </div>
        </form>
    </div>

    {{-- Goals Grid --}}
    @forelse($goals as $goal)
    @php
        $pct      = $goal->percentage;
        $barColor = $goal->status === 'completed' ? 'bg-green-500' :
                    ($pct >= 75 ? 'bg-blue-500' : ($pct >= 50 ? 'bg-primary-500' : 'bg-dark-500'));
    @endphp
    <div class="glass-card p-6 {{ $goal->status === 'completed' ? 'ring-1 ring-green-500/30' : '' }}">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl"
                     style="background: {{ $goal->color }}20; border: 1px solid {{ $goal->color }}40">
                    {{ $goal->icon }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-white font-semibold">{{ $goal->title }}</h3>
                        @if($goal->status === 'completed')
                            <span class="badge badge-success text-xs">✅ Tercapai!</span>
                        @elseif($goal->status === 'paused')
                            <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">⏸ Dijeda</span>
                        @endif
                    </div>
                    @if($goal->target_date)
                    <p class="text-dark-400 text-xs mt-0.5">
                        Target: {{ $goal->target_date->format('d M Y') }}
                        @if($goal->status === 'active')
                            @php $daysLeft = $goal->days_remaining; @endphp
                            @if($daysLeft !== null)
                                · <span class="{{ $daysLeft < 30 ? 'text-yellow-400' : 'text-dark-400' }}">
                                    {{ $daysLeft > 0 ? $daysLeft.' hari lagi' : 'Sudah terlewat' }}
                                </span>
                            @endif
                        @endif
                    </p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('goals.edit', $goal) }}" class="btn-secondary text-xs py-1.5 px-3">Edit</a>
                <form action="{{ route('goals.destroy', $goal) }}" method="POST"
                      onsubmit="return confirm('Hapus tujuan ini?')">
                    @csrf @method('DELETE')
                    <button class="btn-icon text-dark-500 hover:text-red-400">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mb-3">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-white font-bold text-lg">Rp{{ number_format($goal->current_amount,0,',','.') }}</span>
                <span class="text-dark-400 text-sm">dari Rp{{ number_format($goal->target_amount,0,',','.') }}</span>
            </div>
            <div class="h-3 bg-dark-700 rounded-full overflow-hidden">
                <div class="{{ $barColor }} h-full rounded-full transition-all duration-700"
                     style="width: {{ $pct }}%"></div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-dark-400 text-xs">{{ $pct }}% tercapai</span>
                <span class="text-dark-400 text-xs">Sisa: Rp{{ number_format($goal->remaining,0,',','.') }}</span>
            </div>
        </div>

        {{-- Add funds form --}}
        @if($goal->status === 'active')
        <details class="mt-4">
            <summary class="text-sm text-primary-400 cursor-pointer hover:text-primary-300">+ Tambah Dana</summary>
            <form action="{{ route('goals.add-funds', $goal) }}" method="POST" class="mt-3 flex gap-2 flex-wrap">
                @csrf
                <input type="number" name="amount" class="input-field text-sm py-2 w-40" min="1" step="1" placeholder="Nominal (Rp)">
                <select name="wallet_id" class="input-field text-sm py-2 flex-1">
                    <option value="">Tanpa debit wallet</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ $goal->wallet_id==$w->id?'selected':'' }}>
                        {{ $w->name }} (Rp{{ number_format($w->balance,0,',','.') }})
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary text-sm py-2 px-4">Simpan</button>
            </form>
        </details>
        @endif
    </div>
    @empty
    <div class="glass-card p-12 text-center">
        <p class="text-5xl mb-4">🎯</p>
        <p class="text-white font-semibold text-lg">Belum ada tujuan keuangan</p>
        <p class="text-dark-400 mt-1 mb-5">Mulai tetapkan target — beli rumah, liburan, dana darurat</p>
        <button @click="showAddForm=true; $nextTick(() => window.scrollTo({top: 0, behavior: 'smooth'}))"
                class="btn-primary">Buat Tujuan Pertama</button>
    </div>
    @endforelse
</div>
@endsection
