<?php $__env->startSection('title', 'Finance AI — Smart Personal Finance Assistant'); ?>

<?php $__env->startSection('content'); ?>

<section class="pt-28 pb-16 md:pt-36 md:pb-24 relative overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[600px] bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-purple-500/5 rounded-full blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center max-w-4xl mx-auto">
            <div class="animate-fade-up inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wide mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                AI-Powered Finance Manager
            </div>

            <h1 class="animate-fade-up delay-100 text-4xl md:text-6xl lg:text-7xl font-extrabold text-white leading-[1.1] mb-6 tracking-tight">
                Catat Keuangan<br>
                Semudah <span class="gradient-text">Chat</span>
            </h1>

            <p class="animate-fade-up delay-200 text-base md:text-lg text-slate-400 max-w-xl mx-auto mb-10 leading-relaxed">
                Kirim pesan ke Telegram, foto struk, atau voice note. AI mencatat semuanya otomatis. Tanpa ribet, tanpa lupa.
            </p>

            <div class="animate-fade-up delay-300 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?php echo e(route('register')); ?>" class="group inline-flex items-center px-7 py-3.5 rounded-2xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-base hover:from-blue-600 hover:to-purple-700 transition-all shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.03]">
                    Mulai Gratis Sekarang
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="https://t.me/<?php echo e(config('services.telegram.bot_username', 'FinanceAIBot')); ?>" target="_blank" class="inline-flex items-center px-7 py-3.5 rounded-2xl border border-slate-700/60 text-slate-300 font-semibold text-base hover:border-blue-500/50 hover:text-white hover:bg-blue-500/10 transition-all">
                    <svg class="w-5 h-5 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Buka Bot Telegram
                </a>
            </div>
        </div>

        
        <div class="mt-16 md:mt-20 animate-fade-up delay-400">
            <div class="glass-card-landing rounded-3xl p-5 md:p-8 max-w-3xl mx-auto glow-blue">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-700/30">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <img src="<?php echo e(asset('img/logo.svg')); ?>" class="w-6 h-6">
                    </div>
                    <div>
                        <p class="text-white text-sm font-semibold">Finance AI Bot</p>
                        <p class="text-green-400 text-xs flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-400"></span> Online</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-end gap-2">
                        <div class="bg-slate-800/80 rounded-2xl rounded-bl-md px-4 py-2.5 max-w-[70%]">
                            <p class="text-slate-200 text-sm">beli kopi starbucks 55rb gopay</p>
                            <p class="text-slate-500 text-[10px] mt-1 text-right">09:32</p>
                        </div>
                    </div>
                    <div class="flex items-end gap-2 justify-end">
                        <div class="bg-blue-600/20 border border-blue-500/20 rounded-2xl rounded-br-md px-4 py-2.5 max-w-[75%]">
                            <p class="text-slate-100 text-sm">Tercatat! Pengeluaran <strong>Rp55.000</strong></p>
                            <p class="text-slate-300 text-xs mt-1">Kategori: Makanan & Minuman</p>
                            <p class="text-slate-300 text-xs">Wallet: GoPay (sisa Rp445.000)</p>
                            <p class="text-slate-500 text-[10px] mt-1.5 text-right">09:32</p>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="bg-slate-800/80 rounded-2xl rounded-bl-md px-4 py-2.5 max-w-[70%]">
                            <p class="text-slate-200 text-sm">bulan ini udah habis berapa ya?</p>
                            <p class="text-slate-500 text-[10px] mt-1 text-right">09:33</p>
                        </div>
                    </div>
                    <div class="flex items-end gap-2 justify-end">
                        <div class="bg-blue-600/20 border border-blue-500/20 rounded-2xl rounded-br-md px-4 py-2.5 max-w-[75%]">
                            <p class="text-slate-100 text-sm font-medium">Pengeluaran Mei 2026:</p>
                            <p class="text-slate-200 text-sm mt-1">Rp2.450.000 dari 47 transaksi</p>
                            <p class="text-emerald-400 text-xs mt-1.5">Masih dalam budget! Sisa Rp1.550.000</p>
                            <p class="text-slate-500 text-[10px] mt-1.5 text-right">09:33</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="mt-14 animate-fade-up delay-500">
            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-14">
                <div class="text-center">
                    <p class="text-2xl md:text-3xl font-bold text-white">100%</p>
                    <p class="text-slate-500 text-xs mt-1">Gratis untuk mulai</p>
                </div>
                <div class="w-px h-8 bg-slate-800 hidden sm:block"></div>
                <div class="text-center">
                    <p class="text-2xl md:text-3xl font-bold text-white">3 detik</p>
                    <p class="text-slate-500 text-xs mt-1">Catat transaksi</p>
                </div>
                <div class="w-px h-8 bg-slate-800 hidden sm:block"></div>
                <div class="text-center">
                    <p class="text-2xl md:text-3xl font-bold text-white">24/7</p>
                    <p class="text-slate-500 text-xs mt-1">Bot selalu aktif</p>
                </div>
                <div class="w-px h-8 bg-slate-800 hidden sm:block"></div>
                <div class="text-center">
                    <p class="text-2xl md:text-3xl font-bold text-white">AI</p>
                    <p class="text-slate-500 text-xs mt-1">Smart insights</p>
                </div>
            </div>
        </div>
    </div>
