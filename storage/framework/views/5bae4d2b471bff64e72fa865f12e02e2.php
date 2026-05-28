<?php $__env->startSection('title', 'Transaksi Berulang'); ?>
<?php $__env->startSection('page-title', 'Transaksi Berulang'); ?>
<?php $__env->startSection('page-subtitle', 'Otomatiskan transaksi yang terjadi rutin'); ?>

<?php $__env->startSection('header-actions'); ?>
<a href="<?php echo e(route('recurring.create')); ?>" class="btn-secondary text-sm">+ Tambah</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4 animate-fade-in">
    <?php $__empty_1 = true; $__currentLoopData = $recurring; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $isDue    = $r->isDue();
        $typeIcon = match($r->type) { 'income' => '💰', 'expense' => '💸', default => '🔄' };
        $typeColor= match($r->type) { 'income' => 'text-green-400', 'expense' => 'text-red-400', default => 'text-blue-400' };
    ?>
    <div class="glass-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-3 flex-1">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl
                <?php echo e($r->is_active ? 'bg-dark-700/50' : 'bg-dark-800/50 opacity-50'); ?>">
                <?php echo e($typeIcon); ?>

            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-white font-semibold truncate"><?php echo e($r->title); ?></p>
                    <?php if(!$r->is_active): ?>
                        <span class="badge badge-danger text-xs">Nonaktif</span>
                    <?php endif; ?>
                    <?php if($isDue): ?>
                        <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">Jatuh Tempo</span>
                    <?php endif; ?>
                </div>
                <p class="text-dark-400 text-sm">
                    <?php echo e($r->frequency_label); ?> · <?php echo e($r->wallet->name); ?>

                    <?php if($r->category): ?> · <?php echo e($r->category->name); ?><?php endif; ?>
                </p>
                <p class="text-dark-500 text-xs mt-0.5">
                    Berikutnya: <?php echo e($r->next_run_date->format('d M Y')); ?>

                    <?php if($r->end_date): ?> · Berakhir: <?php echo e($r->end_date->format('d M Y')); ?><?php endif; ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-shrink-0">
            <p class="<?php echo e($typeColor); ?> font-bold text-lg">Rp<?php echo e(number_format($r->amount,0,',','.')); ?></p>

            <?php if($isDue && $r->is_active): ?>
            <form action="<?php echo e(route('recurring.execute', $r)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button class="btn-primary text-xs py-1.5 px-3">▶ Jalankan</button>
            </form>
            <?php endif; ?>

            <a href="<?php echo e(route('recurring.edit', $r)); ?>" class="btn-secondary text-xs py-1.5 px-3">Edit</a>

            <form action="<?php echo e(route('recurring.destroy', $r)); ?>" method="POST"
                  onsubmit="return confirm('Hapus transaksi berulang ini?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-icon text-dark-500 hover:text-red-400">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="glass-card p-12 text-center">
        <p class="text-4xl mb-4">🔄</p>
        <p class="text-white font-semibold">Belum ada transaksi berulang</p>
        <p class="text-dark-400 text-sm mt-1 mb-4">Otomatiskan gaji, tagihan, langganan, cicilan</p>
        <a href="<?php echo e(route('recurring.create')); ?>" class="btn-primary">Tambah Sekarang</a>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/recurring/index.blade.php ENDPATH**/ ?>