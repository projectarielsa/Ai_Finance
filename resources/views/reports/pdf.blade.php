<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 30px; }
h1 { font-size: 22px; color: #1d4ed8; margin-bottom: 2px; }
h2 { font-size: 14px; color: #334155; margin-top: 25px; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
.sub { color: #64748b; font-size: 11px; margin-bottom: 25px; }
.header-line { height: 3px; background: linear-gradient(to right, #3b82f6, #8b5cf6); margin-bottom: 20px; border-radius: 2px; }

/* Stats cards */
.stats { width: 100%; margin-bottom: 20px; }
.stats td { width: 25%; padding: 12px; text-align: center; border: 1px solid #e2e8f0; border-radius: 8px; }
.stat-label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-size: 15px; font-weight: 700; }
.income { color: #16a34a; }
.expense { color: #dc2626; }
.net-positive { color: #2563eb; }
.net-negative { color: #dc2626; }

/* AI Insight box */
.insight-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; }
.insight-title { font-size: 11px; font-weight: 700; color: #0369a1; margin-bottom: 6px; }
.insight-text { color: #334155; font-size: 11px; line-height: 1.6; }

/* Category breakdown */
.category-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.category-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
.category-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
.category-table tr:last-child td { border-bottom: none; }
.bar-bg { background: #f1f5f9; height: 8px; border-radius: 4px; width: 100px; display: inline-block; vertical-align: middle; }
.bar-fill { height: 8px; border-radius: 4px; display: inline-block; vertical-align: middle; }
.bar-expense { background: #ef4444; }
.bar-income { background: #22c55e; }

/* Wallet summary */
.wallet-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.wallet-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; color: #475569; }
.wallet-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }

/* Transaction table */
table.tx-table { width: 100%; border-collapse: collapse; font-size: 10px; }
table.tx-table th { background: #f1f5f9; padding: 7px 8px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600; font-size: 9px; color: #475569; }
table.tx-table td { padding: 6px 8px; border-bottom: 1px solid #f8fafc; }
table.tx-table tr:nth-child(even) td { background: #fafbfc; }
.type-income { color: #16a34a; font-weight: 600; }
.type-expense { color: #dc2626; font-weight: 600; }
.type-transfer { color: #8b5cf6; font-weight: 600; }

/* Summary stats row */
.summary-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; margin-bottom: 15px; }
.summary-row td { padding: 4px 10px; }

/* Footer */
.footer { margin-top: 25px; color: #94a3b8; font-size: 9px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }

/* Page break helper */
.page-break { page-break-before: always; }
</style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- HEADER --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<h1>📊 Laporan Keuangan</h1>
<div class="header-line"></div>
<p class="sub">
    <strong>{{ $user->name }}</strong> · Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
</p>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SUMMARY CARDS --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<table class="stats" cellspacing="8">
<tr>
    <td>
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
    </td>
    <td>
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
    </td>
    <td>
        <div class="stat-label">Net Cashflow</div>
        <div class="stat-value {{ $netCashflow >= 0 ? 'net-positive' : 'net-negative' }}">
            {{ $netCashflow >= 0 ? '+' : '' }}Rp {{ number_format($netCashflow, 0, ',', '.') }}
        </div>
    </td>
    <td>
        <div class="stat-label">Rata-rata Harian</div>
        <div class="stat-value expense">Rp {{ number_format($avgDaily, 0, ',', '.') }}</div>
    </td>
</tr>
</table>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- AI INSIGHT --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@if($aiInsight)
<div class="insight-box">
    <div class="insight-title">🤖 AI Insight</div>
    <div class="insight-text">{{ $aiInsight }}</div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- WALLET BALANCES --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<h2>💳 Saldo Wallet</h2>
<table class="wallet-table">
<thead><tr><th>Wallet</th><th>Tipe</th><th style="text-align:right">Saldo</th></tr></thead>
<tbody>
@foreach($walletSummary as $w)
<tr>
    <td><strong>{{ $w->name }}</strong></td>
    <td>{{ ucfirst(str_replace('_', ' ', $w->type)) }}</td>
    <td style="text-align:right">Rp {{ number_format($w->balance, 0, ',', '.') }}</td>
</tr>
@endforeach
<tr style="border-top: 2px solid #e2e8f0; font-weight: bold;">
    <td colspan="2">Total</td>
    <td style="text-align:right">Rp {{ number_format($walletSummary->where('include_in_total', true)->sum('balance'), 0, ',', '.') }}</td>
</tr>
</tbody>
</table>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- TOP EXPENSE CATEGORIES --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<h2>💸 Top Pengeluaran per Kategori</h2>
<table class="category-table">
<thead><tr><th>#</th><th>Kategori</th><th>Jumlah</th><th>Transaksi</th><th>%</th><th>Proporsi</th></tr></thead>
<tbody>
@foreach($expenseByCategory as $i => $cat)
<tr>
    <td>{{ $loop->iteration }}</td>
    <td><strong>{{ $cat['name'] }}</strong></td>
    <td>Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
    <td>{{ $cat['count'] }}x</td>
    <td>{{ $cat['pct'] }}%</td>
    <td>
        <span class="bar-bg"><span class="bar-fill bar-expense" style="width: {{ min(100, $cat['pct']) }}px;"></span></span>
    </td>
</tr>
@endforeach
</tbody>
</table>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- TOP INCOME CATEGORIES --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@if($incomeByCategory->isNotEmpty())
<h2>💰 Sumber Pemasukan</h2>
<table class="category-table">
<thead><tr><th>#</th><th>Kategori</th><th>Jumlah</th><th>Transaksi</th><th>%</th><th>Proporsi</th></tr></thead>
<tbody>
@foreach($incomeByCategory as $cat)
<tr>
    <td>{{ $loop->iteration }}</td>
    <td><strong>{{ $cat['name'] }}</strong></td>
    <td>Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
    <td>{{ $cat['count'] }}x</td>
    <td>{{ $cat['pct'] }}%</td>
    <td>
        <span class="bar-bg"><span class="bar-fill bar-income" style="width: {{ min(100, $cat['pct']) }}px;"></span></span>
    </td>
</tr>
@endforeach
</tbody>
</table>
@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- TRANSACTION LIST --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="page-break"></div>
<h2>📋 Detail Transaksi ({{ $transactions->count() }} transaksi)</h2>
<table class="tx-table">
<thead>
<tr>
    <th>Tanggal</th>
    <th>Deskripsi</th>
    <th>Kategori</th>
    <th>Wallet</th>
    <th>Tipe</th>
    <th style="text-align:right">Jumlah</th>
</tr>
</thead>
<tbody>
@foreach($transactions as $tx)
<tr>
    <td>{{ $tx->transaction_date->format('d M Y') }}</td>
    <td>{{ \Illuminate\Support\Str::limit($tx->description ?? '—', 30) }}</td>
    <td>{{ $tx->category?->name ?? '—' }}</td>
    <td>{{ $tx->wallet?->name ?? '—' }}</td>
    <td class="type-{{ $tx->type }}">{{ ucfirst($tx->type) }}</td>
    <td style="text-align:right">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
</tr>
@endforeach
</tbody>
</table>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- FOOTER --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div class="footer">
    Generated by Finance AI · {{ now()->format('d M Y H:i') }} · {{ config('app.url') }}
</div>

</body>
</html>
