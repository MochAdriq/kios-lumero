<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark"><?= sim_icon('ti-shopping-bag', 'me-2 text-primary') ?> Penjualan Produk</h5>
                    <form method="get" action="<?= url('/product-sales') ?>" class="d-flex gap-2 align-items-center mt-2 mt-md-0">
                        <?php if (Auth::role() === 'super_admin'): ?>
                        <select name="outlet_id" class="form-select form-select-sm" style="border-radius:20px;">
                            <option value="">Semua Outlet</option>
                            <?php foreach($outlets as $out): ?>
                                <option value="<?= $out['id'] ?>" <?= $selectedOutlet == $out['id'] ? 'selected' : '' ?>><?= htmlspecialchars($out['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0" style="border-radius:20px 0 0 20px;"><?= sim_icon('ti-calendar') ?></span>
                            <input type="date" name="start_date" class="form-control border-start-0" value="<?= htmlspecialchars($startDate) ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <span class="text-muted small">s/d</span>
                        <div class="input-group input-group-sm">
                            <input type="date" name="end_date" class="form-control border-end-0" value="<?= htmlspecialchars($endDate) ?>" max="<?= date('Y-m-d') ?>">
                            <span class="input-group-text bg-light" style="border-radius:0 20px 20px 0;"><?= sim_icon('ti-calendar') ?></span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
                    </form>
                </div>

                <!-- Top 10 Chart Section -->
                <?php if (count($stats) > 0): ?>
                <div class="row mt-4 mb-5">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-4 border">
                            <h6 class="fw-bold text-muted mb-3 text-center fs-14">Top 10 Produk Terlaris (Qty)</h6>
                            <div style="height: 250px; position: relative;">
                                <canvas id="topProductsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="productSalesTable">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th class="py-3 px-3 rounded-start">NAMA PRODUK</th>
                                <th class="py-3 px-3">VARIAN</th>
                                <th class="py-3 px-3 text-center">QTY TERJUAL</th>
                                <th class="py-3 px-3 text-end">TOTAL OMZET</th>
                                <th class="py-3 px-3 text-end rounded-end">TOTAL PROFIT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grandTotalQty = 0;
                            $grandTotalRevenue = 0;
                            $grandTotalProfit = 0;
                            foreach ($stats as $row): 
                                $grandTotalQty += $row['total_qty'];
                                $grandTotalRevenue += $row['total_revenue'];
                                $grandTotalProfit += $row['total_profit'];
                            ?>
                            <tr>
                                <td class="px-3 fw-medium text-dark"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="px-3 text-muted"><?= $row['variant_name'] ? htmlspecialchars($row['variant_name']) : '-' ?></td>
                                <td class="px-3 text-center"><span class="badge bg-primary rounded-pill px-3"><?= $row['total_qty'] ?></span></td>
                                <td class="px-3 text-end fw-bold text-success"><?= rupiah($row['total_revenue']) ?></td>
                                <td class="px-3 text-end fw-bold text-info"><?= rupiah($row['total_profit']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <?= sim_icon('ti-inbox', 'fs-1 mb-2 d-block') ?>
                                    Belum ada data penjualan pada rentang tanggal tersebut.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($stats)): ?>
                        <tfoot class="bg-light fw-bold text-dark">
                            <tr>
                                <td colspan="2" class="px-3 py-3 text-end">TOTAL KESELURUHAN:</td>
                                <td class="px-3 py-3 text-center fs-5 text-primary"><?= $grandTotalQty ?></td>
                                <td class="px-3 py-3 text-end fs-5 text-success"><?= rupiah($grandTotalRevenue) ?></td>
                                <td class="px-3 py-3 text-end fs-5 text-info"><?= rupiah($grandTotalProfit) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
div.dataTables_wrapper div.dataTables_filter input { border-radius: 20px; padding: 0.375rem 1rem; }
div.dataTables_wrapper div.dataTables_length select { border-radius: 10px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Load Chart.js
    var chartScript = document.createElement('script');
    chartScript.src = "https://cdn.jsdelivr.net/npm/chart.js";
    chartScript.onload = function() {
        var ctx = document.getElementById('topProductsChart');
        if (ctx) {
            var labels = <?= $chartLabels ?>;
            var data = <?= $chartData ?>;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Qty Terjual',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#f1f5f9' } },
                        x: { grid: { display: false }, ticks: {
                            callback: function(value) {
                                let label = this.getLabelForValue(value);
                                return label.length > 15 ? label.substr(0, 15) + '...' : label;
                            }
                        } }
                    }
                }
            });
        }
    };
    document.body.appendChild(chartScript);

    // 2. Load DataTables
    var dtScript = document.createElement('script');
    dtScript.src = "https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js";
    dtScript.onload = function() {
        var dtBsScript = document.createElement('script');
        dtBsScript.src = "https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js";
        dtBsScript.onload = function() {
            $('#productSalesTable').DataTable({
                "pageLength": 25,
                "order": [], // Let SQL decide initial order
                "language": {
                    "search": "Cari produk:",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Tidak ada data tersedia",
                    "paginate": { "first": "Awal", "last": "Akhir", "next": "Selanjutnya", "previous": "Sebelumnya" }
                }
            });
        };
        document.body.appendChild(dtBsScript);
    };
    document.body.appendChild(dtScript);
});
</script>
