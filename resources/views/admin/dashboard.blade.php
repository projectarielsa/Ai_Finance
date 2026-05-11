@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Users</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $totalUsers }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Transaksi</p>
            <p class="text-3xl font-bold text-white mt-1">{{ number_format($totalTransactions) }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Pesan Telegram</p>
            <p class="text-3xl font-bold text-white mt-1">{{ number_format($totalTgMessages) }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">AI Requests</p>
            <p class="text-3xl font-bold text-white mt-1">{{ number_format($totalAiRequests) }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">AI Success Rate</p>
            <p class="text-3xl font-bold {{ $aiSuccessRate>=80?'text-green-400':($aiSuccessRate>=60?'text-yellow-400':'text-red-400') }} mt-1">{{ $aiSuccessRate }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Recent Users</h3></div>
            <table class="data-table">
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th></tr></thead>
                <tbody>
                    @foreach($recentUsers as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td class="text-dark-400">{{ $u->email }}</td>
                        <td><span class="badge {{ $u->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success' }}">{{ $u->role }}</span></td>
                        <td class="text-dark-400 text-xs">{{ $u->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Recent AI Requests</h3></div>
            <table class="data-table">
                <thead><tr><th>Type</th><th>User</th><th>Status</th><th>Waktu</th></tr></thead>
                <tbody>
                    @foreach($recentAiLogs as $log)
                    <tr>
                        <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded">{{ $log->type }}</span></td>
                        <td class="text-dark-400 text-xs">{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="badge {{ $log->success?'badge-success':'badge-danger' }}">{{ $log->success?'OK':'FAIL' }}</span></td>
                        <td class="text-dark-500 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
