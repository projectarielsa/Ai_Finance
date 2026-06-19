<?php $__env->startSection('title', 'Telegram Logs'); ?>
<?php $__env->startSection('page-title', 'Telegram Message Logs'); ?>

<?php $__env->startSection('content'); ?>
<div class="glass-card overflow-hidden animate-fade-in">
    <table class="data-table">
        <thead><tr>
            <th>Waktu</th><th>User</th><th>Chat ID</th><th>Arah</th><th>Tipe</th><th>Konten</th><th>Status</th>
        </tr></thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="text-xs text-dark-400 whitespace-nowrap"><?php echo e($msg->created_at->format('d M Y H:i')); ?></td>
                <td class="text-sm"><?php echo e($msg->user?->name ?? '—'); ?></td>
                <td class="text-xs font-mono text-dark-400"><?php echo e($msg->chat_id); ?></td>
                <td>
                    <span class="badge <?php echo e($msg->direction==='inbound'?'badge-income':'badge-transfer'); ?>">
                        <?php echo e($msg->direction==='inbound' ? '↙ In' : '↗ Out'); ?>

                    </span>
                </td>
                <td><span class="text-xs font-mono bg-dark-700/50 px-2 py-0.5 rounded"><?php echo e($msg->type); ?></span></td>
                <td class="max-w-xs">
                    <p class="text-xs text-dark-300 truncate"><?php echo e(\Str::limit($msg->content, 60) ?? '(media)'); ?></p>
                </td>
                <td>
                    <span class="badge <?php echo e(in_array($msg->status,['processed','sent'])?'badge-success':($msg->status==='failed'?'badge-danger':'badge-pending')); ?>">
                        <?php echo e($msg->status); ?>

                    </span>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="text-center py-10 text-dark-400">Belum ada pesan Telegram</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-dark-700/30"><?php echo e($messages->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/admin/logs/telegram.blade.php ENDPATH**/ ?>