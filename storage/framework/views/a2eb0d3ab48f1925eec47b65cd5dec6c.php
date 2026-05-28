<?php $__env->startSection('title', 'Detail Transaksi'); ?>
<?php $__env->startSection('page-title', 'Detail Transaksi'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto animate-fade-in space-y-5">
    <div class="glass-card p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <span class="badge badge-<?php echo e($transaction->type); ?> text-sm px-3 py-1 mb-2 inline-block">
                    <?php echo e(ucfirst($transaction->type)); ?>

                </span>
                <p class="text-3xl font-bold <?php echo e($transaction->type==='income'?'text-green-400':($transaction->type==='expense'?'text-red-400':'text-blue-400')); ?>">
                    <?php echo e($transaction->type==='income'?'+':($transaction->type==='expense'?'-':'')); ?>Rp <?php echo e(number_format($transaction->amount,0,',','.')); ?>

                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('transactions.edit',$transaction)); ?>" class="btn-secondary text-sm">Edit</a>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4">
            <div><dt class="text-dark-400 text-xs mb-0.5">Deskripsi</dt><dd class="text-white text-sm"><?php echo e($transaction->description ?? '—'); ?></dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Tanggal</dt><dd class="text-white text-sm"><?php echo e($transaction->transaction_date->format('d M Y H:i')); ?></dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Wallet</dt><dd class="text-white text-sm"><?php echo e($transaction->wallet->name); ?></dd></div>
            <?php if($transaction->targetWallet): ?><div><dt class="text-dark-400 text-xs mb-0.5">Wallet Tujuan</dt><dd class="text-white text-sm"><?php echo e($transaction->targetWallet->name); ?></dd></div><?php endif; ?>
            <div><dt class="text-dark-400 text-xs mb-0.5">Kategori</dt><dd class="text-white text-sm"><?php echo e($transaction->category?->name ?? '—'); ?></dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Merchant</dt><dd class="text-white text-sm"><?php echo e($transaction->merchant ?? '—'); ?></dd></div>
            <div><dt class="text-dark-400 text-xs mb-0.5">Sumber</dt>
                <dd class="text-white text-sm">
                    <?php $src=['manual'=>'Manual','telegram_text'=>'Telegram Teks','telegram_image'=>'Telegram Foto Struk','telegram_voice'=>'Telegram Voice Note','import'=>'Import'] ?>
                    <?php echo e($src[$transaction->source] ?? $transaction->source); ?>

                </dd>
            </div>
            <?php if($transaction->ai_confidence): ?>
            <div><dt class="text-dark-400 text-xs mb-0.5">AI Confidence</dt>
                <dd class="flex items-center gap-2">
                    <div class="flex-1 bg-dark-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full <?php echo e($transaction->ai_confidence>=80?'bg-green-500':($transaction->ai_confidence>=60?'bg-yellow-500':'bg-red-500')); ?>"
                             style="width:<?php echo e($transaction->ai_confidence); ?>%"></div>
                    </div>
                    <span class="text-sm text-white"><?php echo e(round($transaction->ai_confidence)); ?>%</span>
                </dd>
            </div>
            <?php endif; ?>
            <?php if($transaction->notes): ?><div class="col-span-2"><dt class="text-dark-400 text-xs mb-0.5">Catatan</dt><dd class="text-white text-sm"><?php echo e($transaction->notes); ?></dd></div><?php endif; ?>
        </dl>

        <?php if($transaction->attachment): ?>
        <?php $attachmentUrl = asset('storage/' . $transaction->attachment); ?>
        <div class="mt-5 pt-5 border-t border-dark-700/30">
            <p class="text-dark-400 text-xs mb-2">Lampiran Struk</p>
            <a href="<?php echo e($attachmentUrl); ?>" target="_blank" rel="noopener noreferrer" id="struk-link">
                <img src="<?php echo e($attachmentUrl); ?>"
                     alt="struk"
                     id="struk-img"
                     class="max-w-xs rounded-xl border border-dark-600/30 hover:opacity-90 transition-opacity cursor-pointer"
                     onerror="document.getElementById('struk-link').style.display='none'; document.getElementById('struk-fallback').style.display='block';">
            </a>
            <p id="struk-fallback" style="display:none;" class="text-dark-400 text-xs mt-2">
                ⚠️ Gambar tidak dapat ditampilkan.
                <a href="<?php echo e($attachmentUrl); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline">Buka langsung ↗</a>
            </p>
        </div>
        <?php endif; ?>

        <?php if($transaction->voiceNoteTranscription): ?>
        <div class="mt-5 pt-5 border-t border-dark-700/30">
            <p class="text-dark-400 text-xs mb-2">🎤 Transkrip Voice Note</p>
            <p class="text-dark-200 text-sm italic">"<?php echo e($transaction->voiceNoteTranscription->transcription); ?>"</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-3">
        <a href="<?php echo e(route('transactions.index')); ?>" class="btn-secondary">← Kembali</a>
        <form action="<?php echo e(route('transactions.destroy',$transaction)); ?>" method="POST">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" data-confirm="Yakin hapus transaksi ini? Saldo wallet akan dikembalikan." class="btn-danger">Hapus Transaksi</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/transactions/show.blade.php ENDPATH**/ ?>