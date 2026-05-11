@extends('layouts.app')
@section('title', 'Transaksi')
@section('page-title', 'Transaksi')
@section('page-subtitle', 'Riwayat semua transaksi keuangan Anda')

@section('content')
<div class="space-y-5 animate-fade-in">

    {{-- Filters --}}
    <div class="glass-card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="form-group">
                <label class="input-label text-xs">Tipe</label>
                <select name="type" class="input-field py-2 text-sm">
                    <option value="">Semua Tipe</option>
                    <option value="income"   {{ request('type')=='income'?'selected':'' }}>Pemasukan</option>
                    <option value="expense"  {{ request('type')=='expense'?'selected':'' }}>Pengeluaran</option>
                    <option value="transfer" {{ request('type')=='transfer'?'selected':'' }}>Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Wallet</label>
                <select name="wallet" class="input-field py-2 text-sm">
                    <option value="">Semua Wallet</option>
                    @foreach($wallets as $w)
                    <option value="{{ $w->id }}" {{ request('wallet')==$w->id?'selected':'' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Bulan</label>
                <select name="month" class="input-field py-2 text-sm">
                    <option value="">Semua</option>
                    @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ request('month')==$m?'selected':'' }}>{{ DateTime::createFromFormat('!m',$m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Tahun</label>
                <select name="year" class="input-field py-2 text-sm">
                    @for($y=now()->year;$y>=now()->year-3;$y--)
                    <option value="{{ $y }}" {{ request('year')==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="form-group flex-1 min-w-40">
                <label class="input-label text-xs">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Deskripsi, merchant..." class="input-field py-2 text-sm">
            </div>
            <button type="submit" class="btn-primary py-2 text-sm">Filter</button>
            @if(request()->hasAny(['type','wallet','month','year','search']))
            <a href="{{ route('transactions.index') }}" class="btn-secondary py-2 text-sm">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-700/30 flex items-center justify-between">
            <p class="text-dark-400 text-sm">{{ $transactions->total() }} transaksi ditemukan</p>
            <a href="{{ route('transactions.create') }}" class="btn-primary text-sm">+ Tambah</a>
        </div>

        @if($transactions->isEmpty())
        <div class="p-16 text-center">
            <div class="text-4xl mb-3">🔍</div>
            <p class="text-dark-300 font-medium">Tidak ada transaksi</p>
            <p class="text-dark-500 text-sm mt-1">Coba ubah filter atau tambah transaksi baru</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Wallet</th>
                    <th>Kategori</th>
                    <th>Sumber</th>
                    <th>Tipe</th>
                    <th class="text-right">Jumlah</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @foreach($transactions as $tx)
                    <tr>
                        <td class="text-dark-400 text-xs whitespace-nowrap">
                            {{ $tx->transaction_date->format('d M Y') }}<br>
                            <span class="text-dark-600">{{ $tx->transaction_date->format('H:i') }}</span>
                        </td>
                        <td>
                            <p class="text-white text-sm font-medium truncate max-w-[180px]">{{ $tx->description ?? 'Transaksi' }}</p>
                            @if($tx->merchant)<p class="text-dark-500 text-xs">{{ $tx->merchant }}</p>@endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <div class="w-6 h-6 rounded-lg text-xs font-bold flex items-center justify-center" style="background:{{ $tx->wallet->color }}22;color:{{ $tx->wallet->color }}">{{ substr($tx->wallet->name,0,1) }}</div>
                                <span class="text-sm text-dark-200">{{ $tx->wallet->name }}</span>
                            </div>
                            @if($tx->targetWallet)<p class="text-xs text-dark-500 mt-0.5">→ {{ $tx->targetWallet->name }}</p>@endif
                        </td>
                        <td>
                            @if($tx->category)
                            <span class="text-sm text-dark-300">{{ $tx->category->name }}</span>
                            @else
                            <span class="text-dark-600">—</span>
                            @endif
                        </td>
                        <td>
                            @php $src=['manual'=>'✋','telegram_text'=>'💬','telegram_image'=>'📸','telegram_voice'=>'🎤','import'=>'📥'] @endphp
                            <span title="{{ $tx->source }}" class="text-sm">{{ $src[$tx->source] ?? '?' }}</span>
                            @if($tx->ai_confidence)<span class="text-xs text-dark-500 ml-1">{{ round($tx->ai_confidence) }}%</span>@endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $tx->type }}">{{ ucfirst($tx->type) }}</span>
                            @if($tx->is_duplicate)
                                <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs ml-1" title="Kemungkinan duplikat">⚠️ dup</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold whitespace-nowrap {{ $tx->type==='income'?'text-green-400':($tx->type==='expense'?'text-red-400':'text-blue-400') }}">
                            {{ $tx->type==='income'?'+':($tx->type==='expense'?'-':'') }}Rp{{ number_format($tx->amount,0,',','.') }}
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('transactions.show',$tx) }}" class="btn-icon p-1.5">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                </a>
                                <form action="{{ route('transactions.destroy',$tx) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" data-confirm="Yakin hapus transaksi ini? Saldo wallet akan dikembalikan." class="btn-icon p-1.5 text-red-400 hover:text-red-300">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-dark-700/30">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
