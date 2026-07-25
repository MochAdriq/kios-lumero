<?php
$summary = $summary ?? [];
$items = $items ?? [];
$categories = $categories ?? [];
$statusLabel = [
    'available' => 'Tersedia',
    'low' => 'Menipis',
    'sold_out' => 'Habis',
    'inactive' => 'Nonaktif',
];
$statusClass = [
    'available' => 'success',
    'low' => 'warning',
    'sold_out' => 'danger',
    'inactive' => 'secondary',
];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
        <?= sim_icon('ti-circle-check', 'me-1') ?><?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="sim-hero mb-4">
    <div>
        <span class="sim-kicker">Live Projection</span>
        <h2>Proyeksi Stok Harian Dinamis</h2>
        <p>Sistem secara otomatis menghitung porsi maksimal siap jual berdasarkan ketersediaan bahan mentah di Gudang secara real-time.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('/pos') ?>" class="btn btn-light rounded-pill px-4"><?= sim_icon('ti-cash-register', 'me-1') ?>POS Kasir</a>
        <a href="<?= url('/reports/daily') ?>" class="btn btn-dark rounded-pill px-4"><?= sim_icon('ti-report-analytics', 'me-1') ?>Closing</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini"><small>Total Menu Terdaftar</small><strong><?= number_format((float)$summary['total_items'], 0, ',', '.') ?></strong></div></div>
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini"><small>Total Terjual</small><strong><?= number_format((float)$summary['sold_qty'], 0, ',', '.') ?></strong></div></div>
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini danger"><small>Total Rusak (Wastage)</small><strong><?= number_format((float)$summary['wasted_qty'], 0, ',', '.') ?></strong></div></div>
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini success"><small>Total Porsi Tersedia</small><strong><?= number_format((float)$summary['closing_qty'], 0, ',', '.') ?></strong></div></div>
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini warning"><small>Menu Menipis</small><strong><?= number_format((float)$summary['low_count'], 0, ',', '.') ?></strong></div></div>
    <div class="col-6 col-lg-2"><div class="sim-card stat-mini danger"><small>Menu Habis (Bahan Kosong)</small><strong><?= number_format((float)$summary['sold_out_count'], 0, ',', '.') ?></strong></div></div>
</div>

<div class="sim-card mb-4">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Tanggal Operasional</label>
            <input type="date" name="date" value="<?= htmlspecialchars($businessDate) ?>" class="form-control rounded-3" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Kategori</label>
            <select name="category_id" class="form-select rounded-3">
                <option value="0">Semua kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Cari Produk/Variant</label>
            <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control rounded-3" placeholder="Contoh: dada, sayap, matcha, kopi">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-danger rounded-3"><?= sim_icon('ti-search', 'me-1') ?>Tampilkan</button>
        </div>
    </form>
</div>

