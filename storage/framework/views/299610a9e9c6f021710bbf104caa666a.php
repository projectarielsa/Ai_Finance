<?php $__env->startSection('title', 'Tambah Wallet'); ?>
<?php $__env->startSection('page-title', 'Tambah Wallet'); ?>
<?php $__env->startSection('page-subtitle', 'Tambahkan rekening atau dompet digital baru'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <?php if($errors->any()): ?>
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('wallets.store')); ?>" class="space-y-5">
            <?php echo csrf_field(); ?>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Wallet *</label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" class="input-field" placeholder="Contoh: BCA, Gopay, Cash" required>
                </div>

                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <option value="bank" <?php echo e(old('type')=='bank'?'selected':''); ?>>Bank</option>
                        <option value="e_wallet" <?php echo e(old('type')=='e_wallet'?'selected':''); ?>>E-Wallet</option>
                        <option value="cash" <?php echo e(old('type')=='cash'?'selected':''); ?>>Cash</option>
                        <option value="investment" <?php echo e(old('type')=='investment'?'selected':''); ?>>Investasi</option>
                        <option value="credit_card" <?php echo e(old('type')=='credit_card'?'selected':''); ?>>Kartu Kredit</option>
                        <option value="other" <?php echo e(old('type')=='other'?'selected':''); ?>>Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="input-label">Provider</label>
                    <input type="text" name="provider" value="<?php echo e(old('provider')); ?>" class="input-field" placeholder="BCA, BRI, Gopay, dll">
                </div>

                <div class="form-group">
                    <label class="input-label">Saldo Awal (Rp)</label>
                    <input type="number" name="initial_balance" value="<?php echo e(old('initial_balance', 0)); ?>" class="input-field currency-input" placeholder="0" min="0">
                </div>

                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color" value="<?php echo e(old('color','#3b82f6')); ?>" class="w-11 h-11 rounded-lg border border-dark-600 bg-dark-800 cursor-pointer p-1">
                        <span class="text-dark-400 text-sm">Pilih warna wallet</span>
                    </div>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Nomor Rekening</label>
                    <input type="text" name="account_number" value="<?php echo e(old('account_number')); ?>" class="input-field" placeholder="Optional">
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <textarea name="description" rows="2" class="input-field" placeholder="Catatan tambahan (opsional)"><?php echo e(old('description')); ?></textarea>
                </div>

                <div class="col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="include_in_total" value="1" <?php echo e(old('include_in_total',1) ? 'checked' : ''); ?> class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <div>
                            <span class="text-sm text-dark-200">Hitung dalam total saldo</span>
                            <p class="text-xs text-dark-500">Jika dicentang, saldo wallet ini akan dihitung dalam total keseluruhan</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Wallet</button>
                <a href="<?php echo e(route('wallets.index')); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/wallets/create.blade.php ENDPATH**/ ?>