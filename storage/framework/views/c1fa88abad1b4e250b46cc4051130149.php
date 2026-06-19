<?php $__env->startSection('title', 'API Credentials'); ?>
<?php $__env->startSection('page-title', 'API Credentials'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-5 animate-fade-in">
    <div class="flex justify-between items-center">
        <p class="text-dark-400 text-sm">Kelola API keys untuk Grok AI dan layanan lainnya. Semua key terenkripsi di database.</p>
        <a href="<?php echo e(route('admin.api-credentials.create')); ?>" class="btn-primary text-sm">+ Tambah Credential</a>
    </div>

    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead><tr>
                <th>Nama</th><th>Provider</th><th>Model</th><th>API Key</th><th>Status</th><th>Last Test</th><th>Default</th><th></th>
            </tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $credentials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cred): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="<?php echo e($cred->deleted_at ? 'opacity-40' : ''); ?>">
                    <td class="font-medium text-white"><?php echo e($cred->name); ?></td>
                    <td><span class="badge bg-primary-500/15 text-primary-300 border-primary-500/25"><?php echo e($cred->provider); ?></span></td>
                    <td class="text-dark-400 text-xs font-mono"><?php echo e($cred->model ?? '—'); ?></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <code class="text-xs font-mono bg-dark-700/50 px-2 py-1 rounded text-dark-300"><?php echo e($cred->masked_key); ?></code>
                        </div>
                    </td>
                    <td>
                        <?php if($cred->is_active): ?><span class="badge badge-success">Aktif</span>
                        <?php else: ?><span class="badge badge-danger">Nonaktif</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if($cred->last_tested_at): ?>
                        <div>
                            <span class="badge <?php echo e($cred->last_test_success?'badge-success':'badge-danger'); ?>">
                                <?php echo e($cred->last_test_success?'✓ OK':'✗ Gagal'); ?>

                            </span>
                            <p class="text-xs text-dark-500 mt-0.5"><?php echo e($cred->last_tested_at->diffForHumans()); ?></p>
                        </div>
                        <?php else: ?><span class="text-dark-500 text-xs">Belum ditest</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if($cred->is_default): ?><span class="text-yellow-400 text-lg" title="Default">★</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if(!$cred->deleted_at): ?>
                        <div class="flex items-center gap-1">
                            <button onclick="testCredential(<?php echo e($cred->id); ?>, this)" class="btn-icon p-1.5 text-blue-400 hover:text-blue-300" title="Test Koneksi">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                            </button>
                            <a href="<?php echo e(route('admin.api-credentials.edit',$cred)); ?>" class="btn-icon p-1.5">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </a>
                            <form action="<?php echo e(route('admin.api-credentials.destroy',$cred)); ?>" method="POST">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" data-confirm="Hapus credential <?php echo e($cred->name); ?>?" class="btn-icon p-1.5 text-red-400">
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="text-center py-10 text-dark-400">Belum ada API credential. Tambahkan Grok API key terlebih dahulu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="glass-card p-5 border border-blue-500/20 bg-blue-500/5">
        <h4 class="text-blue-300 font-semibold text-sm mb-2">📌 Panduan Setup Groq AI</h4>
        <ol class="text-dark-300 text-sm space-y-1 list-decimal list-inside">
            <li>Dapatkan API key dari <a href="https://console.groq.com/keys" target="_blank" class="text-primary-400 hover:underline">console.groq.com/keys</a> (gratis!)</li>
            <li>Klik "+ Tambah Credential" dan pilih provider <strong class="text-white">groq</strong></li>
            <li>Input API key (format: <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">gsk_xxxxxxxxxxxx</code>)</li>
            <li>Endpoint URL: <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">https://api.groq.com/openai/v1</code></li>
            <li>Model teks: <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">llama-3.3-70b-versatile</code></li>
            <li>Model vision (untuk struk): <code class="text-xs bg-dark-700 px-1.5 py-0.5 rounded">meta-llama/llama-4-scout-17b-16e-instruct</code></li>
            <li>Centang "Default" dan "Aktif", lalu simpan</li>
            <li>Klik tombol ▶ untuk test koneksi</li>
        </ol>
        <div class="mt-3 p-3 rounded-lg bg-dark-700/50 border border-dark-600/30">
            <p class="text-dark-400 text-xs font-medium mb-1">💡 Tips: Vision Model</p>
            <p class="text-dark-500 text-xs">Untuk mengisi vision model di field "Meta/Additional", gunakan JSON: <code class="bg-dark-800 px-1 py-0.5 rounded text-dark-300">{"vision_model": "meta-llama/llama-4-scout-17b-16e-instruct"}</code></p>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
async function testCredential(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
    try {
        const res = await axios.post(`/admin/api-credentials/${id}/test`, {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        showToast(res.data.message, res.data.success ? 'success' : 'error');
        setTimeout(() => location.reload(), 1500);
    } catch(e) {
        showToast('Test gagal: ' + (e.response?.data?.message || e.message), 'error');
    }
    btn.disabled = false;
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/arielsa/projects/Ai_Finance/resources/views/admin/api-credentials/index.blade.php ENDPATH**/ ?>