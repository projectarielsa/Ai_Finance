@extends('layouts.admin')
@section('title', 'Manage Users')
@section('page-title', 'Manage Users')

@section('content')
<div class="space-y-5 animate-fade-in">
    <div class="glass-card p-4">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" class="input-field py-2 text-sm flex-1" placeholder="Cari nama, email, nomor...">
            <button type="submit" class="btn-primary py-2 text-sm">Cari</button>
            @if(request('search'))<a href="{{ route('admin.users.index') }}" class="btn-secondary py-2 text-sm">Reset</a>@endif
        </form>
    </div>

    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead><tr><th>User</th><th>Phone</th><th>Role</th><th>Status</th><th>Bergabung</th><th></th></tr></thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full object-cover">
                            <div>
                                <p class="text-white text-sm font-medium">{{ $u->name }}</p>
                                <p class="text-dark-400 text-xs">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-dark-400 text-sm">{{ $u->phone ?? '—' }}</td>
                    <td><span class="badge {{ $u->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success' }}">{{ $u->role }}</span></td>
                    <td><span class="badge {{ $u->is_active?'badge-success':'badge-danger' }}">{{ $u->is_active?'Aktif':'Nonaktif' }}</span></td>
                    <td class="text-dark-400 text-xs">{{ $u->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.users.show',$u) }}" class="btn-icon p-1.5 text-xs">👁</a>
                            <form action="{{ route('admin.users.toggle-active',$u) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-icon p-1.5 text-xs" title="{{ $u->is_active?'Nonaktifkan':'Aktifkan' }}">{{ $u->is_active?'🔒':'🔓' }}</button>
                            </form>
                            <form action="{{ route('admin.users.toggle-role',$u) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-icon p-1.5 text-xs" title="Toggle Role">{{ $u->role==='admin'?'👤':'👑' }}</button>
                            </form>
                            @if($u->trashed())
                                <form action="{{ route('admin.users.restore',$u->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-icon p-1.5 text-xs" title="Pulihkan User">♻️</button>
                                </form>
                                <form action="{{ route('admin.users.force-delete',$u->id) }}" method="POST" onsubmit="return confirm('HAPUS PERMANEN user {{ $u->name }}? Semua data akan hilang dan tidak bisa dikembalikan!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon p-1.5 text-xs text-red-400" title="Hapus Permanen">💀</button>
                                </form>
                            @elseif($u->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy',$u) }}" method="POST" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon p-1.5 text-xs text-red-400" title="Hapus User">🗑️</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-dark-400">Tidak ada user</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-dark-700/30">{{ $users->links() }}</div>
    </div>
</div>
@endsection
