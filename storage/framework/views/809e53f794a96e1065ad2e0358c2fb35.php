<?php $__env->startSection('title', $user->name); ?>
<?php $__env->startSection('page-title', $user->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl mx-auto space-y-5 animate-fade-in">
    <div class="glass-card p-6">
        <div class="flex items-start gap-5">
            <img src="<?php echo e($user->avatar_url); ?>" class="w-16 h-16 rounded-2xl object-cover">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-white"><?php echo e($user->name); ?></h2>
                <p class="text-dark-400"><?php echo e($user->email); ?></p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="badge <?php echo e($user->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success'); ?>"><?php echo e($user->role); ?></span>
                    <span class="badge <?php echo e($user->is_active?'badge-success':'badge-danger'); ?>"><?php echo e($user->is_active?'Aktif':'Nonaktif'); ?></span>
                </div>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-dark-700/30">
            <div><dt class="text-dark-400 text-xs">Phone</dt><dd class="text-white text-sm"><?php echo e($user->phone ?? '—'); ?></dd></div>
            <div><dt class="text-dark-400 text-xs">Total Saldo</dt><dd class="text-white text-sm">Rp <?php echo e(number_format($user->total_balance,0,',','.')); ?></dd></div>
            <div><dt class="text-dark-400 text-xs">Bergabung</dt><dd class="text-white text-sm"><?php echo e($user->created_at->format('d M Y')); ?></dd></div>
            <div><dt class="text-dark-400 text-xs">WA Notifikasi</dt><dd class="text-white text-sm"><?php echo e($user->whatsapp_notifications?'Aktif':'Nonaktif'); ?></dd></div>
        </dl>
    </div>

    
    <div class="glass-card p-5">
        <h3 class="text-white font-semibold mb-3">Wallets (<?php echo e($user->wallets->count()); ?>)</h3>
        <div class="grid grid-cols-2 gap-2">
            <?php $__currentLoopData = $user->wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="p-3 rounded-xl bg-dark-700/30 flex items-center justify-between">
                <span class="text-sm text-dark-200"><?php echo e($w->name); ?></span>
                <span class="text-sm font-medium text-white">Rp<?php echo e(number_format($w->balance,0,',','.')); ?></span>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <div class="glass-card overflow-hidden">
        <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Transaksi Terbaru</h3></div>
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Tipe</th><th class="text-right">Jumlah</th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $user->transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="text-xs text-dark-400"><?php echo e($tx->transaction_date->format('d M Y')); ?></td>
                    <td><?php echo e($tx->description ?? '—'); ?></td>
                    <td><span class="badge badge-<?php echo e($tx->type); ?>"><?php echo e(ucfirst($tx->type)); ?></span></td>
                    <td class="text-right text-sm <?php echo e($tx->type==='income'?'text-green-400':'text-red-400'); ?>">Rp<?php echo e(number_format($tx->amount,0,',','.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn-secondary inline-flex">← Kembali</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/admin/users/show.blade.php ENDPATH**/ ?>