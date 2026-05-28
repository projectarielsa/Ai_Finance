<?php $__env->startSection('title', 'Daftar'); ?>

<?php $__env->startSection('content'); ?>
<h2 class="text-xl font-bold text-white mb-1">Buat Akun Baru</h2>
<p class="text-dark-400 text-sm mb-6">Mulai kelola keuangan Anda dengan AI</p>

<?php if(session('error')): ?>
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
    <?php echo e(session('error')); ?>

</div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('register')); ?>" class="space-y-4">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label class="input-label">Nama Lengkap</label>
        <input type="text" name="name" value="<?php echo e(old('name')); ?>" class="input-field" placeholder="John Doe" required autofocus>
    </div>
    <div class="form-group">
        <label class="input-label">Email</label>
        <input type="email" name="email" value="<?php echo e(old('email')); ?>" class="input-field" placeholder="email@example.com" required>
    </div>
    <div class="form-group">
        <label class="input-label">Nomor Telepon <span class="text-dark-500">(opsional)</span></label>
        <input type="text" name="phone" value="<?php echo e(old('phone')); ?>" class="input-field" placeholder="08123456789">
    </div>
    <div class="form-group">
        <label class="input-label">Password</label>
        <input type="password" name="password" class="input-field" placeholder="Minimal 8 karakter" required>
    </div>
    <div class="form-group">
        <label class="input-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="input-field" placeholder="Ulangi password" required>
    </div>
    <button type="submit" class="btn-primary w-full justify-center py-3">Daftar Sekarang</button>
</form>

<p class="text-center text-dark-400 text-sm mt-5">
    Sudah punya akun? <a href="<?php echo e(route('login')); ?>" class="text-primary-400 hover:text-primary-300 font-medium">Masuk</a>
</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/auth/register.blade.php ENDPATH**/ ?>