<?php $flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']); $flashError = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']); ?>
<?php if ($flashSuccess): ?><div class="alert alert-success rounded-4 border-0 shadow-sm"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-danger rounded-4 border-0 shadow-sm"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white rounded-4 border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 opacity-75">Total Transaksi Valid</h6>
                <h3 class="card-title mb-0 fw-bold"><?= number_format($summary['total_orders'] ?? 0, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white rounded-4 border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 opacity-75">Total Pendapatan</h6>
                <h3 class="card-title mb-0 fw-bold"><?= rupiah($summary['total_revenue'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white rounded-4 border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 opacity-75">Total Profit Kasar</h6>
                <h3 class="card-title mb-0 fw-bold"><?= rupiah($summary['total_profit'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="soft-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div><h5 class="card-title-sim">Semua Transaksi</h5></div>
        <a href="<?= url('/pos') ?>" class="btn btn-sim">Buka POS</a>
    </div>

    <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label mb-1 small text-muted">Dari Tanggal</label>
            <input type="date" id="filterStartDate" class="form-control form-control-sm border-0 bg-light" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1 small text-muted">Sampai Tanggal</label>
            <input type="date" id="filterEndDate" class="form-control form-control-sm border-0 bg-light" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1 small text-muted">Status</label>
            <select id="filterStatus" class="form-select form-select-sm border-0 bg-light">
                <option value="">Semua Status</option>
                <optgroup label="Status Pembayaran">
                    <option value="Hutang Kembalian">Hutang Kembalian</option>
                    <option value="Belum Bayar">Belum Bayar</option>
                    <option value="Lunas">Lunas</option>
                </optgroup>
                <optgroup label="Status Dapur">
                    <option value="Antre">Antre</option>
                    <option value="Dimasak">Dimasak</option>
                    <option value="Siap Saji">Siap Saji</option>
                    <option value="Selesai">Selesai</option>
                </optgroup>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-sm btn-secondary w-100" id="resetFilter">Reset Filter</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle" id="ordersTable">
            <thead>
                <tr>
                    <th>Pelanggan / No Order</th>
                    <th>Waktu</th>
                    <th>Pesanan</th>
                    <th>Status Pembayaran</th>
                    <th>Status Dapur</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>
                        <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($o['customer_name'] ?? ('Guest '.$o['order_number'])) ?></div>
                        <div class="text-muted" style="font-size: 11px;">#<?= htmlspecialchars($o['order_number']) ?> &middot; <?= htmlspecialchars($o['order_source']) ?></div>
                    </td>
                    <td data-sort="<?= strtotime($o['created_at']) ?>">
                        <span class="d-none order-date-raw"><?= date('Y-m-d', strtotime($o['created_at'])) ?></span>
                        <div class="fw-medium"><?= date('H:i', strtotime($o['created_at'])) ?></div>
                        <div class="text-muted" style="font-size: 11px;"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                    </td>
                    <td>
                        <?php 
                        $pMethod = strtoupper(trim($o['payment_method'] ?? ''));
                        $oType = strtolower(trim($o['order_type'] ?? 'takeaway'));
                        if ($pMethod === 'POINT' || $pMethod === 'LOYALTY') {
                            echo '<span class="badge bg-warning text-dark px-2 py-1 mb-1 d-inline-block"><i class="bi bi-gift-fill me-1"></i>Tukar Poin</span>';
                        } elseif ($oType === 'delivery') {
                            echo '<span class="badge bg-info text-white px-2 py-1 mb-1 d-inline-block"><i class="bi bi-truck me-1"></i>Delivery</span>';
                        } elseif ($oType === 'dine_in') {
                            echo '<span class="badge text-white px-2 py-1 mb-1 d-inline-block" style="background-color: #6f42c1;"><i class="bi bi-cup-hot-fill me-1"></i>Dine In</span>';
                        } else {
                            echo '<span class="badge bg-success text-white px-2 py-1 mb-1 d-inline-block"><i class="bi bi-bag-check-fill me-1"></i>Takeaway</span>';
                        }
                        ?>
                        <div style="font-size: 11px;" class="text-muted"><i class="bi bi-wallet2"></i> <?= htmlspecialchars(strtoupper($o['payment_method'])) ?></div>
                    </td>
                    <td>
                        <?php if (($o['payment_status'] ?? '') === 'owes_change'): ?>
                            <span class="badge bg-warning text-dark px-2 py-1">Hutang Kembalian</span>
                            <div class="text-danger fw-bold mt-1" style="font-size: 12px;">Rp <?= number_format($o['change_owed_amount'] ?? 0, 0, ',', '.') ?></div>
                        <?php elseif (($o['payment_status'] ?? '') === 'paid'): ?>
                            <span class="badge bg-success text-white px-2 py-1">Lunas</span>
                        <?php elseif (($o['payment_status'] ?? '') === 'unpaid'): ?>
                            <span class="badge bg-danger text-white px-2 py-1">Belum Bayar</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1"><?= htmlspecialchars(strtoupper($o['payment_status'] ?? '')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($o['order_status'] ?? '') === 'pending'): ?>
                            <span class="badge bg-danger text-white px-2 py-1">Antre</span>
                        <?php elseif (($o['order_status'] ?? '') === 'preparing'): ?>
                            <span class="badge bg-warning text-dark px-2 py-1">Dimasak</span>
                        <?php elseif (($o['order_status'] ?? '') === 'ready'): ?>
                            <span class="badge bg-info text-dark px-2 py-1">Siap Saji</span>
                        <?php elseif (($o['order_status'] ?? '') === 'completed'): ?>
                            <span class="badge bg-success text-white px-2 py-1">Selesai</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1"><?= htmlspecialchars(strtoupper($o['order_status'] ?? '')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold" data-sort="<?= $o['grand_total'] ?>"><?= rupiah($o['grand_total']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-light btn-sm rounded-pill mb-1" href="<?= url('/pos/receipt/'.$o['id']) ?>">Struk</a>
                        <button class="btn btn-light btn-sm rounded-pill mb-1 ms-1" onclick="printOrderRawBT(<?= $o['id'] ?>, this)" title="Cetak ke Printer via RawBT">Cetak</button>
                        
                        <div class="mt-1 d-flex gap-1 justify-content-end flex-wrap">
                            <?php if(($o['payment_status'] ?? '') === 'owes_change'): ?>
                                <form action="<?= url('/orders/update-payment') ?>" method="post" class="d-inline" onsubmit="return confirm('Lunasi hutang kembalian pesanan ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="paid">
                                    <button type="submit" class="btn btn-warning btn-sm rounded-pill text-dark" style="font-size:11px;">Lunasi Kembalian</button>
                                </form>
                            <?php endif; ?>

                            <?php if(($o['order_status'] ?? '') === 'pending'): ?>
                                <form action="<?= url('/orders/update-status') ?>" method="post" class="d-inline" onsubmit="return confirm('Tandai pesanan sedang dimasak?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="preparing">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill text-white" style="font-size:11px;">Masak</button>
                                </form>
                            <?php elseif(($o['order_status'] ?? '') === 'preparing'): ?>
                                <form action="<?= url('/orders/update-status') ?>" method="post" class="d-inline" onsubmit="return confirm('Tandai pesanan siap saji?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="ready">
                                    <button type="submit" class="btn btn-info btn-sm rounded-pill text-white" style="font-size:11px;">Siap Saji</button>
                                </form>
                            <?php elseif(($o['order_status'] ?? '') === 'ready'): ?>
                                <form action="<?= url('/orders/update-status') ?>" method="post" class="d-inline" onsubmit="return confirm('Serahkan pesanan ke pelanggan?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill text-white" style="font-size:11px;">Selesai</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if(($o['order_type'] ?? '') === 'delivery'): ?>
                                <a class="btn btn-info btn-sm rounded-pill text-white" style="font-size:11px;" href="<?= url('/delivery?q='.urlencode($o['order_number'])) ?>" title="Buka di Monitoring Kurir">Kurir</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
/* Make sure the datatable fits nicely */
div.dataTables_wrapper div.dataTables_filter input {
    border-radius: 20px;
    padding: 0.375rem 1rem;
}
div.dataTables_wrapper div.dataTables_length select {
    border-radius: 10px;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // We dynamically load DataTables so that it runs AFTER jQuery has been initialized in the footer
    var dtScript = document.createElement('script');
    dtScript.src = "https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js";
    dtScript.onload = function() {
        var dtBsScript = document.createElement('script');
        dtBsScript.src = "https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js";
        dtBsScript.onload = function() {
            // Custom filtering function for date range and status
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var min = $('#filterStartDate').val();
                    var max = $('#filterEndDate').val();
                    var statusFilter = $('#filterStatus').val().toLowerCase();
                    
                    var dateStr = data[1] || ""; // Tanggal is column index 1
                    var statusStr = data[4] || ""; // Status is column index 4
                    statusStr = statusStr.toLowerCase();
                    
                    // Extract date from hidden YYYY-MM-DD span or fallback to YYYY-MM-DD regex
                    var dateOnly = "";
                    var ymdMatch = dateStr.match(/(\d{4})-(\d{2})-(\d{2})/);
                    if (ymdMatch) {
                        dateOnly = ymdMatch[0];
                    }
                    
                    if (min && dateOnly < min) return false;
                    if (max && dateOnly > max) return false;
                    
                    if (statusFilter && statusStr.indexOf(statusFilter) === -1) {
                        return false;
                    }
                    
                    return true;
                }
            );

            var table = $('#ordersTable').DataTable({
                order: [[1, 'desc']], // Sort by Tanggal desc by default
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                    lengthMenu: "Tampilkan _MENU_ entri"
                },
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, 500, -1],
                    [10, 25, 50, 100, 500, "Semua"]
                ],
                responsive: true
            });

            // Event listeners for filters
            $('#filterStartDate, #filterEndDate, #filterStatus').on('change', function() {
                table.draw();
            });

            $('#resetFilter').on('click', function() {
                $('#filterStartDate').val('');
                $('#filterEndDate').val('');
                $('#filterStatus').val('');
                table.draw();
            });
        };
        document.body.appendChild(dtBsScript);
    };
    document.body.appendChild(dtScript);
});
</script>
<script>
function printOrderRawBT(orderId, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    fetch('<?= app_base_url() . '/api/print/rawbt.php?id=' ?>' + orderId)
        .then(async r => {
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                throw new Error("Bukan JSON: " + text.substring(0, 50));
            }
        })
        .then(d => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Cetak';
            }
            if (d.success && d.rawbt_url) {
                window.location.href = d.rawbt_url;
            } else {
                alert('Gagal membuat link printer: ' + (d.message || 'Unknown'));
            }
        })
        .catch(e => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Cetak';
            }
            alert('Error: ' + e.message);
        });
}
</script>

