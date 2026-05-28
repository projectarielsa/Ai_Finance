<?php $__env->startSection('title', 'Tujuan Keuangan'); ?>
<?php $__env->startSection('page-title', 'Tujuan Keuangan'); ?>
<?php $__env->startSection('page-subtitle', 'Tetapkan target dan pantau progress tabungan Anda'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in" x-data="{ showAddForm: false }">

    
    <div class="flex justify-end">
        <button @click="showAddForm = !showAddForm" class="btn-primary text-sm">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Tujuan
        </button>
    </div>

    
    <div x-show="showAddForm" x-transition class="glass-card p-6">
        <h3 class="text-white font-semibold mb-4">Tujuan Baru</h3>
        <?php if($errors->any()): ?>
        <div class="mb-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
        <form action="<?php echo e(route('goals.store')); ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php echo csrf_field(); ?>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Nama Tujuan *</label>
                <input type="text" name="title" value="<?php echo e(old('title')); ?>" class="input-field" placeholder="Beli Motor, Liburan Eropa, Dana Darurat..." required>
            </div>
            <div class="form-group">
                <label class="input-label">Target Nominal (Rp) *</label>
                <input type="number" name="target_amount" value="<?php echo e(old('target_amount')); ?>" class="input-field" min="1000" step="100000" required>
            </div>
            <div class="form-group">
                <label class="input-label">Dana Awal (Rp)</label>
                <input type="number" name="current_amount" value="<?php echo e(old('current_amount', 0)); ?>" class="input-field" min="0" step="1000">
            </div>
            <div class="form-group">
                <label class="input-label">Target Tanggal</label>
                <input type="date" name="target_date" value="<?php echo e(old('target_date')); ?>" class="input-field" min="<?php echo e(now()->addDay()->toDateString()); ?>">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Tabungan (opsional)</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Tidak terkait wallet</option>
                    <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($w->id); ?>"><?php echo e($w->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">Ikon</label>
                <input type="text" name="icon" value="<?php echo e(old('icon', '🎯')); ?>" class="input-field text-2xl" maxlength="10">
            </div>
            <div class="form-group">
                <label class="input-label">Warna</label>
                <input type="color" name="color" value="<?php echo e(old('color', '#3b82f6')); ?>" class="h-10 w-full rounded-xl bg-dark-800 border border-dark-600/50 cursor-pointer">
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Deskripsi (opsional)</label>
                <input type="text" name="description" value="<?php echo e(old('description')); ?>" class="input-field" placeholder="Motivasi atau detail...">
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_milestone" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram setiap 25% tercapai</span>
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="btn-primary">Buat Tujuan</button>
                <button type="button" @click="showAddForm=false" class="btn-secondary">Batal</button>
            </div>
        </form>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $goals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $goal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $pct      = $goal->percentage;
        $barColor = $goal->status === 'completed' ? 'bg-green-500' :
                    ($pct >= 75 ? 'bg-blue-500' : ($pct >= 50 ? 'bg-primary-500' : 'bg-dark-500'));
    ?>
    <div class="glass-card p-6 <?php echo e($goal->status === 'completed' ? 'ring-1 ring-green-500/30' : ''); ?>">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl"
                     style="background: <?php echo e($goal->color); ?>20; border: 1px solid <?php echo e($goal->color); ?>40">
                    <?php echo e($goal->icon); ?>

                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-white font-semibold"><?php echo e($goal->title); ?></h3>
                        <?php if($goal->status === 'completed'): ?>
                            <span class="badge badge-success text-xs">✅ Tercapai!</span>
                        <?php elseif($goal->status === 'paused'): ?>
                            <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">⏸ Dijeda</span>
                        <?php endif; ?>
                    </div>
                    <?php if($goal->target_date): ?>
                    <p class="text-dark-400 text-xs mt-0.5">
                        Target: <?php echo e($goal->target_date->format('d M Y')); ?>

                        <?php if($goal->status === 'active'): ?>
                            <?php $daysLeft = $goal->days_remaining; ?>
                            <?php if($daysLeft !== null): ?>
                                · <span class="<?php echo e($daysLeft < 30 ? 'text-yellow-400' : 'text-dark-400'); ?>">
                                    <?php echo e($daysLeft > 0 ? $daysLeft.' hari lagi' : 'Sudah terlewat'); ?>

                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('goals.edit', $goal)); ?>" class="btn-secondary text-xs py-1.5 px-3">Edit</a>
                <form action="<?php echo e(route('goals.destroy', $goal)); ?>" method="POST"
                      onsubmit="return confirm('Hapus tujuan ini?')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button class="btn-icon text-dark-500 hover:text-red-400">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </form>
            </div>
        </div>

        
        <div class="mb-3">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-white font-bold text-lg">Rp<?php echo e(number_format($goal->current_amount,0,',','.')); ?></span>
                <span class="text-dark-400 text-sm">dari Rp<?php echo e(number_format($goal->target_amount,0,',','.')); ?></span>
            </div>
            <div class="h-3 bg-dark-700 rounded-full overflow-hidden">
                <div class="<?php echo e($barColor); ?> h-full rounded-full transition-all duration-700"
                     style="width: <?php echo e($pct); ?>%"></div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-dark-400 text-xs"><?php echo e($pct); ?>% tercapai</span>
                <span class="text-dark-400 text-xs">Sisa: Rp<?php echo e(number_format($goal->remaining,0,',','.')); ?></span>
            </div>
        </div>

        
        <?php if($goal->status === 'active'): ?>
        <details class="mt-4">
            <summary class="text-sm text-primary-400 cursor-pointer hover:text-primary-300">+ Tambah Dana</summary>
            <form action="<?php echo e(route('goals.add-funds', $goal)); ?>" method="POST" class="mt-3 flex gap-2 flex-wrap">
                <?php echo csrf_field(); ?>
                <input type="number" name="amount" class="input-field text-sm py-2 w-40" min="1000" step="10000" placeholder="Nominal (Rp)">
                <select name="wallet_id" class="input-field text-sm py-2 flex-1">
                    <option value="">Tanpa debit wallet</option>
                    <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($w->id); ?>" <?php echo e($goal->wallet_id==$w->id?'selected':''); ?>>
                        <?php echo e($w->name); ?> (Rp<?php echo e(number_format($w->balance,0,',','.')); ?>)
                    </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit" class="btn-primary text-sm py-2 px-4">Simpan</button>
            </form>
        </details>
        <?php endif; ?>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="glass-card p-12 text-center">
        <p class="text-5xl mb-4">🎯</p>
        <p class="text-white font-semibold text-lg">Belum ada tujuan keuangan</p>
        <p class="text-dark-400 mt-1 mb-5">Mulai tetapkan target — beli rumah, liburan, dana darurat</p>
        <button @click="showAddForm=true; $nextTick(() => window.scrollTo({top: 0, behavior: 'smooth'}))"
                class="btn-primary">Buat Tujuan Pertama</button>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/goals/index.blade.php ENDPATH**/ ?>