</section>


<section id="features" class="py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="text-blue-400 text-sm font-semibold uppercase tracking-wide mb-3">Fitur Lengkap</p>
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Semua yang Kamu Butuhkan</h2>
            <p class="text-slate-400 text-base max-w-lg mx-auto">Satu platform untuk semua kebutuhan pencatatan keuangan pribadi</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-500/5 border border-blue-500/20 flex items-center justify-center text-xl mb-5">💬</div>
                <h3 class="text-white font-semibold text-base mb-2">Input Natural via Telegram</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Ketik bebas seperti "makan siang 35rb cash" — AI mengerti dan langsung catat.</p>
            </div>
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-500/5 border border-purple-500/20 flex items-center justify-center text-xl mb-5">📸</div>
                <h3 class="text-white font-semibold text-base mb-2">Scan Struk Otomatis</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Foto struk/nota belanja, AI membaca semua item dan total secara otomatis.</p>
            </div>
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-green-500/20 to-green-500/5 border border-green-500/20 flex items-center justify-center text-xl mb-5">🎤</div>
                <h3 class="text-white font-semibold text-base mb-2">Voice Note Transcription</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Rekam suara "bayar listrik 350ribu BCA" — AI transkripsi dan catat otomatis.</p>
            </div>
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500/20 to-amber-500/5 border border-amber-500/20 flex items-center justify-center text-xl mb-5">📊</div>
                <h3 class="text-white font-semibold text-base mb-2">AI Insight & Laporan</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Rekapan otomatis, analisis tren pengeluaran, dan saran penghematan dari AI.</p>
            </div>
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-500/20 to-rose-500/5 border border-rose-500/20 flex items-center justify-center text-xl mb-5">🎯</div>
                <h3 class="text-white font-semibold text-base mb-2">Budget & Target Tabungan</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Set budget per kategori dan goals tabungan. Alert otomatis saat mendekati limit.</p>
            </div>
            <div class="feature-card rounded-2xl p-7">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-500/20 to-cyan-500/5 border border-cyan-500/20 flex items-center justify-center text-xl mb-5">🔒</div>
                <h3 class="text-white font-semibold text-base mb-2">Aman & Private</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Data keuangan terenkripsi. 2FA via Telegram. Hanya kamu yang bisa akses.</p>
            </div>
        </div>
    </div>
</section>


