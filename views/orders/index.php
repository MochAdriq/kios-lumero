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
            <input type="date" id="filterStartDate" class="form-control form-control-sm border-0 bg-light">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1 small text-muted">Sampai Tanggal</label>
            <input type="date" id="filterEndDate" class="form-control form-control-sm border-0 bg-light">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1 small text-muted">Status</label>
            <select id="filterStatus" class="form-select form-select-sm border-0 bg-light">
                <option value="">Semua Status</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
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
                    <th>No Order</th>
                    <th>Tanggal</th>
                    <th>Sumber</th>
                    <th>Jenis Pesanan</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                    <td data-sort="<?= strtotime($o['created_at']) ?>"><?= htmlspecialchars($o['created_at']) ?></td>
                    <td><?= htmlspecialchars($o['order_source']) ?></td>
                    <td>
                        <?php 
                        $pMethod = strtoupper(trim($o['payment_method'] ?? ''));
                        $oType = strtolower(trim($o['order_type'] ?? 'takeaway'));
                        if ($pMethod === 'POINT' || $pMethod === 'LOYALTY') {
                            echo '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-gift-fill me-1"></i>Tukar Poin</span>';
                        } elseif ($oType === 'delivery') {
                            echo '<span class="badge bg-info text-white px-2 py-1"><i class="bi bi-truck me-1"></i>Delivery / Kirim</span>';
                        } elseif ($oType === 'dine_in') {
                            echo '<span class="badge text-white px-2 py-1" style="background-color: #6f42c1;"><i class="bi bi-cup-hot-fill me-1"></i>Dine In</span>';
                        } else {
                            echo '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-bag-check-fill me-1"></i>Takeaway / Ambil</span>';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars(strtoupper($o['payment_method'])) ?></td>
                    <td>
                        <span class="badge-soft <?= $o['order_status']==='completed'?'badge-open':($o['order_status']==='cancelled'?'bg-danger text-white':'') ?>">
                            <?= htmlspecialchars($o['order_status']) ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold" data-sort="<?= $o['grand_total'] ?>"><?= rupiah($o['grand_total']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-light btn-sm rounded-pill" href="<?= url('/pos/receipt/'.$o['id']) ?>">Struk</a>
                        <button class="btn btn-primary btn-sm rounded-pill text-white ms-1" onclick="printOrderRawBT(<?= $o['id'] ?>, this)" title="Cetak ke Printer via RawBT">Cetak</button>
                        <?php if($o['order_status'] === 'processing'): ?>
                            <form action="<?= url('/orders/update-status') ?>" method="post" class="d-inline" onsubmit="return confirm('Tandai pesanan ini selesai?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-success btn-sm rounded-pill ms-1 text-white">✔ Selesai</button>
                            </form>
                        <?php endif; ?>
                        <?php if($o['order_type'] === 'delivery'): ?>
                        <a class="btn btn-info btn-sm rounded-pill text-white ms-1" href="<?= url('/delivery?q='.urlencode($o['order_number'])) ?>" title="Buka di Monitoring Kurir">Kurir</a>
                        <?php endif; ?>
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
                    
                    // Parse date "YYYY-MM-DD" from "YYYY-MM-DD HH:mm:ss"
                    var dateOnly = dateStr.trim().substring(0, 10);
                    
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
    fetch('<?= url('/api/print/rawbt.php?id=') ?>' + orderId)
        .then(r => r.json())
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
            alert('Error koneksi saat membuat link RawBT.');
        });
}
</script>
