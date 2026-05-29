@extends('layouts.app')
@section('title', 'Detail ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang'))
@section('page-title', 'Detail ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang'))
@section('page-subtitle', $debt->contact_name)

@section('header-actions')
<a href="{{ route('debts.index') }}" class="btn-secondary text-sm">← Kembali</a>
@endsection

@section('content')
@php
    $typeColor  = $debt->type === 'receivable' ? 'green' : 'red';
    $typeLabel  = $debt->type === 'receivable' ? 'Piutang' : 'Hutang';
    $isOverdue  = $debt->is_overdue;
    $statusBadge = match($debt->status) {
        'paid'      => 'bg-green-500/15 text-green-400 border-green-500/25',
        'partial'   => 'bg-yellow-500/15 text-yellow-400 border-yellow-500/25',
        'cancelled' => 'bg-dark-600/30 text-dark-400 border-dark-600/30',
        default     => 'bg-blue-500/15 text-blue-400 border-blue-500/25',
    };
@endphp
<div class="space-y-6 animate-fade-in" x-data="{ showPayForm: false }">

    {{-- Header Card --}}
    <div class="glass-card p-6 {{ $isOverdue ? 'ring-1 ring-yellow-500/40' : '' }}">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl font-bold
                    {{ $debt->type==='receivable' ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                    {{ strtoupper(substr($debt->contact_name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-white font-bold text-xl">{{ $debt->contact_name }}</h2>
                        <span class="badge text-xs border {{ $statusBadge }}">{{ $debt->status_label }}</span>
                        @if($isOverdue)
                        <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">⚠ Jatuh Tempo!</span>
                        @endif
                    </div>
                    @if($debt->contact_phone)
                    <p class="text-dark-400 text-sm mt-1">📱 {{ $debt->contact_phone }}</p>
                    @endif
                    <p class="text-xs text-dark-500 mt-0.5">{{ $typeLabel }} · Dibuat {{ $debt->debt_date->format('d M Y') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('debts.edit', $debt) }}" class="btn-secondary text-sm">Edit</a>
            </div>
        </div>

        {{-- Amount breakdown --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="p-4 rounded-xl bg-dark-800/50">
                <p class="text-dark-400 text-xs mb-1">Total {{ $typeLabel }}</p>
                <p class="text-white font-bold text-xl">Rp {{ number_format($debt->amount, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-dark-800/50">
                <p class="text-dark-400 text-xs mb-1">Sudah Dibayar</p>
                <p class="text-green-400 font-bold text-xl">Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}</p>
            </div>
            <div class="p-4 rounded-xl bg-dark-800/50">
                <p class="text-dark-400 text-xs mb-1">Sisa</p>
                <p class="{{ $debt->status==='paid' ? 'text-green-400' : 'text-'.$typeColor.'-400' }} font-bold text-xl">
                    @if($debt->status==='paid') ✅ Lunas @else Rp {{ number_format($debt->remaining_amount, 0, ',', '.') }} @endif
                </p>
            </div>
        </div>

        {{-- Progress --}}
        @if($debt->amount > 0)
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-dark-300">Progress Pembayaran</span>
                <span class="text-white font-semibold">{{ $debt->percentage }}%</span>
            </div>
            <div class="h-3 bg-dark-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 {{ $debt->type==='receivable' ? 'bg-green-500' : 'bg-blue-500' }}"
                     style="width: {{ $debt->percentage }}%"></div>
            </div>
        </div>
        @endif

        {{-- Detail info --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @if($debt->description)
            <div class="flex gap-2"><span class="text-dark-400 w-28 flex-shrink-0">Keterangan</span><span class="text-dark-200">{{ $debt->description }}</span></div>
            @endif
            @if($debt->due_date)
            <div class="flex gap-2">
                <span class="text-dark-400 w-28 flex-shrink-0">Jatuh Tempo</span>
                <span class="{{ $isOverdue ? 'text-yellow-400 font-semibold' : 'text-dark-200' }}">
                    {{ $debt->due_date->format('d M Y') }}
                    @if($isOverdue) (Telat {{ abs($debt->days_until_due) }} hari)
                    @elseif($debt->days_until_due !== null && $debt->days_until_due >= 0 && $debt->status !== 'paid')
                        ({{ $debt->days_until_due }} hari lagi)
                    @endif
                </span>
            </div>
            @endif
            @if($debt->wallet)
            <div class="flex gap-2"><span class="text-dark-400 w-28 flex-shrink-0">Wallet</span><span class="text-dark-200">{{ $debt->wallet->name }}</span></div>
            @endif
            @if($debt->notes)
            <div class="flex gap-2 sm:col-span-2"><span class="text-dark-400 w-28 flex-shrink-0">Catatan</span><span class="text-dark-200">{{ $debt->notes }}</span></div>
            @endif
        </div>

        {{-- Action buttons --}}
        @if($debt->status !== 'paid' && $debt->status !== 'cancelled')
        <div class="flex gap-3 mt-6 pt-5 border-t border-dark-700/40 flex-wrap">
            <button @click="showPayForm = !showPayForm" class="btn-primary">
                💳 Catat Pembayaran
            </button>
            <form action="{{ route('debts.markPaid', $debt) }}" method="POST"
                  onsubmit="return confirm('Tandai {{ strtolower($typeLabel) }} ini sebagai lunas?')">
                @csrf
                <button class="btn-secondary border-green-500/30 text-green-400 hover:bg-green-500/10">✓ Tandai Lunas</button>
            </form>
        </div>

        {{-- Pay form --}}
        <div x-show="showPayForm" x-transition class="mt-5 p-5 rounded-xl bg-dark-800/50 border border-dark-600/40">
            <h4 class="text-white font-semibold mb-4">Catat Pembayaran</h4>
            <form action="{{ route('debts.pay', $debt) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <div class="form-group">
                    <label class="input-label">Nominal Bayar (Rp) *</label>
                    <input type="number" name="amount" class="input-field" min="1" max="{{ $debt->remaining_amount }}"
                           step="1" placeholder="Max: Rp {{ number_format($debt->remaining_amount,0,',','.') }}" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Tanggal Bayar *</label>
                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Wallet (opsional)</label>
                    <select name="wallet_id" class="input-field">
                        <option value="">Tanpa debit/kredit wallet</option>
                        @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ $debt->wallet_id==$w->id?'selected':'' }}>
                            {{ $w->name }} (Rp {{ number_format($w->balance,0,',','.') }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Catatan</label>
                    <input type="text" name="notes" class="input-field" placeholder="Keterangan pembayaran...">
                </div>
                <div class="sm:col-span-2 flex gap-3">
                    <button type="submit" class="btn-primary">Simpan Pembayaran</button>
                    <button type="button" @click="showPayForm=false" class="btn-secondary">Batal</button>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- Payment History --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-4">Riwayat Pembayaran</h3>
        @forelse($debt->payments as $payment)
        <div class="flex items-center justify-between py-3 border-b border-dark-700/30 last:border-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-green-500/15 flex items-center justify-center text-green-400 text-sm">✓</div>
                <div>
                    <p class="text-white text-sm font-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-2 text-xs text-dark-400">
                        <span>{{ $payment->payment_date->format('d M Y') }}</span>
                        @if($payment->wallet)<span>· {{ $payment->wallet->name }}</span>@endif
                        @if($payment->notes)<span>· {{ $payment->notes }}</span>@endif
                    </div>
                </div>
            </div>
            <span class="text-xs text-dark-500">{{ $payment->source }}</span>
        </div>
        @empty
        <p class="text-dark-400 text-sm text-center py-6">Belum ada pembayaran yang dicatat</p>
        @endforelse
    </div>

</div>
@endsection
