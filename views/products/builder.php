<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="mb-4">
    <a href="<?= url('/products') ?>" class="text-decoration-none text-muted fw-bold">
        <?= sim_icon('ti-arrow-left', 'me-1') ?> Kembali ke Daftar Produk
    </a>
</div>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <span class="sim-kicker"><?= sim_icon('ti-burger') ?> Dedicated Module</span>
        <h2 class="mb-1">Product Builder (Racik Produk Jual Final)</h2>
        <p class="mb-0 text-muted">Satu modul khusus untuk meracik menu kasir (POS), menentukan komposisi resep HPP, serta mengatur harga jual dan analisis margin profit secara langsung.</p>
    </div>
</div>

<form method="post" action="<?= url('/products/builder/save') ?>" enctype="multipart/form-data" id="builderForm">
    <?= csrf_field() ?>
    
    <?php if(!empty($exp_id)): ?>
        <div class="alert alert-info d-flex align-items-center mb-4 shadow-sm border-info">
            <span class="fs-2 me-3"><?= sim_icon('ti-bulb') ?></span>
            <div>
                <h5 class="alert-heading mb-1 fw-bold">Setup Eksperimen 7 Hari</h5>
                <p class="mb-0">Anda sedang menyiapkan menu kasir untuk uji coba eksperimen <b><?= htmlspecialchars($exp_name) ?></b>.</p>
            </div>
        </div>
        <input type="hidden" name="exp_id" value="<?= (int)$exp_id ?>">
    <?php endif; ?>
    
    <!-- SECTION 1: IDENTITAS & DISPLAY PRODUK -->
    <div class="sim-card shadow-sm border-0 p-4 mb-4">
        <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-4">
            <span class="badge bg-primary rounded-circle p-2 fs-6"><?= sim_icon('ti-info-circle') ?></span>
            <h5 class="fw-bold mb-0">1. Identitas & Display Menu POS</h5>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Nama Produk / Menu <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-lg" required placeholder="Contoh: Ayam Crispy Saus Keju" value="<?= htmlspecialchars($exp_name ?? '') ?>">
                <small class="text-muted">Nama utama produk yang akan muncul di menu kasir.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Nama Varian (Opsional)</label>
                <input type="text" name="variant_name" class="form-control form-control-lg" placeholder="Contoh: Original / Spesial / Reguler (Default: Original)">
                <small class="text-muted">Jika produk tunggal tanpa varian khusus, biarkan kosong (akan menjadi "Original" atau "Default").</small>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Kategori Produk Kasir</label>
                <select name="product_category_id" class="form-select form-select-lg">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">SKU / Kode Item (Opsional)</label>
                <input type="text" name="sku" class="form-control form-control-lg" placeholder="Contoh: PRD-001">
                <small class="text-muted">Kosongkan jika ingin sistem membuat SKU otomatis.</small>
            </div>
        </div>

        <!-- GAMBAR PRODUK -->
        <h6 class="fw-bold mt-4 mb-3 border-top pt-3">Display Foto Menu POS & Self-Order</h6>
        <div class="row g-4 align-items-center bg-light rounded p-3">
            <div class="col-md-7">
                <div class="mb-3">
                    <label class="form-label fw-medium">Pilih dari Galeri Existing (Default)</label>
                    <select name="image" id="builder_image_select" class="form-select">
                        <option value="images/pos-products/original.png">Ayam Crispy (Original)</option>
                        <option value="images/pos-products/dada.png">Ayam Dada</option>
                        <option value="images/pos-products/paha-atas.png">Ayam Paha Atas</option>
                        <option value="images/pos-products/paha-bawah.png">Ayam Paha Bawah</option>
                        <option value="images/pos-products/sayap.png">Ayam Sayap</option>
                        <option value="images/pos-products/tanpa-nasi.png">Paket Tanpa Nasi</option>
                        <option value="images/pos-products/nasi.png">Nasi Putih</option>
                        <option value="images/pos-products/kentang-kriwil.png">Kentang Kriwil</option>
                        <option value="images/pos-products/kentang-lumero.png">Kentang Lumero</option>
                        <option value="images/pos-products/matcha.png">Minuman Matcha</option>
                        <option value="images/pos-products/kopi.png">Minuman Kopi</option>
                        <option value="images/pos-products/celup-saus.png">Celup Saus</option>
                        <option value="images/pos-products/saus.png">Saus Tambahan</option>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-medium">Atau Upload Foto Baru (Prioritas Utama)</label>
                    <input type="file" name="image" id="builder_image_input" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted d-block mt-1">Maksimal 2MB. Format JPG, PNG, WEBP. Jika diisi, foto upload ini yang akan disimpan.</small>
                </div>
            </div>
            <div class="col-md-5 text-center">
                <label class="form-label fw-medium d-block">Live Preview Foto:</label>
                <div class="border rounded p-3 bg-white text-center shadow-sm d-flex align-items-center justify-content-center" style="height: 160px;">
                    <img id="builder_image_preview" src="<?= asset('images/pos-products/original.png') ?>" alt="Preview" style="max-height: 140px; max-width: 100%; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: RACIKAN KOMPOSISI & KALKULASI HPP -->
    <div class="sim-card shadow-sm border-0 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark rounded-circle p-2 fs-6"><?= sim_icon('ti-components') ?></span>
                <h5 class="fw-bold mb-0">2. Racikan Komposisi Resep & Kalkulasi HPP</h5>
            </div>
            <span class="badge bg-info-subtle text-info-emphasis border border-info px-3 py-2 rounded-pill fw-bold">
                <?= sim_icon('ti-calculator', 'me-1') ?> HPP Otomatis Terkalkulasi
            </span>
        </div>

        <div class="alert alert-light border shadow-sm small mb-4">
            <?= sim_icon('ti-info-circle', 'text-primary me-2 fs-5') ?>
            <strong>Racikan Resep:</strong> Tentukan komponen bahan baku mentah atau sub-resep (bahan setengah jadi) yang dibutuhkan per 1 porsi menu ini. Sistem akan otomatis menjumlahkan estimasi Harga Pokok Produksi (HPP) berdasarkan harga rata-rata bahan di gudang saat ini.
        </div>

        <div class="table-responsive mb-3 border rounded shadow-sm">
            <table class="table align-middle mb-0" id="compTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%;">Tipe Bahan</th>
                        <th style="width: 32%;">Nama Bahan / Sub-Resep</th>
                        <th style="width: 15%;">Takaran (Qty)</th>
                        <th style="width: 15%;">Satuan</th>
                        <th style="width: 13%;" class="text-end">Estimasi Biaya</th>
                        <th style="width: 5%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="compTableBody">
                    <!-- Dynamic Rows added by JS -->
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end fs-6 py-3">TOTAL ESTIMASI HPP (PER PORSI):</td>
                        <td class="text-end fs-5 text-primary py-3" id="totalHppDisplay">Rp 0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mb-2">
            <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" onclick="addCompRow()">
                <?= sim_icon('ti-plus', 'me-1') ?> Tambah Komponen Bahan
            </button>
        </div>
    </div>

    <!-- SECTION 3: HARGA JUAL & ANALISIS MARGIN PROFIT -->
    <div class="sim-card shadow-sm border-0 p-4 mb-5">
        <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-4">
            <span class="badge bg-success rounded-circle p-2 fs-6"><?= sim_icon('ti-coin') ?></span>
            <h5 class="fw-bold mb-0">3. Penentuan Harga Jual & Analisis Margin Profit</h5>
        </div>

        <div class="row g-4 align-items-center">
            <div class="col-md-6">
                <label class="form-label fw-bold fs-5">Harga Jual per Porsi (Rp) <span class="text-danger">*</span></label>
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                    <input type="number" name="selling_price" id="selling_price_input" class="form-control fw-bold text-dark fs-4" required min="0" value="<?= isset($exp_price) && $exp_price > 0 ? (int)$exp_price : 0 ?>" oninput="calculateMargin()">
                </div>
                <small class="text-muted d-block mt-2">Harga jual akhir kepada pelanggan sebelum diskon atau pajak (jika ada).</small>
            </div>

            <!-- Panel Analisis Live -->
            <div class="col-md-6">
                <div class="border rounded-4 p-4 bg-light shadow-sm" id="profit_card">
                    <h6 class="fw-bold text-muted mb-3 d-flex justify-content-between align-items-center">
                        <span><?= sim_icon('ti-chart-pie', 'me-1') ?> ANALISIS KEUNTUNGAN LIVE</span>
                        <span id="marginBadge" class="badge bg-secondary rounded-pill px-3 py-2">Belum Diatur</span>
                    </h6>
                    <div class="row g-3 text-center">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block fw-bold mb-1">Keuntungan Bersih (Rp)</small>
                            <span class="fs-4 fw-bold text-success" id="profitRpDisplay">Rp 0</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fw-bold mb-1">Persentase Margin (%)</small>
                            <span class="fs-4 fw-bold text-primary" id="profitPercentDisplay">0%</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top text-center small text-muted" id="profitRecommendation">
                        Masukkan harga jual dan racik komposisi di atas untuk melihat analisis profitabilitas menu ini.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: DISTRIBUSI CABANG -->
    <?php if (Auth::can(['super_admin'])): ?>
    <div class="sim-card shadow-sm border-0 p-4 mb-4 bg-primary-subtle">
        <div class="form-check form-switch fs-5">
            <input class="form-check-input" type="checkbox" role="switch" id="pushToBranches" name="push_to_branches" value="1" checked>
            <label class="form-check-label fw-bold text-primary-emphasis" for="pushToBranches">
                <?= sim_icon('ti-building-broadcast', 'me-1') ?> Push ke Semua Cabang
            </label>
            <small class="d-block text-muted mt-1 fs-6">Aktifkan untuk otomatis menyalin (kloning) produk ini ke semua cabang yang ada. Jika dimatikan, produk hanya tersedia di Pusat.</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- SUBMIT BUTTON -->
    <div class="d-flex justify-content-between align-items-center py-4 border-top">
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary px-4 py-3 rounded-pill fw-bold">
            <?= sim_icon('ti-x', 'me-1') ?> Batal
        </a>
        <button type="submit" class="btn btn-success px-5 py-3 rounded-pill shadow fw-bold fs-5 d-flex align-items-center gap-2">
            <?= sim_icon('ti-device-floppy', 'fs-4') ?> Simpan Menu POS & Resep Komposisi
        </button>
    </div>
