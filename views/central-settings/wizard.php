<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="mb-4">
    <a href="<?= url('/central-settings') ?>" class="text-decoration-none text-muted">
        <?= sim_icon('ti-arrow-left', 'me-1') ?> Kembali ke Sentral Data
    </a>
</div>

<div class="sim-hero mb-4">
    <h2 class="mb-1"><?= sim_icon('ti-file-plus') ?> Form Penambahan Data</h2>
    <p class="mb-0 text-muted">Silakan lengkapi formulir di bawah ini untuk menambahkan data ke sistem secara langsung.</p>
</div>

<div class="sim-card shadow-sm border-0 p-4 mb-4">
    <form method="post" action="<?= url('/central-settings/wizard') ?>" enctype="multipart/form-data" id="wizardForm">
        <?= csrf_field() ?>
        
        <!-- SECTION: TIPE DATA -->
        <h5 class="fw-bold mb-4 border-bottom pb-2">Tipe Data & Detail Dasar</h5>
        
        <div class="mb-4">
            <label class="form-label fw-bold">Tipe Data <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 flex-wrap">
                <label class="btn btn-outline-primary flex-fill text-start p-3 position-relative">
                    <input type="radio" name="item_type" value="raw_material" class="position-absolute opacity-0" required onchange="toggleTypeForms()">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-2"><?= sim_icon('ti-box') ?></div>
                        <div>
                            <h6 class="fw-bold mb-1">Bahan Baku Mentah</h6>
                            <small class="text-muted">Barang gudang (misal: Tepung, Minyak)</small>
                        </div>
                    </div>
                </label>
                
                <label class="btn btn-outline-primary flex-fill text-start p-3 position-relative">
                    <input type="radio" name="item_type" value="sub_recipe" class="position-absolute opacity-0" onchange="toggleTypeForms()">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-2"><?= sim_icon('ti-components') ?></div>
                        <div>
                            <h6 class="fw-bold mb-1">Sub-Resep</h6>
                            <small class="text-muted">Bahan setengah jadi (misal: Gula Cair)</small>
                        </div>
                    </div>
                </label>
                
                <label class="btn btn-outline-primary flex-fill text-start p-3 position-relative">
                    <input type="radio" name="item_type" value="product" class="position-absolute opacity-0" onchange="toggleTypeForms()">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-2"><?= sim_icon('ti-burger') ?></div>
                        <div>
                            <h6 class="fw-bold mb-1">Produk Jual</h6>
                            <small class="text-muted">Barang di Kasir (misal: Nasi Goreng)</small>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-medium">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-lg" required placeholder="Contoh: Ayam Crispy">
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-medium">SKU / Kode (Opsional)</label>
            <input type="text" name="sku" class="form-control" placeholder="Contoh: BRG-001">
        </div>
        
        <!-- Fields for Raw Material -->
        <div id="fields_raw_material" class="d-none">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Kategori Bahan Baku</label>
                    <select name="raw_category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($rawCategories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Satuan <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" id="raw_unit_select">
                        <option value="">-- Pilih Satuan --</option>
                        <?php foreach($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['symbol']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">Minimum Stok Peringatan</label>
                <input type="number" step="0.01" name="min_stock_qty" class="form-control" value="0">
            </div>
        </div>
        
        <!-- Fields for Sub-Recipe -->
        <div id="fields_sub_recipe" class="d-none">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Hasil / Yield Qty <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="yield_qty" class="form-control" value="1" id="sub_yield_qty">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Satuan Yield <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" id="sub_unit_select">
                        <option value="">-- Pilih Satuan --</option>
                        <?php foreach($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['symbol']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        

        <!-- Fields for Product -->
        <div id="fields_product" class="d-none">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Kategori Produk</label>
                    <select name="product_category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($productCategories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">Nama Varian (Opsional)</label>
                    <input type="text" name="variant_name" class="form-control" placeholder="Default">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium">Harga Jual (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="selling_price" class="form-control form-control-lg" value="0" min="0" id="product_selling_price">
            </div>
        </div>

        <!-- SECTION: GAMBAR (Only for Product) -->
        <div id="section_image" class="d-none mt-5">
            <h5 class="fw-bold mb-4 border-bottom pb-2">Gambar Produk</h5>
            <div class="row g-4 mb-4">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label fw-medium">1. Pilih dari Galeri Existing (Default)</label>
                        <select name="image" id="wizard_image_select" class="form-select">
                            <option value="images/pos-products/original.png">Ayam Crispy (Original)</option>
                            <option value="images/pos-products/dada.png">Ayam Dada</option>
                            <option value="images/pos-products/paha-atas.png">Ayam Paha Atas</option>
                            <option value="images/pos-products/paha-bawah.png">Ayam Paha Bawah</option>
                            <option value="images/pos-products/sayap.png">Ayam Sayap</option>
                            <option value="images/pos-products/tanpa-nasi.png">Paket Tanpa Nasi</option>
                            <option value="images/pos-products/nasi.png">Nasi Putih</option>
                            <option value="images/pos-products/kentang-kriwil.png">Kentang Kriwil</option>
                            <option value="images/pos-products/kentang-dcelup.png">Kentang D'Celup</option>
                            <option value="images/pos-products/matcha.png">Minuman Matcha</option>
                            <option value="images/pos-products/kopi.png">Minuman Kopi</option>
                            <option value="images/pos-products/celup-saus.png">Celup Saus</option>
                            <option value="images/pos-products/saus.png">Saus Tambahan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-medium">2. Atau Upload File Gambar Baru (Prioritas Utama)</label>
                        <input type="file" name="image" id="wizard_image_input" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted d-block mt-1">Maksimal 2MB. Format JPG, PNG, WEBP. Jika diisi, foto upload ini yang akan digunakan.</small>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium d-block">Live Preview Gambar:</label>
                    <div class="border rounded p-3 bg-white text-center shadow-sm" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                        <img id="wizard_image_preview" src="<?= asset('images/pos-products/original.png') ?>" alt="Preview" style="max-height: 130px; max-width: 100%; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION: KOMPOSISI -->
        <div id="section_composition" class="d-none mt-5">
            <h5 class="fw-bold mb-4 border-bottom pb-2">Komposisi / Resep</h5>
            
            <div class="alert alert-info border-0 shadow-sm bg-info-subtle small mb-4">
                Tentukan bahan baku atau sub-resep apa saja yang dibutuhkan untuk membuat item ini. Sistem akan otomatis menghitung HPP berdasarkan komposisi di bawah ini. Harga Jual dan Margin HPP akan dikalkulasi dari data di sini.
            </div>

            <div class="table-responsive mb-3 border rounded shadow-sm">
                <table class="table align-middle mb-0" id="compTable">
                    <thead class="table-light">
                        <tr>
                            <th>Tipe Bahan</th>
                            <th>Nama Bahan / Sub-Resep</th>
                            <th style="width: 150px;">Qty</th>
                            <th style="width: 150px;">Satuan</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="compTableBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
            
            <div class="mb-4">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold" onclick="addCompRow()">
                    <?= sim_icon('ti-plus', 'me-1') ?> Tambah Komponen Bahan
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-5 border-top pt-4">
            <button type="submit" class="btn btn-success px-5 py-3 rounded-pill shadow-sm fw-bold fs-5">
                <?= sim_icon('ti-device-floppy', 'me-2') ?> Simpan Data
            </button>
        </div>
        
    </form>
</div>

<script>
let itemsData = [];
let unitsData = <?= json_encode($units) ?>;

// Load items via API
fetch('<?= url('/central-settings/api/items') ?>')
    .then(res => res.json())
    .then(res => {
        if(res.status === 'success') itemsData = res.data;
    });

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const targetType = urlParams.get('type');
    if (targetType) {
        const radio = document.querySelector(`input[name="item_type"][value="${targetType}"]`);
        if (radio) {
            radio.checked = true;
            toggleTypeForms();
        }
    }
});

