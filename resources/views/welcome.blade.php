@extends('layouts.landing')
@section('title', 'Finance AI — Smart Personal Finance Assistant')

@section('content')
{{-- Hero Section --}}
<section class="pt-32 pb-20 md:pt-40 md:pb-32 relative overflow-hidden">
    {{-- Background decorations --}}
    <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="animate-fade-up inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-sm font-medium mb-8">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                Powered by AI
            </div>

            {{-- Headline --}}
            <h1 class="animate-fade-up delay-100 text-4xl md:text-6xl lg:text-7xl font-bold text-white leading-tight mb-6">
                Kelola Keuangan<br>
                <span class="gradient-text">Lebih Cerdas</span> dengan AI
            </h1>

            {{-- Subtitle --}}
            <p class="animate-fade-up delay-200 text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Catat transaksi cukup kirim pesan di Telegram. Scan struk otomatis. Dapatkan insight keuangan real-time. Semua dalam satu aplikasi.
            </p>

            {{-- CTA Buttons --}}
            <div class="animate-fade-up delay-300 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition-all shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-105">
                    Mulai Gratis
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="#how-it-works" class="inline-flex items-center px-8 py-4 rounded-2xl border border-slate-700 text-slate-300 font-semibold hover:border-slate-500 hover:text-white transition-all">
                    Lihat Cara Kerja
                </a>
            </div>

            {{-- Stats --}}
            <div class="animate-fade-up delay-400 mt-16 grid grid-cols-3 gap-8 max-w-lg mx-auto">
                <div>
                    <p class="text-2xl md:text-3xl font-bold text-white">100%</p>
                    <p class="text-slate-500 text-sm mt-1">Gratis Mulai</p>
                </div>
                <div>
                    <p class="text-2xl md:text-3xl font-bold text-white">AI</p>
                    <p class="text-slate-500 text-sm mt-1">Powered</p>
                </div>
                <div>
                    <p class="text-2xl md:text-3xl font-bold text-white">24/7</p>
                    <p class="text-slate-500 text-sm mt-1">Via Telegram</p>
                </div>
            </div>
        </div>

        {{-- Hero Mockup --}}
        <div class="mt-20 animate-float">
            <div class="glass-hero rounded-3xl p-6 md:p-8 max-w-4xl mx-auto">
                <div class="bg-slate-900 rounded-2xl p-4 md:p-6 border border-slate-800">
                    {{-- Mockup chat --}}
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-sm">👤</div>
                            <div class="bg-slate-800 rounded-2xl rounded-tl-none px-4 py-3 max-w-xs">
                                <p class="text-slate-200 text-sm">beli kopi 25rb gopay</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 flex-row-reverse">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-sm">🤖</div>
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl rounded-tr-none px-4 py-3 max-w-sm">
                                <p class="text-slate-200 text-sm">Tercatat! Pengeluaran Rp25.000 untuk Kopi dari GoPay.</p>
                                <p class="text-slate-400 text-xs mt-2">Saldo GoPay: Rp475.000</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-sm">👤</div>
                            <div class="bg-slate-800 rounded-2xl rounded-tl-none px-4 py-3 max-w-xs">
                                <p class="text-slate-200 text-sm">berapa pengeluaran bulan ini?</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 flex-row-reverse">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-sm">🤖</div>
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl rounded-tr-none px-4 py-3 max-w-sm">
                                <p class="text-slate-200 text-sm font-medium">Pengeluaran Mei 2026:</p>
                                <p class="text-slate-300 text-sm mt-1">Rp2.450.000 dari 47 transaksi</p>
                                <p class="text-green-400 text-xs mt-2">Masih dalam budget! Sisa Rp1.550.000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- Features Section --}}
