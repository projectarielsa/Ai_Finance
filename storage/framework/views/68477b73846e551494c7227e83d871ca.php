<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Admin'); ?> — Finance AI Admin</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen" x-data="{ sidebarOpen: false }">

<?php if(session('success')): ?><div data-flash-success="<?php echo e(session('success')); ?>" class="hidden"></div><?php endif; ?>
<?php if(session('error')): ?><div data-flash-error="<?php echo e(session('error')); ?>" class="hidden"></div><?php endif; ?>

<div class="flex h-screen overflow-hidden">
    <aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-dark-900/95 backdrop-blur-xl border-r border-dark-700/40 transform transition-transform duration-300 lg:relative lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <div class="flex items-center gap-3 px-6 py-5 border-b border-dark-700/40">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center text-lg">⚙️</div>
            <div>
                <span class="text-white font-bold text-sm">Admin Panel</span>
                <p class="text-dark-400 text-xs">Finance AI</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">🏠 <span>Dashboard</span></a>
            <a href="<?php echo e(route('admin.users.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.users.*') ? 'active' : ''); ?>">👥 <span>Users</span></a>

            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-1">Konfigurasi AI</p>
            <a href="<?php echo e(route('admin.api-credentials.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.api-credentials.*') ? 'active' : ''); ?>">🔑 <span>API Credentials</span></a>

            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-1">Telegram Bot</p>
            <a href="<?php echo e(route('admin.telegram.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.telegram.*') ? 'active' : ''); ?>">🤖 <span>Telegram Bot</span></a>

            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-1">Pengaturan</p>
            <a href="<?php echo e(route('admin.settings.index')); ?>" class="sidebar-link <?php echo e(request()->routeIs('admin.settings.*') ? 'active' : ''); ?>">⚙️ <span>App Settings</span></a>

            <p class="px-3 pt-4 text-[10px] font-semibold uppercase tracking-widest text-dark-500 mb-1">Logs</p>
            <a href="<?php echo e(route('admin.ai-logs')); ?>"  class="sidebar-link <?php echo e(request()->routeIs('admin.ai-logs') ? 'active' : ''); ?>">🤖 <span>AI Logs</span></a>
            <a href="<?php echo e(route('admin.tg-logs')); ?>"  class="sidebar-link <?php echo e(request()->routeIs('admin.tg-logs') ? 'active' : ''); ?>">💬 <span>Telegram Logs</span></a>
        </nav>

        <div class="px-4 py-4 border-t border-dark-700/30 space-y-1">
            <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link text-primary-400">← <span>Kembali ke App</span></a>
        </div>
    </aside>

    <div class="fixed inset-0 z-40 bg-black/60 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen=false" x-transition></div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between px-6 py-4 border-b border-dark-700/30 bg-dark-900/80 backdrop-blur-md">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden btn-icon">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <h1 class="text-white font-semibold text-lg"><?php echo $__env->yieldContent('page-title', 'Admin'); ?></h1>
            </div>
            <span class="badge bg-red-500/15 text-red-400 border border-red-500/25">Admin</span>
        </header>
        <main class="flex-1 overflow-y-auto p-6"><?php echo $__env->yieldContent('content'); ?></main>
    </div>
</div>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/layouts/admin.blade.php ENDPATH**/ ?>