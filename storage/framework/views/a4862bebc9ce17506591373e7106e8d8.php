<?php $__env->startSection('title', 'Laporan'); ?>
<?php $__env->startSection('page-title', 'Laporan Keuangan'); ?>
<?php $__env->startSection('page-subtitle', 'Analisa dan export laporan keuangan Anda'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 animate-fade-in">

    
    <div class="glass-card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="form-group">
                <label class="input-label text-xs">Periode</label>
                <select name="type" class="input-field py-2 text-sm">
                    <option value="monthly" <?php echo e($type=='monthly'?'selected':''); ?>>Bulanan</option>
                    <option value="yearly"  <?php echo e($type=='yearly'?'selected':''); ?>>Tahunan</option>
                    <option value="weekly"  <?php echo e($type=='weekly'?'selected':''); ?>>Mingguan</option>
                    <option value="daily"   <?php echo e($type=='daily'?'selected':''); ?>>Harian</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Bulan</label>
                <select name="month" class="input-field py-2 text-sm">
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?php echo e($m); ?>" <?php echo e($month==$m?'selected':''); ?>><?php echo e(DateTime::createFromFormat('!m',$m)->format('F')); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label text-xs">Tahun</label>
                <select name="year" class="input-field py-2 text-sm">
                    <?php for($y=now()->year;$y>=now()->year-3;$y--): ?>
                    <option value="<?php echo e($y); ?>" <?php echo e($year==$y?'selected':''); ?>><?php echo e($y); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary py-2 text-sm">Tampilkan</button>
            <a href="<?php echo e(route('reports.pdf', request()->query())); ?>" class="btn-secondary py-2 text-sm">
                📄 Export PDF
            </a>
            <a href="<?php echo e(route('reports.excel', request()->query())); ?>" class="btn-secondary py-2 text-sm">
                📊 Export Excel
            </a>
        </form>
    </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Total Pemasukan</p>
            <p class="text-2xl font-bold text-green-400">Rp <?php echo e(number_format($totalIncome,0,',','.')); ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Total Pengeluaran</p>
            <p class="text-2xl font-bold text-red-400">Rp <?php echo e(number_format($totalExpense,0,',','.')); ?></p>
        </div>
        <div class="glass-card p-5">
            <p class="text-dark-400 text-xs mb-1">Net Cashflow</p>
            <?php $net = $totalIncome - $totalExpense ?>
            <p class="text-2xl font-bold <?php echo e($net>=0?'text-green-400':'text-red-400'); ?>">
                <?php echo e($net>=0?'+':''); ?>Rp <?php echo e(number_format($net,0,',','.')); ?>

            </p>
        </div>
    </div>

    
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4">Tren Harian</h3>
            <div class="h-52"><canvas id="dailyChart"></canvas></div>
        </div>

        
        <div class="glass-card p-5">
            <h3 class="text-white font-semibold mb-4">Pengeluaran per Kategori</h3>
            <?php if($expenseByCategory->isEmpty()): ?>
            <p class="text-dark-400 text-sm">Tidak ada data pengeluaran</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php $maxExp = $expenseByCategory->max('total') ?>
                <?php $__currentLoopData = $expenseByCategory->take(7); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-dark-200"><?php echo e($cat['name']); ?></span>
                        <span class="text-red-400 font-medium">Rp<?php echo e(number_format($cat['total'],0,',','.')); ?></span>
                    </div>
                    <div class="bg-dark-700/50 rounded-full h-1.5">
                        <div class="bg-red-500/60 h-1.5 rounded-full transition-all duration-500" style="width:<?php echo e($maxExp>0 ? round($cat['total']/$maxExp*100) : 0); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-dark-700/30">
            <h3 class="text-white font-semibold">Detail Transaksi (<?php echo e($transactions->count()); ?> transaksi)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th>Wallet</th><th>Tipe</th><th class="text-right">Jumlah</th>
                </tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-dark-400 text-xs"><?php echo e($tx->transaction_date->format('d M Y')); ?></td>
                        <td><?php echo e($tx->description ?? '—'); ?></td>
                        <td><?php echo e($tx->category?->name ?? '—'); ?></td>
                        <td><?php echo e($tx->wallet->name); ?></td>
                        <td><span class="badge badge-<?php echo e($tx->type); ?>"><?php echo e(ucfirst($tx->type)); ?></span></td>
                        <td class="text-right font-medium <?php echo e($tx->type==='income'?'text-green-400':($tx->type==='expense'?'text-red-400':'text-blue-400')); ?>">
                            Rp <?php echo e(number_format($tx->amount,0,',','.')); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center py-8 text-dark-400">Tidak ada transaksi</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const dailyData = <?php echo json_encode($dailyData, 15, 512) ?>;
const labels  = Object.keys(dailyData);
const income  = labels.map(l => dailyData[l].income);
const expense = labels.map(l => dailyData[l].expense);

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { label:'Pemasukan', data: income, borderColor:'rgba(34,197,94,0.8)', backgroundColor:'rgba(34,197,94,0.1)', fill:true, tension:0.4, borderWidth:2, pointRadius:3 },
            { label:'Pengeluaran', data: expense, borderColor:'rgba(239,68,68,0.8)', backgroundColor:'rgba(239,68,68,0.1)', fill:true, tension:0.4, borderWidth:2, pointRadius:3 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ labels:{ color:'#94a3b8', font:{size:11} } }, tooltip:{ backgroundColor:'#1e293b', borderColor:'#334155', borderWidth:1 } },
        scales:{
            x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{size:10}}},
            y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#64748b',font:{size:10},callback:v=>'Rp'+Intl.NumberFormat('id-ID',{notation:'compact'}).format(v)}}
        }
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/reports/index.blade.php ENDPATH**/ ?>