<section id="how-it-works" class="py-20 md:py-28 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-blue-950/5 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center mb-14">
            <p class="text-purple-400 text-sm font-semibold uppercase tracking-wide mb-3">Cara Kerja</p>
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Mulai dalam 3 Langkah</h2>
            <p class="text-slate-400 text-base">Tidak perlu setup rumit. Langsung pakai.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            <div class="text-center p-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-5 shadow-lg shadow-blue-500/30">1</div>
                <h3 class="text-white font-semibold text-lg mb-2">Daftar Gratis</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Buat akun dalam 30 detik. Verifikasi email, selesai.</p>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-5 shadow-lg shadow-purple-500/30">2</div>
                <h3 class="text-white font-semibold text-lg mb-2">Hubungkan Telegram</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-3">Kirim <code class="text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded text-xs">/link email@kamu.com</code> ke bot.</p>
                <a href="https://t.me/<?php echo e(config('services.telegram.bot_username', 'FinanceAIBot')); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Buka {{ config('services.telegram.bot_username', 'FinanceAIBot') }}
                </a>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-5 shadow-lg shadow-emerald-500/30">3</div>
                <h3 class="text-white font-semibold text-lg mb-2">Langsung Pakai!</h3>
                <p class="text-slate-400 text-sm leading-relaxed">Ketik transaksi, foto struk, atau kirim voice note. Done.</p>
            </div>
        </div>
    </div>
</section>


<section id="testimonials" class="py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="text-emerald-400 text-sm font-semibold uppercase tracking-wide mb-3">Testimoni</p>
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Yang Mereka Bilang</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 max-w-5xl mx-auto">
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    <span class="text-amber-400 text-sm">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                </div>
                <p class="text-slate-300 text-sm leading-relaxed mb-5">"Dulu males banget catat pengeluaran. Sekarang tinggal chat ke bot, done. Akhirnya tau uang habis kemana."</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold">R</div>
                    <div>
                        <p class="text-white text-sm font-medium">Rina</p>
                        <p class="text-slate-500 text-xs">Freelancer</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    <span class="text-amber-400 text-sm">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                </div>
                <p class="text-slate-300 text-sm leading-relaxed mb-5">"Fitur scan struk ini gila sih. Foto nota langsung masuk semua itemnya. Hemat waktu banget."</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center text-white text-xs font-bold">A</div>
                    <div>
                        <p class="text-white text-sm font-medium">Andi</p>
                        <p class="text-slate-500 text-xs">Karyawan Swasta</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-center gap-1 mb-4">
                    <span class="text-amber-400 text-sm">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                </div>
                <p class="text-slate-300 text-sm leading-relaxed mb-5">"Voice note-nya keren. Lagi nyetir tinggal rekam 'bayar tol 15rb' udah langsung kecatat. Mantap."</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-rose-500 flex items-center justify-center text-white text-xs font-bold">D</div>
                    <div>
                        <p class="text-white text-sm font-medium">Dimas</p>
                        <p class="text-slate-500 text-xs">Mahasiswa</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="py-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="glass-card-landing rounded-3xl p-10 md:p-14 text-center glow-purple relative overflow-hidden">
            <div class="absolute -top-20 -right-20 w-60 h-60 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="relative">
                <h2 class="text-2xl md:text-4xl font-extrabold text-white mb-4 tracking-tight">Siap Mulai?</h2>
                <p class="text-slate-400 text-base mb-8 max-w-md mx-auto">Daftar gratis, hubungkan Telegram, dan mulai catat keuangan tanpa ribet.</p>
                <a href="<?php echo e(route('register')); ?>" class="group inline-flex items-center px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-lg hover:from-blue-600 hover:to-purple-700 transition-all shadow-xl shadow-blue-500/25 hover:shadow-blue-500/40 hover:scale-[1.03]">
                    Daftar Gratis
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <p class="text-slate-500 text-xs mt-5">Tidak perlu kartu kredit. Setup dalam 30 detik.</p>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.landing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/welcome.blade.php ENDPATH**/ ?>