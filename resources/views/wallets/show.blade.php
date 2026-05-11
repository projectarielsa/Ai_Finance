@extends('layouts.app')
@section('title', $wallet->name)
@section('page-title', $wallet->name)
@section('page-subtitle', 'Histori transaksi & analitik wallet')

@section('content')
<div class="space-y-6 animate-fade-in">
    {{-- Stats --}}
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

    {{-- Chart --}}
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-white font-semibold">Pemasukan vs Pengeluaran</h3>
                <p class="text-dark-400 text-xs mt-0.5">6 bulan terakhir</p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span><span class="text-dark-400">Pemasukan</span></span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span><span class="text-dark-400">Pengeluaran</span></span>
            </div>
        </div>
        <div class="h-52">
            <canvas id="walletChart"></canvas>
        </div>
    </div>

    {{-- Transactions --}}
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
                    <td>
                        {{ $tx->description ?? '-' }}
                        @if($tx->is_duplicate)
                            <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs ml-1">duplikat</span>
                        @endif
                    </td>
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

@push('scripts')
<script>
const chartData = @json($chartData);
const ctx = document.getElementById('walletChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: chartData.income,
                    backgroundColor: 'rgba(34,197,94,0.7)',
                    borderRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: chartData.expense,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                        callback: v => 'Rp' + (v >= 1000000 ? (v/1000000).toFixed(1)+'jt' : v >= 1000 ? (v/1000).toFixed(0)+'rb' : v)
                    }
                }
            }
        }
    });
}
</script>
@endpush
@endsection
