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

        <div class="row g-4">
            <?php foreach($subRecipes as $r): ?>
            <div class="col-md-6 col-lg-4">
                <div class="sim-card h-100 shadow-sm border border-light-subtle position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 bg-secondary" style="height: 4px;"></div>
                    
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($r['name']) ?></h5>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Sub-Resep</span>
                    </div>
                    
                    <div class="d-flex flex-column gap-2 mb-4">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted small">Hasil (Yield)</span>
                            <strong class="text-dark"><?= number_format($r['yield_qty'], 2) ?> <?= htmlspecialchars($r['yield_unit_label'] ?: 'unit') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted small">Total HPP Sub-Resep</span>
                            <strong class="text-dark"><?= rupiah($r['total_hpp']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between bg-light p-2 rounded">
                            <span class="text-muted small fw-medium">HPP per Satuan</span>
                            <strong class="text-primary fs-5"><?= rupiah((float)$r['total_hpp'] / max(1, (float)$r['yield_qty'])) ?></strong>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="<?= url('/recipes/'.$r['id']) ?>" class="btn btn-outline-primary w-100 rounded-pill">
                            <?= sim_icon('ti-eye', 'me-1') ?> Lihat Komposisi
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($subRecipes)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Belum ada sub-resep.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
