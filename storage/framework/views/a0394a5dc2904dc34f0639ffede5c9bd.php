<?php $__env->startSection('title', 'Edit ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang')); ?>
<?php $__env->startSection('page-title', 'Edit ' . ($debt->type === 'receivable' ? 'Piutang' : 'Hutang')); ?>
<?php $__env->startSection('page-subtitle', $debt->contact_name); ?>

<?php $__env->startSection('header-actions'); ?>
<a href="<?php echo e(route('debts.show', $debt)); ?>" class="btn-secondary text-sm">← Kembali</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl animate-fade-in">
    <div class="glass-card p-6">
        <?php if($errors->any()): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p>• <?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo e(route('debts.update', $debt)); ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

            <div class="form-group">
                <label class="input-label">Nama *</label>
                <input type="text" name="contact_name" value="<?php echo e(old('contact_name', $debt->contact_name)); ?>" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">No. HP</label>
                <input type="text" name="contact_phone" value="<?php echo e(old('contact_phone', $debt->contact_phone)); ?>" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Nominal (Rp) *</label>
                <input type="number" name="amount" value="<?php echo e(old('amount', $debt->amount)); ?>" class="input-field" min="<?php echo e($debt->paid_amount); ?>" step="1000" required>
                <?php if($debt->paid_amount > 0): ?>
                <p class="text-dark-500 text-xs mt-1">Minimal Rp <?php echo e(number_format($debt->paid_amount,0,',','.')); ?> (sudah terbayar)</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="input-label">Status</label>
                <select name="status" class="input-field">
                    <option value="active"    <?php echo e($debt->status==='active'    ? 'selected' : ''); ?>>Aktif</option>
                    <option value="partial"   <?php echo e($debt->status==='partial'   ? 'selected' : ''); ?>>Bayar Sebagian</option>
                    <option value="paid"      <?php echo e($debt->status==='paid'      ? 'selected' : ''); ?>>Lunas</option>
                    <option value="cancelled" <?php echo e($debt->status==='cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Tanggal</label>
                <input type="date" name="debt_date" value="<?php echo e(old('debt_date', $debt->debt_date->toDateString())); ?>" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">Jatuh Tempo</label>
                <input type="date" name="due_date" value="<?php echo e(old('due_date', $debt->due_date?->toDateString())); ?>" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Terkait</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Tidak ada</option>
                    <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($w->id); ?>" <?php echo e((old('wallet_id', $debt->wallet_id)==$w->id)?'selected':''); ?>><?php echo e($w->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Keterangan</label>
                <input type="text" name="description" value="<?php echo e(old('description', $debt->description)); ?>" class="input-field">
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Catatan</label>
                <textarea name="notes" class="input-field" rows="2"><?php echo e(old('notes', $debt->notes)); ?></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_due" value="1" <?php echo e($debt->notify_on_due ? 'checked' : ''); ?> class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram saat mendekati jatuh tempo</span>
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="<?php echo e(route('debts.show', $debt)); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/debts/edit.blade.php ENDPATH**/ ?>