</form>

<script>
const itemsData = <?= json_encode($compItems ?? []) ?>;
const unitsData = <?= json_encode($units ?? []) ?>;
const baseAssetUrl = "<?= rtrim(asset(''), '/') ?>/";

let totalHppValue = 0;

document.addEventListener('DOMContentLoaded', () => {
    // Foto Live Preview
    const selectImg = document.getElementById('builder_image_select');
    const fileInput = document.getElementById('builder_image_input');
    const previewImg = document.getElementById('builder_image_preview');

    function updatePreview(val) {
        if (!val) val = 'images/pos-products/original.png';
        if (val.startsWith('http://') || val.startsWith('https://')) {
            previewImg.src = val;
        } else {
            previewImg.src = baseAssetUrl + val.replace(/^\/+/, '');
        }
    }

    if (selectImg && previewImg) {
        selectImg.addEventListener('change', function() {
            if (fileInput) fileInput.value = '';
            updatePreview(this.value);
        });
    }

    if (fileInput && previewImg) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else if (selectImg) {
                updatePreview(selectImg.value);
            }
        });
    }

    // Add 1 default row initially
    addCompRow();
});

function addCompRow() {
    const tbody = document.getElementById('compTableBody');
    const tr = document.createElement('tr');
    
    let typeSelect = `<select name="comp_item_type[]" class="form-select border-1 comp-type" required onchange="populateItems(this)">
        <option value="">-- Tipe Bahan --</option>
        <option value="raw_material">Bahan Baku Mentah</option>
        <option value="sub_recipe">Sub-Resep (Bahan Olahan)</option>
    </select>`;
    
    let itemSelect = `<select name="comp_item_id[]" class="form-select border-1 comp-item" required onchange="onItemSelectChange(this)">
        <option value="">-- Pilih Tipe Dulu --</option>
    </select>`;
    
    let unitOptions = unitsData.map(u => `<option value="${u.id}">${u.name} (${u.symbol})</option>`).join('');
    let unitSelect = `<select name="comp_unit_id[]" class="form-select border-1 comp-unit" required onchange="calculateRowCost(this)">
        <option value="">-- Satuan --</option>
        ${unitOptions}
    </select>`;

    tr.innerHTML = `
        <td>${typeSelect}</td>
        <td>${itemSelect}</td>
        <td><input type="number" name="comp_qty[]" class="form-control border-1 comp-qty" required step="0.0001" min="0.0001" placeholder="0.00" oninput="calculateRowCost(this)"></td>
        <td>${unitSelect}</td>
        <td class="text-end fw-bold text-dark comp-row-cost" data-cost="0">Rp 0</td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" onclick="removeCompRow(this)" title="Hapus baris"><?= sim_icon('ti-trash') ?></button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeCompRow(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    calculateTotalHpp();
}

function populateItems(selectEl) {
    const tr = selectEl.closest('tr');
    const itemSelect = tr.querySelector('.comp-item');
    const unitSelect = tr.querySelector('.comp-unit');
    const type = selectEl.value;
    
    itemSelect.innerHTML = '<option value="">-- Pilih Bahan / Komponen --</option>';
    if (!type) {
        calculateRowCost(selectEl);
        return;
    }
    
    itemsData.forEach(item => {
        if (item.type === type) {
            let label = item.name + (item.sku ? ` (${item.sku})` : '');
            let opt = document.createElement('option');
            opt.value = item.id;
            opt.dataset.unitCost = item.unit_cost || 0;
            opt.dataset.unitId = item.unit_id || '';
            opt.textContent = label;
            itemSelect.appendChild(opt);
        }
    });
    calculateRowCost(selectEl);
}

function onItemSelectChange(selectEl) {
    const tr = selectEl.closest('tr');
    const unitSelect = tr.querySelector('.comp-unit');
    const opt = selectEl.options[selectEl.selectedIndex];
    
    // Auto select unit if defined on item
    if (opt && opt.dataset.unitId) {
        unitSelect.value = opt.dataset.unitId;
    }
    calculateRowCost(selectEl);
}

function calculateRowCost(el) {
    const tr = el.closest('tr');
    if (!tr) return;
    
    const itemSelect = tr.querySelector('.comp-item');
    const qtyInput = tr.querySelector('.comp-qty');
    const costCell = tr.querySelector('.comp-row-cost');
    
    let unitCost = 0;
    if (itemSelect && itemSelect.selectedIndex >= 0) {
        const opt = itemSelect.options[itemSelect.selectedIndex];
        unitCost = parseFloat(opt ? opt.dataset.unitCost : 0) || 0;
    }
    
    let qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
    let rowCost = unitCost * qty;
    
    if (costCell) {
        costCell.dataset.cost = rowCost;
        costCell.textContent = 'Rp ' + Math.round(rowCost).toLocaleString('id-ID');
    }
    calculateTotalHpp();
}

function calculateTotalHpp() {
    const costCells = document.querySelectorAll('.comp-row-cost');
    let sum = 0;
    costCells.forEach(cell => {
        sum += parseFloat(cell.dataset.cost || 0);
    });
    
    totalHppValue = sum;
    const displayEl = document.getElementById('totalHppDisplay');
    if (displayEl) {
        displayEl.textContent = 'Rp ' + Math.round(sum).toLocaleString('id-ID');
    }
    calculateMargin();
}

function calculateMargin() {
    const sellInput = document.getElementById('selling_price_input');
    const profitRpEl = document.getElementById('profitRpDisplay');
    const profitPercentEl = document.getElementById('profitPercentDisplay');
    const badgeEl = document.getElementById('marginBadge');
    const recEl = document.getElementById('profitRecommendation');
    
    let sellPrice = parseFloat(sellInput ? sellInput.value : 0) || 0;
    let profitRp = sellPrice - totalHppValue;
    let marginPercent = (sellPrice > 0) ? (profitRp / sellPrice * 100) : 0;
    
    if (profitRpEl) {
        profitRpEl.textContent = 'Rp ' + Math.round(profitRp).toLocaleString('id-ID');
        profitRpEl.className = 'fs-4 fw-bold ' + (profitRp >= 0 ? 'text-success' : 'text-danger');
    }
    
    if (profitPercentEl) {
        profitPercentEl.textContent = marginPercent.toFixed(1) + '%';
        profitPercentEl.className = 'fs-4 fw-bold ' + (marginPercent >= 40 ? 'text-success' : (marginPercent >= 20 ? 'text-warning text-dark' : 'text-danger'));
    }
    
    if (badgeEl && recEl) {
        if (sellPrice <= 0 && totalHppValue <= 0) {
            badgeEl.className = 'badge bg-secondary rounded-pill px-3 py-2';
            badgeEl.textContent = 'Belum Diatur';
            recEl.textContent = 'Masukkan harga jual dan racik komposisi di atas untuk melihat analisis profitabilitas menu ini.';
        } else if (sellPrice <= totalHppValue) {
            badgeEl.className = 'badge bg-danger rounded-pill px-3 py-2';
            badgeEl.textContent = 'Rugi / Di Bawah HPP';
            recEl.innerHTML = '<span class="text-danger fw-bold">Peringatan:</span> Harga jual lebih rendah dari atau sama dengan HPP (Rp ' + Math.round(totalHppValue).toLocaleString('id-ID') + '). Anda akan mengalami kerugian.';
        } else if (marginPercent < 20) {
            badgeEl.className = 'badge bg-danger rounded-pill px-3 py-2';
            badgeEl.textContent = 'Margin Tipis (< 20%)';
            recEl.innerHTML = '<span class="text-danger fw-bold">Perhatian:</span> Margin profit tipis (' + marginPercent.toFixed(1) + '%). Disarankan menaikkan harga atau efisiensi bahan baku agar keuntungan operasional aman.';
        } else if (marginPercent < 40) {
            badgeEl.className = 'badge bg-warning text-dark rounded-pill px-3 py-2';
            badgeEl.textContent = 'Margin Sedang (20-39%)';
            recEl.innerHTML = '<span class="text-dark fw-bold">Cukup Baik:</span> Margin profit berada di rentang wajar F&B (' + marginPercent.toFixed(1) + '%).';
        } else {
            badgeEl.className = 'badge bg-success rounded-pill px-3 py-2';
            badgeEl.textContent = 'Margin Sehat (>= 40%)';
            recEl.innerHTML = '<span class="text-success fw-bold">Sangat Baik:</span> Margin profit di atas 40% (' + marginPercent.toFixed(1) + '%) sangat ideal untuk menutupi operasional & profit bisnis.';
        }
    }
}
</script>
