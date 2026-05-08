@extends('layouts.app')
@section('title', 'Detail Transaksi')
@section('page-title', 'Detail Transaksi')

@section('content')
<div class="max-w-2xl mx-auto animate-fade-in space-y-5">
    <div class="glass-card p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <span class="badge badge-{{ $transaction->type }} text-sm px-3 py-1 mb-2 inline-block">
                    {{ ucfirst($transaction->type) }}
                </span>
                <p class="text-3xl font-bold {{ $transaction->type==='income'?'text-green-400':($transaction->type==='expense'?'text-red-400':'text-blue-400') }}">
                    {{ $transaction->type==='income'?'+':($transaction->type==='expense'?'-':'') }}Rp {{ number_format($transaction->amount,0,',','.') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('transactions.edit',$transaction) }}" class="btn-secondary text-sm">Edit</a>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4">
            <div><dt class="text-dark-400 text-xs mb-0.5">Deskripsi</dt><dd class="text-white text-sm">{{ $transaction->description ?? '—' }}</dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Tanggal</dt><dd class="text-white text-sm">{{ $transaction->transaction_date->format('d M Y H:i') }}</dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Wallet</dt><dd class="text-white text-sm">{{ $transaction->wallet->name }}</dd></div>
            @if($transaction->targetWallet)<div><dt class="text-dark-400 text-xs mb-0.5">Wallet Tujuan</dt><dd class="text-white text-sm">{{ $transaction->targetWallet->name }}</dd></div>@endif
            <div><dt class="text-dark-400 text-xs mb-0.5">Kategori</dt><dd class="text-white text-sm">{{ $transaction->category?->name ?? '—' }}</dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Merchant</dt><dd class="text-white text-sm">{{ $transaction->merchant ?? '—' }}</dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Sumber</dt>
                <dd class="text-white text-sm">
                    @php $src=['manual'=>'Manual','whatsapp_text'=>'WhatsApp Text','whatsapp_image'=>'WhatsApp Foto Struk','whatsapp_voice'=>'WhatsApp Voice Note','import'=>'Import'] @endphp
                    {{ $src[$transaction->source] ?? $transaction->source }}
                </dd>
            </div>
            @if($transaction->ai_confidence)
            <div><dt class="text-dark-400 text-xs mb-0.5">AI Confidence</dt>
                <dd class="flex items-center gap-2">
                    <div class="flex-1 bg-dark-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $transaction->ai_confidence>=80?'bg-green-500':($transaction->ai_confidence>=60?'bg-yellow-500':'bg-red-500') }}"
                             style="width:{{ $transaction->ai_confidence }}%"></div>
                    </div>
                    <span class="text-sm text-white">{{ round($transaction->ai_confidence) }}%</span>
                </dd>
            </div>
            @endif
            @if($transaction->notes)<div class="col-span-2"><dt class="text-dark-400 text-xs mb-0.5">Catatan</dt><dd class="text-white text-sm">{{ $transaction->notes }}</dd></div>@endif
        </dl>

        @if($transaction->attachment)
        <div class="mt-5 pt-5 border-t border-dark-700/30">
            <p class="text-dark-400 text-xs mb-2">Lampiran Struk</p>
            <img src="{{ $transaction->attachment_url }}" alt="struk" class="max-w-xs rounded-xl border border-dark-600/30">
        </div>
        @endif

        @if($transaction->voiceNoteTranscription)
        <div class="mt-5 pt-5 border-t border-dark-700/30">
            <p class="text-dark-400 text-xs mb-2">🎤 Transkrip Voice Note</p>
            <p class="text-dark-200 text-sm italic">"{{ $transaction->voiceNoteTranscription->transcription }}"</p>
        </div>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('transactions.index') }}" class="btn-secondary">← Kembali</a>
        <form action="{{ route('transactions.destroy',$transaction) }}" method="POST">
            @csrf @method('DELETE')
            <button type="submit" data-confirm="Yakin hapus transaksi ini? Saldo wallet akan dikembalikan." class="btn-danger">Hapus Transaksi</button>
        </form>
    </div>
</div>
@endsection
