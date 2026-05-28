<?php $__env->startSection('title', 'Hutang & Piutang'); ?>
<?php $__env->startSection('page-title', 'Hutang & Piutang'); ?>
<?php $__env->startSection('page-subtitle', 'Kelola catatan hutang dan piutang Anda'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in" x-data="{ showAddForm: false, formType: 'payable' }">

    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Total Piutang</p>
                    <p class="text-2xl font-bold text-green-400 mt-1">Rp <?php echo e(number_format($totalReceivable,0,',','.')); ?></p>
                    <p class="text-dark-500 text-xs mt-1">Uang yang harus diterima</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-green-500/15 flex items-center justify-center text-green-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Total Hutang</p>
                    <p class="text-2xl font-bold text-red-400 mt-1">Rp <?php echo e(number_format($totalPayable,0,',','.')); ?></p>
                    <p class="text-dark-500 text-xs mt-1">Uang yang harus dibayar</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-red-500/15 flex items-center justify-center text-red-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">Jatuh Tempo</p>
                    <p class="text-2xl font-bold <?php echo e($overdueCount > 0 ? 'text-yellow-400' : 'text-dark-300'); ?> mt-1"><?php echo e($overdueCount); ?></p>
                    <p class="text-dark-500 text-xs mt-1">item sudah lewat jatuh tempo</p>
                </div>
                <div class="w-11 h-11 rounded-xl <?php echo e($overdueCount > 0 ? 'bg-yellow-500/15 text-yellow-400' : 'bg-dark-700/50 text-dark-400'); ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    
    <div class="flex flex-wrap items-center justify-between gap-3">
        
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['type'=>'all']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($type==='all' ? 'bg-primary-500/20 text-primary-300 border border-primary-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               Semua
            </a>
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['type'=>'receivable']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($type==='receivable' ? 'bg-green-500/20 text-green-300 border border-green-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               💰 Piutang
            </a>
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['type'=>'payable']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($type==='payable' ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               💸 Hutang
            </a>
            <span class="text-dark-600">|</span>
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['status'=>'active']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($status==='active' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               Aktif
            </a>
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['status'=>'paid']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($status==='paid' ? 'bg-green-500/20 text-green-300 border border-green-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               Lunas
            </a>
            <a href="<?php echo e(route('debts.index', array_merge(request()->query(), ['status'=>'all']))); ?>"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?php echo e($status==='all' ? 'bg-dark-600/50 text-white border border-dark-500/30' : 'text-dark-400 hover:text-white hover:bg-dark-700/50'); ?>">
               Semua Status
            </a>
        </div>
        <div class="flex gap-2">
            <button @click="formType='receivable'; showAddForm=true; $nextTick(()=>document.getElementById('addForm').scrollIntoView({behavior:'smooth'}))"
                    class="btn-secondary text-sm border-green-500/30 text-green-400 hover:bg-green-500/10">
                + Piutang
            </button>
            <button @click="formType='payable'; showAddForm=true; $nextTick(()=>document.getElementById('addForm').scrollIntoView({behavior:'smooth'}))"
                    class="btn-primary text-sm">
                + Hutang
            </button>
        </div>
    </div>

    
    <div id="addForm" x-show="showAddForm" x-transition class="glass-card p-6">
        <h3 class="text-white font-semibold mb-1" x-text="formType==='receivable' ? '💰 Tambah Piutang (orang hutang ke saya)' : '💸 Tambah Hutang (saya hutang ke orang)'"></h3>
        <p class="text-dark-400 text-sm mb-4" x-text="formType==='receivable' ? 'Catat uang yang orang lain pinjam dari Anda' : 'Catat uang yang Anda pinjam dari orang lain'"></p>

        <?php if($errors->any()): ?>
        <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p>• <?php echo e($e); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo e(route('debts.store')); ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="type" :value="formType">

            <div class="form-group">
                <label class="input-label" x-text="formType==='receivable' ? 'Nama Peminjam *' : 'Nama Pemberi Hutang *'"></label>
                <input type="text" name="contact_name" value="<?php echo e(old('contact_name')); ?>" class="input-field" placeholder="Nama lengkap" required>
            </div>
            <div class="form-group">
                <label class="input-label">No. HP (opsional)</label>
                <input type="text" name="contact_phone" value="<?php echo e(old('contact_phone')); ?>" class="input-field" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label class="input-label">Nominal (Rp) *</label>
                <input type="number" name="amount" value="<?php echo e(old('amount')); ?>" class="input-field" min="1000" step="1000" required>
            </div>
            <div class="form-group">
                <label class="input-label">Tanggal</label>
                <input type="date" name="debt_date" value="<?php echo e(old('debt_date', now()->toDateString())); ?>" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">Jatuh Tempo (opsional)</label>
                <input type="date" name="due_date" value="<?php echo e(old('due_date')); ?>" class="input-field">
            </div>
            <div class="form-group">
                <label class="input-label">Wallet Terkait (opsional)</label>
                <select name="wallet_id" class="input-field">
                    <option value="">Pilih wallet...</option>
                    <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($w->id); ?>" <?php echo e(old('wallet_id')==$w->id?'selected':''); ?>><?php echo e($w->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Keterangan</label>
                <input type="text" name="description" value="<?php echo e(old('description')); ?>" class="input-field" placeholder="Untuk keperluan apa...">
            </div>
            <div class="form-group sm:col-span-2">
                <label class="input-label">Catatan</label>
                <textarea name="notes" class="input-field" rows="2" placeholder="Catatan tambahan..."><?php echo e(old('notes')); ?></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="notify_on_due" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    <span class="text-sm text-dark-200">Notifikasi Telegram saat mendekati jatuh tempo</span>
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="btn-primary" x-text="formType==='receivable' ? 'Simpan Piutang' : 'Simpan Hutang'"></button>
                <button type="button" @click="showAddForm=false" class="btn-secondary">Batal</button>
            </div>
        </form>
    </div>

    
    <?php $__empty_1 = true; $__currentLoopData = $debts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $debt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $isOverdue  = $debt->is_overdue;
        $typeColor  = $debt->type === 'receivable' ? 'green' : 'red';
        $statusBadge = match($debt->status) {
            'paid'      => 'bg-green-500/15 text-green-400 border-green-500/25',
            'partial'   => 'bg-yellow-500/15 text-yellow-400 border-yellow-500/25',
            'cancelled' => 'bg-dark-600/30 text-dark-400 border-dark-600/30',
            default     => 'bg-blue-500/15 text-blue-400 border-blue-500/25',
        };
    ?>
    <div class="glass-card p-5 <?php echo e($isOverdue ? 'ring-1 ring-yellow-500/30' : ''); ?> <?php echo e($debt->status==='paid' ? 'opacity-70' : ''); ?>">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                
                <div class="w-11 h-11 rounded-xl flex-shrink-0 flex items-center justify-center font-bold text-lg
                    <?php echo e($debt->type==='receivable' ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400'); ?>">
                    <?php echo e(strtoupper(substr($debt->contact_name, 0, 1))); ?>

                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-white font-semibold truncate"><?php echo e($debt->contact_name); ?></h3>
                        <span class="badge text-xs border <?php echo e($statusBadge); ?>"><?php echo e($debt->status_label); ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?php echo e($debt->type==='receivable' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'); ?>">
                            <?php echo e($debt->type_label); ?>

                        </span>
                        <?php if($isOverdue): ?>
                        <span class="badge bg-yellow-500/15 text-yellow-400 border-yellow-500/25 text-xs">⚠ Jatuh Tempo</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3 mt-1 flex-wrap">
                        <?php if($debt->description): ?>
                        <span class="text-dark-400 text-xs"><?php echo e(Str::limit($debt->description, 50)); ?></span>
                        <?php endif; ?>
                        <?php if($debt->due_date): ?>
                        <span class="text-dark-500 text-xs">· Jatuh tempo: <span class="<?php echo e($isOverdue ? 'text-yellow-400' : 'text-dark-400'); ?>"><?php echo e($debt->due_date->format('d M Y')); ?></span></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="text-right flex-shrink-0">
                <p class="text-lg font-bold <?php echo e($debt->type==='receivable' ? 'text-green-400' : 'text-red-400'); ?>">
                    Rp <?php echo e(number_format($debt->amount, 0, ',', '.')); ?>

                </p>
                <?php if($debt->status !== 'paid'): ?>
                <p class="text-dark-400 text-xs">Sisa: Rp <?php echo e(number_format($debt->remaining_amount, 0, ',', '.')); ?></p>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($debt->paid_amount > 0): ?>
        <div class="mt-3">
            <div class="flex justify-between text-xs text-dark-400 mb-1">
                <span>Terbayar: Rp <?php echo e(number_format($debt->paid_amount, 0, ',', '.')); ?></span>
                <span><?php echo e($debt->percentage); ?>%</span>
            </div>
            <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all <?php echo e($debt->type==='receivable' ? 'bg-green-500' : 'bg-blue-500'); ?>"
                     style="width: <?php echo e($debt->percentage); ?>%"></div>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="flex items-center gap-2 mt-4 flex-wrap">
            <a href="<?php echo e(route('debts.show', $debt)); ?>" class="btn-secondary text-xs py-1.5 px-3">Detail & Bayar</a>
            <?php if($debt->status !== 'paid' && $debt->status !== 'cancelled'): ?>
            <form action="<?php echo e(route('debts.markPaid', $debt)); ?>" method="POST"
                  onsubmit="return confirm('Tandai lunas?')">
                <?php echo csrf_field(); ?>
                <button class="text-xs py-1.5 px-3 rounded-lg border border-green-500/30 text-green-400 hover:bg-green-500/10 transition-colors">✓ Lunas</button>
            </form>
            <?php endif; ?>
            <a href="<?php echo e(route('debts.edit', $debt)); ?>" class="btn-secondary text-xs py-1.5 px-3">Edit</a>
            <form action="<?php echo e(route('debts.destroy', $debt)); ?>" method="POST"
                  onsubmit="return confirm('Hapus catatan ini?')" class="ml-auto">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn-icon text-dark-500 hover:text-red-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="glass-card p-12 text-center">
        <p class="text-5xl mb-4">🤝</p>
        <p class="text-white font-semibold text-lg">Belum ada catatan hutang/piutang</p>
        <p class="text-dark-400 mt-1 mb-5">Catat semua hutang dan piutang agar tidak pusing mengingatnya</p>
        <div class="flex gap-3 justify-center">
            <button @click="formType='receivable'; showAddForm=true" class="btn-secondary text-sm border-green-500/30 text-green-400 hover:bg-green-500/10">+ Piutang</button>
            <button @click="formType='payable'; showAddForm=true" class="btn-primary text-sm">+ Hutang</button>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/debts/index.blade.php ENDPATH**/ ?>