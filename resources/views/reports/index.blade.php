@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Keuangan')
@section('page-subtitle', 'Analisa dan export laporan keuangan Anda')

@section('content')
<div class="space-y-6 animate-fade-in">

    {{-- Filter --}}
    <div class="glass-card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="form-group">
                <label class="input-label text-xs">Periode</label>
                <select name="type" class="input-field py-2 text-sm">
                    <option value="monthly" {{ $type=='monthly'?'selected':'' }}>Bulanan</option>
                    <option value="yearly"  {{ $type=='yearly'?'selected':'' }}>Tahunan</option>
                    <option value="weekly"  {{ $type=='weekly'?'selected':'' }}>Mingguan</option>
                    <option value="daily"   {{ $type=='daily'?'selected':'' }}>Harian</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Bulan</label>
                <select name="month" class="input-field py-2 text-sm">
                    @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ DateTime::createFromFormat('!m',$m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Tahun</label>
                <select name="year" class="input-field py-2 text-sm">
                    @for($y=now()->year;$y>=now()->year-3;$y--)
                    <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn-primary py-2 text-sm">Tampilkan</button>
            <a href="{{ route('reports.pdf', request()->query()) }}" class="btn-secondary py-2 text-sm">
                📄 Export PDF
            </a>
            <a href="{{ route('reports.excel', request()->query()) }}" class="btn-secondary py-2 text-sm">
                📊 Export Excel
            </a>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Total Pemasukan</p>
            <p class="text-2xl font-bold text-green-400">Rp {{ number_format($totalIncome,0,',','.') }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Total Pengeluaran</p>
            <p class="text-2xl font-bold text-red-400">Rp {{ number_format($totalExpense,0,',','.') }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Net Cashflow</p>
            @php $net = $totalIncome - $totalExpense @endphp
            <p class="text-2xl font-bold {{ $net>=0?'text-green-400':'text-red-400' }}">
                {{ $net>=0?'+':'' }}Rp {{ number_format($net,0,',','.') }}
            </p>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Daily trend chart --}}
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4">Tren Harian</h3>
            <div class="h-52"><canvas id="dailyChart"></canvas></div>
        </div>

        {{-- Expense by category --}}
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4">Pengeluaran per Kategori</h3>
            @if($expenseByCategory->isEmpty())
            <p class="text-dark-400 text-sm">Tidak ada data pengeluaran</p>
            @else
            <div class="space-y-3">
                @php $maxExp = $expenseByCategory->max('total') @endphp
                @foreach($expenseByCategory->take(7) as $cat)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-dark-200">{{ $cat['name'] }}</span>
                        <span class="text-red-400 font-medium">Rp{{ number_format($cat['total'],0,',','.') }}</span>
                    </div>
                    <div class="bg-dark-700/50 rounded-full h-1.5">
                        <div class="bg-red-500/60 h-1.5 rounded-full transition-all duration-500" style="width:{{ $maxExp>0 ? round($cat['total']/$maxExp*100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Transactions table --}}
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-700/30">
            <h3 class="text-white font-semibold">Detail Transaksi ({{ $transactions->count() }} transaksi)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Wallet</th><th>Tipe</th><th class="text-right">Jumlah</th>
                </tr></thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="text-dark-400 text-xs">{{ $tx->transaction_date->format('d M Y') }}</td>
                        <td>{{ $tx->description ?? '—' }}</td>
                        <td>{{ $tx->category?->name ?? '—' }}</td>
                        <td>{{ $tx->wallet->name }}</td>
                        <td><span class="badge badge-{{ $tx->type }}">{{ ucfirst($tx->type) }}</span></td>
                        <td class="text-right font-medium {{ $tx->type==='income'?'text-green-400':($tx->type==='expense'?'text-red-400':'text-blue-400') }}">
                            Rp {{ number_format($tx->amount,0,',','.') }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-8 text-dark-400">Tidak ada transaksi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const dailyData = @json($dailyData);
const labels  = Object.keys(dailyData);
const income  = labels.map(l => dailyData[l].income);
const expense = labels.map(l => dailyData[l].expense);

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { label:'Pemasukan', data: income, borderColor:'rgba(34,197,94,0.8)', backgroundColor:'rgba(34,197,94,0.1)', fill:true, tension:0.4, borderWidth:2, pointRadius:3 },
            { label:'Pengeluaran', data: expense, borderColor:'rgba(239,68,68,0.8)', backgroundColor:'rgba(239,68,68,0.1)', fill:true, tension:0.4, borderWidth:2, pointRadius:3 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ labels:{ color:'#94a3b8', font:{size:11} } }, tooltip:{ backgroundColor:'#1e293b', borderColor:'#334155', borderWidth:1 } },
        scales:{
            x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{size:10}}},
            y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{size:10},callback:v=>'Rp'+Intl.NumberFormat('id-ID',{notation:'compact'}).format(v)}}
        }
    }
});
</script>
@endpush
