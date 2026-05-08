@extends('layouts.admin')
@section('title', 'Telegram Bot')
@section('page-title', 'Telegram Bot')

@section('content')
<div class="space-y-5 animate-fade-in">

    {{-- Webhook URL Banner --}}
    <div class="glass-card p-5 border border-green-500/20 bg-green-500/5">
        <h4 class="text-green-300 font-semibold text-sm mb-2">🔗 Webhook URL</h4>
        <p class="text-dark-300 text-xs mb-3">Set URL ini ke Telegram dengan tombol di bawah, atau jalankan perintah Artisan:</p>
        <div class="flex flex-wrap gap-2 items-center">
            <code class="text-sm font-mono bg-dark-800 px-3 py-2 rounded-lg text-green-300 flex-1 min-w-0 truncate">{{ route('webhook.telegram') }}</code>
            <button onclick="navigator.clipboard.writeText('{{ route('webhook.telegram') }}').then(()=>showToast('URL disalin!','success'))" class="btn-secondary text-xs py-2 px-3 flex-shrink-0">Copy</button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5 text-center">
            <p class="text-dark-400 text-xs mb-1">Total Pesan</p>
            <p class="text-3xl font-bold text-white">{{ number_format($totalMsgs) }}</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-dark-400 text-xs mb-1">Pengguna Aktif</p>
            <p class="text-3xl font-bold text-white">{{ $uniqueUsers }}</p>
        </div>
        <div class="glass-card p-5 text-center">
            <p class="text-dark-400 text-xs mb-1">Status Webhook</p>
            @if(!empty($webhookInfo['url']))
            <p class="text-green-400 font-bold">✅ Connected</p>
            <p class="text-dark-500 text-xs mt-1 truncate">{{ $webhookInfo['url'] }}</p>
            @else
            <p class="text-yellow-400 font-bold">⚠️ Not Set</p>
            @endif
        </div>
    </div>

    {{-- Bot Actions --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-4">🤖 Bot Management</h3>
        <div class="flex flex-wrap gap-3">
            <button onclick="testBot()" class="btn-success text-sm">
                🔌 Test Koneksi Bot
            </button>
            <button onclick="setWebhook()" class="btn-primary text-sm">
                🔗 Set Webhook
            </button>
            <button onclick="deleteWebhook()" class="btn-secondary text-sm">
                🗑 Hapus Webhook
            </button>
        </div>
        <div id="bot-result" class="mt-3 hidden p-3 rounded-xl bg-dark-700/50 border border-dark-600/30">
            <p id="bot-result-text" class="text-sm text-dark-200"></p>
        </div>
    </div>

    {{-- Send Test Message --}}
    <div class="glass-card p-6">
        <h3 class="text-white font-semibold mb-4">📨 Kirim Pesan Test</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" id="test-chat-id" class="input-field text-sm" placeholder="Chat ID (dari /start bot Anda)">
            <input type="text" id="test-message" class="input-field text-sm sm:col-span-2" value="Halo! Ini test dari Finance AI Bot 🤖">
        </div>
        <button onclick="sendTest()" class="btn-primary text-sm mt-3">Kirim Pesan Test</button>
    </div>

    {{-- Setup Guide --}}
    <div class="glass-card p-6 border border-blue-500/20 bg-blue-500/5">
        <h4 class="text-blue-300 font-semibold mb-3">📖 Cara Setup Telegram Bot</h4>
        <ol class="text-dark-300 text-sm space-y-2 list-decimal list-inside">
            <li>Buka Telegram, cari <strong class="text-white">@BotFather</strong></li>
            <li>Ketik <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">/newbot</code> dan ikuti instruksi</li>
            <li>Salin <strong class="text-white">Bot Token</strong> yang diberikan BotFather</li>
            <li>Isi di file <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">.env</code>:
                <pre class="mt-1 text-xs bg-dark-800 p-3 rounded-lg text-green-300 overflow-x-auto">TELEGRAM_BOT_TOKEN=1234567890:ABCDefGhIJKlmNoPQRstuVWXyz
TELEGRAM_BOT_USERNAME=YourBotName</pre>
            </li>
            <li>Klik tombol <strong class="text-white">🔗 Set Webhook</strong> di atas</li>
            <li>Buka bot Anda di Telegram, kirim <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">/start</code></li>
            <li>User harus menghubungkan akun di <strong class="text-white">Profil → Hubungkan Telegram</strong></li>
        </ol>
        <div class="mt-4 p-3 rounded-xl bg-dark-700/30 border border-dark-600/30">
            <p class="text-dark-400 text-xs font-medium mb-1">💡 Link akun via command bot:</p>
            <p class="text-dark-500 text-xs">User bisa hubungkan akun dengan mengirim: <code class="bg-dark-800 px-1 py-0.5 rounded text-yellow-300">/link email@mereka.com</code> (tambahkan fitur ini di TelegramWebhookService jika diperlukan)</p>
        </div>
    </div>

    {{-- Webhook Detail --}}
    @if(!empty($webhookInfo))
    <div class="glass-card p-5">
        <h3 class="text-white font-semibold mb-3">📡 Webhook Info</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-dark-400 text-xs">URL</dt><dd class="text-white text-xs font-mono truncate">{{ $webhookInfo['url'] ?? '—' }}</dd></div>
            <div><dt class="text-dark-400 text-xs">Pending Updates</dt><dd class="text-white">{{ $webhookInfo['pending_update_count'] ?? 0 }}</dd></div>
            <div><dt class="text-dark-400 text-xs">Last Error</dt><dd class="text-{{ empty($webhookInfo['last_error_message']) ? 'green' : 'red' }}-400 text-xs">{{ $webhookInfo['last_error_message'] ?? 'None' }}</dd></div>
            <div><dt class="text-dark-400 text-xs">Max Connections</dt><dd class="text-white">{{ $webhookInfo['max_connections'] ?? 40 }}</dd></div>
        </dl>
    </div>
    @endif

    {{-- Recent Messages --}}
    <div class="glass-card overflow-hidden">
        <div class="px-5 py-4 border-b border-dark-700/30 flex items-center justify-between">
            <h3 class="text-white font-semibold">Pesan Terbaru</h3>
            <a href="{{ route('admin.tg-logs') }}" class="text-primary-400 text-xs hover:text-primary-300">Lihat semua →</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Waktu</th><th>User</th><th>Chat ID</th><th>Tipe</th><th>Konten</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($recentMsgs->take(10) as $msg)
                <tr>
                    <td class="text-xs text-dark-400 whitespace-nowrap">{{ $msg->created_at->format('d M H:i') }}</td>
                    <td class="text-sm">{{ $msg->user?->name ?? '—' }}</td>
                    <td class="text-xs font-mono text-dark-400">{{ $msg->chat_id }}</td>
                    <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded">{{ $msg->type }}</span></td>
                    <td class="max-w-xs"><p class="text-xs text-dark-300 truncate">{{ \Str::limit($msg->content, 50) ?? '(media)' }}</p></td>
                    <td><span class="badge {{ in_array($msg->status,['processed','sent'])?'badge-success':($msg->status==='failed'?'badge-danger':'badge-pending') }}">{{ $msg->status }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-8 text-dark-400">Belum ada pesan Telegram</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name=csrf-token]').content;
const headers   = { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' };

function showResult(text, success = true) {
    const el = document.getElementById('bot-result');
    const txt = document.getElementById('bot-result-text');
    el.classList.remove('hidden');
    el.className = `mt-3 p-3 rounded-xl border ${success ? 'bg-green-900/20 border-green-500/30' : 'bg-red-900/20 border-red-500/30'}`;
    txt.textContent = text;
}

async function testBot() {
    try {
        const res = await axios.post('{{ route('admin.telegram.test-connection') }}', {}, { headers });
        showResult(res.data.message, res.data.success);
        showToast(res.data.message, res.data.success ? 'success' : 'error');
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

async function setWebhook() {
    try {
        const res = await axios.post('{{ route('admin.telegram.set-webhook') }}', {}, { headers });
        showResult(res.data.message, res.data.success);
        showToast(res.data.success ? 'Webhook berhasil diset!' : res.data.message, res.data.success ? 'success' : 'error');
        if (res.data.success) setTimeout(() => location.reload(), 2000);
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

async function deleteWebhook() {
    if (!confirm('Hapus webhook Telegram?')) return;
    try {
        const res = await axios.post('{{ route('admin.telegram.delete-webhook') }}', {}, { headers });
        showResult(res.data.message, res.data.success);
        showToast(res.data.message, res.data.success ? 'info' : 'error');
        if (res.data.success) setTimeout(() => location.reload(), 1500);
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

async function sendTest() {
    const chatId  = document.getElementById('test-chat-id').value;
    const message = document.getElementById('test-message').value;
    if (!chatId) { showToast('Isi Chat ID terlebih dahulu', 'warning'); return; }
    try {
        const res = await axios.post('{{ route('admin.telegram.send-test') }}', { chat_id: chatId, message }, { headers });
        showToast(res.data.message, res.data.success ? 'success' : 'error');
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}
</script>
@endpush
