@extends('layouts.admin')
@section('title', 'AI Logs')
@section('page-title', 'AI Request Logs')

@section('content')
<div class="glass-card overflow-hidden animate-fade-in">
    <table class="data-table">
        <thead><tr><th>Waktu</th><th>User</th><th>Provider</th><th>Type</th><th>Tokens</th><th>Duration</th><th>Status</th><th>Response</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-xs text-dark-400 whitespace-nowrap">{{ $log->created_at->format('d M H:i:s') }}</td>
                <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded">{{ $log->provider }}</span></td>
                <td><span class="text-xs font-mono bg-blue-500/10 text-blue-300 px-2 py-0.5 rounded">{{ $log->type }}</span></td>
                <td class="text-xs text-dark-400">{{ number_format($log->total_tokens) }}</td>
                <td class="text-xs text-dark-400">{{ $log->duration_ms }}ms</td>
                <td><span class="badge {{ $log->success?'badge-success':'badge-danger' }}">{{ $log->success?'OK':'FAIL' }}</span></td>
                <td class="max-w-xs">
                    <p class="text-xs text-dark-500 truncate" title="{{ $log->response }}">
                        {{ $log->success ? Str::limit($log->response, 60) : $log->error_message }}
                    </p>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-10 text-dark-400">Belum ada AI logs</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-dark-700/30">{{ $logs->links() }}</div>
</div>
@endsection
