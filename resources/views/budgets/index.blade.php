@extends('layouts.app')
@section('title', 'Budget')
@section('page-title', 'Budget')
@section('page-subtitle', 'Kelola batas pengeluaran per kategori')

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- Month selector --}}
    <div class="glass-card p-4 flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-2">
            <select name="month" class="input-field text-sm py-2 w-36">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                    </option>
                @endfor
            </select>
            <select name="year" class="input-field text-sm py-2 w-24">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn-secondary text-sm">Lihat</button>
        </form>
        <p class="text-dark-400 text-sm ml-auto">{{ $budgets->count() }} budget aktif</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Budget list --}}
        <div class="xl:col-span-2 space-y-3">
            @forelse($budgets as $b)
            @php
                $pct   = $b->percentage;
                $color = $pct >= 100 ? 'red' : ($pct >= 80 ? 'yellow' : 'green');
                $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-green-500');
            @endphp
            <div class="glass-card p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-white font-semibold">{{ $b->category?->name ?? 'Semua Kategori' }}</p>
                        <p class="text-dark-400 text-xs mt-0.5">
                            Rp{{ number_format($b->spent,0,',','.') }} / Rp{{ number_format($b->limit_amount,0,',','.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-{{ $color }}-400 font-bold text-lg">{{ $pct }}%</span>
                        <form action="{{ route('budgets.destroy', $b) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn-icon text-dark-500 hover:text-red-400" title="Hapus">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="h-2.5 bg-dark-700 rounded-full overflow-hidden">
                    <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                         style="width: {{ min(100, $pct) }}%"></div>
                </div>

                <div class="flex items-center justify-between mt-2 text-xs">
                    <span class="text-dark-500">Sisa: <span class="text-{{ $color }}-400 font-medium">Rp{{ number_format($b->remaining,0,',','.') }}</span></span>
                    @if($pct >= 100)
                        <span class="text-red-400 font-medium">🚨 Terlampaui!</span>
                    @elseif($pct >= 80)
                        <span class="text-yellow-400 font-medium">⚠️ Mendekati batas</span>
                    @endif
                </div>

                {{-- Edit form inline --}}
                <details class="mt-3">
                    <summary class="text-xs text-dark-400 cursor-pointer hover:text-dark-200">Edit budget</summary>
                    <form action="{{ route('budgets.update', $b) }}" method="POST" class="mt-2 flex gap-2 flex-wrap">
                        @csrf @method('PUT')
                        <input type="number" name="limit_amount" value="{{ $b->limit_amount }}" class="input-field text-sm py-1.5 w-40" min="1000" step="1">
                        <input type="text" name="notes" value="{{ $b->notes }}" class="input-field text-sm py-1.5 flex-1" placeholder="Catatan...">
                        <button type="submit" class="btn-primary text-xs">Simpan</button>
                    </form>
                </details>
            </div>
            @empty
            <div class="glass-card p-8 text-center">
                <p class="text-4xl mb-3">📊</p>
                <p class="text-white font-medium">Belum ada budget</p>
                <p class="text-dark-400 text-sm mt-1">Tambah budget di sebelah kanan untuk mulai memantau pengeluaran</p>
            </div>
            @endforelse
        </div>

        {{-- Add Budget Form --}}
        <div class="glass-card p-6 h-fit">
            <h3 class="text-white font-semibold mb-4">+ Tambah Budget</h3>
            @if($errors->any())
                <div class="mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif
            <form action="{{ route('budgets.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="input-label">Kategori</label>
                    <select name="category_id" class="input-field" required>
                        <option value="">Pilih kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Batas Pengeluaran (Rp)</label>
                    <input type="number" name="limit_amount" class="input-field" min="1000" step="1" placeholder="500000" required>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="form-group">
                        <label class="input-label">Bulan</label>
                        <select name="month" class="input-field">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{ $m }}" {{ $m==$month?'selected':'' }}>
                                    {{ \Carbon\Carbon::createFromDate(null,$m,1)->format('M') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">Tahun</label>
                        <select name="year" class="input-field">
                            @for($y=now()->year;$y<=now()->year+1;$y++)
                                <option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="input-label">Catatan (opsional)</label>
                    <input type="text" name="notes" class="input-field" placeholder="Contoh: batas makan siang">
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-dark-200 cursor-pointer">
                        <input type="checkbox" name="alert_at_80" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span>Notif Telegram di 80%</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-dark-200 cursor-pointer">
                        <input type="checkbox" name="alert_at_100" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span>Notif Telegram di 100%</span>
                    </label>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Simpan Budget</button>
            </form>
        </div>
    </div>
</div>
@endsection
