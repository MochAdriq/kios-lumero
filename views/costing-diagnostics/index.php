<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-stethoscope') ?> Costing Diagnostics</span>
        <h2 class="mb-1">Diagnostik HPP & Resep</h2>
        <p class="mb-0 text-muted">Deteksi otomatis anomali harga, resep, dan data HPP. Pastikan semua produk memiliki margin sehat.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/recipes') ?>" class="btn btn-outline-secondary btn-sm"><?= sim_icon('ti-list-details', 'me-1') ?>Resep</a>
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary btn-sm"><?= sim_icon('ti-burger', 'me-1') ?>Produk</a>
        <a href="<?= url('/corrections') ?>" class="btn btn-outline-primary btn-sm"><?= sim_icon('ti-adjustments', 'me-1') ?>Koreksi & Void</a>
    </div>
</div>

<?php
    $scoreColor = $healthScore >= 80 ? 'success' : ($healthScore >= 50 ? 'warning' : 'danger');
    $scoreLabel = $healthScore >= 80 ? 'Sehat' : ($healthScore >= 50 ? 'Perlu Perbaikan' : 'Kritis');
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3 border-bottom border-<?= $scoreColor ?> border-3">
            <div class="bg-<?= $scoreColor ?>-subtle text-<?= $scoreColor ?> rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-heart-rate-monitor', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-3 fw-bold text-dark lh-1"><?= $healthScore ?><small class="fs-6 text-muted">%</small></div>
                <small class="text-muted fw-medium"><?= $scoreLabel ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-list-search', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= $stats['total_issues'] ?></div>
                <small class="text-muted fw-medium">Total Masalah</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3 border-bottom border-danger border-3">
            <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-alert-octagon', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= $stats['critical'] ?></div>
                <small class="text-muted fw-medium">Kritis</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sim-card shadow-sm border-0 bg-white h-100 d-flex align-items-center p-3 border-bottom border-warning border-3">
            <div class="bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <?= sim_icon('ti-alert-triangle', 'fs-4 m-0') ?>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark lh-1"><?= $stats['warning'] ?></div>
                <small class="text-muted fw-medium">Peringatan</small>
            </div>
        </div>
    </div>
</div>

