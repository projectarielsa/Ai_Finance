<?php $__env->startSection('title', 'Budget'); ?>
<?php $__env->startSection('page-title', 'Budget'); ?>
<?php $__env->startSection('page-subtitle', 'Kelola batas pengeluaran per kategori'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in">

    
    <div class="glass-card p-4 flex flex-wrap items-center gap-3">
        <form method="GET" action="<?php echo e(route('budgets.index')); ?>" class="flex items-center gap-2">
            <select name="month" class="input-field text-sm py-2 w-36">
                <?php for($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo e($m); ?>" <?php echo e($m == $month ? 'selected' : ''); ?>>
                        <?php echo e(\Carbon\Carbon::createFromDate(null, $m, 1)->format('F')); ?>

                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" class="input-field text-sm py-2 w-24">
                <?php for($y = now()->year - 1; $y <= now()->year + 1; $y++): ?>
                    <option value="<?php echo e($y); ?>" <?php echo e($y == $year ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn-secondary text-sm">Lihat</button>
        </form>
        <p class="text-dark-400 text-sm ml-auto"><?php echo e($budgets->count()); ?> budget aktif</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <div class="xl:col-span-2 space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $pct   = $b->percentage;
                $color = $pct >= 100 ? 'red' : ($pct >= 80 ? 'yellow' : 'green');
                $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-green-500');
            ?>
            <div class="glass-card p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-white font-semibold"><?php echo e($b->category?->name ?? 'Semua Kategori'); ?></p>
                        <p class="text-dark-400 text-xs mt-0.5">
                            Rp<?php echo e(number_format($b->spent,0,',','.')); ?> / Rp<?php echo e(number_format($b->limit_amount,0,',','.')); ?>

                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-<?php echo e($color); ?>-400 font-bold text-lg"><?php echo e($pct); ?>%</span>
                        <form action="<?php echo e(route('budgets.destroy', $b)); ?>" method="POST">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button class="btn-icon text-dark-500 hover:text-red-400" title="Hapus">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="h-2.5 bg-dark-700 rounded-full overflow-hidden">
                    <div class="<?php echo e($barColor); ?> h-full rounded-full transition-all duration-500"
                         style="width: <?php echo e(min(100, $pct)); ?>%"></div>
                </div>

                <div class="flex items-center justify-between mt-2 text-xs">
                    <span class="text-dark-500">Sisa: <span class="text-<?php echo e($color); ?>-400 font-medium">Rp<?php echo e(number_format($b->remaining,0,',','.')); ?></span></span>
                    <?php if($pct >= 100): ?>
                        <span class="text-red-400 font-medium">🚨 Terlampaui!</span>
                    <?php elseif($pct >= 80): ?>
                        <span class="text-yellow-400 font-medium">⚠️ Mendekati batas</span>
                    <?php endif; ?>
                </div>

                
                <details class="mt-3">
                    <summary class="text-xs text-dark-400 cursor-pointer hover:text-dark-200">Edit budget</summary>
                    <form action="<?php echo e(route('budgets.update', $b)); ?>" method="POST" class="mt-2 flex gap-2 flex-wrap">
                        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                        <input type="number" name="limit_amount" value="<?php echo e($b->limit_amount); ?>" class="input-field text-sm py-1.5 w-40" min="1000" step="10000">
                        <input type="text" name="notes" value="<?php echo e($b->notes); ?>" class="input-field text-sm py-1.5 flex-1" placeholder="Catatan...">
                        <button type="submit" class="btn-primary text-xs">Simpan</button>
                    </form>
                </details>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="glass-card p-8 text-center">
                <p class="text-4xl mb-3">📊</p>
                <p class="text-white font-medium">Belum ada budget</p>
                <p class="text-dark-400 text-sm mt-1">Tambah budget di sebelah kanan untuk mulai memantau pengeluaran</p>
            </div>
            <?php endif; ?>
        </div>

        
        <div class="glass-card p-6 h-fit">
            <h3 class="text-white font-semibold mb-4">+ Tambah Budget</h3>
            <?php if($errors->any()): ?>
                <div class="mb-3 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo e(route('budgets.store')); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="input-label">Kategori</label>
                    <select name="category_id" class="input-field" required>
                        <option value="">Pilih kategori...</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Batas Pengeluaran (Rp)</label>
                    <input type="number" name="limit_amount" class="input-field" min="1000" step="10000" placeholder="500000" required>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="form-group">
                        <label class="input-label">Bulan</label>
                        <select name="month" class="input-field">
                            <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?php echo e($m); ?>" <?php echo e($m==$month?'selected':''); ?>>
                                    <?php echo e(\Carbon\Carbon::createFromDate(null,$m,1)->format('M')); ?>

                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">Tahun</label>
                        <select name="year" class="input-field">
                            <?php for($y=now()->year;$y<=now()->year+1;$y++): ?>
                                <option value="<?php echo e($y); ?>" <?php echo e($y==$year?'selected':''); ?>><?php echo e($y); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="input-label">Catatan (opsional)</label>
                    <input type="text" name="notes" class="input-field" placeholder="Contoh: batas makan siang">
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-dark-200 cursor-pointer">
                        <input type="checkbox" name="alert_at_80" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span>Notif Telegram di 80%</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-dark-200 cursor-pointer">
                        <input type="checkbox" name="alert_at_100" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                        <span>Notif Telegram di 100%</span>
                    </label>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Simpan Budget</button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/budgets/index.blade.php ENDPATH**/ ?>