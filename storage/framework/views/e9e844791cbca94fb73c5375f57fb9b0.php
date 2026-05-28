<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>
<?php $__env->startSection('page-subtitle', 'Selamat datang, ' . auth()->user()->name . '!'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $healthScoreValue = is_array($healthScore)
        ? ($healthScore['score'] ?? 0)
        : $healthScore;

    $healthStatus = is_array($healthScore)
        ? ($healthScore['status'] ?? 'Unknown')
        : 'Unknown';

    $healthEmoji = is_array($healthScore)
        ? ($healthScore['emoji'] ?? '📊')
        : '📊';

    $scoreColor =
        $healthScoreValue >= 80 ? 'text-green-400'
        : ($healthScoreValue >= 60 ? 'text-yellow-400'
        : 'text-red-400');

    $barColor =
        $healthScoreValue >= 80 ? 'bg-green-500'
        : ($healthScoreValue >= 60 ? 'bg-yellow-500'
        : 'bg-red-500');
?>

<div class="space-y-6 animate-fade-in">

    
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        
        <div class="stat-card group">
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">
                        Total Saldo
                    </p>

                    <p class="text-2xl font-bold text-white mt-1">
                        Rp <?php echo e(number_format($totalBalance,0,',','.')); ?>

                    </p>

                    <p class="text-dark-500 text-xs mt-1">
                        <?php echo e($wallets->count()); ?> wallet aktif
                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-primary-500/15 flex items-center justify-center text-primary-400 group-hover:scale-110 transition-transform duration-200">
                    💰
                </div>

            </div>
        </div>

        
        <div class="stat-card group">
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">
                        Pemasukan Bulan Ini
                    </p>

                    <p class="text-2xl font-bold text-green-400 mt-1">
                        Rp <?php echo e(number_format($monthlyIncome,0,',','.')); ?>

                    </p>

                    <p class="text-dark-500 text-xs mt-1">
                        <?php echo e(now()->format('F Y')); ?>

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-green-500/15 flex items-center justify-center text-green-400 group-hover:scale-110 transition-transform duration-200">
                    📈
                </div>

            </div>
        </div>

        
        <div class="stat-card group">
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">
                        Pengeluaran Bulan Ini
                    </p>

                    <p class="text-2xl font-bold text-red-400 mt-1">
                        Rp <?php echo e(number_format($monthlyExpense,0,',','.')); ?>

                    </p>

                    <p class="text-dark-500 text-xs mt-1">
                        <?php echo e(now()->format('F Y')); ?>

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl bg-red-500/15 flex items-center justify-center text-red-400 group-hover:scale-110 transition-transform duration-200">
                    📉
                </div>

            </div>
        </div>

        
        <div class="stat-card group">
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-dark-400 text-xs font-medium uppercase tracking-wide">
                        Net Cashflow
                    </p>

                    <p class="text-2xl font-bold mt-1 <?php echo e($netCashflow >= 0 ? 'text-green-400' : 'text-red-400'); ?>">
                        <?php echo e($netCashflow >= 0 ? '+' : ''); ?>Rp <?php echo e(number_format($netCashflow,0,',','.')); ?>

                    </p>

                    <p class="text-dark-500 text-xs mt-1">
                        <?php echo e($netCashflow >= 0 ? '📈 Positif' : '📉 Negatif'); ?>

                    </p>
                </div>

                <div class="w-11 h-11 rounded-xl <?php echo e($netCashflow >= 0 ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400'); ?> flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                    ⚖️
                </div>

            </div>
        </div>

    </div>

    
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        
        <div class="glass-card p-6 xl:col-span-2">

            <div class="flex items-center justify-between mb-5">

                <div>
                    <h3 class="text-white font-semibold">
                        Pemasukan vs Pengeluaran
                    </h3>

                    <p class="text-dark-400 text-xs mt-0.5">
                        <?php echo e(now()->format('F Y')); ?> — per hari
                    </p>
                </div>

            </div>

            <div class="relative h-80">
                <canvas id="cashflowChart"></canvas>
            </div>

        </div>

        
        <div class="space-y-4">

            
            <div class="glass-card p-5 border border-primary-500/20 bg-primary-500/5 relative overflow-hidden">

                <div class="absolute top-0 right-0 w-24 h-24 bg-primary-500/10 rounded-full blur-3xl"></div>

                <div class="relative">

                    <div class="flex items-center gap-2 mb-3">

                        <div class="w-8 h-8 rounded-xl bg-primary-500/20 flex items-center justify-center text-sm">
                            🤖
                        </div>

                        <span class="text-sm font-semibold text-primary-300">
                            Smart AI Insight
                        </span>

                    </div>

                    <p class="text-dark-200 text-sm leading-relaxed">
                        <?php echo e($smartInsight ?? $aiInsight); ?>

                    </p>

                </div>

            </div>

            
            <div class="glass-card p-5 relative overflow-hidden">

                <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/10 rounded-full blur-3xl"></div>

                <div class="relative">

                    <div class="flex items-center justify-between mb-4">

                        <div>
                            <p class="text-dark-400 text-xs uppercase tracking-wide">
                                Financial Health
                            </p>

                            <h3 class="text-white font-semibold mt-1">
                                Health Score
                            </h3>
                        </div>

                        <div class="w-10 h-10 rounded-xl bg-green-500/15 flex items-center justify-center">
                            <?php echo e($healthEmoji); ?>

                        </div>

                    </div>

                    <div class="flex items-end gap-2 mb-3">

                        <span class="text-5xl font-bold <?php echo e($scoreColor); ?>">
                            <?php echo e($healthScoreValue); ?>

                        </span>

                        <span class="text-dark-400 mb-1">
                            /100
                        </span>

                    </div>

                    <div class="h-2 bg-dark-700 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700 <?php echo e($barColor); ?>"
                            style="width: <?php echo e($healthScoreValue); ?>%"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-dark-300 text-sm">
                            Status:
                            <span class="font-semibold">
                                <?php echo e($healthStatus); ?>

                            </span>
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>
        
    <div class="glass-card p-6 relative overflow-hidden">

        <div class="absolute top-0 right-0 w-52 h-52 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="relative">

            <div class="flex items-center justify-between mb-6">

                <div>
                    <p class="text-dark-400 text-xs uppercase tracking-wide">
                        Prediction AI
                    </p>

                    <h3 class="text-white text-lg font-semibold mt-1">
                        Prediksi Keuangan
                    </h3>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-blue-500/15 flex items-center justify-center text-xl">
                    📈
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                
                <div class="rounded-2xl bg-dark-700/30 border border-white/5 p-5">

                    <p class="text-dark-400 text-xs mb-2">
                        Prediksi Saldo
                    </p>

                    <h4 class="text-xl font-bold text-white">
                        Rp <?php echo e(number_format($prediction['predicted_balance'] ?? 0,0,',','.')); ?>

                    </h4>

                    <p class="text-xs text-dark-500 mt-2">
                        Estimasi saldo akhir bulan
                    </p>

                </div>

                
                <div class="rounded-2xl bg-dark-700/30 border border-white/5 p-5">

                    <p class="text-dark-400 text-xs mb-2">
                        Rata-rata Harian
                    </p>

                    <h4 class="text-xl font-bold text-red-400">
                        Rp <?php echo e(number_format($prediction['daily_average_expense'] ?? 0,0,',','.')); ?>

                    </h4>

                    <p class="text-xs text-dark-500 mt-2">
                        Pengeluaran per hari
                    </p>

                </div>

                
                <div class="rounded-2xl bg-dark-700/30 border border-white/5 p-5">

                    <p class="text-dark-400 text-xs mb-2">
                        Saving Rate
                    </p>

                    <h4 class="text-xl font-bold text-green-400">
                        <?php echo e($prediction['saving_rate'] ?? 0); ?>%
                    </h4>

                    <p class="text-xs text-dark-500 mt-2">
                        Rasio tabungan bulan ini
                    </p>

                </div>

            </div>

            
            <div class="mt-5 p-5 rounded-2xl bg-primary-500/5 border border-primary-500/10">

                <div class="flex items-start gap-3">

                    <div class="w-10 h-10 rounded-xl bg-primary-500/15 flex items-center justify-center flex-shrink-0">
                        🤖
                    </div>

                    <div>

                        <p class="text-primary-300 text-sm font-medium mb-1">
                            Smart Prediction
                        </p>

                        <p class="text-dark-200 text-sm leading-relaxed">
                            <?php echo e($prediction['message'] ?? 'AI sedang menganalisa pola transaksi Anda.'); ?>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const chartData = <?php echo json_encode($chartData, 15, 512) ?>;

    const canvas = document.getElementById('cashflowChart');

    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Gradient pemasukan
    const incomeGradient = ctx.createLinearGradient(0, 0, 0, 400);
    incomeGradient.addColorStop(0, 'rgba(34,197,94,0.45)');
    incomeGradient.addColorStop(1, 'rgba(34,197,94,0.02)');

    // Gradient pengeluaran
    const expenseGradient = ctx.createLinearGradient(0, 0, 0, 400);
    expenseGradient.addColorStop(0, 'rgba(239,68,68,0.45)');
    expenseGradient.addColorStop(1, 'rgba(239,68,68,0.02)');

    new Chart(ctx, {

        type: 'line',

        data: {

            labels: chartData.labels,

            datasets: [

                {
                    label: 'Pemasukan',

                    data: chartData.income,

                    borderColor: '#22c55e',
                    backgroundColor: incomeGradient,

                    fill: true,

                    tension: 0.45,

                    borderWidth: 3,

                    pointRadius: 4,
                    pointHoverRadius: 7,

                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,

                    pointHoverBorderWidth: 3,

                    cubicInterpolationMode: 'monotone',
                },

                {
                    label: 'Pengeluaran',

                    data: chartData.expense,

                    borderColor: '#ef4444',
                    backgroundColor: expenseGradient,

                    fill: true,

                    tension: 0.45,

                    borderWidth: 3,

                    pointRadius: 4,
                    pointHoverRadius: 7,

                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,

                    pointHoverBorderWidth: 3,

                    cubicInterpolationMode: 'monotone',
                }
            ]
        },

        options: {

            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false
            },

            animation: {
                duration: 1200,
                easing: 'easeOutQuart'
            },

            plugins: {

                legend: {

                    display: true,

                    position: 'top',

                    align: 'end',

                    labels: {

                        color: '#cbd5e1',

                        usePointStyle: true,

                        pointStyle: 'circle',

                        padding: 18,

                        font: {
                            size: 12,
                            weight: '600'
                        }
                    }
                },

                tooltip: {

                    backgroundColor: '#0f172a',

                    borderColor: 'rgba(255,255,255,0.08)',

                    borderWidth: 1,

                    padding: 14,

                    titleColor: '#ffffff',

                    bodyColor: '#cbd5e1',

                    displayColors: true,

                    callbacks: {

                        title: function(items) {
                            return 'Tanggal ' + items[0].label;
                        },

                        label: function(item) {

                            return (
                                item.dataset.label +
                                ': Rp ' +
                                new Intl.NumberFormat('id-ID')
                                    .format(item.raw)
                            );
                        }
                    }
                }
            },

            scales: {

                x: {

                    grid: {
                        display: false
                    },

                    ticks: {

                        color: '#64748b',

                        font: {
                            size: 11
                        }
                    }
                },

                y: {

                    beginAtZero: true,

                    grid: {

                        color: 'rgba(255,255,255,0.05)',

                        drawBorder: false
                    },

                    ticks: {

                        color: '#64748b',

                        padding: 10,

                        font: {
                            size: 11
                        },

                        callback: function(v) {

                            return 'Rp ' +
                                new Intl.NumberFormat(
                                    'id-ID',
                                    {
                                        notation: 'compact',
                                        maximumFractionDigits: 1
                                    }
                                ).format(v);
                        }
                    }
                }
            }
        }
    });

});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/finance.arielsa.site/resources/views/dashboard/index.blade.php ENDPATH**/ ?>