<?php $__env->startSection('title', 'Edit Wallet'); ?>
<?php $__env->startSection('page-title', 'Edit Wallet'); ?>
<?php $__env->startSection('page-subtitle', $wallet->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <?php if($errors->any()): ?>
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('wallets.update', $wallet)); ?>" class="space-y-5">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Wallet *</label>
                    <input type="text" name="name" value="<?php echo e(old('name', $wallet->name)); ?>" class="input-field" required>
                </div>
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <?php $__currentLoopData = ['bank','e_wallet','cash','investment','credit_card','other']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($t); ?>" <?php echo e($wallet->type==$t?'selected':''); ?>><?php echo e(ucfirst(str_replace('_',' ',$t))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Provider</label>
                    <input type="text" name="provider" value="<?php echo e(old('provider', $wallet->provider)); ?>" class="input-field">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="<?php echo e(old('color', $wallet->color)); ?>" class="w-11 h-11 rounded-lg border border-dark-600 bg-dark-800 cursor-pointer p-1">
                        <span class="text-dark-400 text-sm">Pilih warna</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="input-label">Nomor Rekening</label>
                    <input type="text" name="account_number" value="<?php echo e(old('account_number', $wallet->account_number)); ?>" class="input-field">
                </div>
                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <textarea name="description" rows="2" class="input-field"><?php echo e(old('description', $wallet->description)); ?></textarea>
                </div>
                <div class="col-span-2 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="include_in_total" value="1" <?php echo e($wallet->include_in_total?'checked':''); ?> class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span class="text-sm text-dark-200">Hitung dalam total saldo</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?php echo e($wallet->is_active?'checked':''); ?> class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span class="text-sm text-dark-200">Wallet aktif</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="<?php echo e(route('wallets.index')); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/wallets/edit.blade.php ENDPATH**/ ?>