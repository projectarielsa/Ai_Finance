<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Finance AI') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💰</text></svg>">

    {{-- PWA Meta Tags --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Finance AI">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <meta name="msapplication-TileImage" content="/icons/icon-144.svg">
    <meta name="msapplication-TileColor" content="#3b82f6">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }">

{{-- Flash data attributes for JS toasts --}}
@if(session('success'))<div data-flash-success="{{ session('success') }}" class="hidden"></div>@endif
@if(session('error'))<div data-flash-error="{{ session('error') }}" class="hidden"></div>@endif

<div class="flex h-screen overflow-hidden">
    {{-- ── SIDEBAR ──────────────────────────────────── --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-dark-900/95 backdrop-blur-xl border-r border-dark-700/40 transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-dark-700/40">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-lg shadow-lg shadow-primary-900/40">💰</div>
            <div>
                <span class="text-white font-bold text-sm leading-none">Finance AI</span>
                <p class="text-dark-400 text-xs mt-0.5">Smart Finance</p>
            </div>
        </div>

        {{-- User info --}}
        <div class="px-4 py-4 border-b border-dark-700/30">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-dark-800/50">
                <img src="{{ auth()->user()->avatar_url }}" alt="avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-primary-500/30">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-dark-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <p class="px-3 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-2">Menu Utama</p>

            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('wallets.index') }}" class="sidebar-link {{ request()->routeIs('wallets.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18-3a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3m18-3v3M3 9h18"/></svg>
                <span>Wallet</span>
            </a>

            <a href="{{ route('transactions.index') }}" class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                <span>Transaksi</span>
            </a>

            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                <span>Laporan</span>
            </a>

            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-2">Perencanaan</p>

            <a href="{{ route('budgets.index') }}" class="sidebar-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Budget</span>
            </a>

            <a href="{{ route('goals.index') }}" class="sidebar-link {{ request()->routeIs('goals.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                <span>Tujuan Keuangan</span>
            </a>

            <a href="{{ route('recurring.index') }}" class="sidebar-link {{ request()->routeIs('recurring.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                <span>Transaksi Berulang</span>
            </a>

            <a href="{{ route('debts.index') }}" class="sidebar-link {{ request()->routeIs('debts.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25H9m6 3H9m3 6-3-3h1.5a3 3 0 100-6M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Hutang & Piutang</span>
            </a>

            <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                <span>Kategori</span>
            </a>

            @if(auth()->user()->isAdmin())
            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-2">Admin</p>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                <span>Admin Panel</span>
            </a>
            @endif
        </nav>

        {{-- Bottom --}}
        <div class="px-4 py-4 border-t border-dark-700/30 space-y-1">
            <a href="https://t.me/{{ config('services.telegram.bot_username', 'FinanceAIBot') }}" target="_blank" class="sidebar-link text-blue-400 hover:text-blue-300 hover:bg-blue-500/10">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                <span>Buka Bot Telegram</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Profil</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link w-full text-red-400 hover:text-red-300 hover:bg-red-500/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Sidebar overlay (mobile) --}}
    <div class="fixed inset-0 z-40 bg-black/60 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen=false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

{{-- PWA Install Banner --}}
<div id="pwa-install-banner"
     class="hidden fixed bottom-0 left-0 right-0 z-50 p-4 lg:bottom-6 lg:left-auto lg:right-6 lg:max-w-sm">
    <div class="bg-dark-800 border border-primary-500/30 rounded-2xl p-4 shadow-2xl shadow-black/50 backdrop-blur-xl">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-xl flex-shrink-0">💰</div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-semibold text-sm">Pasang Finance AI</p>
                <p class="text-dark-400 text-xs mt-0.5">Install ke layar utama HP untuk akses lebih cepat tanpa buka browser</p>
                <div class="flex gap-2 mt-3">
                    <button id="pwa-install-btn"
                            class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold py-2 px-3 rounded-lg transition-colors">
                        Install Sekarang
                    </button>
                    <button id="pwa-dismiss-btn"
                            class="text-dark-400 hover:text-white text-xs py-2 px-3 rounded-lg hover:bg-dark-700/50 transition-colors">
                        Nanti
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    {{-- ── MAIN CONTENT ─────────────────────────────── --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- Top bar --}}
        <header class="flex items-center justify-between px-6 py-4 border-b border-dark-700/30 bg-dark-900/80 backdrop-blur-md">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden btn-icon">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <div>
                    <h1 class="text-white font-semibold text-lg leading-none">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('page-subtitle')<p class="text-dark-400 text-sm mt-0.5">@yield('page-subtitle')</p>@endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
                <a href="{{ route('transactions.create') }}" class="btn-primary text-sm">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Transaksi
                </a>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')

{{-- PWA: Service Worker Registration & Install Prompt --}}
<script>
// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.warn('SW registration failed:', err));
    });
}

// PWA Install Prompt
let deferredPrompt = null;
const installBanner = document.getElementById('pwa-install-banner');
const installBtn    = document.getElementById('pwa-install-btn');
const dismissBtn    = document.getElementById('pwa-dismiss-btn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Show banner if not dismissed before
    if (!localStorage.getItem('pwa-dismissed') && installBanner) {
        installBanner.classList.remove('hidden');
        setTimeout(() => installBanner.classList.add('pwa-banner-show'), 100);
    }
});

installBtn?.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    installBanner?.classList.add('hidden');
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('PWA install outcome:', outcome);
    deferredPrompt = null;
});

dismissBtn?.addEventListener('click', () => {
    installBanner?.classList.add('hidden');
    localStorage.setItem('pwa-dismissed', '1');
});

window.addEventListener('appinstalled', () => {
    installBanner?.classList.add('hidden');
    deferredPrompt = null;
    console.log('PWA installed!');
});
</script>
</body>
</html>
