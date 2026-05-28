<?php $__env->startSection('title', 'App Settings'); ?>
<?php $__env->startSection('page-title', 'App Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in">
    <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <?php $__currentLoopData = $settings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $groupSettings): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="glass-card p-6 mb-4">
            <h3 class="text-white font-semibold capitalize mb-4 flex items-center gap-2">
                <?php $icons=['general'=>'⚙️','finance'=>'💰','ai'=>'🤖','whatsapp'=>'📱']; ?>
                <?php echo e($icons[$group] ?? '📌'); ?> <?php echo e(ucfirst($group)); ?>

            </h3>
            <div class="space-y-4">
                <?php $__currentLoopData = $groupSettings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <label for="s_<?php echo e($setting->key); ?>" class="text-sm font-medium text-dark-200"><?php echo e($setting->label ?? $setting->key); ?></label>
                        <?php if($setting->description): ?><p class="text-xs text-dark-500 mt-0.5"><?php echo e($setting->description); ?></p><?php endif; ?>
                    </div>
                    <div class="w-64">
                        <?php if($setting->type === 'boolean'): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="settings[<?php echo e($setting->key); ?>]" value="0">
                            <input type="checkbox" name="settings[<?php echo e($setting->key); ?>]" id="s_<?php echo e($setting->key); ?>" value="1" <?php echo e($setting->value?'checked':''); ?> class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                            <span class="text-sm text-dark-300"><?php echo e($setting->value ? 'Aktif' : 'Nonaktif'); ?></span>
                        </label>
                        <?php else: ?>
                        <input type="<?php echo e(in_array($setting->type,['integer','float'])?'number':'text'); ?>"
                               name="settings[<?php echo e($setting->key); ?>]"
                               id="s_<?php echo e($setting->key); ?>"
                               value="<?php echo e($setting->value); ?>"
                               class="input-field text-sm py-2">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($settings->isEmpty()): ?>
        <div class="glass-card p-10 text-center"><p class="text-dark-400">Tidak ada settings. Jalankan seeder terlebih dahulu.</p></div>
        <?php else: ?>
        <button type="submit" class="btn-primary">Simpan Semua Pengaturan</button>
        <?php endif; ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/admin/settings/index.blade.php ENDPATH**/ ?>