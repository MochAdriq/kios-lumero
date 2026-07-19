<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-flask') ?> HPP Engine</span>
        <h2 class="mb-1">Pengaturan Resep</h2>
        <p class="mb-0 text-muted">Mesin penghitung HPP (Harga Pokok Penjualan) berlapis dari bahan baku hingga produk akhir.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= url('/recipes/recalculate-all') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-warning shadow-sm" data-confirm="Sinkronkan ulang HPP untuk seluruh resep di sistem?">
                <?= sim_icon('ti-refresh', 'me-1') ?> Recalculate All
            </button>
        </form>

</div>

<ul class="nav nav-pills mb-4 gap-2" id="recipeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="final-tab" data-bs-toggle="tab" data-bs-target="#final" type="button" role="tab">
            <?= sim_icon('ti-box', 'me-1') ?> Produk Final
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="sub-tab" data-bs-toggle="tab" data-bs-target="#sub" type="button" role="tab">
            <?= sim_icon('ti-components', 'me-1') ?> Sub-Resep (Bahan Setengah Jadi)
        </button>
    </li>
</ul>

<div class="tab-content" id="recipeTabContent">
    <!-- Tab: Produk Final -->
    <div class="tab-pane fade show active" id="final" role="tabpanel">
        <div class="sim-card shadow-sm border-0">
            <h5 class="mb-3 fw-bold"><?= sim_icon('ti-box') ?> Resep Produk Final</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kategori</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th class="text-end">
                                HPP Varian (Produk)
                                <span title="HPP statis yang dimasukkan saat membuat produk. Ini yang dipakai mesin Kasir untuk menghitung keuntungan (margin)." data-bs-toggle="tooltip" style="cursor:help;">
                                    <?= sim_icon('ti-info-circle', 'text-muted ms-1', 'width:16px;height:16px;') ?>
                                </span>
                            </th>
                            <th class="text-end">
                                Total HPP Resep
                                <span title="HPP dinamis/asli yang dihitung otomatis oleh sistem berdasarkan racikan bahan baku dan fluktuasi harga beli riil." data-bs-toggle="tooltip" style="cursor:help;">
                                    <?= sim_icon('ti-info-circle', 'text-muted ms-1', 'width:16px;height:16px;') ?>
                                </span>
                            </th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recipes as $r): if($r['recipe_type'] !== 'final') continue; ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['category_name']) ?></span></td>
                            <td><strong><?= htmlspecialchars($r['product_name']) ?></strong></td>
                            <td>
                                <?php if (empty(trim((string)$r['variant_name'])) || strtolower(trim((string)$r['variant_name'])) === 'default'): ?>
                                    <span class="text-muted">-</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($r['variant_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <span class="text-muted"><?= rupiah($r['variant_hpp']) ?></span>
                            </td>
                            <td class="text-end">
                                <?php $diff = abs((float)$r['variant_hpp'] - (float)$r['total_hpp']); ?>
                                <?php if ($r['recipe_id']): ?>
                                    <strong class="<?= $diff > 0.01 ? 'text-danger' : 'text-success' ?>">
                                        <?= rupiah($r['total_hpp']) ?>
                                    </strong>
                                    <?php if($diff > 0.01): ?>
                                        <br>
                                        <small class="text-danger fw-bold" style="cursor:help;" title="Modal asli (Total HPP) sudah berubah akibat fluktuasi harga bahan atau perubahan takaran. Klik tombol kuning 'Recalculate All' di atas untuk menyinkronkan kembali HPP Varian dengan modal aslinya.">
                                            Tidak Sinkron! <?= sim_icon('ti-info-circle', 'text-danger', 'width:14px;height:14px;') ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Belum diatur</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($r['recipe_id']): ?>
                                    <a class="btn btn-sm btn-primary rounded-pill px-3" href="<?= url('/recipes/'.$r['recipe_id']) ?>">
                                        <?= sim_icon('ti-eye', 'me-1') ?> Lihat HPP
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="<?= url('/recipes/variant/'.$r['variant_id']) ?>">
                                        <?= sim_icon('ti-settings', 'me-1') ?> Atur Resep
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <!-- Tab: Sub-Resep -->
    <div class="tab-pane fade" id="sub" role="tabpanel">
        <div class="sim-card shadow-sm border-0 bg-primary-subtle border-primary-subtle mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <?= sim_icon('ti-info-circle', 'm-0', 'width: 24px; height: 24px;') ?>
                </div>
                <div>
                    <h6 class="fw-bold text-primary mb-1">Apa itu Sub-Resep?</h6>
                    <p class="text-primary mb-0 small">Sub-resep adalah resep bahan setengah jadi (contoh: <em>Gula Cair</em>, <em>Air Teh</em>) yang dibikin dari bahan baku dan memiliki HPP / hasil yield sendiri, kemudian dipakai sebagai komponen resep final.</p>
                </div>
            </div>
        </div>

        <div class="sim-card shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold"><?= sim_icon('ti-components', 'me-2') ?>Daftar Sub-Resep (Bahan Setengah Jadi)</h5>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill"><?= count($subRecipes) ?> Sub-Resep</span>
                </div>
                
                <!-- Bulk Toolbar -->
                <form id="bulkDeleteSubForm" method="post" action="<?= url('/recipes/sub/bulk-delete') ?>" onsubmit="return confirmBulkDeleteSub();" class="m-0">
                    <?= csrf_field() ?>
                    <div id="subBulkToolbar" class="d-none align-items-center gap-2 bg-danger-subtle border border-danger-subtle px-3 py-1 rounded-pill">
                        <span class="text-danger fw-semibold small">
                            <span id="subSelectedCount">0</span> sub-resep terpilih
                        </span>
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <?= sim_icon('ti-trash', 'm-0') ?> <span>Hapus Terpilih</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 4%;">
                                <input class="form-check-input" type="checkbox" id="checkAllSubs" title="Pilih semua sub-resep yang belum dipakai">
                            </th>
                            <th style="width: 28%;">Nama Sub-Resep</th>
                            <th class="text-center" style="width: 14%;">Dipakai Di</th>
                            <th class="text-center" style="width: 14%;">Hasil (Yield)</th>
                            <th class="text-end" style="width: 14%;">Total Modal Sub-Resep</th>
                            <th class="text-end" style="width: 14%;">HPP per Satuan</th>
                            <th class="text-end" style="width: 12%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subRecipes as $r): ?>
                        <?php 
                        $unitHpp = (float)$r['total_hpp'] / max(1, (float)$r['yield_qty']); 
                        $usageCount = (int)($r['used_in_recipes_count'] ?? 0);
                        ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($usageCount == 0): ?>
                                    <input class="form-check-input sub-checkbox" type="checkbox" name="ids[]" value="<?= $r['id'] ?>" form="bulkDeleteSubForm">
                                <?php else: ?>
                                    <input class="form-check-input" type="checkbox" disabled title="Tidak bisa dipilih karena sedang dipakai di <?= $usageCount ?> resep produk final">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Sub-Resep</span>
                                    <strong class="text-dark fs-6"><?= htmlspecialchars($r['name']) ?></strong>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($usageCount > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold" title="Digunakan sebagai bahan di <?= $usageCount ?> resep produk final aktif">
                                        <?= sim_icon('ti-check', 'me-1') ?><?= $usageCount ?> Resep Final
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-semibold" title="Sub-resep ini belum dipakai di produk final manapun">
                                        <?= sim_icon('ti-alert-circle', 'me-1') ?>Belum Dipakai (0)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                                    <?= number_format($r['yield_qty'], 2) ?> <?= htmlspecialchars($r['yield_unit_label'] ?: 'unit') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted fw-medium"><?= rupiah($r['total_hpp']) ?></span>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-bold fs-6">
                                    <?= rupiah($unitHpp) ?> / <?= htmlspecialchars($r['yield_unit_label'] ?: 'unit') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-1">
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3 d-inline-flex align-items-center gap-1" href="<?= url('/recipes/'.$r['id']) ?>">
                                        <?= sim_icon('ti-eye', 'm-0') ?> <span>Lihat</span>
                                    </a>
                                    <?php if ($usageCount > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 opacity-50" disabled title="Tidak bisa dihapus karena sedang dipakai di <?= $usageCount ?> resep final">
                                            <?= sim_icon('ti-trash', 'm-0') ?> <span>Hapus</span>
                                        </button>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('/recipes/sub/'.$r['id'].'/delete') ?>" class="d-inline m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sub-resep [<?= addslashes($r['name']) ?>]?\n\nTindakan ini tidak dapat dibatalkan.');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" title="Hapus Sub-Resep">
                                                <?= sim_icon('ti-trash', 'm-0') ?> <span>Hapus</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($subRecipes)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <p class="text-muted mb-0">Belum ada sub-resep di cabang ini.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('checkAllSubs');
            const checkboxes = document.querySelectorAll('.sub-checkbox');
            const toolbar = document.getElementById('subBulkToolbar');
            const countSpan = document.getElementById('subSelectedCount');

            function updateToolbar() {
                const checked = document.querySelectorAll('.sub-checkbox:checked');
                if (checked.length > 0) {
                    toolbar.classList.remove('d-none');
                    toolbar.classList.add('d-flex');
                    countSpan.textContent = checked.length;
                } else {
                    toolbar.classList.add('d-none');
                    toolbar.classList.remove('d-flex');
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = checkAll.checked;
                    });
                    updateToolbar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateToolbar);
            });

            window.confirmBulkDeleteSub = function() {
                const checked = document.querySelectorAll('.sub-checkbox:checked');
                if (checked.length === 0) {
                    alert('Pilih sub-resep yang ingin dihapus terlebih dahulu.');
                    return false;
                }
                return confirm(`Apakah Anda yakin ingin menghapus ${checked.length} sub-resep terpilih secara permanen?\n\nTindakan ini tidak dapat dibatalkan.`);
            };
        });
        </script>
    </div>
</div>
