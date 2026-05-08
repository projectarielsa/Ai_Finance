@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name . '!')

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- ── STAT CARDS ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Total Saldo --}}
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Total Saldo</p>
                    <p class="text-2xl font-bold text-white mt-1">Rp {{ number_format($totalBalance,0,',','.') }}</p>
                    <p class="text-dark-500 text-xs mt-1">{{ $wallets->count() }} wallet aktif</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-primary-500/15 flex items-center justify-center text-primary-400 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18-3a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3m18-3v3M3 9h18"/></svg>
                </div>
            </div>
        </div>

        {{-- Total Pemasukan --}}
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Pemasukan Bulan Ini</p>
                    <p class="text-2xl font-bold text-green-400 mt-1">Rp {{ number_format($monthlyIncome,0,',','.') }}</p>
                    <p class="text-dark-500 text-xs mt-1">{{ now()->format('F Y') }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-green-500/15 flex items-center justify-center text-green-400 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                </div>
            </div>
        </div>

        {{-- Total Pengeluaran --}}
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Pengeluaran Bulan Ini</p>
                    <p class="text-2xl font-bold text-red-400 mt-1">Rp {{ number_format($monthlyExpense,0,',','.') }}</p>
                    <p class="text-dark-500 text-xs mt-1">{{ now()->format('F Y') }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-red-500/15 flex items-center justify-center text-red-400 group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181"/></svg>
                </div>
            </div>
        </div>

        {{-- Net Cashflow --}}
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Net Cashflow</p>
                    <p class="text-2xl font-bold mt-1 {{ $netCashflow >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $netCashflow >= 0 ? '+' : '' }}Rp {{ number_format($netCashflow,0,',','.') }}
                    </p>
                    <p class="text-dark-500 text-xs mt-1">{{ $netCashflow >= 0 ? '📈 Positif' : '📉 Negatif' }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $netCashflow >= 0 ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }} flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.97z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ── CHART + AI INSIGHT ROW ──────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Cashflow Chart --}}
        <div class="glass-card p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-white font-semibold">Pemasukan vs Pengeluaran</h3>
                    <p class="text-dark-400 text-xs mt-0.5">6 bulan terakhir</p>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1.5 text-dark-300"><span class="w-3 h-3 rounded-full bg-green-400"></span>Pemasukan</span>
                    <span class="flex items-center gap-1.5 text-dark-300"><span class="w-3 h-3 rounded-full bg-red-400"></span>Pengeluaran</span>
                </div>
            </div>
            <div class="relative h-56">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>

        {{-- AI Insight + Top Categories --}}
        <div class="space-y-4">
            {{-- AI Insight --}}
            <div class="glass-card p-5 border border-primary-500/20 bg-primary-500/5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg bg-primary-500/20 flex items-center justify-center text-sm">🤖</div>
                    <span class="text-sm font-semibold text-primary-300">AI Insight</span>
                </div>
                <p class="text-dark-200 text-sm leading-relaxed">{{ $aiInsight }}</p>
            </div>

            {{-- Top Categories --}}
            <div class="glass-card p-5">
                <h3 class="text-white font-semibold text-sm mb-3">Top Pengeluaran</h3>
                <div class="space-y-2.5">
                    @forelse($topCategories as $i => $tc)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-dark-400 text-xs w-4">{{ $i+1 }}.</span>
                            <span class="text-dark-200 text-sm">{{ $tc->category?->name ?? 'Lainnya' }}</span>
                        </div>
                        <span class="text-red-400 text-sm font-medium">Rp{{ number_format($tc->total,0,',','.') }}</span>
                    </div>
                    @empty
                    <p class="text-dark-400 text-sm">Belum ada data</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── WALLETS + RECENT TRANSACTIONS ──────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Wallets --}}
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-semibold">Wallet Saya</h3>
                <a href="{{ route('wallets.index') }}" class="text-primary-400 text-xs hover:text-primary-300">Lihat semua →</a>
            </div>
            <div class="space-y-3">
                @foreach($wallets->take(5) as $wallet)
                <div class="flex items-center justify-between p-3 rounded-xl bg-dark-700/30 hover:bg-dark-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg font-bold" style="background: {{ $wallet->color }}22; color: {{ $wallet->color }}">
                            {{ substr($wallet->name,0,1) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">{{ $wallet->name }}</p>
                            <p class="text-xs text-dark-400">{{ ucfirst(str_replace('_',' ', $wallet->type)) }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white">Rp {{ number_format($wallet->balance,0,',','.') }}</p>
                    </div>
                </div>
                @endforeach
                @if($wallets->count() === 0)
                <div class="text-center py-4">
                    <p class="text-dark-400 text-sm">Belum ada wallet</p>
                    <a href="{{ route('wallets.create') }}" class="text-primary-400 text-xs hover:text-primary-300">+ Tambah wallet</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="glass-card p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-semibold">Transaksi Terbaru</h3>
                <a href="{{ route('transactions.index') }}" class="text-primary-400 text-xs hover:text-primary-300">Lihat semua →</a>
            </div>
            <div class="space-y-2">
                @forelse($recentTransactions as $tx)
                <div class="flex items-center justify-between p-3 rounded-xl hover:bg-dark-700/20 transition-colors group">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                            {{ $tx->type === 'income' ? 'bg-green-500/15 text-green-400' : ($tx->type === 'expense' ? 'bg-red-500/15 text-red-400' : 'bg-blue-500/15 text-blue-400') }}">
                            {{ $tx->type === 'income' ? '↑' : ($tx->type === 'transfer' ? '⇄' : '↓') }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white truncate max-w-[200px]">{{ $tx->description ?? $tx->category?->name ?? 'Transaksi' }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-dark-400">{{ $tx->wallet->name }}</span>
                                @if($tx->category)<span class="text-dark-600">·</span><span class="text-xs text-dark-500">{{ $tx->category->name }}</span>@endif
                            </div>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-semibold {{ $tx->type === 'income' ? 'text-green-400' : ($tx->type === 'expense' ? 'text-red-400' : 'text-blue-400') }}">
                            {{ $tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '') }}Rp {{ number_format($tx->amount,0,',','.') }}
                        </p>
                        <p class="text-xs text-dark-500">{{ $tx->transaction_date->format('d M') }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <div class="text-4xl mb-2">📊</div>
                    <p class="text-dark-400 text-sm">Belum ada transaksi</p>
                    <a href="{{ route('transactions.create') }}" class="text-primary-400 text-xs hover:text-primary-300">+ Tambah transaksi pertama</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const chartData = @json($chartData);
const ctx = document.getElementById('cashflowChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'Pemasukan',
                data: chartData.income,
                backgroundColor: 'rgba(34,197,94,0.25)',
                borderColor: 'rgba(34,197,94,0.8)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Pengeluaran',
                data: chartData.expense,
                backgroundColor: 'rgba(239,68,68,0.25)',
                borderColor: 'rgba(239,68,68,0.8)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                borderColor: '#334155',
                borderWidth: 1,
                titleColor: '#f1f5f9',
                bodyColor: '#94a3b8',
                callbacks: {
                    label: (ctx) => ` Rp${new Intl.NumberFormat('id-ID').format(ctx.raw)}`
                }
            }
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: {
                    color: '#64748b', font: { size: 11 },
                    callback: (v) => 'Rp' + new Intl.NumberFormat('id-ID', {notation:'compact'}).format(v)
                }
            }
        }
    }
});
</script>
@endpush
