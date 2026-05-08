@extends('layouts.admin')
@section('title', 'Telegram Logs')
@section('page-title', 'Telegram Message Logs')

@section('content')
<div class="glass-card overflow-hidden animate-fade-in">
    <table class="data-table">
        <thead><tr>
            <th>Waktu</th><th>User</th><th>Chat ID</th><th>Arah</th><th>Tipe</th><th>Konten</th><th>Status</th>
        </tr></thead>
        <tbody>
            @forelse($messages as $msg)
            <tr>
                <td class="text-xs text-dark-400 whitespace-nowrap">{{ $msg->created_at->format('d M Y H:i') }}</td>
                <td class="text-sm">{{ $msg->user?->name ?? '—' }}</td>
                <td class="text-xs font-mono text-dark-400">{{ $msg->chat_id }}</td>
                <td>
                    <span class="badge {{ $msg->direction==='inbound'?'badge-income':'badge-transfer' }}">
                        {{ $msg->direction==='inbound' ? '↙ In' : '↗ Out' }}
                    </span>
                </td>
                <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded">{{ $msg->type }}</span></td>
                <td class="max-w-xs">
                    <p class="text-xs text-dark-300 truncate">{{ \Str::limit($msg->content, 60) ?? '(media)' }}</p>
                </td>
                <td>
                    <span class="badge {{ in_array($msg->status,['processed','sent'])?'badge-success':($msg->status==='failed'?'badge-danger':'badge-pending') }}">
                        {{ $msg->status }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-dark-400">Belum ada pesan Telegram</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-dark-700/30">{{ $messages->links() }}</div>
</div>
@endsection