function toggleTypeForms() {
    const type = document.querySelector('input[name="item_type"]:checked')?.value;
    
    document.getElementById('fields_raw_material').classList.add('d-none');
    document.getElementById('fields_sub_recipe').classList.add('d-none');
    document.getElementById('fields_product').classList.add('d-none');
    
    document.getElementById('section_image').classList.add('d-none');
    document.getElementById('section_composition').classList.add('d-none');
    
    document.getElementById('raw_unit_select').required = false;
    document.getElementById('sub_yield_qty').required = false;
    document.getElementById('sub_unit_select').required = false;
    document.getElementById('product_selling_price').required = false;
    
    document.getElementById('raw_unit_select').disabled = true;
    document.getElementById('sub_unit_select').disabled = true;

    if (type === 'raw_material') {
        document.getElementById('fields_raw_material').classList.remove('d-none');
        document.getElementById('raw_unit_select').required = true;
        document.getElementById('raw_unit_select').disabled = false;
    } else if (type === 'sub_recipe') {
        document.getElementById('fields_sub_recipe').classList.remove('d-none');
        document.getElementById('section_composition').classList.remove('d-none');
        
        document.getElementById('sub_yield_qty').required = true;
        document.getElementById('sub_unit_select').required = true;
        document.getElementById('sub_unit_select').disabled = false;
    } else if (type === 'product') {
        document.getElementById('fields_product').classList.remove('d-none');
        document.getElementById('section_image').classList.remove('d-none');
        document.getElementById('section_composition').classList.remove('d-none');
        
        document.getElementById('product_selling_price').required = true;
    }
}

