<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <a href="<?= url('/purchases') ?>" class="btn btn-sm btn-outline-secondary mb-2"><?= sim_icon('ti-arrow-left', 'me-1') ?> Kembali ke Riwayat</a>
        <h2 class="mb-1">Edit Belanja (PO: <?= htmlspecialchars($po['po_number']) ?>)</h2>
        <p class="mb-0 text-muted">Pengeditan ini akan mengembalikan stok sebelumnya, memasukkan stok baru, dan menghitung ulang HPP.</p>
    </div>
</div>

<div class="row g-4 mb-5 justify-content-center">
    <div class="col-lg-8">
        <div class="sim-card shadow-sm border-0">
            <h5 class="fw-bold border-bottom pb-3 mb-3"><?= sim_icon('ti-edit', 'me-1 text-primary') ?> Edit Transaksi Pembelian</h5>
            
            <form method="post" action="<?= url('/purchases/update/' . $po['id']) ?>">
                <?= csrf_field() ?>
                
                <div class="bg-light p-3 rounded mb-3 border">
                    <h6 class="small fw-bold text-secondary mb-3 text-uppercase">1. Info Transaksi</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium mb-1">Tanggal Pembelian <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" value="<?= htmlspecialchars($po['purchase_date']) ?>" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium mb-1">Vendor (Opsional)</label>
                            <select name="vendor_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Vendor --</option>
                                <?php foreach($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $po['vendor_id'] == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded mb-3 border border-primary-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h6 class="small fw-bold text-primary mb-0 text-uppercase">2. Detail Bahan</h6>
                        <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-medium rounded-pill shadow-sm" id="btn-add-item">
                            <?= sim_icon('ti-plus', 'me-1') ?>Tambah Baris
                        </button>
                    </div>
                    
                    <div id="items-container">
                        <?php foreach($po['items'] as $it): ?>
                        <div class="purchase-item-row bg-white p-3 rounded border mb-3 position-relative shadow-sm">
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-2 me-2 btn-remove-item border-0" title="Hapus bahan ini">
                                <?= sim_icon('ti-trash') ?>
                            </button>

                            <div class="mb-3 pe-4">
                                <label class="form-label small fw-medium mb-1">Pilih Bahan Baku <span class="text-danger">*</span></label>
                                <select name="raw_material_id[]" class="form-select form-select-sm item-select" required>
                                    <option value="" disabled>-- Pilih bahan --</option>
                                    <?php 
                                    $currentCategory = null;
                                    foreach($materials as $r): 
                                        $cat = $r['category_name'] ?: 'Tanpa Kategori';
                                        if ($cat !== $currentCategory):
                                            if ($currentCategory !== null) echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($cat) . '">';
                                            $currentCategory = $cat;
                                        endif;
                                    ?>
                                    <option value="<?= $r['id'] ?>" data-cost="<?= (float)$r['average_cost'] ?>" data-unit="<?= htmlspecialchars($r['unit_symbol']) ?>" <?= $it['raw_material_id'] == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']) ?> - Stok: <?= (float)$r['stock_qty'] ?> <?= htmlspecialchars($r['unit_symbol']) ?>
                                    </option>
                                    <?php endforeach; 
                                    if ($currentCategory !== null) echo '</optgroup>';
                                    ?>
                                </select>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-medium mb-1">Qty & Satuan Beli <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.0001" value="<?= (float)$it['qty'] ?>" class="form-control item-qty-input" placeholder="0" required>
                                        <select class="form-select item-unit-multiplier" style="max-width: 140px;">
                                            <option value="1">x1 (Satuan Dasar)</option>
                                            <option value="1000">x1.000 (Kg/L/Paket)</option>
                                            <option value="100">x100 (Ons/Ratusan)</option>
                                            <option value="12">x12 (Lusin)</option>
                                            <option value="24">x24 (Karton 24)</option>
                                            <option value="40">x40 (Karton 40)</option>
                                            <option value="48">x48 (Karton 48)</option>
                                            <option value="50000">x50.000 (Karung 50Kg)</option>
                                            <option value="25000">x25.000 (Karung 25Kg)</option>
                                            <option value="86">x86 (1 pack plastik besar)</option>
                                            <option value="150">x150 (1 pack plastik kecil)</option>
                                            <option value="8">x8 (1 Sachet Royco)</option>
                                            <option value="10">x10 (1 Sachet Ladaku)</option>
                                            <option value="4">x4 (1 Sachet Bawang putih bubuk)</option>
                                            <option value="19000">x19.000 (1 Galon)</option>
                                        </select>
                                        <input type="hidden" name="qty[]" value="<?= (float)$it['qty'] ?>" class="item-qty">
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Masuk ke gudang: <strong class="text-real-qty text-primary"><?= (float)$it['qty'] ?></strong> <span class="text-unit-symbol"><?= htmlspecialchars($it['unit_symbol'] ?? 'Pcs/gr') ?></span></small>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-medium mb-1">Total Harga <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted">Rp</span>
                                        <input type="number" step="0.01" class="form-control item-total" value="<?= (float)$it['total_cost'] ?>" placeholder="0" required>
                                        <input type="hidden" name="unit_cost[]" class="item-cost" value="<?= (float)$it['unit_cost'] ?>">
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Satuan Dasar: Rp <span class="text-unit-cost"><?= number_format($it['unit_cost'], 2, ',', '.') ?></span></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border border-secondary-subtle">
                        <span class="small text-muted fw-medium">Total Estimasi:</span>
                        <span class="fw-bold text-dark fs-6">Rp <span id="text-subtotal"><?= number_format($po['grand_total'], 2, ',', '.') ?></span></span>
                    </div>
                </div>

                <div class="bg-warning-subtle p-3 rounded mb-4 border border-warning-subtle">
                    <h6 class="small fw-bold text-dark mb-3 text-uppercase">3. Pembayaran</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium mb-1">Jumlah Dibayar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted">Rp</span>
                                <input type="number" step="0.01" name="paid_amount" value="<?= (float)$po['paid_amount'] ?>" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium mb-1">Jatuh Tempo</label>
                            <input type="date" name="due_date" value="<?= $po['due_date'] ?>" class="form-control form-control-sm">
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">*Kosongkan jika lunas</small>
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-medium mb-1">Catatan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan PO (opsional)..."><?= htmlspecialchars($po['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm py-2">
                    <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('items-container');
    const btnAdd = document.getElementById('btn-add-item');
    const subtotalText = document.getElementById('text-subtotal');

    function initTomSelect(el) {
        if (el.tomselect) return;
        new TomSelect(el, {
            create: false,
            sortField: false,
            placeholder: '-- Pilih bahan --'
        });
    }

    // Initialize initial selects
    container.querySelectorAll('.item-select').forEach(initTomSelect);

    // Fungsi menghitung subtotal & konversi satuan beli
    function calculateTotal() {
        let total = 0;
        container.querySelectorAll('.purchase-item-row').forEach(row => {
            const qtyInput = row.querySelector('.item-qty-input');
            const multiplier = row.querySelector('.item-unit-multiplier');
            const hiddenQty = row.querySelector('.item-qty');
            const itemTotalInput = row.querySelector('.item-total');
            
            const rawQty = parseFloat(qtyInput.value) || 0;
            const mult = parseFloat(multiplier.value) || 1;
            const realQty = rawQty * mult;
            
            hiddenQty.value = realQty;
            const realQtyEl = row.querySelector('.text-real-qty');
            if (realQtyEl) realQtyEl.textContent = realQty.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:2});
            
            const itemTotal = parseFloat(itemTotalInput.value) || 0;
            const unitCost = realQty > 0 ? (itemTotal / realQty) : 0;
            row.querySelector('.item-cost').value = unitCost;
            row.querySelector('.text-unit-cost').textContent = unitCost.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:2});
            
            total += itemTotal;
        });
        
        // Format angka subtotal
        subtotalText.textContent = total.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:2});
    }

    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty-input') || e.target.classList.contains('item-total')) {
            calculateTotal();
        }
    });

    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-unit-multiplier')) {
            calculateTotal();
        }
        if (e.target.classList.contains('item-select')) {
            const row = e.target.closest('.purchase-item-row');
            const qtyInput = row.querySelector('.item-qty-input');
            const totalInput = row.querySelector('.item-total');
            const selectedOpt = e.target.options[e.target.selectedIndex];
            
            if (selectedOpt && selectedOpt.dataset.cost) {
                const unitSymbol = selectedOpt.getAttribute('data-unit') || 'Pcs/gr';
                const unitEl = row.querySelector('.text-unit-symbol');
                if (unitEl) unitEl.textContent = unitSymbol;

                const avgCost = parseFloat(selectedOpt.dataset.cost) || 0;
                const rawQty = parseFloat(qtyInput.value) || 0;
                const mult = parseFloat(row.querySelector('.item-unit-multiplier').value) || 1;
                const realQty = rawQty * mult;

                if (realQty > 0 && !totalInput.value) {
                    totalInput.value = avgCost * realQty;
                } else if (realQty === 0) {
                    qtyInput.value = 1;
                    totalInput.value = avgCost * mult;
                }
                calculateTotal();
            }
        }
    });

    container.addEventListener('click', function(e) {
        const btnRemove = e.target.closest('.btn-remove-item');
        if (btnRemove) {
            btnRemove.closest('.purchase-item-row').remove();
            
            // Update remove button visibility
            const rows = container.querySelectorAll('.purchase-item-row');
            if (rows.length === 1) {
                rows[0].querySelector('.btn-remove-item').style.display = 'none';
            }
            
            calculateTotal();
        }
    });

    btnAdd.addEventListener('click', function() {
        const rows = container.querySelectorAll('.purchase-item-row');
        if (rows.length === 0) return;
        
        // Ensure remove buttons are visible if > 1 row
        rows.forEach(r => r.querySelector('.btn-remove-item').style.display = 'block');
        
        const firstRow = rows[0];
        const newRow = firstRow.cloneNode(true);
        
        // Reset values
        newRow.querySelector('.item-qty-input').value = '';
        newRow.querySelector('.item-qty').value = '';
        newRow.querySelector('.item-total').value = '';
        newRow.querySelector('.item-cost').value = '';
        newRow.querySelector('.text-unit-cost').textContent = '0';
        newRow.querySelector('.text-real-qty').textContent = '0';
        newRow.querySelector('.item-unit-multiplier').selectedIndex = 0;
        
        // TomSelect needs to be re-initialized on cloned element
        const select = newRow.querySelector('.item-select');
        const oldTom = newRow.querySelector('.ts-wrapper');
        if (oldTom) oldTom.remove();
        select.classList.remove('tomselected');
        select.removeAttribute('id');
        select.style.display = '';
        select.value = '';
        
        container.appendChild(newRow);
        initTomSelect(select);
        
        // Show remove button for new row
        newRow.querySelector('.btn-remove-item').style.display = 'block';
    });
    
    // Ensure remove button visibility on load
    const rows = container.querySelectorAll('.purchase-item-row');
    if (rows.length > 1) {
        rows.forEach(r => r.querySelector('.btn-remove-item').style.display = 'block');
    } else if (rows.length === 1) {
        rows[0].querySelector('.btn-remove-item').style.display = 'none';
    }
});
</script>