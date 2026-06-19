<?php $__env->startSection('title', 'AI Logs'); ?>
<?php $__env->startSection('page-title', 'AI Request Logs'); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card overflow-hidden animate-fade-in">
    <table class="data-table">
        <thead><tr><th>Waktu</th><th>User</th><th>Provider</th><th>Type</th><th>Tokens</th><th>Duration</th><th>Status</th><th>Response</th></tr></thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="text-xs text-dark-400 whitespace-nowrap"><?php echo e($log->created_at->format('d M H:i:s')); ?></td>
                <td class="text-sm"><?php echo e($log->user?->name ?? 'System'); ?></td>
                <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded"><?php echo e($log->provider); ?></span></td>
                <td><span class="text-xs font-mono bg-blue-500/10 text-blue-300 px-2 py-0.5 rounded"><?php echo e($log->type); ?></span></td>
                <td class="text-xs text-dark-400"><?php echo e(number_format($log->total_tokens)); ?></td>
                <td class="text-xs text-dark-400"><?php echo e($log->duration_ms); ?>ms</td>
                <td><span class="badge <?php echo e($log->success?'badge-success':'badge-danger'); ?>"><?php echo e($log->success?'OK':'FAIL'); ?></span></td>
                <td class="max-w-xs">
                    <p class="text-xs text-dark-500 truncate" title="<?php echo e($log->response); ?>">
                        <?php echo e($log->success ? Str::limit($log->response, 60) : $log->error_message); ?>

                    </p>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="text-center py-10 text-dark-400">Belum ada AI logs</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-dark-700/30"><?php echo e($logs->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/admin/logs/ai.blade.php ENDPATH**/ ?>