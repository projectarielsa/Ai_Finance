@extends('layouts.admin')
@section('title', 'WhatsApp Gateway')
@section('page-title', 'WhatsApp Gateway')

@section('content')
<div class="space-y-5 animate-fade-in">
    <div class="flex justify-between items-center">
        <p class="text-dark-400 text-sm">Kelola gateway WhatsApp untuk menerima dan mengirim pesan otomatis.</p>
        <a href="{{ route('admin.whatsapp-gateways.create') }}" class="btn-primary text-sm">+ Tambah Gateway</a>
    </div>

    <div class="glass-card p-5 border border-yellow-500/20 bg-yellow-500/5">
        <h4 class="text-yellow-300 font-semibold text-sm mb-2">📌 Webhook URL</h4>
        <p class="text-dark-300 text-xs mb-2">Daftarkan URL ini ke provider WhatsApp Gateway Anda:</p>
        <div class="flex items-center gap-2">
            <code class="text-sm font-mono bg-dark-800 px-3 py-2 rounded-lg text-green-300 flex-1">{{ route('webhook.whatsapp') }}</code>
            <button onclick="navigator.clipboard.writeText('{{ route('webhook.whatsapp') }}').then(()=>showToast('URL disalin!','success'))" class="btn-secondary text-sm py-2">Copy</button>
        </div>
    </div>

    @foreach($gateways as $gw)
    <div class="glass-card p-5 {{ $gw->deleted_at ? 'opacity-50' : '' }}">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-500/15 flex items-center justify-center text-2xl">📱</div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-white font-semibold">{{ $gw->name }}</h3>
                        @if($gw->is_default)<span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25">Default</span>@endif
                        <span class="badge {{ $gw->status_badge }}">{{ ucfirst($gw->status) }}</span>
                    </div>
                    <p class="text-dark-400 text-sm">Provider: {{ $gw->provider }}</p>
                    <p class="text-dark-500 text-xs">{{ $gw->base_url }}</p>
                    @if($gw->sender_number)<p class="text-dark-400 text-xs mt-0.5">Nomor: +{{ $gw->sender_number }}</p>@endif
                    @if($gw->last_connected_at)<p class="text-dark-500 text-xs mt-0.5">Terakhir connect: {{ $gw->last_connected_at->diffForHumans() }}</p>@endif
                </div>
            </div>

            @if(!$gw->deleted_at)
            <div class="flex items-center gap-2" x-data="{ showTestForm: false }">
                <button onclick="testGateway({{ $gw->id }}, this)" class="btn-success text-xs py-1.5 px-3">Test Koneksi</button>
                <button @click="showTestForm=!showTestForm" class="btn-secondary text-xs py-1.5 px-3">Kirim Test</button>
                <a href="{{ route('admin.whatsapp-gateways.edit',$gw) }}" class="btn-icon p-2">✏️</a>
                <form action="{{ route('admin.whatsapp-gateways.destroy',$gw) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" data-confirm="Hapus gateway {{ $gw->name }}?" class="btn-icon p-2 text-red-400">🗑</button>
                </form>

                <div x-show="showTestForm" x-transition class="absolute right-0 top-12 w-72 glass-card p-4 z-10 shadow-2xl" style="position:relative">
                    <p class="text-white text-sm font-medium mb-3">Kirim Pesan Test</p>
                    <input type="text" id="test-phone-{{ $gw->id }}" class="input-field text-sm mb-2" placeholder="628xxx (nomor tujuan)">
                    <input type="text" id="test-msg-{{ $gw->id }}" class="input-field text-sm mb-3" value="Halo! Ini test dari Finance AI.">
                    <button onclick="sendTest({{ $gw->id }})" class="btn-primary text-sm w-full justify-center">Kirim</button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach

    @if($gateways->isEmpty())
    <div class="glass-card p-12 text-center">
        <div class="text-4xl mb-3">📱</div>
        <p class="text-white font-semibold">Belum ada WhatsApp Gateway</p>
        <p class="text-dark-400 text-sm mt-1 mb-4">Tambahkan gateway untuk mengaktifkan fitur WhatsApp AI</p>
        <a href="{{ route('admin.whatsapp-gateways.create') }}" class="btn-primary">+ Tambah Gateway</a>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function testGateway(id, btn) {
    btn.disabled = true;
    btn.textContent = 'Testing...';
    try {
        const res = await axios.post(`/admin/whatsapp-gateways/${id}/test`, {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        showToast(res.data.message, res.data.success ? 'success' : 'error');
        setTimeout(() => location.reload(), 1500);
    } catch(e) {
        showToast('Test gagal', 'error');
    }
    btn.disabled = false;
    btn.textContent = 'Test Koneksi';
}

async function sendTest(id) {
    const phone = document.getElementById(`test-phone-${id}`).value;
    const msg   = document.getElementById(`test-msg-${id}`).value;
    try {
        const res = await axios.post(`/admin/whatsapp-gateways/${id}/test-send`, { phone, message: msg }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        showToast(res.data.message, res.data.success ? 'success' : 'error');
    } catch(e) {
        showToast('Gagal kirim pesan', 'error');
    }
}
</script>
@endpush