function addCompRow() {
    const tbody = document.getElementById('compTableBody');
    const tr = document.createElement('tr');
    
    let typeSelect = `<select name="comp_item_type[]" class="form-select border-0 bg-light comp-type" required onchange="populateItems(this)">
        <option value="">-- Tipe --</option>
        <option value="raw_material">Bahan Baku</option>
        <option value="sub_recipe">Sub-Resep</option>
    </select>`;
    
    let itemSelect = `<select name="comp_item_id[]" class="form-select border-0 bg-light comp-item" required>
        <option value="">-- Pilih Tipe Dulu --</option>
    </select>`;
    
    let unitOptions = unitsData.map(u => `<option value="${u.id}">${u.name} (${u.symbol})</option>`).join('');
    let unitSelect = `<select name="comp_unit_id[]" class="form-select border-0 bg-light" required>
        <option value="">-- Satuan --</option>
        ${unitOptions}
    </select>`;

    tr.innerHTML = `
        <td>${typeSelect}</td>
        <td>${itemSelect}</td>
        <td><input type="number" name="comp_qty[]" class="form-control border-0 bg-light" required step="0.0001" min="0.0001" placeholder="Qty"></td>
        <td>${unitSelect}</td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger btn-icon border-0" onclick="this.closest('tr').remove()"><?= sim_icon('ti-trash') ?></button>
        </td>
    `;
    tbody.appendChild(tr);
}

function populateItems(selectEl) {
    const tr = selectEl.closest('tr');
    const itemSelect = tr.querySelector('.comp-item');
    const type = selectEl.value;
    
    itemSelect.innerHTML = '<option value="">-- Pilih Bahan --</option>';
    if (!type) return;
    
    itemsData.forEach(item => {
        if (item.type === type) {
            let label = item.name + (item.sku ? ` (${item.sku})` : '');
            let opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = label;
            itemSelect.appendChild(opt);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const baseAssetUrl = "<?= rtrim(asset(''), '/') ?>/";
    const selectImg = document.getElementById('wizard_image_select');
    const fileInput = document.getElementById('wizard_image_input');
    const previewImg = document.getElementById('wizard_image_preview');

    function updateWizardPreview(val) {
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
            updateWizardPreview(this.value);
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
                updateWizardPreview(selectImg.value);
            }
        });
    }
});
</script>
