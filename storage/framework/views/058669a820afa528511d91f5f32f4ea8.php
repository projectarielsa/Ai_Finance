<?php $__env->startSection('title', 'Transaksi'); ?>
<?php $__env->startSection('page-title', 'Transaksi'); ?>
<?php $__env->startSection('page-subtitle', 'Riwayat semua transaksi keuangan Anda'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-5 animate-fade-in">

    
    <div class="glass-card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="form-group">
                <label class="input-label text-xs">Tipe</label>
                <select name="type" class="input-field py-2 text-sm">
                    <option value="">Semua Tipe</option>
                    <option value="income"   <?php echo e(request('type')=='income'?'selected':''); ?>>Pemasukan</option>
                    <option value="expense"  <?php echo e(request('type')=='expense'?'selected':''); ?>>Pengeluaran</option>
                    <option value="transfer" <?php echo e(request('type')=='transfer'?'selected':''); ?>>Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Wallet</label>
                <select name="wallet" class="input-field py-2 text-sm">
                    <option value="">Semua Wallet</option>
                    <?php $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($w->id); ?>" <?php echo e(request('wallet')==$w->id?'selected':''); ?>><?php echo e($w->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Bulan</label>
                <select name="month" class="input-field py-2 text-sm">
                    <option value="">Semua</option>
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?php echo e($m); ?>" <?php echo e(request('month')==$m?'selected':''); ?>><?php echo e(DateTime::createFromFormat('!m',$m)->format('F')); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Tahun</label>
                <select name="year" class="input-field py-2 text-sm">
                    <?php for($y=now()->year;$y>=now()->year-3;$y--): ?>
                    <option value="<?php echo e($y); ?>" <?php echo e(request('year')==$y?'selected':''); ?>><?php echo e($y); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group flex-1 min-w-40">
                <label class="input-label text-xs">Cari</label>
                <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Deskripsi, merchant..." class="input-field py-2 text-sm">
            </div>
            <button type="submit" class="btn-primary py-2 text-sm">Filter</button>
            <?php if(request()->hasAny(['type','wallet','month','year','search'])): ?>
            <a href="<?php echo e(route('transactions.index')); ?>" class="btn-secondary py-2 text-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-700/30 flex items-center justify-between">
            <p class="text-dark-400 text-sm"><?php echo e($transactions->total()); ?> transaksi ditemukan</p>
            <a href="<?php echo e(route('transactions.create')); ?>" class="btn-primary text-sm">+ Tambah</a>
        </div>

        <?php if($transactions->isEmpty()): ?>
        <div class="p-16 text-center">
            <div class="text-4xl mb-3">🔍</div>
            <p class="text-dark-300 font-medium">Tidak ada transaksi</p>
            <p class="text-dark-500 text-sm mt-1">Coba ubah filter atau tambah transaksi baru</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Wallet</th>
                    <th>Kategori</th>
                    <th>Sumber</th>
                    <th>Tipe</th>
                    <th class="text-right">Jumlah</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="text-dark-400 text-xs whitespace-nowrap">
                            <?php echo e($tx->transaction_date->format('d M Y')); ?><br>
                            <span class="text-dark-600"><?php echo e($tx->transaction_date->format('H:i')); ?></span>
                        </td>
                        <td>
                            <p class="text-white text-sm font-medium truncate max-w-[180px]"><?php echo e($tx->description ?? 'Transaksi'); ?></p>
                            <?php if($tx->merchant): ?><p class="text-dark-500 text-xs"><?php echo e($tx->merchant); ?></p><?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <div class="w-6 h-6 rounded-lg text-xs font-bold flex items-center justify-center" style="background:<?php echo e($tx->wallet->color); ?>22;color:<?php echo e($tx->wallet->color); ?>"><?php echo e(substr($tx->wallet->name,0,1)); ?></div>
                                <span class="text-sm text-dark-200"><?php echo e($tx->wallet->name); ?></span>
                            </div>
                            <?php if($tx->targetWallet): ?><p class="text-xs text-dark-500 mt-0.5">→ <?php echo e($tx->targetWallet->name); ?></p><?php endif; ?>
                        </td>
                        <td>
                            <?php if($tx->category): ?>
                            <span class="text-sm text-dark-300"><?php echo e($tx->category->name); ?></span>
                            <?php else: ?>
                            <span class="text-dark-600">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $src=['manual'=>'✋','whatsapp_text'=>'💬','whatsapp_image'=>'📸','whatsapp_voice'=>'🎤','import'=>'📥'] ?>
                            <span title="<?php echo e($tx->source); ?>" class="text-sm"><?php echo e($src[$tx->source] ?? '?'); ?></span>
                            <?php if($tx->ai_confidence): ?><span class="text-xs text-dark-500 ml-1"><?php echo e(round($tx->ai_confidence)); ?>%</span><?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?php echo e($tx->type); ?>"><?php echo e(ucfirst($tx->type)); ?></span></td>
                        <td class="text-right font-semibold whitespace-nowrap <?php echo e($tx->type==='income'?'text-green-400':($tx->type==='expense'?'text-red-400':'text-blue-400')); ?>">
                            <?php echo e($tx->type==='income'?'+':($tx->type==='expense'?'-':'')); ?>Rp<?php echo e(number_format($tx->amount,0,',','.')); ?>

                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="<?php echo e(route('transactions.show',$tx)); ?>" class="btn-icon p-1.5">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                                </a>
                                <form action="<?php echo e(route('transactions.destroy',$tx)); ?>" method="POST">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button type="submit" data-confirm="Yakin hapus transaksi ini? Saldo wallet akan dikembalikan." class="btn-icon p-1.5 text-red-400 hover:text-red-300">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-dark-700/30"><?php echo e($transactions->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/transactions/index.blade.php ENDPATH**/ ?>