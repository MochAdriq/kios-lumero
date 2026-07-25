<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="sim-kicker"><?= sim_icon('ti-box') ?> Master Data</span>
            <h2 class="mb-1">Produk Jual</h2>
            <p class="mb-0 text-muted">Daftar produk final yang tampil di kasir (POS).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/products/builder') ?>" class="btn btn-primary shadow-sm">
                <?= sim_icon('ti-plus', 'me-1') ?> Tambah Produk Jual
            </a>
            <a href="<?= url('/recipes') ?>" class="btn btn-outline-primary bg-white">
                <?= sim_icon('ti-flask', 'me-1') ?> Atur HPP via Resep
            </a>

        </div>
    </div>
</div>

<div class="row g-4">
    <!-- List Produk -->
    <div class="col-lg-12">
        <div class="sim-card shadow-sm border-0 mb-4 p-4 bg-white">
            <form method="get" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><?= sim_icon('ti-search', 'text-muted') ?></span>
                    <input name="q" class="form-control border-start-0 ps-0 bg-light" placeholder="Cari produk, varian, SKU..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>
                <select name="cat" class="form-select bg-light" style="max-width: 200px;">
                    <option value="">Semua Kategori</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (isset($_GET['cat']) && (int)$_GET['cat'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary px-4">Filter</button>
            </form>
        </div>

        <div class="sim-card shadow-sm border-0 p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">SKU / Produk</th>
                            <th>Kategori</th>
                            <th class="text-end">HPP</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Margin</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $it): 
                            $marginPercent = (float)$it['margin_percent'];
                            $marginClass = $marginPercent >= 40 ? 'success' : ($marginPercent >= 20 ? 'warning text-dark' : 'danger');
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= asset($it['image'] ?? 'images/pos-products/original.png') ?>" alt="" class="rounded border shadow-sm" style="width: 44px; height: 44px; object-fit: contain; background: #fff; padding: 3px;">
                                    <div>
                                        <small class="text-muted d-block"><?= htmlspecialchars($it['sku']) ?></small>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($it['product_name']) ?></strong>
                                        <span class="text-muted small"><?= htmlspecialchars($it['variant_name']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['category_name']) ?></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted"><?= rupiah($it['hpp']) ?></span>
                            </td>
                            <td class="text-end">
                                <strong class="text-primary"><?= rupiah($it['selling_price']) ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $marginClass ?>">
                                    <?= number_format($marginPercent, 1) ?>%
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light border shadow-sm rounded-circle me-1 btn-edit-product" 
                                    data-id="<?= $it['id'] ?>"
                                    data-sku="<?= htmlspecialchars($it['sku']) ?>"
                                    data-product-name="<?= htmlspecialchars($it['product_name']) ?>"
                                    data-variant-name="<?= htmlspecialchars($it['variant_name']) ?>"
                                    data-image="<?= htmlspecialchars($it['image'] ?? 'images/pos-products/original.png') ?>"
                                    data-selling-price="<?= (int)$it['selling_price'] ?>"
                                    data-hpp="<?= (int)$it['hpp'] ?>"
                                    title="Edit Produk">
                                    <?= sim_icon('ti-pencil') ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm rounded-circle btn-delete-product" 
                                    data-id="<?= $it['id'] ?>"
                                    data-name="<?= htmlspecialchars($it['variant_name']) ?>"
                                    title="Hapus Produk">
                                    <?= sim_icon('ti-trash') ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($items)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada produk.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Produk Global -->
<div class="modal fade" id="globalEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" method="POST" action="<?= url('/products/update') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id" value="">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="ti ti-pencil me-2 text-primary"></i>Edit Produk & Varian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SKU</label>
                    <input type="text" id="edit_sku" class="form-control bg-light" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Produk Induk (Nama Utama)</label>
                    <input type="text" id="edit_product_name" name="product_name" list="productNamesList" class="form-control" autocomplete="off" required>
                    <datalist id="productNamesList">
                        <?php foreach ($productNames ?? [] as $pn): ?>
                            <option value="<?= htmlspecialchars($pn) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nama Varian (Tampil di Kasir)</label>
                    <input type="text" id="edit_variant_name" name="variant_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Gambar Produk (Tampil di Kasir / POS)</label>
                    <div class="d-flex align-items-start gap-3">
                        <div class="border rounded p-2 bg-white text-center shadow-sm" style="width: 86px; height: 86px; flex-shrink: 0;">
                            <img id="edit_image_preview" src="<?= asset('images/pos-products/original.png') ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label small text-muted mb-1">1. Pilih dari Galeri Existing:</label>
                            <select name="image" id="edit_image" class="form-select mb-2">
                                <option value="images/pos-products/original.png">Ayam Crispy (Original)</option>
                                <option value="images/pos-products/dada.png">Ayam Dada</option>
                                <option value="images/pos-products/paha-atas.png">Ayam Paha Atas</option>
                                <option value="images/pos-products/paha-bawah.png">Ayam Paha Bawah</option>
                                <option value="images/pos-products/sayap.png">Ayam Sayap</option>
                                <option value="images/pos-products/tanpa-nasi.png">Paket Tanpa Nasi</option>
                                <option value="images/pos-products/nasi.png">Nasi Putih</option>
                                <option value="images/pos-products/kentang-kriwil.png">Kentang Kriwil</option>
                                <option value="images/pos-products/kentang-dcelup.png">Kentang Lumero</option>
                                <option value="images/pos-products/matcha.png">Minuman Matcha</option>
                                <option value="images/pos-products/kopi.png">Minuman Kopi</option>
                                <option value="images/pos-products/celup-saus.png">Celup Saus</option>
                                <option value="images/pos-products/saus.png">Saus Tambahan</option>
                            </select>
                            <label class="form-label small text-muted mb-1">2. Atau Upload Foto Baru (Prioritas Utama):</label>
                            <input type="file" name="image_file" id="edit_image_file" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Jika upload foto baru di sini, foto upload akan disimpan & dipakai.</small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Harga Jual (Rp)</label>
                    <input type="number" id="edit_selling_price" name="selling_price" class="form-control" required min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Modal Dasar / HPP Langsung (Rp) <span class="badge bg-light text-secondary border">HPP Cepat</span></label>
                    <input type="number" id="edit_hpp" name="cost_price" class="form-control" min="0">
                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Isi modal langsung atau gunakan resep bahan baku untuk perhitungan otomatis.</small>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Hapus Produk Global -->
<div class="modal fade" id="globalDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content text-center p-4 border-0 shadow-lg" method="POST" action="<?= url('/products/delete') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="delete_id" value="">
            <div class="mb-3 text-danger">
                <?= sim_icon('ti-alert-circle', 'fs-1') ?>
            </div>
            <h5 class="fw-bold mb-3">Hapus Produk?</h5>
            <p class="text-muted small mb-4">Anda yakin ingin menghapus <strong id="delete_product_name"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger px-4">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseAssetUrl = "<?= rtrim(asset(''), '/') ?>/";
    const editImageSelect = document.getElementById('edit_image');
    const editImagePreview = document.getElementById('edit_image_preview');

    function updatePreview(val) {
        if (!val) val = 'images/pos-products/original.png';
        if (val.startsWith('http://') || val.startsWith('https://')) {
            editImagePreview.src = val;
        } else {
            editImagePreview.src = baseAssetUrl + val.replace(/^\/+/, '');
        }
    }

    const editImageFile = document.getElementById('edit_image_file');

    if (editImageSelect && editImagePreview) {
        editImageSelect.addEventListener('change', function() {
            if (editImageFile) editImageFile.value = '';
            updatePreview(this.value);
        });
    }

    if (editImageFile && editImagePreview) {
        editImageFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    editImagePreview.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                updatePreview(editImageSelect.value);
            }
        });
    }

    document.querySelectorAll('.btn-edit-product').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_sku').value = this.dataset.sku;
            document.getElementById('edit_product_name').value = this.dataset.productName;
            document.getElementById('edit_variant_name').value = this.dataset.variantName;
            document.getElementById('edit_selling_price').value = this.dataset.sellingPrice;
            document.getElementById('edit_hpp').value = this.dataset.hpp;
            
            const selectedImg = this.dataset.image || 'images/pos-products/original.png';
            if (editImageSelect) {
                editImageSelect.value = selectedImg;
                updatePreview(selectedImg);
            }
            if (editImageFile) editImageFile.value = '';
            
            const editModal = new bootstrap.Modal(document.getElementById('globalEditModal'));
            editModal.show();
        });
    });

    document.querySelectorAll('.btn-delete-product').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.dataset.id;
            document.getElementById('delete_product_name').textContent = this.dataset.name;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('globalDeleteModal'));
            deleteModal.show();
        });
    });
});
</script>
