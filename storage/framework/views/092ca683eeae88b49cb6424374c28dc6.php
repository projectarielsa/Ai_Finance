<?php $__env->startSection('title', 'Edit Transaksi'); ?>
<?php $__env->startSection('page-title', 'Edit Transaksi'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <div class="mb-5 p-4 rounded-xl bg-dark-700/30 border border-dark-600/30">
            <p class="text-dark-400 text-xs mb-1">Transaksi (tidak bisa diubah)</p>
            <p class="text-white font-bold text-xl">
                <?php echo e($transaction->type==='income'?'+':'-'); ?>Rp <?php echo e(number_format($transaction->amount,0,',','.')); ?>

            </p>
            <div class="flex items-center gap-2 mt-1">
                <span class="badge badge-<?php echo e($transaction->type); ?>"><?php echo e(ucfirst($transaction->type)); ?></span>
                <span class="text-dark-400 text-xs"><?php echo e($transaction->wallet->name); ?></span>
            </div>
        </div>

        <form method="POST" action="<?php echo e(route('transactions.update',$transaction)); ?>" class="space-y-4">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

            <div class="form-group">
                <label class="input-label">Deskripsi</label>
                <input type="text" name="description" value="<?php echo e(old('description',$transaction->description)); ?>" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Merchant</label>
                <input type="text" name="merchant" value="<?php echo e(old('merchant',$transaction->merchant)); ?>" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Kategori</label>
                <select name="category_id" class="input-field">
                    <option value="">Pilih Kategori</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($c->id); ?>" <?php echo e($transaction->category_id==$c->id?'selected':''); ?>><?php echo e($c->name); ?> (<?php echo e($c->type); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="form-group">
                <label class="input-label">Tanggal Transaksi</label>
                <input type="datetime-local" name="transaction_date" value="<?php echo e(old('transaction_date', $transaction->transaction_date->format('Y-m-d\TH:i'))); ?>" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Catatan</label>
                <textarea name="notes" rows="2" class="input-field"><?php echo e(old('notes',$transaction->notes)); ?></textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="<?php echo e(route('transactions.show',$transaction)); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/transactions/edit.blade.php ENDPATH**/ ?>