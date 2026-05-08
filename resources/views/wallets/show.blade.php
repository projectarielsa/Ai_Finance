@extends('layouts.app')
@section('title', $wallet->name)
@section('page-title', $wallet->name)
@section('page-subtitle', 'Histori transaksi wallet')

@section('content')
<div class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Saldo Saat Ini</p>
            <p class="text-2xl font-bold text-white mt-1">Rp {{ number_format($wallet->balance,0,',','.') }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Pemasukan</p>
            <p class="text-2xl font-bold text-green-400 mt-1">Rp {{ number_format($wallet->total_income,0,',','.') }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Pengeluaran</p>
            <p class="text-2xl font-bold text-red-400 mt-1">Rp {{ number_format($wallet->total_expense,0,',','.') }}</p>
        </div>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-700/30 flex items-center justify-between">
            <h3 class="text-white font-semibold">Histori Transaksi</h3>
            <a href="{{ route('wallets.edit', $wallet) }}" class="btn-secondary text-sm">Edit Wallet</a>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Tipe</th><th class="text-right">Jumlah</th>
            </tr></thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td class="text-dark-400">{{ $tx->transaction_date->format('d M Y') }}</td>
                    <td>{{ $tx->description ?? '-' }}</td>
                    <td>{{ $tx->category?->name ?? '-' }}</td>
                    <td><span class="badge badge-{{ $tx->type }}">{{ ucfirst($tx->type) }}</span></td>
                    <td class="text-right font-semibold {{ $tx->type==='income'?'text-green-400':($tx->type==='expense'?'text-red-400':'text-blue-400') }}">
                        {{ $tx->type==='income'?'+':'-' }}Rp {{ number_format($tx->amount,0,',','.') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-8 text-dark-400">Belum ada transaksi</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-dark-700/30">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