<section id="features" class="py-20 md:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold text-white mb-4">Semua yang Kamu Butuhkan</h2>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto">Fitur lengkap untuk mengelola keuangan pribadi dengan bantuan AI</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Feature 1 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center text-2xl mb-4">💬</div>
                <h3 class="text-white font-semibold text-lg mb-2">Input via Telegram</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Ketik "beli makan 35rb cash" dan transaksi otomatis tercatat. Tidak perlu buka app.</p>
            </div>

            {{-- Feature 2 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-purple-500/15 flex items-center justify-center text-2xl mb-4">📸</div>
                <h3 class="text-white font-semibold text-lg mb-2">Scan Struk Otomatis</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Foto struk belanja, AI akan membaca dan mencatat semua item secara otomatis.</p>
            </div>

            {{-- Feature 3 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-green-500/15 flex items-center justify-center text-2xl mb-4">🎤</div>
                <h3 class="text-white font-semibold text-lg mb-2">Voice Note</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Rekam ucapan "gaji masuk 5 juta BCA" dan transaksi langsung tercatat.</p>
            </div>

            {{-- Feature 4 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-yellow-500/15 flex items-center justify-center text-2xl mb-4">📊</div>
                <h3 class="text-white font-semibold text-lg mb-2">Laporan & Insight AI</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Dapatkan rekapan bulanan, analisis pengeluaran, dan saran cerdas dari AI.</p>
            </div>

            {{-- Feature 5 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-red-500/15 flex items-center justify-center text-2xl mb-4">🎯</div>
                <h3 class="text-white font-semibold text-lg mb-2">Budget & Goals</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Atur budget per kategori dan target tabungan. Alert otomatis saat mendekati limit.</p>
            </div>

            {{-- Feature 6 --}}
            <div class="feature-card rounded-2xl p-6">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/15 flex items-center justify-center text-2xl mb-4">🔄</div>
                <h3 class="text-white font-semibold text-lg mb-2">Transaksi Berulang</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Cicilan, langganan, dan tagihan rutin otomatis tercatat setiap bulan.</p>
            </div>
        </div>
    </div>
</section>

{{-- How it Works --}}
<section id="how-it-works" class="py-20 md:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold text-white mb-4">Cara Kerja</h2>
            <p class="text-slate-400 text-lg">3 langkah mudah untuk mulai mengelola keuangan</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-500/5 border border-blue-500/20 flex items-center justify-center text-3xl mx-auto mb-5">1</div>
                <h3 class="text-white font-semibold text-lg mb-2">Daftar & Hubungkan</h3>
                <p class="text-slate-400 text-sm">Buat akun gratis, lalu hubungkan Telegram dengan perintah /link</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500/20 to-purple-500/5 border border-purple-500/20 flex items-center justify-center text-3xl mx-auto mb-5">2</div>
                <h3 class="text-white font-semibold text-lg mb-2">Kirim Transaksi</h3>
                <p class="text-slate-400 text-sm">Ketik, foto struk, atau voice note di Telegram. AI yang catat.</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500/20 to-green-500/5 border border-green-500/20 flex items-center justify-center text-3xl mx-auto mb-5">3</div>
                <h3 class="text-white font-semibold text-lg mb-2">Lihat Insight</h3>
                <p class="text-slate-400 text-sm">Dashboard lengkap + AI insight otomatis setiap minggu via Telegram.</p>
            </div>
        </div>
    </div>
</section>


{{-- Pricing Section --}}
<section id="pricing" class="py-20 md:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-5xl font-bold text-white mb-4">Pilih Paket Kamu</h2>
            <p class="text-slate-400 text-lg">Mulai gratis, upgrade kapan saja</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            {{-- Free Plan --}}
            <div class="pricing-card rounded-2xl p-8">
                <div class="mb-6">
                    <h3 class="text-white font-semibold text-lg">Free</h3>
                    <p class="text-slate-400 text-sm mt-1">Untuk mulai mencoba</p>
                </div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">Rp0</span>
                    <span class="text-slate-500 text-sm">/bulan</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        50 transaksi/bulan
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        2 wallet
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Input via Telegram
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        5 scan struk/bulan
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Voice note
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Export PDF/Excel
                    </li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl border border-slate-700 text-slate-300 font-semibold hover:border-slate-500 hover:text-white transition-all">
                    Mulai Gratis
                </a>
            </div>

            {{-- Pro Plan --}}
            <div class="pricing-card popular rounded-2xl p-8 relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs font-bold">
                    POPULER
                </div>
                <div class="mb-6">
                    <h3 class="text-white font-semibold text-lg">Premium</h3>
                    <p class="text-slate-400 text-sm mt-1">Untuk penggunaan penuh</p>
                </div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">Rp39rb</span>
                    <span class="text-slate-500 text-sm">/bulan</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Transaksi unlimited
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Wallet unlimited
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Scan struk unlimited
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Voice note unlimited
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        AI insight & chat unlimited
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Export PDF & Excel
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Weekly AI summary
                    </li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/25">
                    Mulai 7 Hari Trial
                </a>
            </div>

            {{-- Lifetime Plan --}}
            <div class="pricing-card rounded-2xl p-8">
                <div class="mb-6">
                    <h3 class="text-white font-semibold text-lg">Lifetime</h3>
                    <p class="text-slate-400 text-sm mt-1">Bayar sekali, selamanya</p>
                </div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">Rp599rb</span>
                    <span class="text-slate-500 text-sm">sekali bayar</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Semua fitur Premium
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Akses selamanya
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update fitur gratis
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Priority support
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-300">
                        <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Early access fitur baru
                    </li>
                </ul>
                <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl border border-slate-700 text-slate-300 font-semibold hover:border-slate-500 hover:text-white transition-all">
                    Beli Lifetime
                </a>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="glass-hero rounded-3xl p-10 md:p-16">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Siap Kelola Keuangan Lebih Cerdas?</h2>
            <p class="text-slate-400 text-lg mb-8 max-w-xl mx-auto">Daftar sekarang dan mulai catat keuangan hanya lewat chat Telegram.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition-all shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-105">
                Daftar Gratis Sekarang
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</section>
@endsection
