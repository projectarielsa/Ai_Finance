@extends('layouts.admin')
@section('title', $user->name)
@section('page-title', $user->name)

@section('content')
<div class="max-w-3xl mx-auto space-y-5 animate-fade-in">
    <div class="glass-card p-6">
        <div class="flex items-start gap-5">
            <img src="{{ $user->avatar_url }}" class="w-16 h-16 rounded-2xl object-cover">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-white">{{ $user->name }}</h2>
                <p class="text-dark-400">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="badge {{ $user->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success' }}">{{ $user->role }}</span>
                    <span class="badge {{ $user->is_active?'badge-success':'badge-danger' }}">{{ $user->is_active?'Aktif':'Nonaktif' }}</span>
                </div>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-dark-700/30">
            <div><dt class="text-dark-400 text-xs">Phone</dt><dd class="text-white text-sm">{{ $user->phone ?? '—' }}</dd></div>
            <div><dt class="text-dark-400 text-xs">Total Saldo</dt><dd class="text-white text-sm">Rp {{ number_format($user->total_balance,0,',','.') }}</dd></div>
            <div><dt class="text-dark-400 text-xs">Bergabung</dt><dd class="text-white text-sm">{{ $user->created_at->format('d M Y') }}</dd></div>
            <div><dt class="text-dark-400 text-xs">WA Notifikasi</dt><dd class="text-white text-sm">{{ $user->whatsapp_notifications?'Aktif':'Nonaktif' }}</dd></div>
        </dl>
    </div>

    {{-- Wallets --}}
    <div class="glass-card p-5">
        <h3 class="text-white font-semibold mb-3">Wallets ({{ $user->wallets->count() }})</h3>
        <div class="grid grid-cols-2 gap-2">
            @foreach($user->wallets as $w)
            <div class="p-3 rounded-xl bg-dark-700/30 flex items-center justify-between">
                <span class="text-sm text-dark-200">{{ $w->name }}</span>
                <span class="text-sm font-medium text-white">Rp{{ number_format($w->balance,0,',','.') }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="glass-card overflow-hidden">
        <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Transaksi Terbaru</h3></div>
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Tipe</th><th class="text-right">Jumlah</th></tr></thead>
            <tbody>
                @foreach($user->transactions as $tx)
                <tr>
                    <td class="text-xs text-dark-400">{{ $tx->transaction_date->format('d M Y') }}</td>
                    <td>{{ $tx->description ?? '—' }}</td>
                    <td><span class="badge badge-{{ $tx->type }}">{{ ucfirst($tx->type) }}</span></td>
                    <td class="text-right text-sm {{ $tx->type==='income'?'text-green-400':'text-red-400' }}">Rp{{ number_format($tx->amount,0,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('admin.users.index') }}" class="btn-secondary inline-flex">← Kembali</a>
</div>
@endsection
