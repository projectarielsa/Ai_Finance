<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Auth'); ?> — Finance AI</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background: radial-gradient(ellipse at 30% 20%, rgba(59,130,246,0.12) 0%, transparent 50%), radial-gradient(ellipse at 70% 80%, rgba(139,92,246,0.08) 0%, transparent 50%), #020617;">

<div class="w-full max-w-md">
    
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 text-3xl shadow-xl shadow-primary-900/40 mb-4">💰</div>
        <h1 class="text-2xl font-bold text-white">Finance AI</h1>
        <p class="text-dark-400 text-sm mt-1">Smart Personal Finance Assistant</p>
    </div>

    <div class="glass-card p-8">
        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <p class="text-center text-dark-500 text-xs mt-6">
        © <?php echo e(date('Y')); ?> Finance AI. All rights reserved.
    </p>
</div>
</body>
</html>
<?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/layouts/auth.blade.php ENDPATH**/ ?>