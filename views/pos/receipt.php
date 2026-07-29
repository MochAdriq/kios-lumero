<?php
require_once __DIR__ . '/../../config/loyalty.php';
$order = $receipt['order'];
$items = $receipt['items'];
?>
<?php if (isset($_GET['embed']) || isset($_GET['print'])): ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?= htmlspecialchars($order['order_number'] ?? '') ?></title>
</head>
<body style="background:#fff !important; margin:0; padding:10px;">
<?php endif; ?>
<style>
.paper {
    max-width: 360px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 18px 16px;
    font-family: Arial, Helvetica, sans-serif;
    color: #111;
}
.paper hr {
    border: none;
    border-top: 1px dashed #bbb;
    margin: 8px 0;
}
.paper .center { text-align: center; }
.paper .muted { color: #666; font-size: 11px; }
.paper .row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; margin-bottom: 3px; }
.paper .total { font-weight: 800; font-size: 16px; margin-top: 4px; border-top: 1px solid #ddd; padding-top: 4px; }
.paper .order-no { font-size: 24px; font-weight: 900; margin: 4px 0; color: #ef3a2d; }
.print-logo { width:54px; height:54px; object-fit:contain; margin:0 auto 6px; }

@media print {
    body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
    .sim-topbar, .sim-sidebar, .pos-header, header, aside, .no-print { display: none !important; }
    .paper { border: none !important; max-width: none !important; padding: 0 !important; }
    .print-logo { filter: grayscale(100%) contrast(1.5); }
    .order-no, .total span { color: #000 !important; }
}
</style>

<!-- HIDDEN CASH DRAWER TRIGGER (Prints nothing visually, but sends hex to thermal printer if driver supports raw text) -->
<?php if (strtolower($order['payment_method'] ?? '') === 'cash'): ?>
<div class="no-print" style="display:none;">&#27;&#112;&#0;&#25;&#250;</div>
<?php endif; ?>

<div class="paper">
    <div class="center">
        <img src="<?= asset('images/pos-products/icon-192.png') ?>" alt="Logo Lumero" class="print-logo">
        <h3 style="margin:0; font-size:15px; font-weight:900;">Lumero CHICKEN CRISPY</h3>
        <div class="muted"><?= htmlspecialchars($order['outlet_name'] ?? 'Pasekon') ?></div>
        <div class="order-no"><?= htmlspecialchars($order['order_number'] ?? '') ?></div>
    </div>
    <hr>
    <div class="row"><span>Tgl</span><span><?= htmlspecialchars($order['created_at'] ?? '') ?></span></div>
    <div class="row"><span>Kasir</span><span><?= htmlspecialchars($order['cashier_name'] ?? 'Kasir') ?></span></div>
    <div class="row"><span>Bayar</span><span><?= htmlspecialchars(strtoupper($order['payment_method'] ?? '-')) ?></span></div>
    <?php if (!empty($order['customer_phone'])): ?>
    <div class="row"><span>Member</span><span><?= htmlspecialchars(function_exists('loyalty_mask_phone') ? loyalty_mask_phone($order['customer_phone']) : substr($order['customer_phone'],0,4).'****'.substr($order['customer_phone'],-2)) ?></span></div>
    <?php endif; ?>
    <hr>
    <?php foreach ($items as $it): ?>
    <div style="margin-bottom: 6px;">
        <div style="font-weight:bold; font-size:13px; text-transform:uppercase; margin-bottom:2px;">
            <?php
                $pName = trim((string)($it['product_name_snapshot'] ?? ''));
                $vName = trim((string)($it['variant_name_snapshot'] ?? ''));
                $name = $pName;
                if ($vName !== '' && strtolower($vName) !== 'default' && $vName !== $pName) $name .= ' - ' . $vName;
                if ($name === '') $name = $vName ?: 'Item';
                echo htmlspecialchars($name);
            ?>
        </div>
        <div class="row" style="margin-bottom:0;">
            <span style="font-size:12px; font-weight:600; color:#555;"><?= number_format($it['qty'],0,',','.') ?>x <?= rupiah($it['selling_price'] ?? $it['price'] ?? 0) ?></span>
            <strong style="font-size:13px;"><?= rupiah($it['subtotal']) ?></strong>
        </div>
    </div>
    <?php endforeach; ?>
    <hr>
    <div class="row"><span>Subtotal</span><span><?= rupiah($order['subtotal']) ?></span></div>
    <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
    <div class="row"><span>Diskon</span><span>-<?= rupiah($order['discount_amount']) ?></span></div>
    <?php endif; ?>
    <div class="row total"><span>Total</span><span style="color:#ef3a2d;"><?= rupiah($order['grand_total']) ?></span></div>
    
    <?php
    $claimCode = trim((string)($order['loyalty_claim_code'] ?? ''));
    $claimPoints = (int)($order['loyalty_claim_points'] ?? max(1, floor(($order['grand_total'] ?? 0) / 1000)));
    if (empty($order['member_id']) && $claimCode !== ''):
    ?>
    <hr>
    <div class="center" style="background:#fffaf0; border:1px dashed #f1d99f; border-radius:12px; padding:12px 10px; margin:10px 0;">
        <div style="font-size:11px; font-weight:900; color:#6e5c38; letter-spacing:1px;">KODE KLAIM POIN</div>
        <div style="font-size:20px; font-weight:950; color:#111; margin:6px 0; letter-spacing:2px;"><?= htmlspecialchars($claimCode) ?></div>
        <div style="font-size:12px; font-weight:800; color:#138a43; margin-bottom:8px;">Bonus: +<?= $claimPoints ?> Poin (Berlaku 14 Hari)</div>
        <?php if (function_exists('loyalty_member_qr_url')): ?>
        <div style="margin:8px auto; display:flex; justify-content:center;">
            <img src="<?= htmlspecialchars(loyalty_member_qr_url($claimCode, 130)) ?>" alt="QR Klaim Poin" style="width:130px; height:130px; object-fit:contain; border:1px solid #ead7b6; border-radius:10px; background:#fff; padding:6px;">
        </div>
        <?php endif; ?>
        <div style="font-size:11px; color:#666; font-weight:bold; margin-top:4px;">Scan QR di atas untuk mengklaim poin member</div>
    </div>
    <?php endif; ?>

    <hr>
    <div class="center muted" style="margin-top:10px; font-weight:bold;">Terima kasih. Selamat menikmati.</div>
</div>

<?php if (!isset($_GET['embed']) && !isset($_GET['print'])): ?>
<div class="center no-print" style="margin:16px 0;">
    <button onclick="window.print()" class="btn" style="background:#ef3a2d; color:#fff; border:none; padding:10px 20px; border-radius:99px; font-weight:bold; cursor:pointer;">Cetak Struk</button>
</div>
<?php endif; ?>

<?php if (isset($_GET['embed']) || isset($_GET['print'])): ?>
</body>
</html>
<?php endif; ?>
