<?php include __DIR__.'/../shared-flash.php'; ?>
<?php
$outletId = (int)($currentOutlet['id'] ?? 0);
$outletName = htmlspecialchars($currentOutlet['name'] ?? 'Pilih Cabang');
?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-adjustments') ?> Pengaturan Harga Cabang</span>
        <h2 class="mb-1">Override Produk: <span class="text-primary"><?= $outletName ?></span></h2>
        <p class="mb-0 text-muted">Sesuaikan harga jual dan HPP untuk cabang tertentu. Kosongkan input untuk mengikuti Harga Pusat.</p>
    </div>
</div>

<div class="sim-card shadow-sm border-0 mb-4 p-3 bg-light rounded">
    <form method="get" class="row g-2 align-items-center">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1 fw-bold">Pilih Cabang / Outlet</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><?= sim_icon('ti-building-store', 'text-muted') ?></span>
                <select name="outlet_id" class="form-select border-start-0 ps-0" onchange="this.form.submit()">
                    <option value="0" disabled <?= $outletId === 0 ? 'selected' : '' ?>>-- Pilih Cabang --</option>
                    <?php foreach ($outlets as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $outletId === (int)$o['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['name']) ?> <?= !empty($o['is_hq']) ? '(HQ)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label small text-muted mb-1 fw-bold">Cari Produk</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><?= sim_icon('ti-search', 'text-muted') ?></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Ketik nama produk atau varian..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
        </div>
        <div class="col-md-2 mt-auto">
            <button class="btn btn-primary w-100 fw-medium">Terapkan</button>
        </div>
    </form>
</div>

<?php if ($outletId > 0): ?>
<form method="post" action="<?= url('/products/overrides') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="outlet_id" value="<?= $outletId ?>">

    <div class="sim-card shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom sticky-top bg-white" style="top: 0; z-index: 10;">
            <h5 class="mb-0 fw-bold"><?= sim_icon('ti-box', 'me-2 text-primary') ?>Daftar Penyesuaian Harga</h5>
            <button type="submit" class="btn btn-success shadow-sm fw-medium px-4">
                <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Perubahan
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35%;">Info Produk</th>
                        <th style="width: 25%;">Harga Jual (Override)</th>
                        <th style="width: 25%;">Harga Pokok (HPP)</th>
                        <th class="text-center" style="width: 15%;">Status di Cabang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <?php 
                        // Visual cues untuk input yang terisi
                        $hasPriceOverride = $item['override_price'] !== null;
                        $hasHppOverride = $item['override_hpp'] !== null;
                        $inputClassPrice = $hasPriceOverride ? 'border-primary bg-primary-subtle' : 'bg-light';
                        $inputClassHpp = $hasHppOverride ? 'border-warning bg-warning-subtle' : 'bg-light';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <strong class="text-dark fs-6"><?= htmlspecialchars($item['product_name']) ?></strong>
                                <?php if ($item['variant_name'] && $item['variant_name'] !== 'Default'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary align-self-start mt-1 border border-secondary-subtle">
                                        <?= htmlspecialchars($item['variant_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="items[<?= $i ?>][variant_id]" value="<?= (int)$item['variant_id'] ?>">
                        </td>

                        <td>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text <?= $hasPriceOverride ? 'bg-primary text-white border-primary' : 'bg-white text-muted' ?>">Rp</span>
                                <input type="number" name="items[<?= $i ?>][selling_price]" 
                                       class="form-control <?= $inputClassPrice ?>"
                                       value="<?= $hasPriceOverride ? (float)$item['override_price'] : '' ?>"
                                       placeholder="Ikuti Pusat" step="100" min="0">
                            </div>
                            <div class="d-flex justify-content-between px-1">
                                <small class="text-muted" style="font-size: 0.75rem;">Pusat:</small>
                                <small class="<?= $hasPriceOverride ? 'text-decoration-line-through text-muted' : 'text-dark fw-medium' ?>" style="font-size: 0.75rem;">
                                    <?= rupiah($item['master_price']) ?>
                                </small>
                            </div>
                        </td>

                        <td>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text <?= $hasHppOverride ? 'bg-warning text-dark border-warning' : 'bg-white text-muted' ?>">Rp</span>
                                <input type="number" name="items[<?= $i ?>][hpp]" 
                                       class="form-control <?= $inputClassHpp ?>"
                                       value="<?= $hasHppOverride ? (float)$item['override_hpp'] : '' ?>"
                                       placeholder="Ikuti Pusat" step="100" min="0">
                            </div>
                            <div class="d-flex justify-content-between px-1">
                                <small class="text-muted" style="font-size: 0.75rem;">Pusat:</small>
                                <small class="<?= $hasHppOverride ? 'text-decoration-line-through text-muted' : 'text-dark fw-medium' ?>" style="font-size: 0.75rem;">
                                    <?= rupiah($item['master_hpp']) ?>
                                </small>
                            </div>
                        </td>

                        <td class="text-center">
                            <select name="items[<?= $i ?>][is_active]" class="form-select form-select-sm mx-auto shadow-none
                                <?= $item['override_active'] === null ? 'border-secondary text-secondary bg-light' : 
                                   ((int)$item['override_active'] === 1 ? 'border-success text-success bg-success-subtle fw-bold' : 'border-danger text-danger bg-danger-subtle fw-bold') ?>" 
                                style="max-width: 130px; font-size: 0.8rem;">
                                <option value="" <?= $item['override_active'] === null ? 'selected' : '' ?>>Ikuti Pusat</option>
                                <option value="1" <?= $item['override_active'] !== null && (int)$item['override_active'] === 1 ? 'selected' : '' ?>>✓ Dijual</option>
                                <option value="0" <?= $item['override_active'] !== null && (int)$item['override_active'] === 0 ? 'selected' : '' ?>>✕ Disembunyikan</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <?= sim_icon('ti-package', 'fs-1 text-light mb-2 d-block') ?>
                            <p class="text-muted mb-0">Tidak ada produk yang ditemukan.<br><small>Coba gunakan kata kunci pencarian yang lain.</small></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($items)): ?>
        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <span class="text-muted small">Menampilkan <?= count($items) ?> produk.</span>
            <button type="submit" class="btn btn-success shadow-sm fw-medium px-4">
                <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Perubahan
            </button>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php else: ?>
<div class="sim-card border-0 shadow-sm text-center py-5 mt-4">
    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
        <?= sim_icon('ti-building-store', 'text-secondary', 'width:40px;height:40px;') ?>
    </div>
    <h5 class="text-dark fw-bold">Belum Ada Cabang Dipilih</h5>
    <p class="text-muted mb-0 mx-auto" style="max-width: 400px;">Silakan pilih cabang melalui menu *dropdown* di atas untuk mulai mengatur harga *override* khusus untuk outlet tersebut.</p>
</div>
<?php endif; ?>

<style>
/* Hilangkan arrow up/down pada input number agar terlihat lebih bersih */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}
</style>