<!-- KDS Checklist Modal -->
<div class="modal fade" id="kdsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="kdsModalTitle">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="kdsLoading" class="text-center py-4 text-muted" style="display:none;">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div> Mengambil data...
                </div>
                <div id="kdsContent" style="display:none;">
                    <div class="mb-3 p-3 bg-light rounded-3">
                        <div class="fw-bold" id="kdsCustomerName"></div>
                        <div class="text-muted small" id="kdsOrderMeta"></div>
                    </div>
                    <h6 class="fw-bold mb-3">Daftar Menu:</h6>
                    <div id="kdsItemsList"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle row click
        $('#ordersTable tbody').on('click', 'tr', function(e) {
            // Ignore clicks on buttons/links inside the row
            if ($(e.target).closest('button, a, form').length) return;
            
            const orderIdStr = $(this).find('input[name="id"]').val() || $(this).find('a[href*="/pos/receipt/"]').attr('href').split('/').pop();
            const orderId = parseInt(orderIdStr);
            if (!orderId) return;

            const modal = new bootstrap.Modal(document.getElementById('kdsModal'));
            modal.show();

            $('#kdsContent').hide();
            $('#kdsLoading').show();

            fetch(`<?= url('/orders/details') ?>?id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    $('#kdsLoading').hide();
                    if(data.success) {
                        $('#kdsCustomerName').text(data.order.customer_name || 'Guest ' + data.order.order_number);
                        $('#kdsOrderMeta').text('#' + data.order.order_number + ' • ' + (data.order.order_type === 'dine_in' ? 'Dine In' : 'Takeaway'));
                        
                        let itemsHtml = '';
                        data.items.forEach(item => {
                            const qty = Math.floor(item.qty);
                            const fulfilled = Math.floor(item.fulfilled_qty || 0);
                            
                            itemsHtml += `
                            <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                <div>
                                    <div class="fw-medium text-dark">${item.product_name_snapshot}</div>
                                    ${item.variant_name_snapshot ? `<div class="small text-muted">${item.variant_name_snapshot}</div>` : ''}
                                    ${item.notes ? `<div class="small text-danger fst-italic">Note: ${item.notes}</div>` : ''}
                                </div>
                                <div class="d-flex gap-2">`;
                            
                            for(let i=1; i<=qty; i++) {
                                const isChecked = i <= fulfilled ? 'checked' : '';
                                itemsHtml += `
                                    <div class="form-check" style="min-height: 24px;">
                                        <input class="form-check-input kds-checkbox" type="checkbox" style="width:24px; height:24px; cursor:pointer;" 
                                            data-order-id="${data.order.id}" 
                                            data-item-id="${item.id}" 
                                            data-item-qty="${qty}"
                                            value="1" ${isChecked}>
                                    </div>`;
                            }
                            itemsHtml += `</div></div>`;
                        });
                        
                        $('#kdsItemsList').html(itemsHtml || '<div class="text-muted small">Tidak ada item</div>');
                        $('#kdsContent').show();
                    } else {
                        $('#kdsItemsList').html('<div class="text-danger small">Gagal mengambil data.</div>');
                        $('#kdsContent').show();
                    }
                })
                .catch(err => {
                    $('#kdsLoading').hide();
                    $('#kdsItemsList').html('<div class="text-danger small">Koneksi terputus.</div>');
                    $('#kdsContent').show();
                });
        });

        // Handle checkbox click
        $(document).on('change', '.kds-checkbox', function() {
            const container = $(this).closest('.d-flex.gap-2');
            const checkedCount = container.find('.kds-checkbox:checked').length;
            const orderId = $(this).data('order-id');
            const itemId = $(this).data('item-id');
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('order_id', orderId);
            formData.append('item_id', itemId);
            formData.append('fulfilled_qty', checkedCount);
            
            fetch(`<?= url('/orders/update-item-fulfillment') ?>`, {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                if(data.success && data.auto_ready) {
                    location.reload(); // Reload immediately if order becomes ready
                }
            });
        });

        // Reload page when KDS modal is closed to refresh statuses
        $('#kdsModal').on('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>
