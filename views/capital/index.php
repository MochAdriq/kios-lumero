<?php
/** * @var array $capitals @var array $capitalByCat @var ?array $edit 
 * @var string $from @var string $to @var string $today
 */
include __DIR__ . '/../shared-flash.php';

// View helpers
$_e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$_m = fn($n) => rupiah((int)round($n));
$_months = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$_did = function($date) use ($_months) { 
    $ts = strtotime($date); 
    return $ts ? date('d',$ts).' '.($_months[date('m',$ts)] ?? date('m',$ts)).' '.date('Y',$ts) : $date; 
};

$store = current_outlet_name();
?>

<link rel="stylesheet" href="<?= asset('css/executive.css') ?>?v=001">

<div class="executive-dashboard-wrapper">
    <header class="ex-hero mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Manajemen Modal</h1>
            <p class="mb-0 text-muted">
                <?= $_e($store) ?> — Catat suntikan dana, pembelian alat, renovasi, dan modal awal di sini.
            </p>
        </div>
        <div class="ex-hero-actions">
            <a class="btn btn-outline-secondary fw-bold shadow-sm" href="<?= url('/executive') ?>">
                &larr; Kembali ke Navigator
            </a>
        </div>
    </header>

    <section class="ex-grid-2 mb-4">
        <!-- Form dan Tabel Modal -->
        <article class="ex-panel" style="grid-column: span 2;">
            <h2>Komponen Modal</h2>
            <form class="ex-form d-flex flex-wrap gap-3 mb-4" method="post" action="<?= url('/capital/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $_e($edit['id'] ?? '') ?>">
                
                <div class="form-group w-100 d-flex gap-3">
                    <label class="flex-fill">
                        Tanggal 
                        <input type="date" name="capital_date" class="form-control" value="<?= $_e($edit['capital_date'] ?? $today) ?>">
                    </label>
                    <label class="flex-fill">
                        Kategori 
                        <select name="category" class="form-select">
                            <?php 
                            $categories = ['Modal Awal','Peralatan','Renovasi/Outlet','Bahan Awal','Marketing','Legal/Perizinan','Lain-lain'];
                            foreach ($categories as $cat): 
                            ?>
                                <option <?= ($edit['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                
                <label style="flex:2">
                    Nama Komponen 
                    <input name="component_name" class="form-control" value="<?= $_e($edit['component_name'] ?? '') ?>" required>
                </label>
                <label style="flex:1">
                    Nominal 
                    <input name="amount" inputmode="numeric" class="form-control" value="<?= $_e($edit['amount'] ?? '') ?>" required>
                </label>
                
                <div class="w-100">
                    <label class="d-block w-100 mb-2">
                        Supplier 
                        <input name="supplier" class="form-control" value="<?= $_e($edit['supplier'] ?? '') ?>">
                    </label>
                    <label class="d-block w-100 mb-3">
                        Catatan 
                        <textarea name="description" class="form-control" rows="2"><?= $_e($edit['description'] ?? '') ?></textarea>
                    </label>
                </div>
                
                <?php if ($edit): ?>
                    <label class="mb-3">
                        Status 
                        <select name="is_active" class="form-select">
                            <option value="1" <?= (int)$edit['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (int)$edit['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </label>
                <?php endif; ?>
                
                <div class="w-100 d-flex gap-2">
                    <button class="btn btn-danger rounded-pill fw-bold px-4"><?= $edit ? 'Update Modal' : 'Tambah Modal Baru' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-outline-secondary rounded-pill px-4" href="<?= url('/capital') ?>">Batal</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="ex-table-wrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Komponen</th>
                            <th class="ex-num">Nominal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$capitals): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada modal yang tercatat.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($capitals as $c): ?>
                            <tr class="<?= (int)($c['is_active'] ?? 1) === 1 ? '' : 'ex-inactive opacity-50' ?>">
                                <td><?= $_did($c['capital_date']) ?></td>
                                <td><span class="ex-badge ex-badge-gold"><?= $_e($c['category'] ?? $c['capital_type'] ?? '') ?></span></td>
                                <td>
                                    <b><?= $_e($c['component_name'] ?? $c['description'] ?? '') ?></b>
                                    <?php if (!empty($c['description']) && !empty($c['component_name']) && $c['description'] !== $c['component_name']): ?>
                                        <div class="ex-hint mt-1"><?= $_e($c['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="ex-num fw-bold"><?= $_m($c['amount']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= url('/capital?edit=' . $c['id']) ?>">Edit</a>
                                        <?php if ((int)($c['is_active'] ?? 1) === 1): ?>
                                            <form method="post" action="<?= url('/capital/delete') ?>" class="d-inline" onsubmit="return confirm('Nonaktifkan modal ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger rounded-pill">Nonaktif</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[inputmode="numeric"]').forEach(function(el){
        el.addEventListener('input', function() { 
            this.value = this.value.replace(/[^\d]/g, ''); 
        });
    });
});
</script>
