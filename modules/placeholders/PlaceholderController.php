<?php
class PlaceholderController extends Controller
{
    private array $modules = [
        'pos' => ['POS Kasir', 'ti ti-cash-register', 'cashier', 'Antarmuka kasir, keranjang, cetak struk, dan pemotongan stok otomatis.', ['super_admin','administrator','cashier']],
        'orders' => ['Order & Transaksi', 'ti ti-receipt', 'cashier', 'Daftar transaksi, status order, paid/unpaid, void, refund, dan detail item.', ['super_admin','administrator','cashier']],
        'payments' => ['Verifikasi Payment', 'ti ti-qrcode', 'cashier', 'Kontrol pembayaran QRIS/e-wallet, verifikasi manual kasir, dan payment gateway log.', ['super_admin','administrator','cashier']],
        'daily-stock' => ['Stock Harian', 'ti ti-building-store', 'cashier', 'Stok awal, masuk dari dapur, terjual otomatis, wastage, dan stok akhir.', ['super_admin','administrator','cashier']],
        'purchases' => ['Input Belanja', 'ti ti-shopping-cart-plus', 'admin', 'Pencatatan pembelian bahan baku, moving average cost, hutang vendor, dan due date.', ['super_admin','administrator']],
        'expenses' => ['Pengeluaran Operasional', 'ti ti-wallet', 'admin', 'Input listrik, kebersihan, gaji tambahan, marketing, dan biaya tak terduga.', ['super_admin','administrator']],
        'categories' => ['Kategori & Variant', 'ti ti-category-2', 'admin', 'Manajemen kategori produk, kategori bahan, satuan, variant rasa, dan channel pricing.', ['super_admin','administrator']],
        'vendors' => ['Vendor / Supplier', 'ti ti-truck-delivery', 'admin', 'Database supplier, kontak vendor, termin pembayaran, dan histori belanja.', ['super_admin','administrator']],
        'reports-daily' => ['Laporan Harian', 'ti ti-report-analytics', 'owner', 'End-of-day closing, omzet, HPP, laba bersih, payment breakdown, dan cash drawer.', ['super_admin']],
        'reports-financial' => ['Laporan Keuangan', 'ti ti-chart-infographic', 'owner', 'Laba rugi komprehensif, arus kas, account summary, dan export owner report.', ['super_admin']],
        'executive' => ['Executive Suite', 'ti ti-presentation-analytics', 'owner', 'ROI, BEP, target omzet, profit trend, insight strategis, dan health score bisnis.', ['super_admin']],
        'forecasting' => ['Forecast Belanja', 'ti ti-chart-line', 'admin', 'Rekomendasi belanja harian/mingguan berdasarkan target penjualan dan lead time bahan.', ['super_admin','administrator']],
        'users' => ['User & HR', 'ti ti-users', 'owner', 'User management, role access, gaji harian, staf bertugas, dan payroll otomatis.', ['super_admin']],
        'audit-logs' => ['Audit Trail', 'ti ti-history', 'owner', 'Riwayat aktivitas user, perubahan stok, perubahan harga, void order, dan perubahan data penting.', ['super_admin']],
        'settings' => ['Setting Sistem', 'ti ti-settings', 'owner', 'Profil outlet, printer, payment gateway, backup database, dan general setting.', ['super_admin']],
        'recipes' => ['Daftar Resep / BOM', 'ti ti-list-details', 'admin', 'Index recipe engine untuk semua varian produk dan perhitungan ulang HPP massal.', ['super_admin','administrator']],
    ];

    public function show(string $key): void
    {
        Auth::requireLogin();
        $module = $this->modules[$key] ?? ['Modul', 'ti ti-apps', 'admin', 'Modul sedang disiapkan.', ['super_admin']];
        Auth::requireRoles($module[4]);
        $this->view('placeholders/module', [
            'pageTitle' => $module[0],
            'moduleKey' => $key,
            'module' => $module,
        ]);
    }
}
