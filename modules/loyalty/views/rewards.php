<?php include __DIR__ . '/../../../views/layouts/header.php'; ?>

<?php
// Pastikan variabel $menuProducts dan $rewards tersedia
$menuProducts = $menuProducts ?? [];
$rewards = $rewards ?? [];
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-gift me-2 text-danger"></i>Katalog Hadiah Poin</h3>
                <p class="text-muted mb-0">Kelola daftar produk atau diskon yang dapat ditukarkan member menggunakan poin mereka.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('/loyalty/members') ?>" class="btn btn-outline-secondary rounded-pill">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Data Member
                </a>
                <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReward" onclick="resetRewardForm()">
                    <i class="ti ti-plus me-1"></i> Tambah Hadiah Poin
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
    <i class="ti ti-check me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
    <i class="ti ti-alert-triangle me-2"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">Nama Hadiah & Sumber</th>
                        <th>Kategori / Deskripsi</th>
                        <th>Nilai Nominal / HPP</th>
                        <th>Poin Dibutuhkan</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rewards)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ti ti-gift-off fs-1 d-block mb-2 text-secondary"></i>
                            Belum ada katalog hadiah poin. Klik tombol <b>Tambah Hadiah Poin</b> untuk mulai menambahkan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rewards as $r): ?>
                    <?php
                        $isPosSource = !empty($r['source_menu_item_id']);
                        $nominal = isset($r['nominal_value']) && $r['nominal_value'] !== null ? (int)$r['nominal_value'] : (int)($r['source_menu_price'] ?? 0);
                        $hpp = isset($r['source_hpp']) && $r['source_hpp'] !== null ? (int)$r['source_hpp'] : (int)($r['source_menu_hpp'] ?? 0);
                    ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <?php 
                                    $imgSrc = loyalty_resolve_image_url(!empty($r['image_url']) ? $r['image_url'] : ($r['source_menu_image_url'] ?? ''));
                                ?>
                                <?php if (!empty($imgSrc)): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 48px; height: 48px;" alt="Reward" onerror="this.outerHTML='<div class=\'rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center shadow-sm\' style=\'width: 48px; height: 48px;\'><i class=\'ti ti-gift fs-4\'></i></div>'">
                                <?php else: ?>
                                    <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                        <i class="ti ti-gift fs-4"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold fs-6"><?= htmlspecialchars($r['name']) ?></div>
                                    <?php if ($isPosSource): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle mt-1">
                                            <i class="ti ti-link me-1"></i>POS: <?= htmlspecialchars($r['source_menu_name'] ?? 'ID #' . $r['source_menu_item_id']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary mt-1">Custom / Non-Menu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($r['category'])): ?>
                                <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($r['category']) ?></span><br>
                            <?php endif; ?>
                            <span class="text-muted small"><?= htmlspecialchars($r['description'] ?? '-') ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">Rp <?= number_format($nominal, 0, ',', '.') ?></div>
                            <?php if ($hpp > 0): ?>
                                <div class="text-muted small">HPP: Rp <?= number_format($hpp, 0, ',', '.') ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold fs-6 shadow-sm">
                                <?= number_format((int)$r['required_points'], 0, ',', '.') ?> Poin
                            </span>
                        </td>
                        <td>
                            <?php if (isset($r['stock_qty']) && $r['stock_qty'] !== null && $r['stock_qty'] !== ''): ?>
                                <span class="badge <?= (int)$r['stock_qty'] <= 0 ? 'bg-danger' : 'bg-success-subtle text-success' ?> rounded-pill px-3">
                                    <?= number_format((int)$r['stock_qty'], 0, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border rounded-pill px-3">Tak Terbatas</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <form action="<?= url('/loyalty/rewards/toggle-status') ?>" method="POST" class="m-0 p-0">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= ($r['is_active'] ?? 1) ? 'btn-success-subtle text-success border-success-subtle' : 'btn-secondary-subtle text-secondary border-secondary-subtle' ?> rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="ti <?= ($r['is_active'] ?? 1) ? 'ti-check' : 'ti-x' ?>"></i> <?= ($r['is_active'] ?? 1) ? 'Aktif' : 'Nonaktif' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light border text-primary rounded-pill px-3 py-1 fw-medium d-inline-flex align-items-center gap-1 shadow-sm" onclick="editReward(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" title="Edit Hadiah">
                                    <i class="ti ti-edit fs-6"></i> Edit
                                </button>
                                <form action="<?= url('/loyalty/rewards/delete') ?>" method="POST" class="m-0 p-0" onsubmit="return confirm('Apakah Boss yakin ingin menghapus hadiah poin ini?');">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-light border text-danger rounded-pill px-3 py-1 fw-medium d-inline-flex align-items-center gap-1 shadow-sm" title="Hapus Hadiah">
                                        <i class="ti ti-trash fs-6"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Hadiah Poin -->
<div class="modal fade" id="modalReward" tabindex="-1" aria-labelledby="modalRewardLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalRewardLabel"><i class="ti ti-gift me-2"></i>Tambah Hadiah Poin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/loyalty/rewards/save') ?>" method="POST" id="formReward">
                <input type="hidden" name="id" id="reward_id" value="0">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-3 mb-4">
                        <i class="ti ti-info-circle me-1"></i> Boss dapat memilih produk existing dari sistem POS/Menu agar harga nominal & HPP otomatis tersinkronisasi, atau membuat hadiah khusus secara manual.
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Pilih Sumber Produk dari Katalog Existing <span class="text-muted small">(Opsional)</span></label>
                            <select class="form-select form-select-lg rounded-3 border-danger-subtle" name="source_menu_item_id" id="source_menu_item_id" onchange="onSelectSourceProduct()">
                                <option value="">-- Input Manual / Custom (Tanpa Tautan Produk POS) --</option>
                                <?php foreach ($menuProducts as $mp): ?>
                                    <option value="<?= (int)$mp['id'] ?>" 
                                            data-name="<?= htmlspecialchars($mp['name']) ?>"
                                            data-price="<?= (int)($mp['price'] ?? 0) ?>"
                                            data-hpp="<?= (int)($mp['hpp'] ?? 0) ?>"
                                            data-category="<?= htmlspecialchars($mp['category_name'] ?? '') ?>"
                                            data-desc="<?= htmlspecialchars($mp['description'] ?? '') ?>"
                                            data-image="<?= htmlspecialchars($mp['image_url'] ?? '') ?>">
                                        [<?= htmlspecialchars($mp['category_name'] ?? 'Produk') ?>] <?= htmlspecialchars($mp['name']) ?> — Rp <?= number_format((int)($mp['price'] ?? 0), 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="source_info" class="form-text text-success mt-1 d-none fw-semibold">
                                <i class="ti ti-check me-1"></i> Terhubung ke Katalog Produk: <span id="source_info_text"></span>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Hadiah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="name" id="reward_name" required placeholder="Contoh: Voucher Ayam Crispy / Tumbler Eksklusif">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Poin Dibutuhkan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control rounded-start-3" name="required_points" id="required_points" required min="1" value="50" placeholder="50">
                                <span class="input-group-text rounded-end-3 bg-light fw-bold text-danger">Poin</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori Hadiah</label>
                            <input type="text" class="form-control rounded-3" name="category" id="reward_category" placeholder="Contoh: Makanan, Minuman, Voucher, Merchandise">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nilai Nominal / Harga Setara (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-start-3 bg-light">Rp</span>
                                <input type="number" class="form-control rounded-end-3" name="nominal_value" id="nominal_value" min="0" placeholder="0 (Otomatis dari harga produk)">
                            </div>
                            <div class="form-text small">Kosongkan jika ingin mengikuti harga jual produk POS yang dipilih.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stok Hadiah <span class="text-muted small">(Kosongkan = Tak Terbatas)</span></label>
                            <input type="number" class="form-control rounded-3" name="stock_qty" id="stock_qty" min="0" placeholder="Tak Terbatas">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Batas Tukar per Member <span class="text-muted small">(Opsional)</span></label>
                            <input type="number" class="form-control rounded-3" name="max_redeem_per_member" id="max_redeem_per_member" min="1" placeholder="Tanpa Batas">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control rounded-3" name="description" id="reward_description" rows="2" placeholder="Jelaskan detail hadiah atau cara penggunaannya..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Syarat & Ketentuan <span class="text-muted small">(Opsional)</span></label>
                            <input type="text" class="form-control rounded-3" name="terms" id="reward_terms" placeholder="Contoh: Berlaku makan di tempat / Tidak dapat digabungkan promo lain">
                        </div>

                        <!-- Hidden fields to store source_hpp / source_price when selected -->
                        <input type="hidden" name="source_price" id="source_price" value="">
                        <input type="hidden" name="source_hpp" id="source_hpp" value="">
                        <input type="hidden" name="image_url" id="image_url" value="">
                        <input type="hidden" name="sort_order" id="sort_order" value="0">
                        <input type="hidden" name="is_active" id="is_active" value="1">
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-5 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Hadiah
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetRewardForm() {
    document.getElementById('modalRewardLabel').innerHTML = '<i class="ti ti-gift me-2"></i>Tambah Hadiah Poin';
    document.getElementById('reward_id').value = '0';
    document.getElementById('source_menu_item_id').value = '';
    document.getElementById('reward_name').value = '';
    document.getElementById('required_points').value = '50';
    document.getElementById('reward_category').value = '';
    document.getElementById('nominal_value').value = '';
    document.getElementById('stock_qty').value = '';
    document.getElementById('max_redeem_per_member').value = '';
    document.getElementById('reward_description').value = '';
    document.getElementById('reward_terms').value = '';
    document.getElementById('source_price').value = '';
    document.getElementById('source_hpp').value = '';
    document.getElementById('image_url').value = '';
    document.getElementById('source_info').classList.add('d-none');
}

function onSelectSourceProduct() {
    const select = document.getElementById('source_menu_item_id');
    const option = select.options[select.selectedIndex];
    const infoBox = document.getElementById('source_info');
    const infoText = document.getElementById('source_info_text');
    
    if (!select.value) {
        infoBox.classList.add('d-none');
        document.getElementById('source_price').value = '';
        document.getElementById('source_hpp').value = '';
        return;
    }

    const name = option.getAttribute('data-name');
    const price = option.getAttribute('data-price');
    const hpp = option.getAttribute('data-hpp');
    const category = option.getAttribute('data-category');
    const desc = option.getAttribute('data-desc');
    const image = option.getAttribute('data-image');

    // Auto-fill jika nama hadiah masih kosong atau dari pilihan sebelumnya
    document.getElementById('reward_name').value = name;
    if (category && !document.getElementById('reward_category').value) {
        document.getElementById('reward_category').value = category;
    }
    if (desc && !document.getElementById('reward_description').value) {
        document.getElementById('reward_description').value = desc;
    }
    if (price && parseInt(price) > 0) {
        document.getElementById('nominal_value').value = price;
        document.getElementById('source_price').value = price;
    }
    if (hpp && parseInt(hpp) > 0) {
        document.getElementById('source_hpp').value = hpp;
    }
    if (image) {
        document.getElementById('image_url').value = image;
    }

    infoText.textContent = name + ' (Harga POS: Rp ' + parseInt(price || 0).toLocaleString('id-ID') + ')';
    infoBox.classList.remove('d-none');
}

function editReward(reward) {
    resetRewardForm();
    document.getElementById('modalRewardLabel').innerHTML = '<i class="ti ti-edit me-2"></i>Edit Hadiah Poin';
    document.getElementById('reward_id').value = reward.id;
    
    if (reward.source_menu_item_id && parseInt(reward.source_menu_item_id) > 0) {
        document.getElementById('source_menu_item_id').value = reward.source_menu_item_id;
        onSelectSourceProduct();
    }
    
    document.getElementById('reward_name').value = reward.name || '';
    document.getElementById('required_points').value = reward.required_points || 50;
    document.getElementById('reward_category').value = reward.category || '';
    document.getElementById('nominal_value').value = reward.nominal_value || '';
    document.getElementById('stock_qty').value = (reward.stock_qty !== null && reward.stock_qty !== undefined) ? reward.stock_qty : '';
    document.getElementById('max_redeem_per_member').value = reward.max_redeem_per_member || '';
    document.getElementById('reward_description').value = reward.description || '';
    document.getElementById('reward_terms').value = reward.terms || '';
    if (reward.source_price) document.getElementById('source_price').value = reward.source_price;
    if (reward.source_hpp) document.getElementById('source_hpp').value = reward.source_hpp;
    if (reward.image_url) document.getElementById('image_url').value = reward.image_url;
    
    const modal = new bootstrap.Modal(document.getElementById('modalReward'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>
