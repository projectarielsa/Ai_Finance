<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Finance AI — Smart Personal Finance Assistant'); ?></title>
    <meta name="description" content="Kelola keuangan pribadi dengan AI. Catat transaksi via Telegram, scan struk otomatis, dan dapatkan insight keuangan cerdas.">
    <meta property="og:title" content="Finance AI — Kelola Keuangan Lebih Cerdas dengan AI">
    <meta property="og:description" content="Catat transaksi cukup kirim pesan di Telegram. Scan struk otomatis. Insight keuangan real-time.">
    <meta property="og:type" content="website">
    <link rel="icon" href="<?php echo e(asset('img/logo.svg')); ?>" type="image/svg+xml">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-card-landing {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(59, 130, 246, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .feature-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(71, 85, 105, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feature-card:hover {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateY(-6px);
            box-shadow: 0 24px 48px rgba(59, 130, 246, 0.12);
        }
        .testimonial-card {
            background: rgba(30, 41, 59, 0.3);
            border: 1px solid rgba(71, 85, 105, 0.2);
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            border-color: rgba(139, 92, 246, 0.3);
        }
        .glow-blue { box-shadow: 0 0 60px rgba(59, 130, 246, 0.15); }
        .glow-purple { box-shadow: 0 0 60px rgba(139, 92, 246, 0.12); }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-slow { animation: float 8s ease-in-out infinite; animation-delay: 1s; }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .animate-fade-up { animation: fadeUp 0.8s ease-out forwards; opacity: 0; }
        .animate-fade-in { animation: fadeIn 1s ease-out forwards; opacity: 0; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .step-connector {
            position: relative;
        }
        .step-connector::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -16px;
            width: 32px;
            height: 2px;
            background: linear-gradient(90deg, rgba(59,130,246,0.4), rgba(139,92,246,0.4));
        }
        @media (max-width: 768px) {
            .step-connector::after { display: none; }
        }
    </style>
</head>
<body class="min-h-screen antialiased" style="background: radial-gradient(ellipse at 20% 10%, rgba(59,130,246,0.07) 0%, transparent 50%), radial-gradient(ellipse at 80% 90%, rgba(139,92,246,0.05) 0%, transparent 50%), radial-gradient(ellipse at 50% 50%, rgba(6,182,212,0.03) 0%, transparent 40%), #020617;">

    
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-500" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18 md:h-20">
                
                <a href="/" class="flex items-center gap-3 group">
                    <img src="<?php echo e(asset('img/logo.svg')); ?>" alt="Finance AI" class="w-9 h-9 group-hover:scale-110 transition-transform">
                    <span class="text-lg font-bold text-white tracking-tight">Finance<span class="text-blue-400">AI</span></span>
                </a>

                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-slate-400 hover:text-white text-sm font-medium transition-colors duration-200">Fitur</a>
                    <a href="#how-it-works" class="text-slate-400 hover:text-white text-sm font-medium transition-colors duration-200">Cara Kerja</a>
                    <a href="#testimonials" class="text-slate-400 hover:text-white text-sm font-medium transition-colors duration-200">Testimoni</a>
                </div>

                
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(route('login')); ?>" class="hidden sm:inline-flex text-slate-300 hover:text-white text-sm font-medium transition-colors">Masuk</a>
                    <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white text-sm font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 hover:scale-105">
                        Mulai Gratis
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php echo $__env->yieldContent('content'); ?>

    
    <footer class="border-t border-slate-800/40 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-5">
                        <img src="<?php echo e(asset('img/logo.svg')); ?>" alt="Finance AI" class="w-9 h-9">
                        <span class="text-lg font-bold text-white">Finance<span class="text-blue-400">AI</span></span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-sm">
                        Asisten keuangan pribadi berbasis AI. Catat, analisis, dan kelola keuangan Anda dengan mudah cukup lewat Telegram.
                    </p>
                    <div class="flex items-center gap-4 mt-6">
                        <a href="https://t.me/<?php echo e(config('services.telegram.bot_username', 'FinanceAIBot')); ?>" target="_blank" class="w-9 h-9 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 hover:text-blue-400 hover:border-blue-500/30 transition-all">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Produk</h4>
                    <ul class="space-y-3">
                        <li><a href="#features" class="text-slate-400 hover:text-white text-sm transition-colors">Fitur</a></li>
                        <li><a href="#how-it-works" class="text-slate-400 hover:text-white text-sm transition-colors">Cara Kerja</a></li>
                        <li><a href="#testimonials" class="text-slate-400 hover:text-white text-sm transition-colors">Testimoni</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Akun</h4>
                    <ul class="space-y-3">
                        <li><a href="<?php echo e(route('login')); ?>" class="text-slate-400 hover:text-white text-sm transition-colors">Masuk</a></li>
                        <li><a href="<?php echo e(route('register')); ?>" class="text-slate-400 hover:text-white text-sm transition-colors">Daftar Gratis</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800/40 mt-10 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-slate-500 text-xs">&copy; <?php echo e(date('Y')); ?> Finance AI. All rights reserved.</p>
                <p class="text-slate-600 text-xs">Made with AI in Indonesia</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 30) {
                navbar.style.background = 'rgba(2, 6, 23, 0.85)';
                navbar.style.backdropFilter = 'blur(20px)';
                navbar.style.borderBottom = '1px solid rgba(51, 65, 85, 0.3)';
            } else {
                navbar.style.background = 'transparent';
                navbar.style.backdropFilter = 'none';
                navbar.style.borderBottom = 'none';
            }
        });

        // Scroll reveal animation
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    </script>
</body>
</html>
<?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/layouts/landing.blade.php ENDPATH**/ ?>