<?php $__env->startSection('title', 'Admin Dashboard'); ?>
<?php $__env->startSection('page-title', 'Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Users</p>
            <p class="text-3xl font-bold text-white mt-1"><?php echo e($totalUsers); ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">Total Transaksi</p>
            <p class="text-3xl font-bold text-white mt-1"><?php echo e(number_format($totalTransactions)); ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">AI Requests</p>
            <p class="text-3xl font-bold text-white mt-1"><?php echo e(number_format($totalAiRequests)); ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs">AI Success Rate</p>
            <p class="text-3xl font-bold <?php echo e($aiSuccessRate>=80?'text-green-400':($aiSuccessRate>=60?'text-yellow-400':'text-red-400')); ?> mt-1"><?php echo e($aiSuccessRate); ?>%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Recent Users</h3></div>
            <table class="data-table">
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $recentUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($u->name); ?></td>
                        <td class="text-dark-400"><?php echo e($u->email); ?></td>
                        <td><span class="badge <?php echo e($u->role==='admin'?'bg-red-500/15 text-red-400 border-red-500/25':'badge-success'); ?>"><?php echo e($u->role); ?></span></td>
                        <td class="text-dark-400 text-xs"><?php echo e($u->created_at->diffForHumans()); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-700/30"><h3 class="text-white font-semibold">Recent AI Requests</h3></div>
            <table class="data-table">
                <thead><tr><th>Type</th><th>User</th><th>Status</th><th>Waktu</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $recentAiLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded"><?php echo e($log->type); ?></span></td>
                        <td class="text-dark-400 text-xs"><?php echo e($log->user?->name ?? 'System'); ?></td>
                        <td><span class="badge <?php echo e($log->success?'badge-success':'badge-danger'); ?>"><?php echo e($log->success?'OK':'FAIL'); ?></span></td>
                        <td class="text-dark-500 text-xs"><?php echo e($log->created_at->diffForHumans()); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>