<!-- Diagnostic Checks -->
<div class="row g-4">

    <!-- Critical: Negative Margins -->
    <div class="col-12">
        <div class="sim-card shadow-sm border-0 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-trending-down', 'me-1 text-danger') ?>
                    Produk Margin Negatif
                    <span class="badge bg-danger ms-2"><?= count($diagnostics['products_negative_margin']) ?> masalah</span>
                </h6>
                <span class="badge bg-danger-subtle text-danger">Kritis</span>
            </div>
            <?php if (empty($diagnostics['products_negative_margin'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Tidak ada produk dengan margin negatif. Bagus!</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Produk-produk ini memiliki HPP lebih tinggi dari harga jual — setiap penjualan = rugi.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>SKU</th><th>Produk</th><th>Varian</th><th class="text-end">Harga Jual</th><th class="text-end">HPP</th><th class="text-end">Margin</th><th class="text-end">%</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['products_negative_margin'] as $row): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($row['sku'] ?? '-') ?></code></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['variant_name']) ?></td>
                                <td class="text-end"><?= rupiah($row['selling_price']) ?></td>
                                <td class="text-end text-danger fw-bold"><?= rupiah($row['hpp']) ?></td>
                                <td class="text-end text-danger fw-bold"><?= rupiah($row['margin']) ?></td>
                                <td class="text-end text-danger"><?= number_format($row['margin_percent'], 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Critical: Duplicate Recipe Items -->
    <div class="col-12">
        <div class="sim-card shadow-sm border-0 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-copy', 'me-1 text-danger') ?>
                    Bahan Duplikat dalam Resep
                    <span class="badge bg-danger ms-2"><?= count($diagnostics['duplicate_recipe_items']) ?> masalah</span>
                </h6>
                <span class="badge bg-danger-subtle text-danger">Kritis</span>
            </div>
            <?php if (empty($diagnostics['duplicate_recipe_items'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Tidak ada bahan duplikat. Data resep bersih!</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Bahan baku yang muncul lebih dari sekali dalam satu resep — menyebabkan HPP dihitung ganda.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Resep</th><th>Bahan</th><th class="text-center">Duplikat</th><th class="text-end">Total Qty</th><th>ID Items</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['duplicate_recipe_items'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['recipe_name']) ?></td>
                                <td><?= htmlspecialchars($row['material_name'] ?? '-') ?></td>
                                <td class="text-center"><span class="badge bg-danger"><?= $row['duplicate_count'] ?>x</span></td>
                                <td class="text-end"><?= number_format((float)$row['total_qty'], 3) ?></td>
                                <td><code><?= htmlspecialchars($row['item_ids']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warning: Products Without Recipe -->
    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-file-off', 'me-1 text-warning') ?>
                    Produk Tanpa Resep
                    <span class="badge bg-warning text-dark ms-2"><?= count($diagnostics['products_without_recipe']) ?></span>
                </h6>
                <span class="badge bg-warning-subtle text-warning-emphasis">Peringatan</span>
            </div>
            <?php if (empty($diagnostics['products_without_recipe'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Semua produk sudah memiliki resep.</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Produk aktif tanpa resep — HPP tidak terhitung, stok bahan tidak terpotong saat POS.</p>
                <div class="table-responsive" style="max-height:300px; overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produk</th><th>Varian</th><th class="text-end">HPP</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['products_without_recipe'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['variant_name']) ?></td>
                                <td class="text-end"><?= rupiah($row['hpp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warning: Recipe Items Zero Cost -->
    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-currency-off', 'me-1 text-warning') ?>
                    Bahan Resep Tanpa Harga
                    <span class="badge bg-warning text-dark ms-2"><?= count($diagnostics['recipe_items_zero_cost']) ?></span>
                </h6>
                <span class="badge bg-warning-subtle text-warning-emphasis">Peringatan</span>
            </div>
            <?php if (empty($diagnostics['recipe_items_zero_cost'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Semua bahan resep sudah ada harganya.</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Bahan baku di resep yang average cost-nya Rp 0 — HPP produk jadi terlalu rendah.</p>
                <div class="table-responsive" style="max-height:300px; overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Resep</th><th>Bahan</th><th class="text-end">Qty</th><th class="text-end">Cost</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['recipe_items_zero_cost'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['recipe_name']) ?></td>
                                <td><?= htmlspecialchars($row['material_name'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format((float)$row['qty'], 3) ?> <?= htmlspecialchars($row['unit_symbol'] ?? '') ?></td>
                                <td class="text-end text-warning fw-bold">Rp 0</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warning: HPP Mismatch -->
    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-arrows-diff', 'me-1 text-warning') ?>
                    HPP Tidak Sinkron
                    <span class="badge bg-warning text-dark ms-2"><?= count($diagnostics['hpp_vs_selling_mismatch']) ?></span>
                </h6>
                <span class="badge bg-warning-subtle text-warning-emphasis">Peringatan</span>
            </div>
            <?php if (empty($diagnostics['hpp_vs_selling_mismatch'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>HPP variant dan resep sudah sinkron.</div>
            <?php else: ?>
                <p class="text-muted small mb-2">HPP di product_variants berbeda dengan total_hpp di resep — perlu recalculate.</p>
                <div class="table-responsive" style="max-height:300px; overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Produk</th><th class="text-end">HPP Variant</th><th class="text-end">HPP Resep</th><th class="text-end">Selisih</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['hpp_vs_selling_mismatch'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?> <small class="text-muted"><?= htmlspecialchars($row['variant_name']) ?></small></td>
                                <td class="text-end"><?= rupiah($row['variant_hpp']) ?></td>
                                <td class="text-end"><?= rupiah($row['recipe_hpp']) ?></td>
                                <td class="text-end text-warning fw-bold"><?= rupiah($row['diff']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    <a href="<?= url('/recipes') ?>" class="btn btn-sm btn-outline-warning"><?= sim_icon('ti-refresh', 'me-1') ?>Recalculate di Halaman Resep</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warning: Raw Materials No Cost -->
    <div class="col-lg-6">
        <div class="sim-card shadow-sm border-0 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-package-off', 'me-1 text-warning') ?>
                    Bahan Baku Tanpa Biaya
                    <span class="badge bg-warning text-dark ms-2"><?= count($diagnostics['raw_materials_no_cost']) ?></span>
                </h6>
                <span class="badge bg-warning-subtle text-warning-emphasis">Peringatan</span>
            </div>
            <?php if (empty($diagnostics['raw_materials_no_cost'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Semua bahan baku dengan stok sudah punya harga.</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Bahan baku aktif dengan stok > 0 tapi average_cost = 0. Input harga melalui Pembelian.</p>
                <div class="table-responsive" style="max-height:300px; overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Bahan</th><th>SKU</th><th class="text-end">Stok</th><th class="text-end">Cost</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['raw_materials_no_cost'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?> <small class="text-muted"><?= htmlspecialchars($row['category_name'] ?? '') ?></small></td>
                                <td><code><?= htmlspecialchars($row['sku'] ?? '-') ?></code></td>
                                <td class="text-end"><?= number_format((float)$row['stock_qty'], 2) ?> <?= htmlspecialchars($row['unit_symbol'] ?? '') ?></td>
                                <td class="text-end text-warning fw-bold">Rp 0</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info: Orphaned Recipes -->
    <div class="col-12">
        <div class="sim-card shadow-sm border-0 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <?= sim_icon('ti-unlink', 'me-1 text-info') ?>
                    Resep Yatim (Orphaned)
                    <span class="badge bg-info ms-2"><?= count($diagnostics['orphaned_recipes']) ?></span>
                </h6>
                <span class="badge bg-info-subtle text-info">Info</span>
            </div>
            <?php if (empty($diagnostics['orphaned_recipes'])): ?>
                <div class="text-success fw-medium"><?= sim_icon('ti-circle-check', 'me-1') ?>Tidak ada resep yatim. Semua resep terhubung ke produk aktif.</div>
            <?php else: ?>
                <p class="text-muted small mb-2">Resep yang produknya sudah nonaktif/dihapus. Tidak berbahaya tapi bisa dibersihkan.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Resep</th><th>Produk</th><th>Variant</th><th class="text-end">HPP Resep</th><th>Status Produk</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['orphaned_recipes'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['recipe_name']) ?></td>
                                <td><?= htmlspecialchars($row['product_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['variant_name'] ?? '—') ?></td>
                                <td class="text-end"><?= rupiah($row['total_hpp']) ?></td>
                                <td>
                                    <?php if ($row['product_active'] === null): ?>
                                        <span class="badge bg-secondary">Dihapus</span>
                                    <?php elseif (!(int)$row['product_active'] || !(int)$row['variant_active']): ?>
                                        <span class="badge bg-warning text-dark">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
