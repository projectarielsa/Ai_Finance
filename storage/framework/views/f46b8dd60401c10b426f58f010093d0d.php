<?php $__env->startSection('title', 'Manage Users'); ?>
<?php $__env->startSection('page-title', 'Manage Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-5 animate-fade-in">
    <div class="glass-card p-4">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" class="input-field py-2 text-sm flex-1" placeholder="Cari nama, email, nomor...">
            <button type="submit" class="btn-primary py-2 text-sm">Cari</button>
            <?php if(request('search')): ?><a href="<?php echo e(route('admin.users.index')); ?>" class="btn-secondary py-2 text-sm">Reset</a><?php endif; ?>
        </form>
    </div>

    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead><tr><th>User</th><th>Phone</th><th>Role</th><th>Status</th><th>Bergabung</th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="<?php echo e($u->avatar_url); ?>" class="w-8 h-8 rounded-full object-cover">
                            <div>
                                <p class="text-white text-sm font-medium"><?php echo e($u->name); ?></p>
                                <p class="text-dark-400 text-xs"><?php echo e($u->email); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-dark-400 text-sm"><?php echo e($u->phone ?? '—'); ?></td>
                    <td><span class="badge <?php echo e($u->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success'); ?>"><?php echo e($u->role); ?></span></td>
                    <td><span class="badge <?php echo e($u->is_active?'badge-success':'badge-danger'); ?>"><?php echo e($u->is_active?'Aktif':'Nonaktif'); ?></span></td>
                    <td class="text-dark-400 text-xs"><?php echo e($u->created_at->format('d M Y')); ?></td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="<?php echo e(route('admin.users.show',$u)); ?>" class="btn-icon p-1.5 text-xs">👁</a>
                            <form action="<?php echo e(route('admin.users.toggle-active',$u)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-icon p-1.5 text-xs" title="<?php echo e($u->is_active?'Nonaktifkan':'Aktifkan'); ?>"><?php echo e($u->is_active?'🔒':'🔓'); ?></button>
                            </form>
                            <form action="<?php echo e(route('admin.users.toggle-role',$u)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-icon p-1.5 text-xs" title="Toggle Role"><?php echo e($u->role==='admin'?'👤':'👑'); ?></button>
                            </form>
                            <?php if($u->trashed()): ?>
                                <form action="<?php echo e(route('admin.users.restore',$u->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-icon p-1.5 text-xs" title="Pulihkan User">♻️</button>
                                </form>
                                <form action="<?php echo e(route('admin.users.force-delete',$u->id)); ?>" method="POST" onsubmit="return confirm('HAPUS PERMANEN user <?php echo e($u->name); ?>? Semua data akan hilang dan tidak bisa dikembalikan!')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-icon p-1.5 text-xs text-red-400" title="Hapus Permanen">💀</button>
                                </form>
                            <?php elseif($u->id !== auth()->id()): ?>
                                <form action="<?php echo e(route('admin.users.destroy',$u)); ?>" method="POST" onsubmit="return confirm('Hapus user <?php echo e($u->name); ?>?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-icon p-1.5 text-xs text-red-400" title="Hapus User">🗑️</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="text-center py-8 text-dark-400">Tidak ada user</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-dark-700/30"><?php echo e($users->links()); ?></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/admin/users/index.blade.php ENDPATH**/ ?>