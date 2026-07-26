<?php
$flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
$flashError = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

require_once __DIR__ . '/../../helpers/pos_helper.php';
$preparedData = sim_pos_prepare_data($categories);
$preparedCategories = $preparedData['categories'];
$posAssets = $preparedData['assets'];
$totalVariants = $preparedData['total_variants'];
$userName = htmlspecialchars((Auth::user()['name'] ?? 'Kasir'));
$userRole = htmlspecialchars((Auth::user()['role'] ?? 'cashier'));
$outletName = htmlspecialchars(current_outlet_name());
$todayDate = date('d F Y');
$orderNo = '#ORD-' . date('Y') . '-' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
?>
<script>
window.SIM_POS_DATA = <?= json_encode(['categories'=>$preparedCategories,'assets'=>$posAssets], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
</script>
<div class="page-wrapper pos-pg-wrapper ms-0">
    <div class="content pos-design p-0">
        <?php if ($flashSuccess): ?><div class="alert alert-success sim-pos-alert mb-0 rounded-0 border-0"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="alert alert-danger sim-pos-alert mb-0 rounded-0 border-0"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>
        <?php if (!$session): ?>
            <div class="alert alert-warning sim-pos-alert mb-0 rounded-0 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><strong>Toko belum dibuka.</strong> Transaksi POS dikunci sampai toko dibuka hari ini.</span>
                <a href="<?= url('/store') ?>" class="btn btn-warning btn-sm fw-semibold">Buka Toko</a>
            </div>
        <?php endif; ?>

        <div class="row pos-wrapper g-0 <?= !$session ? 'pos-locked' : '' ?>">
            <!-- ===== LEFT: PRODUCTS AREA ===== -->
            <div class="col-md-12 col-lg-7 col-xl-8 d-flex sim-pos-main-col">
                <div class="pos-categories tabs_wrapper p-0 flex-fill sim-pos-workspace">
                    <div class="content-wrap sim-pos-content-wrap">
                        <!-- ===== MAIN CONTENT (no sidebar) ===== -->
                        <main class="tab-content-wrap sim-pos-products-panel">
                            <!-- Top bar: Category Title + Search -->
                            <div class="sim-pos-topbar">
                                <div class="sim-pos-topbar-left">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h2 id="activeCategoryLabel" class="mb-0">Semua Menu</h2>
                                        <?= function_exists('render_db_switcher') ? render_db_switcher() : '' ?>
                                    </div>
                                    <small id="visibleProductInfo" class="sim-item-count"><?= (int)$totalVariants ?> item tersedia</small>
                                </div>
                                <div class="sim-pos-topbar-right">
                                    <button class="btn btn-light btn-sm sim-sort-btn" type="button" id="flowBack" style="display:none"><?= sim_icon('ti-arrow-left', 'me-1') ?>Kembali</button>
                                    <button class="btn btn-light btn-sm sim-sort-btn" type="button" id="resetFlow"><?= sim_icon('ti-refresh-dot', 'me-1') ?>Reset</button>
                                    <div class="input-icon-start search-pos position-relative">
                                        <span class="input-icon-addon"><?= sim_icon('ti-search') ?></span>
                                        <input type="text" class="form-control" id="posSearch" placeholder="Cari produk... (⌘K)">
                                    </div>
                                </div>
                            </div>

                            <!-- Horizontal Category Tabs -->
                            <div class="sim-horizontal-cats">
                                <ul class="sim-pos-tabs" id="categoryList" role="tablist">
                                    <?php foreach ($preparedCategories as $idx => $cat): ?>
                                    <li id="cat-<?= (int)$cat['id'] ?>" class="<?= $idx===0 ? 'active' : '' ?>" data-cat="<?= (int)$cat['id'] ?>">
                                        <a href="javascript:void(0);"><img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"></a>
                                        <h6><a href="javascript:void(0);"><?= htmlspecialchars($cat['name']) ?></a></h6>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Flow bar for chicken steps -->
                            <div id="flowBar" class="sim-flow-bar mb-3"></div>
                            <div id="posMessage" class="sim-pos-message mb-3" style="display:none"></div>

                            <!-- Product Grid -->
                            <div class="pos-products sim-products-area">
                                <?php if (!$preparedCategories): ?>
                                    <div class="card border-0 shadow-sm"><div class="card-body text-center py-5">Belum ada produk aktif. Silakan cek menu <strong>Produk & Menu</strong>.</div></div>
                                <?php endif; ?>
                                <div id="productGrid" class="sim-kasir2-grid"></div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT: ORDER PANEL ===== -->
            <div class="col-md-12 col-lg-5 col-xl-4 ps-0 d-lg-flex sim-pos-order-col">
                <aside class="product-order-list bg-secondary-transparent flex-fill">
                    <div class="card sim-order-card">
                        <div class="card-body">
                            <!-- Order Header -->
                            <div class="sim-order-header">
                                <div class="sim-order-header-top">
                                    <div>
                                        <span class="sim-order-title">Pesanan</span>
                                        <span class="sim-order-badge" id="itemCount">0</span>
                                    </div>
                                    <a class="sim-order-clear" href="javascript:void(0);" id="clearCart"><?= sim_icon('ti-trash') ?> Hapus</a>
                                </div>
                                <div class="sim-order-meta">
                                    <div>
                                        <small>Order</small>
                                        <strong id="draftOrderNo"><?= $orderNo ?></strong>
                                    </div>
                                    <div class="sim-order-type-toggle">
                                        <select class="form-select form-select-sm" id="customerType">
                                            <option value="takeaway" selected>Take Away</option>
                                            <option value="dine_in">Dine In</option>
                                            <option value="online">Online</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="product-added block-section">
                                <div class="product-wrap">
                                    <div class="empty-cart" id="emptyCart">
                                        <div class="sim-empty-cart-icon"><?= sim_icon('ti-shopping-cart') ?></div>
                                        <p class="fw-bold mb-1">Keranjang kosong</p>
                                        <small>Pilih produk untuk menambahkan</small>
                                    </div>
                                    <div class="sim-cart-list" id="cartTableWrap" style="display:none">
                                        <div id="cartRows"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Summary -->
                            <form action="<?= url('/pos/checkout') ?>" method="post" id="checkoutForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cart" id="cartJson"><input type="hidden" name="payment_method" id="paymentMethod" value="cash"><input type="hidden" name="order_source" id="orderSource" value="cashier"><input type="hidden" name="order_type" id="orderType" value="takeaway">
                                <div class="sim-summary-section">
                                    <div class="sim-summary-row"><span>Subtotal</span><span id="subtotalText">Rp 0</span></div>
                                    <div class="sim-summary-row sim-summary-discount"><span>Diskon</span><span class="text-danger">- <input type="number" class="form-control form-control-sm text-end sim-summary-input" name="discount_amount" id="discountAmount" value="0" min="0" step="500"></span></div>
                                    <div class="sim-summary-row sim-summary-total"><span>Total</span><strong id="totalText">Rp 0</strong></div>
                                </div>
                                <textarea name="notes" class="form-control mt-2 sim-notes-input" rows="1" placeholder="Catatan order..."></textarea>

                                <!-- Payment Methods -->
                                <div class="sim-pay-section">
                                    <div class="row align-items-center methods g-2 sim-pay-methods">
                                        <div class="col-6 d-flex"><a href="javascript:void(0);" class="payment-item active d-flex align-items-center justify-content-center p-2 flex-fill" data-pay="cash"><?= sim_icon('ti-cash-banknote', 'me-1') ?><p class="fs-12 fw-medium mb-0">Cash</p></a></div>
                                        <div class="col-6 d-flex"><a href="javascript:void(0);" class="payment-item d-flex align-items-center justify-content-center p-2 flex-fill" data-pay="qris"><?= sim_icon('ti-qrcode', 'me-1') ?><p class="fs-12 fw-medium mb-0">QRIS</p></a></div>
                                    </div>
                                </div>

                                <!-- QRIS Display Box -->
                                <div class="sim-qris-section text-center p-3 mt-2 border rounded bg-light" id="simQrisBox" style="display: none;">
                                    <?php $posQris = function_exists('get_setting') ? get_setting('payment_qris_image', '') : ''; ?>
                                    <?php if ($posQris !== ''): ?>
                                        <img src="<?= url('/' . $posQris, false) ?>" alt="QRIS Toko" class="img-fluid rounded border bg-white p-2 mb-2" style="max-height: 200px;">
                                    <?php else: ?>
                                        <div class="alert alert-warning p-2 text-center" style="font-size: 14px;">Gambar QRIS belum diatur. Silakan atur di Setting Sistem.</div>
                                    <?php endif; ?>
                                    <strong class="d-block text-dark fs-13">Scan QRIS Outlet di Kasir</strong>
                                    <small class="text-muted fs-11">Persilakan pelanggan scan kode QR di atas</small>
                                </div>

                                <!-- Uang Diterima -->
                                <div class="sim-paid-section" id="simPaidSection">
                                    <div class="sim-summary-row"><span>Uang Diterima</span><input type="number" class="form-control form-control-sm text-end sim-summary-input" name="paid_amount" id="paidAmount" min="0" step="500" placeholder="50000"></div>
                                    <div class="sim-summary-row"><span>Kembalian</span><span id="changeText">Rp 0</span></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <div class="sim-checkout-btn-wrap">
                        <button type="submit" form="checkoutForm" class="btn sim-checkout-btn" <?= !$session ? 'disabled' : '' ?>>
                            <?= sim_icon('ti-shopping-cart', 'me-2') ?>
                            <div>
                                <strong>Proses Pembayaran</strong>
                                <small><span id="itemCount2">0</span> item - <span id="totalText2">Rp 0</span></small>
                            </div>
                            <?= sim_icon('ti-chevron-right') ?>
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- POS Member Interceptor Modal Popup (Ala Alfamart/Indomaret) -->
<div class="modal fade" id="simPosMemberModal" tabindex="-1" aria-labelledby="simPosMemberModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-warning text-dark py-3 px-4">
                <h6 class="modal-title mb-0 fw-bold d-flex align-items-center fs-15" id="simPosMemberModalLabel">
                    <?= sim_icon('ti-award', 'me-2') ?> Cek Member & Poin Loyalty
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white text-start">
                <p class="text-muted small mb-3">Tanyakan kepada pelanggan apakah memiliki Member Lumero / Nomor WhatsApp terdaftar sebelum menyelesaikan transaksi.</p>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Nomor WhatsApp / HP Member</label>
                    <div class="input-group">
                        <input type="text" id="memberCheckPhone" class="form-control" placeholder="Contoh: 08123456789" autocomplete="off">
                        <button class="btn btn-dark" type="button" id="btnCheckMember">Cek Member</button>
                    </div>
                </div>

                <div id="memberCheckResult" class="d-none"></div>
            </div>
            <div class="modal-footer d-flex flex-column gap-2 p-3 bg-light border-top">
                <button type="button" id="btnConfirmMemberCheckout" class="btn btn-success w-100 fw-bold py-2 d-none" style="border-radius: 10px;">
                    <?= sim_icon('ti-check', 'me-2') ?> Gunakan Member & Lanjutkan Bayar
                </button>
                <button type="button" id="btnSkipMemberCheckout" class="btn btn-outline-secondary w-100 fw-semibold py-2" style="border-radius: 10px;">
                    Lewati Tanpa Member (Cetak Struk QR)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- POS Receipt Print Modal Popup -->
<div class="modal fade" id="simPosReceiptModal" tabindex="-1" aria-labelledby="simPosReceiptModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h6 class="modal-title mb-0 fw-bold d-flex align-items-center fs-15" id="simPosReceiptModalLabel">
                    <?= sim_icon('ti-check', 'me-2 text-success') ?> Transaksi Berhasil!
                </h6>
                <span class="badge bg-success ms-auto fs-12 px-3 py-1" id="posReceiptOrderNo">ORD-000</span>
            </div>
            <div class="modal-body p-3 bg-light text-center">
                <p class="text-muted fs-12 mb-2 fw-medium">Pratinjau Struk Kasir:</p>
                <!-- Receipt Preview iFrame -->
                <iframe id="simReceiptFrame" src="" style="width: 100%; height: 480px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);"></iframe>
            </div>
            <div class="modal-footer d-flex flex-column gap-2 p-3 bg-white border-top">
                <button type="button" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center py-2 fs-14" style="border-radius: 10px;" onclick="printSimReceipt()">
                    <?= sim_icon('ti-printer', 'me-2') ?> Cetak Struk Sekarang
                </button>
                <button type="button" class="btn btn-outline-dark w-100 fw-semibold py-2 fs-14" style="border-radius: 10px;" onclick="resetPosCartAfterOrder()">
                    <?= sim_icon('ti-plus', 'me-2') ?> Order Selanjutnya (Selesai)
                </button>
            </div>
        </div>
    </div>
</div>

