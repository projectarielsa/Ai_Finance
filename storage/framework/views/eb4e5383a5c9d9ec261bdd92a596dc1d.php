<?php $__env->startSection('title', 'Tambah Kategori'); ?>
<?php $__env->startSection('page-title', 'Tambah Kategori'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <?php if($errors->any()): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
        <form action="<?php echo e(route('categories.store')); ?>" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group col-span-2">
                    <label class="input-label">Nama Kategori *</label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" class="input-field" placeholder="Makan Siang, Netflix, Gaji..." required autofocus>
                </div>
                <div class="form-group">
                    <label class="input-label">Tipe *</label>
                    <select name="type" class="input-field" required>
                        <option value="expense" <?php echo e(old('type')=='expense'?'selected':''); ?>>💸 Pengeluaran</option>
                        <option value="income"  <?php echo e(old('type')=='income'?'selected':''); ?>>💰 Pemasukan</option>
                        <option value="transfer"<?php echo e(old('type')=='transfer'?'selected':''); ?>>🔄 Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Ikon Emoji</label>
                    <input type="text" name="icon" value="<?php echo e(old('icon')); ?>" class="input-field text-2xl" placeholder="🍔" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="input-label">Warna</label>
                    <input type="color" name="color" value="<?php echo e(old('color', '#3b82f6')); ?>" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
                </div>
                <div class="form-group">
                    <label class="input-label">Deskripsi</label>
                    <input type="text" name="description" value="<?php echo e(old('description')); ?>" class="input-field" placeholder="Opsional...">
                </div>
                <div class="form-group col-span-2">
                    <label class="input-label">Kata Kunci AI (pisah koma)</label>
                    <input type="text" name="ai_keywords" value="<?php echo e(old('ai_keywords')); ?>" class="input-field" placeholder="makan, restoran, warteg, grab food, gofood">
                    <p class="text-dark-500 text-xs mt-1">AI akan otomatis mencocokkan transaksi ke kategori ini</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Kategori</button>
                <a href="<?php echo e(route('categories.index')); ?>" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/categories/create.blade.php ENDPATH**/ ?>