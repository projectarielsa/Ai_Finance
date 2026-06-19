<?php $__env->startSection('title', 'Wallet'); ?>
<?php $__env->startSection('page-title', 'Wallet'); ?>
<?php $__env->startSection('page-subtitle', 'Kelola semua dompet digital dan rekening Anda'); ?>

<?php $__env->startSection('header-actions'); ?>
<a href="<?php echo e(route('wallets.create')); ?>" class="btn-secondary text-sm">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
    Tambah Wallet
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in">

    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-primary-500/15 flex items-center justify-center text-primary-400">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
            </div>
            <div>
                <p class="text-dark-400 text-xs">Total Saldo</p>
                <p class="text-white font-bold text-lg">Rp <?php echo e(number_format($wallets->where('include_in_total',true)->sum('balance'),0,',','.')); ?></p>
            </div>
        </div>
        <div class="glass-card p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-green-500/15 flex items-center justify-center text-green-400 text-xl">🏦</div>
            <div>
                <p class="text-dark-400 text-xs">Bank</p>
                <p class="text-white font-bold text-lg"><?php echo e($wallets->where('type','bank')->count()); ?> wallet</p>
            </div>
        </div>
        <div class="glass-card p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center text-purple-400 text-xl">📱</div>
            <div>
                <p class="text-dark-400 text-xs">E-Wallet</p>
                <p class="text-white font-bold text-lg"><?php echo e($wallets->where('type','e_wallet')->count()); ?> wallet</p>
            </div>
        </div>
    </div>

    
    <?php if($wallets->isEmpty()): ?>
    <div class="glass-card p-16 text-center">
        <div class="text-5xl mb-4">💳</div>
        <h3 class="text-white font-semibold text-lg mb-2">Belum ada wallet</h3>
        <p class="text-dark-400 text-sm mb-5">Tambahkan wallet pertama Anda untuk mulai mencatat keuangan</p>
        <a href="<?php echo e(route('wallets.create')); ?>" class="btn-primary">+ Tambah Wallet Pertama</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wallet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="glass-card-hover p-5 group" x-data="{ showMenu: false }">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold shadow-lg"
                         style="background: <?php echo e($wallet->color); ?>22; color: <?php echo e($wallet->color); ?>; border: 1px solid <?php echo e($wallet->color); ?>44">
                        <?php echo e(mb_substr($wallet->name, 0, 1)); ?>

                    </div>
                    <div>
                        <h3 class="text-white font-semibold"><?php echo e($wallet->name); ?></h3>
                        <span class="text-xs text-dark-400"><?php echo e(ucfirst(str_replace('_',' ',$wallet->type))); ?></span>
                    </div>
                </div>
                <div class="relative">
                    <button @click="showMenu=!showMenu" class="btn-icon opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                    </button>
                    <div x-show="showMenu" @click.away="showMenu=false" x-transition class="absolute right-0 top-8 w-40 glass-card py-1 z-10 shadow-2xl">
                        <a href="<?php echo e(route('wallets.show', $wallet)); ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-dark-200 hover:text-white hover:bg-dark-700/50">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Detail
                        </a>
                        <a href="<?php echo e(route('wallets.edit', $wallet)); ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-dark-200 hover:text-white hover:bg-dark-700/50">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                            Edit
                        </a>
                        <form action="<?php echo e(route('wallets.destroy', $wallet)); ?>" method="POST">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit" data-confirm="Yakin hapus wallet <?php echo e($wallet->name); ?>?" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 w-full">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <p class="text-dark-400 text-xs mb-1">Saldo saat ini</p>
                <p class="text-2xl font-bold text-white">Rp <?php echo e(number_format($wallet->balance,0,',','.')); ?></p>
                <?php if($wallet->account_number): ?>
                <p class="text-dark-500 text-xs mt-1"><?php echo e($wallet->account_number); ?></p>
                <?php endif; ?>
            </div>

            <div class="mt-4 pt-4 border-t border-dark-700/30 flex items-center justify-between">
                <div class="flex items-center gap-1">
                    <?php if($wallet->is_active): ?>
                    <span class="badge badge-success">Aktif</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Nonaktif</span>
                    <?php endif; ?>
                    <?php if(!$wallet->include_in_total): ?>
                    <span class="badge bg-dark-600/30 text-dark-400 border border-dark-600/30 text-[10px]">Dikecualikan</span>
                    <?php endif; ?>
                </div>
                <span class="text-dark-500 text-xs"><?php echo e($wallet->transactions_count); ?> transaksi</span>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/wallets/index.blade.php ENDPATH**/ ?>