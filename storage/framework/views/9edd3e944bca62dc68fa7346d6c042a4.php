<?php $__env->startSection('title', 'Tambah Transaksi'); ?>
<?php $__env->startSection('page-title', 'Tambah Transaksi'); ?>
<?php $__env->startSection('page-subtitle', 'Catat transaksi keuangan secara manual'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto animate-fade-in" x-data="{ type: '<?php echo e(old('type','expense')); ?>' }">
    <div class="glass-card p-6">
        <?php if($errors->any()): ?>
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('transactions.store')); ?>" enctype="multipart/form-data" class="space-y-5">
            <?php echo csrf_field(); ?>

            
            <div class="form-group">
                <label class="input-label">Jenis Transaksi *</label>
                <div class="grid grid-cols-3 gap-2">
                    <?php $__currentLoopData = ['expense'=>['Pengeluaran','text-red-400','bg-red-500/15 border-red-500/40'],'income'=>['Pemasukan','text-green-400','bg-green-500/15 border-green-500/40'],'transfer'=>['Transfer','text-blue-400','bg-blue-500/15 border-blue-500/40']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val=>[$label,$color,$activeCls]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="<?php echo e($val); ?>" x-model="type" class="sr-only">
                        <div :class="type === '<?php echo e($val); ?>' ? '<?php echo e($activeCls); ?> border' : 'bg-dark-700/30 border border-dark-600/30'"
                             class="p-3 rounded-xl text-center transition-all duration-200">
                            <p class="<?php echo e($color); ?> font-semibold text-sm"><?php echo e($label); ?></p>
                        </div>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Jumlah (Rp) *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 font-medium">Rp</span>
                        <input type="number" name="amount" value="<?php echo e(old('amount')); ?>" class="input-field pl-10" placeholder="0" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">Wallet *</label>
                    <select name="wallet_id" class="input-field" required>
                        <option value="">Pilih Wallet</option>
                        <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($w->id); ?>" <?php echo e(old('wallet_id')==$w->id?'selected':''); ?>><?php echo e($w->name); ?> (Rp<?php echo e(number_format($w->balance,0,',','.')); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-group" x-show="type === 'transfer'" x-transition>
                    <label class="input-label">Wallet Tujuan *</label>
                    <select name="target_wallet_id" class="input-field">
                        <option value="">Pilih Wallet Tujuan</option>
                        <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($w->id); ?>" <?php echo e(old('target_wallet_id')==$w->id?'selected':''); ?>><?php echo e($w->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-group" x-show="type !== 'transfer'" x-transition>
                    <label class="input-label">Kategori</label>
                    <select name="category_id" class="input-field">
                        <option value="">Pilih Kategori</option>
                        <?php $__currentLoopData = $categories->where('type','expense'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($c->type !== 'transfer'): ?>
                        <option value="<?php echo e($c->id); ?>" <?php echo e(old('category_id')==$c->id?'selected':''); ?>><?php echo e($c->name); ?></option>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Deskripsi</label>
                    <input type="text" name="description" value="<?php echo e(old('description')); ?>" class="input-field" placeholder="Contoh: Makan siang, Bayar listrik...">
                </div>

                <div class="form-group">
                    <label class="input-label">Merchant / Toko</label>
                    <input type="text" name="merchant" value="<?php echo e(old('merchant')); ?>" class="input-field" placeholder="Nama toko/merchant">
                </div>

                <div class="form-group">
                    <label class="input-label">Tanggal Transaksi *</label>
                    <input type="datetime-local" name="transaction_date" value="<?php echo e(old('transaction_date', now()->format('Y-m-d\TH:i'))); ?>" class="input-field" required>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Catatan</label>
                    <textarea name="notes" rows="2" class="input-field" placeholder="Catatan tambahan (opsional)"><?php echo e(old('notes')); ?></textarea>
                </div>

                <div class="form-group col-span-2">
                    <label class="input-label">Lampiran Struk</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="input-field py-2.5 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary-500/20 file:text-primary-300 hover:file:bg-primary-500/30">
                    <p class="text-xs text-dark-500 mt-1">Format: JPG, PNG, PDF. Maks 5MB</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Transaksi</button>
                <a href="<?php echo e(route('transactions.index')); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/transactions/create.blade.php ENDPATH**/ ?>