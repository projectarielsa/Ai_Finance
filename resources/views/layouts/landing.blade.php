<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Finance AI — Smart Personal Finance Assistant')</title>
    <meta name="description" content="Kelola keuangan pribadi dengan AI. Catat transaksi via Telegram, scan struk otomatis, dan dapatkan insight keuangan cerdas.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-hero {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }
        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(71, 85, 105, 0.3);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.1);
        }
        .pricing-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(71, 85, 105, 0.3);
            transition: all 0.3s ease;
        }
        .pricing-card.popular {
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.15);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-fade-up {
            animation: fadeUp 0.8s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
    </style>
</head>
<body class="min-h-screen" style="background: radial-gradient(ellipse at 30% 20%, rgba(59,130,246,0.08) 0%, transparent 50%), radial-gradient(ellipse at 70% 80%, rgba(139,92,246,0.06) 0%, transparent 50%), #020617;">

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('img/logo.svg') }}" alt="Finance AI" class="w-10 h-10">
                    <span class="text-xl font-bold text-white">Finance AI</span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-slate-300 hover:text-white text-sm font-medium transition-colors">Fitur</a>
                    <a href="#how-it-works" class="text-slate-300 hover:text-white text-sm font-medium transition-colors">Cara Kerja</a>
                    <a href="#pricing" class="text-slate-300 hover:text-white text-sm font-medium transition-colors">Harga</a>
                </div>

                {{-- CTA Buttons --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white text-sm font-medium transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white text-sm font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/25">
                        Mulai Gratis
                    </a>
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    {{-- Footer --}}
    <footer class="border-t border-slate-800/50 py-12 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('img/logo.svg') }}" alt="Finance AI" class="w-8 h-8">
                        <span class="text-lg font-bold text-white">Finance AI</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-md">
                        Asisten keuangan pribadi berbasis AI. Catat, analisis, dan kelola keuangan Anda dengan mudah melalui Telegram.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Produk</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-slate-400 hover:text-white text-sm transition-colors">Fitur</a></li>
                        <li><a href="#pricing" class="text-slate-400 hover:text-white text-sm transition-colors">Harga</a></li>
                        <li><a href="#how-it-works" class="text-slate-400 hover:text-white text-sm transition-colors">Cara Kerja</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4">Akun</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('login') }}" class="text-slate-400 hover:text-white text-sm transition-colors">Masuk</a></li>
                        <li><a href="{{ route('register') }}" class="text-slate-400 hover:text-white text-sm transition-colors">Daftar</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800/50 mt-8 pt-8 text-center">
                <p class="text-slate-500 text-xs">&copy; {{ date('Y') }} Finance AI. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('bg-slate-900/80', 'backdrop-blur-xl', 'border-b', 'border-slate-800/50');
            } else {
                navbar.classList.remove('bg-slate-900/80', 'backdrop-blur-xl', 'border-b', 'border-slate-800/50');
            }
        });
    </script>
</body>
</html>
