<?php 
// Kalkulasi total periode
$total = array_sum(array_map(fn($x) => (float)$x['grand_total'], $items ?? [])); 
?>
<?php include __DIR__.'/../shared-flash.php'; ?>

<div class="sim-hero mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <span class="sim-kicker text-primary"><?= sim_icon('ti-truck') ?> Procurement</span>
        <h2 class="mb-1">Input Belanja Bahan Baku</h2>
        <p class="mb-0 text-muted">Pembelian bahan otomatis menambah stok gudang, mencatat <em>inventory movement</em>, dan memperbarui <em>average cost</em>.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-4">
        <div class="sim-card shadow-sm border-0 sticky-top" style="top: 20px;">
            <h5 class="fw-bold border-bottom pb-3 mb-3"><?= sim_icon('ti-clipboard-plus', 'me-1 text-primary') ?> Form Pembelian Baru</h5>
            
            <form method="post">
                <?= csrf_field() ?>
                
                <div class="bg-light p-3 rounded mb-3 border">
                    <h6 class="small fw-bold text-secondary mb-3 text-uppercase">1. Info Transaksi</h6>
                    <div>
                        <label class="form-label small fw-medium mb-1">Tanggal Pembelian <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" value="<?= today() ?>" class="form-control form-control-sm" required>
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
                        <div class="purchase-item-row bg-white p-3 rounded border mb-3 position-relative shadow-sm">
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-2 me-2 btn-remove-item border-0" style="display:none;" title="Hapus bahan ini">
                                <?= sim_icon('ti-trash') ?>
                            </button>

                            <div class="mb-3 pe-4">
                                <label class="form-label small fw-medium mb-1">Pilih Bahan Baku <span class="text-danger">*</span></label>
                                <select name="raw_material_id[]" class="form-select form-select-sm item-select" required>
                                    <option value="" disabled selected>-- Pilih bahan --</option>
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
                                    <option value="<?= $r['id'] ?>" data-cost="<?= (float)$r['average_cost'] ?>">
                                        <?= htmlspecialchars($r['name']) ?> - Stok: <?= (float)$r['stock_qty'] ?> <?= htmlspecialchars($r['unit_symbol']) ?>
                                    </option>
                                    <?php endforeach; 
                                    if ($currentCategory !== null) echo '</optgroup>';
                                    ?>
                                </select>
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small fw-medium mb-1">Qty <span class="text-danger">*</span></label>
                                    <input type="number" step="0.0001" name="qty[]" class="form-control form-control-sm item-qty" placeholder="0" required>
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-medium mb-1">Total Harga <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted">Rp</span>
                                        <input type="number" step="0.01" class="form-control item-total" placeholder="0" required>
                                        <input type="hidden" name="unit_cost[]" class="item-cost">
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Satuan: Rp <span class="text-unit-cost">0</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border border-secondary-subtle">
                        <span class="small text-muted fw-medium">Total Estimasi:</span>
                        <span class="fw-bold text-dark fs-6">Rp <span id="text-subtotal">0</span></span>
                    </div>
                </div>

                <div class="bg-warning-subtle p-3 rounded mb-4 border border-warning-subtle">
                    <h6 class="small fw-bold text-dark mb-3 text-uppercase">3. Pembayaran</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-medium mb-1">Jumlah Dibayar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted">Rp</span>
                                <input type="number" step="0.01" name="paid_amount" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-medium mb-1">Jatuh Tempo</label>
                            <input type="date" name="due_date" class="form-control form-control-sm">
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">*Kosongkan jika lunas</small>
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-medium mb-1">Catatan</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan PO (opsional)..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger rounded-pill w-100 fw-bold shadow-sm py-2">
                    <?= sim_icon('ti-device-floppy', 'me-1') ?> Simpan Belanja
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="sim-card shadow-sm border-0 h-100">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-3 gap-3 border-bottom pb-3">
                <div>
                    <h5 class="mb-1 fw-bold"><?= sim_icon('ti-history', 'me-1 text-dark') ?> Riwayat Belanja</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 mt-1">
                        Total Periode: <strong><?= rupiah($total) ?></strong>
                    </span>
                </div>
                
                <form class="d-flex gap-2 bg-light p-2 rounded border">
                    <div>
                        <small class="text-muted d-block mb-1" style="font-size: 0.75rem;">Dari Tanggal</small>
                        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm border-0">
                    </div>
                    <div>
                        <small class="text-muted d-block mb-1" style="font-size: 0.75rem;">Sampai Tanggal</small>
                        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm border-0">
                    </div>
                    <div class="d-flex align-items-end">
                        <button type="submit" class="btn btn-dark btn-sm px-3 fw-medium h-100">Filter</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No PO</th>
                            <th>Vendor</th>
                            <th>Bahan Dibeli</th>
                            <th class="text-end">Total Nilai</th>
                            <th class="text-end">Hutang</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <?= sim_icon('ti-receipt', 'fs-1 text-light d-block mb-2') ?>
                                Belum ada riwayat belanja pada periode ini.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($items as $it): ?>
                            <?php 
                                $statusStr = strtolower(trim($it['payment_status']));
                                $hasDebt = (float)$it['debt_amount'] > 0;
                                
                                if ($hasDebt || $statusStr === 'hutang' || $statusStr === 'unpaid' || $statusStr === 'sebagian') {
                                    $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                } elseif ($statusStr === 'lunas' || $statusStr === 'paid') {
                                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                } else {
                                    $badgeClass = 'bg-light text-dark border';
                                }
                            ?>
                            <tr>
                                <td><span class="text-dark d-block"><?= htmlspecialchars($it['purchase_date']) ?></span></td>
                                <td><code class="text-primary bg-primary-subtle px-2 py-1 rounded"><?= htmlspecialchars($it['po_number']) ?></code></td>
                                <td><span class="fw-medium text-dark"><?= htmlspecialchars($it['vendor_name'] ?? 'Tanpa Vendor') ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars($it['item_details']) ?></small></td>
                                <td class="text-end fw-bold text-dark"><?= rupiah($it['grand_total']) ?></td>
                                <td class="text-end">
                                    <?php if ($hasDebt): ?>
                                        <span class="text-danger fw-medium"><?= rupiah($it['debt_amount']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                                        <?= htmlspecialchars($it['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('/purchases/edit/' . $it['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Belanja">
                                        <?= sim_icon('ti-edit') ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
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
    const paidInput = document.querySelector('input[name="paid_amount"]');

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

    // Fungsi menghitung subtotal
    function calculateTotal() {
        let total = 0;
        container.querySelectorAll('.purchase-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const itemTotal = parseFloat(row.querySelector('.item-total').value) || 0;
            
            // Hitung otomatis harga satuan
            const unitCost = qty > 0 ? (itemTotal / qty) : 0;
            row.querySelector('.item-cost').value = unitCost;
            row.querySelector('.text-unit-cost').textContent = unitCost.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:2});
            
            total += itemTotal;
        });
        
        // Format angka subtotal
        subtotalText.textContent = total.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:2});
        
        // Auto-fill input 'Jumlah Dibayar' jika user belum pernah mengetik manual di kolom tersebut
        if(!paidInput.dataset.manual) {
            paidInput.value = total > 0 ? total : '';
        }
    }

    // Trigger hitung ulang setiap ada input angka
    container.addEventListener('input', function(e) {
        if(e.target.classList.contains('item-qty') || e.target.classList.contains('item-total')) {
            calculateTotal();
        }
    });
    
    // Auto-fill saat memilih bahan
    container.addEventListener('change', function(e) {
        if(e.target.classList.contains('item-select')) {
            const row = e.target.closest('.purchase-item-row');
            const qtyInput = row.querySelector('.item-qty');
            const totalInput = row.querySelector('.item-total');
            const option = e.target.options[e.target.selectedIndex];
            
            if (option && option.value) {
                if (!qtyInput.value) qtyInput.value = 1;
                const cost = option.getAttribute('data-cost');
                if (cost) {
                    const qty = parseFloat(qtyInput.value) || 1;
                    totalInput.value = (parseFloat(cost) * qty);
                }
                calculateTotal();
            }
        }
    });

    // Deteksi jika user mengetik manual jumlah bayar
    paidInput.addEventListener('input', function() {
        this.dataset.manual = '1';
    });

    // Event Tambah Baris
    btnAdd.addEventListener('click', function() {
        const rows = container.querySelectorAll('.purchase-item-row');
        const firstRow = rows[0];
        const newRow = firstRow.cloneNode(true);
        
        // Remove the TomSelect wrapper from the cloned node
        const tsWrapper = newRow.querySelector('.ts-wrapper');
        if (tsWrapper) tsWrapper.remove();
        
        // Restore and reset the original select element
        const sel = newRow.querySelector('select.item-select');
        sel.className = 'form-select form-select-sm item-select'; // Reset classes
        sel.style.display = ''; // Make it visible again
        delete sel.tomselect; // Remove the property just in case
        sel.selectedIndex = 0;

        newRow.querySelectorAll('input').forEach(input => input.value = '');
        
        container.appendChild(newRow);
        initTomSelect(sel);
        updateRemoveButtons();
    });

    // Event Hapus Baris
    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-item');
        if (btn) {
            btn.closest('.purchase-item-row').remove();
            calculateTotal(); // Hitung ulang setelah dihapus
            updateRemoveButtons();
        }
    });

    // Fungsi memunculkan/menyembunyikan tombol hapus
    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.purchase-item-row');
        rows.forEach((row) => {
            const btn = row.querySelector('.btn-remove-item');
            // Tombol hapus muncul jika ada lebih dari 1 baris item
            btn.style.display = rows.length > 1 ? 'block' : 'none';
        });
    }
    
    // Inisialisasi awal
    updateRemoveButtons();
});
</script>