<?php $__env->startSection('title', 'Lupa Password'); ?>
<?php $__env->startSection('content'); ?>
<h2 class="text-xl font-bold text-white mb-1">Lupa Password</h2>
<p class="text-dark-400 text-sm mb-6">Masukkan email Anda untuk reset password.</p>

<?php if(session('status')): ?>
<div class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('password.email')); ?>" class="space-y-4">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label class="input-label">Email</label>
        <input type="email" name="email" class="input-field" placeholder="email@example.com" required>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-3">Kirim Link Reset</button>
</form>
<p class="text-center text-dark-400 text-sm mt-4"><a href="<?php echo e(route('login')); ?>" class="text-primary-400 hover:text-primary-300">← Kembali ke Login</a></p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>