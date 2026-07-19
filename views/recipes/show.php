<?php include __DIR__.'/../shared-flash.php'; ?>
<div class="mb-4">
    <?php if (!empty($_GET['return_to']) && (int)$_GET['return_to'] > 0): ?>
        <a href="<?= url('/recipes/'.(int)$_GET['return_to']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
            <?= sim_icon('ti-arrow-left', 'm-0') ?> <span>Kembali ke Resep Sebelumnya</span>
        </a>
    <?php else: ?>
        <a href="<?= url('/recipes') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1">
            <?= sim_icon('ti-arrow-left', 'm-0') ?> <span>Kembali ke Pengaturan Resep</span>
        </a>
    <?php endif; ?>
</div>

<?php 
$isFinal = $recipe['recipe_type'] === 'final';
$totalHpp = (float)$recipe['total_hpp'];
$yieldQty = (float)$recipe['yield_qty'];
$hppPerUnit = $isFinal ? $totalHpp : ($totalHpp / max(1, $yieldQty));
?>

<div class="sim-card shadow-sm border-0 mb-4 p-4">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <?php if($isFinal): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                        <?= sim_icon('ti-box', 'me-1') ?> Produk Final
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">
                        <?= sim_icon('ti-components', 'me-1') ?> Sub-Resep
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if($isFinal): ?>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($recipe['product_name']) ?></h3>
                <p class="text-muted fs-5 mb-0"><?= htmlspecialchars($recipe['variant_name']) ?> <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($recipe['sku']) ?></span></p>
            <?php else: ?>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($recipe['name']) ?></h3>
                <p class="text-muted fs-5 mb-0">Hasil (Yield): <strong><?= number_format($yieldQty, 2) ?> <?= htmlspecialchars($recipe['yield_unit_label'] ?: 'unit') ?></strong></p>
            <?php endif; ?>
        </div>
        
        <div class="text-end">
            <form method="post" action="<?= url('/recipes/'.$recipe['id'].'/recalculate') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary rounded-pill shadow-sm" data-confirm="Hitung ulang seluruh HPP berdasarkan harga bahan baku saat ini?">
                    <?= sim_icon('ti-calculator', 'me-1') ?> Recalculate HPP
                </button>
            </form>
        </div>
    </div>

    <div class="row g-0 rounded-4 overflow-hidden border">
        <div class="col-md-4 p-4 bg-light border-end text-center">
            <small class="text-muted d-block text-uppercase fw-bold mb-2">
                Total HPP Resep
                <span title="HPP dinamis/asli yang dihitung otomatis berdasarkan racikan bahan baku dan harga beli terbaru." data-bs-toggle="tooltip" style="cursor:help;">
                    <?= sim_icon('ti-info-circle', 'text-muted ms-1', 'width:14px;height:14px;') ?>
                </span>
            </small>
            <h3 class="fw-bold mb-0 text-dark"><?= rupiah($totalHpp) ?></h3>
        </div>
        
        <?php if($isFinal): ?>
            <div class="col-md-4 p-4 bg-white border-end text-center">
                <small class="text-muted d-block text-uppercase fw-bold mb-2">
                    HPP Varian (Kasir)
                    <span title="HPP statis yang disimpan di database produk. Ini yang dipakai mesin Kasir untuk menghitung keuntungan (margin) saat ada transaksi." data-bs-toggle="tooltip" style="cursor:help;">
                        <?= sim_icon('ti-info-circle', 'text-muted ms-1', 'width:14px;height:14px;') ?>
                    </span>
                </small>
                <?php $diff = abs((float)$recipe['variant_hpp'] - $totalHpp); ?>
                <h3 class="fw-bold mb-0 <?= $diff > 0.01 ? 'text-danger' : 'text-success' ?>">
                    <?= rupiah($recipe['variant_hpp']) ?>
                </h3>
                <?php if($diff > 0.01): ?>
                    <span class="badge bg-danger mt-2" title="Modal asli (Total HPP) sudah berubah. Klik 'Recalculate HPP' di atas untuk menyinkronkan nilai ini." data-bs-toggle="tooltip" style="cursor:help;">
                        Tidak Sinkron <?= sim_icon('ti-info-circle', 'ms-1', 'width:12px;height:12px;') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="col-md-4 p-4 bg-primary-subtle text-center">
                <small class="text-primary d-block text-uppercase fw-bold mb-2">Harga Jual & Margin</small>
                <h3 class="fw-bold mb-1 text-primary"><?= rupiah($recipe['selling_price']) ?></h3>
                <?php 
                    $margin = (float)$recipe['selling_price'] - $totalHpp;
                    $mp = (float)$recipe['selling_price'] > 0 ? ($margin / (float)$recipe['selling_price']) * 100 : 0;
                ?>
                <span class="badge <?= $mp >= 40 ? 'bg-success' : ($mp >= 20 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                    Margin: <?= rupiah($margin) ?> (<?= number_format($mp, 1) ?>%)
                </span>
            </div>
        <?php else: ?>
            <div class="col-md-8 p-4 bg-primary-subtle text-center d-flex flex-column justify-content-center">
                <small class="text-primary d-block text-uppercase fw-bold mb-2">HPP per Satuan (<?= htmlspecialchars($recipe['yield_unit_label'] ?: 'unit') ?>)</small>
                <h2 class="fw-bold mb-0 text-primary"><?= rupiah($hppPerUnit) ?></h2>
                <small class="text-primary-emphasis mt-2">Angka ini yang akan ditambahkan ke resep produk final saat sub-resep ini dipilih.</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="sim-card shadow-sm border-0 mb-4 p-3 bg-light">
    <h6 class="fw-bold mb-3"><?= sim_icon('ti-plus', 'me-1') ?> Tambah Bahan ke Resep</h6>
    <form method="post" action="<?= url('/recipes/'.$recipe['id'].'/item') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">Tipe Bahan</label>
            <select name="item_type" class="form-select form-select-sm" id="itemTypeSelect" required onchange="toggleItemType()">
                <option value="raw_material">Bahan Baku</option>
                <option value="sub_recipe">Sub-Resep</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-medium mb-1">Pilih Bahan</label>
            <div id="rawMaterialContainer">
                <select name="item_id" class="form-select form-select-sm" required id="rawMaterialSelect">
                    <option value="" disabled selected>-- Pilih Bahan Baku --</option>
                    <?php 
                    $curCat = null;
                    foreach($rawMaterials as $rm): 
                        $cat = $rm['category_name'] ?: 'Tanpa Kategori';
                        if ($cat !== $curCat) {
                            if ($curCat !== null) echo '</optgroup>';
                            echo '<optgroup label="'.htmlspecialchars($cat).'">';
                            $curCat = $cat;
                        }
                    ?>
                    <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['name']) ?></option>
                    <?php endforeach; 
                    if ($curCat !== null) echo '</optgroup>';
                    ?>
                </select>
            </div>
            <div id="subRecipeContainer" class="d-none">
                <select name="item_id" class="form-select form-select-sm" id="subRecipeSelect" disabled>
                    <option value="" disabled selected>-- Pilih Sub-Resep --</option>
                    <?php foreach($subRecipes as $sr): ?>
                    <option value="<?= $sr['id'] ?>"><?= htmlspecialchars($sr['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-medium mb-1">Qty</label>
            <input type="number" step="0.0001" name="qty" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-medium mb-1">Satuan</label>
            <select name="unit_id" class="form-select form-select-sm" required>
                <?php foreach($units as $u): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['symbol']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 text-end">
            <button type="submit" class="btn btn-sm btn-primary w-100"><?= sim_icon('ti-device-floppy') ?></button>
        </div>
    </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
let tsRaw, tsSub;
document.addEventListener('DOMContentLoaded', function() {
    tsRaw = new TomSelect('#rawMaterialSelect', { create: false, sortField: false, placeholder: '-- Pilih Bahan Baku --' });
    tsSub = new TomSelect('#subRecipeSelect', { create: false, sortField: false, placeholder: '-- Pilih Sub-Resep --' });
});

function toggleItemType() {
    const type = document.getElementById('itemTypeSelect').value;
    const rawCont = document.getElementById('rawMaterialContainer');
    const subCont = document.getElementById('subRecipeContainer');
    const rawSel = document.getElementById('rawMaterialSelect');
    const subSel = document.getElementById('subRecipeSelect');
    
    if (type === 'raw_material') {
        rawCont.classList.remove('d-none');
        subCont.classList.add('d-none');
        rawSel.disabled = false;
        subSel.disabled = true;
        if(tsRaw) tsRaw.enable();
        if(tsSub) tsSub.disable();
    } else {
        subCont.classList.remove('d-none');
        rawCont.classList.add('d-none');
        subSel.disabled = false;
        rawSel.disabled = true;
        if(tsSub) tsSub.enable();
        if(tsRaw) tsRaw.disable();
    }
}
</script>

<div class="sim-card shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-3">
        <h5 class="fw-bold mb-0"><?= sim_icon('ti-list-check', 'me-1') ?> Komposisi Bahan</h5>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tipe</th>
                    <th>Nama Bahan / Sub-Resep</th>
                    <th class="text-end">Qty Dibutuhkan</th>
                    <th class="text-end">Harga per Satuan</th>
                    <th class="text-end">Total Biaya (HPP)</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recipe['items'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada komposisi bahan di resep ini.</td></tr>
                <?php else: ?>
                    <?php foreach($recipe['items'] as $it): 
                        $isItemSub = $it['item_type'] === 'sub_recipe';
                    ?>
                    <tr>
                        <td>
                            <?php if($isItemSub): ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Sub-Resep</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Bahan Baku</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($isItemSub && !empty($it['sub_recipe_id'])): ?>
                                <a href="<?= url('/recipes/'.$it['sub_recipe_id'].'?return_to='.$recipe['id']) ?>" class="btn btn-sm btn-outline-info text-info-emphasis rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold shadow-sm" title="Klik untuk membuka detail & komposisi sub-resep ini">
                                    <span><?= htmlspecialchars($it['material_name']) ?></span>
                                    <?= sim_icon('ti-external-link', 'ms-1 m-0') ?>
                                </a>
                            <?php else: ?>
                                <strong><?= htmlspecialchars($it['material_name']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="fs-5 fw-medium"><?= number_format($it['qty'], 2) ?></span>
                            <small class="text-muted"><?= htmlspecialchars($it['unit_symbol']) ?></small>
                        </td>
                        <td class="text-end text-muted">
                            <?= rupiah($it['cost_per_unit']) ?>
                        </td>
                        <td class="text-end fw-bold">
                            <?= rupiah($it['total_cost']) ?>
                        </td>
                        <td class="text-end">
                            <form method="post" action="<?= url('/recipes/item/'.$it['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus bahan ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger border-0 p-1"><?= sim_icon('ti-trash') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