<div class="sim-card daily-stock-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h5 class="mb-1">Daftar Kapasitas Maksimal Produk</h5>
            <small class="text-muted">Data ketersediaan dihitung dinamis setiap detik dari bahan mentah. Data Terjual diambil real-time dari riwayat POS.</small>
        </div>
    </div>

    <div class="table-responsive sim-table-wrap">
        <table class="table align-middle sim-table daily-stock-table">
            <thead>
                <tr>
                    <th style="min-width:260px">Produk Final</th>
                    <th class="text-center">Stok Awal</th>
                    <th class="text-center">Masuk Dapur</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-center">Rusak</th>
                    <th class="text-center">Tersedia (Proyeksi)</th>
                    <th style="min-width:140px">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada produk/varian untuk filter ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $i => $item): ?>
                <?php $status = $item['stock_status'] ?: 'available'; ?>
                <tr class="stock-row" data-row="<?= $i ?>">
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="stock-product-icon"><?= sim_icon('ti-burger') ?></div>
                            <div>
                                <strong><?= htmlspecialchars($item['variant_name'] ?: $item['product_name']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($item['category_name']) ?> · SKU: <?= htmlspecialchars($item['sku']) ?></div>
                                <div class="small">HPP: <?= rupiah($item['hpp']) ?> · Harga: <?= rupiah($item['selling_price']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-center"><strong><?= number_format((float)$item['sold_qty'], 0, ',', '.') ?></strong></td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-center"><span class="stock-closing badge rounded-pill fs-6 px-4 bg-<?= $statusClass[$status] ?? 'secondary' ?>"><?= number_format((float)$item['closing_qty'], 0, ',', '.') ?></span></td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-light text-dark border w-100 py-2"><?= $statusLabel[$status] ?? 'Unknown' ?></span>
                            <button type="button" class="btn btn-sm btn-light border btn-view-recipe" data-variant-id="<?= (int)$item['product_variant_id'] ?>" title="Lihat Resep & Stok">
                                <?= sim_icon('ti-info-circle') ?>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="recipeStockModal" tabindex="-1" aria-labelledby="recipeStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="recipeStockModalLabel">Detail Kebutuhan Bahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Menampilkan bahan mentah dasar berdasarkan <strong><span id="modalRecipeName">-</span></strong></p>
                <div id="recipeStockLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted small">Mengambil data resep & stok...</p>
                </div>
                <div id="recipeStockError" class="alert alert-danger d-none"></div>
                <div class="table-responsive">
                    <table class="table align-middle sim-table" id="recipeStockTable">
                        <thead class="table-light">
                            <tr>
                                <th>Bahan Baku Dasar</th>
                                <th class="text-center">Kebutuhan (1 Porsi)</th>
                                <th class="text-center">Stok Outlet Saat Ini</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('recipeStockModal'));
    const tableBody = document.querySelector('#recipeStockTable tbody');
    const loading = document.getElementById('recipeStockLoading');
    const error = document.getElementById('recipeStockError');
    const recipeNameEl = document.getElementById('modalRecipeName');
    const table = document.getElementById('recipeStockTable');

    document.querySelectorAll('.btn-view-recipe').forEach(btn => {
        btn.addEventListener('click', function() {
            const variantId = this.dataset.variantId;
            
            // Reset modal
            tableBody.innerHTML = '';
            error.classList.add('d-none');
            table.classList.add('d-none');
            loading.classList.remove('d-none');
            recipeNameEl.textContent = 'Memuat...';
            
            modal.show();

            const timestamp = new Date().getTime();
            fetch('<?= url('/daily-stock/ajax-recipe-stock') ?>?variant_id=' + variantId + '&_=' + timestamp)
                .then(res => res.json())
                .then(data => {
                    loading.classList.add('d-none');
                    if (data.error) {
                        error.textContent = data.error;
                        error.classList.remove('d-none');
                        return;
                    }
                    
                    table.classList.remove('d-none');
                    recipeNameEl.textContent = data.recipe_name;
                    
                    let html = '';
                    if (!data.items || data.items.length === 0) {
                        html = '<tr><td colspan="4" class="text-center text-muted">Tidak ada data bahan baku</td></tr>';
                    } else {
                        data.items.forEach(item => {
                            const isDanger = item.is_bottleneck;
                            const rowClass = isDanger ? 'table-danger' : '';
                            const statusBadge = isDanger 
                                ? '<span class="badge bg-danger">Habis/Kurang</span>'
                                : '<span class="badge bg-success">Cukup</span>';
                                
                            html += `<tr class="${rowClass}">
                                <td><strong>${item.name}</strong></td>
                                <td class="text-center">${item.required} <small class="text-muted">${item.unit}</small></td>
                                <td class="text-center fw-bold">${item.available} <small class="text-muted">${item.unit}</small></td>
                                <td>${statusBadge}</td>
                            </tr>`;
                        });
                    }
                    tableBody.innerHTML = html;
                })
                .catch(err => {
                    loading.classList.add('d-none');
                    error.textContent = 'Terjadi kesalahan jaringan atau server.';
                    error.classList.remove('d-none');
                });
        });
    });
});
</script>
