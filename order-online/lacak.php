<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/free_order_helper.php';
require_once __DIR__ . '/../helpers/delivery_helper.php';

$pdo = Database::connection();
delivery_ensure_columns($pdo);

$q = trim((string)($_GET['no'] ?? $_GET['q'] ?? $_GET['order_id'] ?? ''));
$success = (int)($_GET['success'] ?? 0);

$order = null;
$items = [];

if ($q !== '') {
    // Search by pre_order_no or customer_phone
    $st = $pdo->prepare("SELECT * FROM free_orders WHERE pre_order_no = ? OR customer_phone = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$q, fo_normalize_phone($q)]);
    $order = $st->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stItem = $pdo->prepare("SELECT * FROM free_order_items WHERE free_order_id = ? ORDER BY id ASC");
        $stItem->execute([(int)$order['id']]);
        $items = $stItem->fetchAll(PDO::FETCH_ASSOC);
    }
}

$statusMap = [
    'new' => ['label' => 'Pesanan Diterima', 'color' => '#3b82f6', 'desc' => 'Pesanan Anda telah masuk dan menunggu konfirmasi kasir.'],
    'preparing' => ['label' => 'Sedang Disiapkan', 'color' => '#f59e0b', 'desc' => 'Dapur Lumero sedang menyiapkan hidangan lezat Anda.'],
    'ready' => ['label' => 'Siap Diambil/Diantar', 'color' => '#10b981', 'desc' => 'Pesanan sudah dikemas dan siap.'],
    'on_the_way' => ['label' => 'Sedang Diantar Kurir', 'color' => '#8b5cf6', 'desc' => 'Kurir internal sedang dalam perjalanan ke alamat Anda.'],
    'completed' => ['label' => 'Selesai', 'color' => '#10b981', 'desc' => 'Pesanan telah selesai dan diterima. Terima kasih!'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#ef4444', 'desc' => 'Pesanan dibatalkan.']
];

$deliveryStatusMap = [
    'preparing' => ['label' => 'Menunggu Kurir / Disiapkan', 'color' => '#f59e0b'],
    'on_the_way' => ['label' => 'Dalam Pengantaran', 'color' => '#3b82f6'],
    'delivered' => ['label' => 'Sudah Diterima', 'color' => '#10b981'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#ef4444']
];

$outletCoords = delivery_outlet_coords($pdo) ?? ['lat' => -6.9175, 'lng' => 106.9275];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lacak Pesanan — Lumero Lumero</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    :root {
      --bg: #0d0f17;
      --card: #161926;
      --card-border: rgba(255, 255, 255, 0.08);
      --primary: #ff2d55;
      --primary-hover: #e0264d;
      --gold: #fbbf24;
      --text: #f8fafc;
      --muted: #94a3b8;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; padding: 24px 16px 60px; }
    .container { max-width: 680px; margin: 0 auto; }
    .header { text-align: center; margin-bottom: 28px; }
    .header h1 { font-size: 26px; font-weight: 800; background: linear-gradient(135deg, #fff, #cbd5e1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .header p { color: var(--muted); font-size: 14px; margin-top: 4px; }
    
    .search-box { background: var(--card); border: 1px solid var(--card-border); border-radius: 16px; padding: 8px; display: flex; gap: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); margin-bottom: 28px; }
    .search-box input { flex: 1; background: transparent; border: none; padding: 12px 16px; color: #fff; font-size: 15px; outline: none; }
    .search-box button { background: var(--primary); color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .search-box button:hover { background: var(--primary-hover); transform: translateY(-1px); }

    .banner-success { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(16,185,129,0.05)); border: 1px solid rgba(16,185,129,0.3); border-radius: 16px; padding: 18px; display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
    .banner-success .icon { width: 44px; height: 44px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .banner-success h3 { font-size: 16px; font-weight: 700; color: #34d399; }
    .banner-success p { font-size: 13px; color: var(--muted); margin-top: 2px; }

    .order-card { background: var(--card); border: 1px solid var(--card-border); border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.4); margin-bottom: 24px; }
    .order-header { padding: 20px 24px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .order-no { font-size: 18px; font-weight: 800; color: var(--gold); }
    .badge { padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; }

    .order-body { padding: 24px; }
    .status-panel { background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); border-radius: 14px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; }
    .status-panel .status-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.8; } }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .info-item { background: rgba(255,255,255,0.02); padding: 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
    .info-item .label { font-size: 12px; color: var(--muted); margin-bottom: 4px; }
    .info-item .value { font-size: 14px; font-weight: 700; color: #fff; }

    .delivery-box { border: 1px solid rgba(52, 211, 153, 0.3); background: rgba(52, 211, 153, 0.05); border-radius: 16px; padding: 18px; margin-bottom: 24px; }
    .delivery-box h4 { font-size: 15px; font-weight: 700; color: #34d399; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
    .map-container { width: 100%; height: 260px; border-radius: 14px; overflow: hidden; margin-top: 14px; border: 1px solid var(--card-border); }

    .items-list { border-top: 1px solid var(--card-border); padding-top: 20px; margin-bottom: 20px; }
    .item-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed rgba(255,255,255,0.06); }
    .item-row:last-child { border-bottom: none; }
    .item-name { font-weight: 600; font-size: 14px; }
    .item-qty { color: var(--muted); font-size: 13px; margin-top: 2px; }
    .item-price { font-weight: 700; font-size: 14px; color: var(--gold); }

    .summary-section { background: rgba(0,0,0,0.25); border-radius: 14px; padding: 16px; }
    .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: var(--muted); }
    .summary-row.total { font-size: 18px; font-weight: 800; color: #fff; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--card-border); }

    .actions { display: flex; gap: 12px; justify-content: center; margin-top: 28px; }
    .btn-action { background: var(--card); border: 1px solid var(--card-border); color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action:hover { background: rgba(255,255,255,0.08); }
    .btn-primary { background: var(--primary); border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-hover); }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>Lacak Pesanan Anda</h1>
    <p>Pantau status pesanan dan posisi pengantaran secara real-time</p>
  </div>

  <form class="search-box" method="GET" action="lacak.php">
    <input type="text" name="no" placeholder="Masukkan Nomor Pesanan (cth: FO-...) atau No WhatsApp..." value="<?= fo_e($q) ?>" required>
    <button type="submit">Cari Pesanan</button>
  </form>

  <?php if ($success && $order): ?>
  <div class="banner-success">
    <div class="icon">🎉</div>
    <div>
      <h3>Pesanan Berhasil Dikirim!</h3>
      <p>Terima kasih <b><?= fo_e($order['customer_name']) ?></b>. Pesanan Anda telah tercatat dengan nomor <b><?= fo_e($order['pre_order_no']) ?></b>.</p>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($q !== '' && !$order): ?>
  <div class="order-card" style="padding: 40px; text-align: center;">
    <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Pesanan Tidak Ditemukan</h3>
    <p style="color: var(--muted); font-size: 14px;">Kami tidak dapat menemukan pesanan dengan nomor atau WhatsApp <b><?= fo_e($q) ?></b>. Periksa kembali atau hubungi kasir.</p>
  </div>
  <?php elseif ($order): 
    $stInfo = $statusMap[$order['order_status']] ?? ['label' => strtoupper($order['order_status']), 'color' => '#64748b', 'desc' => 'Status pesanan Anda.'];
    $isDelivery = ($order['pickup_type'] === 'delivery');
  ?>
  <div class="order-card">
    <div class="order-header">
      <div>
        <span style="font-size: 12px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Nomor Pesanan</span>
        <div class="order-no"><?= fo_e($order['pre_order_no']) ?></div>
      </div>
      <span class="badge" style="background: <?= fo_e($stInfo['color']) ?>25; color: <?= fo_e($stInfo['color']) ?>; border: 1px solid <?= fo_e($stInfo['color']) ?>50;">
        <?= fo_e($stInfo['label']) ?>
      </span>
    </div>

    <div class="order-body">
      <div class="status-panel">
        <div class="status-dot" style="background: <?= fo_e($stInfo['color']) ?>; box-shadow: 0 0 10px <?= fo_e($stInfo['color']) ?>;"></div>
        <div>
          <div style="font-weight: 700; font-size: 15px; color: <?= fo_e($stInfo['color']) ?>;"><?= fo_e($stInfo['label']) ?></div>
          <div style="font-size: 13px; color: var(--muted); margin-top: 2px;"><?= fo_e($stInfo['desc']) ?></div>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <div class="label">Nama Pemesan</div>
          <div class="value"><?= fo_e($order['customer_name']) ?></div>
        </div>
        <div class="info-item">
          <div class="label">Nomor WhatsApp</div>
          <div class="value"><?= fo_e($order['customer_phone']) ?></div>
        </div>
        <div class="info-item">
          <div class="label">Tipe Layanan</div>
          <div class="value"><?= $isDelivery ? '🛵 Delivery (Diantar Kurir)' : '🏠 Ambil di Outlet' ?></div>
        </div>
        <div class="info-item">
          <div class="label">Jadwal / Waktu</div>
          <div class="value"><?= fo_e($order['pickup_date']) ?>, <?= fo_e(substr($order['pickup_time'], 0, 5)) ?> WIB</div>
        </div>
      </div>

      <?php if ($isDelivery): 
        $delStatus = $deliveryStatusMap[$order['delivery_status']] ?? ['label' => 'Dalam Proses', 'color' => '#f59e0b'];
      ?>
      <div class="delivery-box">
        <h4>
          <span>🛵 Informasi Pengantaran</span>
          <span class="badge" style="background: <?= fo_e($delStatus['color']) ?>20; color: <?= fo_e($delStatus['color']) ?>;"><?= fo_e($delStatus['label']) ?></span>
        </h4>
        <div style="font-size: 13px; color: #fff; margin-bottom: 6px;"><b>Alamat:</b> <?= fo_e($order['delivery_address']) ?></div>
        <div style="font-size: 13px; color: var(--muted); display: flex; gap: 16px; flex-wrap: wrap;">
          <span><b>Jarak:</b> <?= number_format((float)$order['delivery_distance_km'], 2) ?> km</span>
          <span><b>Kurir:</b> <?= fo_e($order['delivery_courier_name'] ?: 'Kurir Internal') ?></span>
          <span><b>Ongkir:</b> <?= ((int)$order['delivery_fee'] === 0) ? 'GRATIS' : 'Rp ' . number_format((int)$order['delivery_fee'], 0, ',', '.') ?></span>
        </div>
        <?php if ((float)$order['delivery_lat'] != 0 && (float)$order['delivery_lng'] != 0): ?>
        <div id="trackMap" class="map-container"></div>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const outletLat = <?= (float)($outletCoords['lat'] ?? -6.9175) ?>;
            const outletLng = <?= (float)($outletCoords['lng'] ?? 106.9275) ?>;
            const custLat = <?= (float)$order['delivery_lat'] ?>;
            const custLng = <?= (float)$order['delivery_lng'] ?>;

            const map = L.map('trackMap');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              maxZoom: 19,
              attribution: '© OpenStreetMap'
            }).addTo(map);

            const outletIcon = L.divIcon({
              className: 'pin-outlet',
              html: '<div style="background:#ff2d55; border:2px solid #fff; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:12px;">🏠</div>',
              iconSize: [24, 24],
              iconAnchor: [12, 12]
            });
            const custIcon = L.divIcon({
              className: 'pin-cust',
              html: '<div style="background:#34d399; border:2px solid #fff; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; color:#1a1a2e; font-size:14px; font-weight:bold; box-shadow:0 2px 8px rgba(0,0,0,0.6);">📍</div>',
              iconSize: [28, 28],
              iconAnchor: [14, 14]
            });

            L.marker([outletLat, outletLng], {icon: outletIcon}).addTo(map).bindPopup('<b>Outlet Lumero</b>');
            L.marker([custLat, custLng], {icon: custIcon}).addTo(map).bindPopup('<b>Lokasi Pengantaran Anda</b>').openPopup();

            const bounds = L.latLngBounds([ [outletLat, outletLng], [custLat, custLng] ]);
            map.fitBounds(bounds, {padding: [30, 30], maxZoom: 16});

            L.polyline([ [outletLat, outletLng], [custLat, custLng] ], {color: '#34d399', weight: 3, dashArray: '6, 6'}).addTo(map);
          });
        </script>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="items-list">
        <div style="font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 12px;">Daftar Pesanan</div>
        <?php foreach ($items as $it): ?>
        <div class="item-row">
          <div>
            <div class="item-name"><?= fo_e($it['item_name']) ?></div>
            <div class="item-qty"><?= (int)$it['qty'] ?> x Rp <?= number_format((int)$it['price'], 0, ',', '.') ?></div>
          </div>
          <div class="item-price">Rp <?= number_format((int)$it['line_total'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="summary-section">
        <div class="summary-row">
          <span>Subtotal Pesanan</span>
          <span>Rp <?= number_format((int)$order['subtotal'], 0, ',', '.') ?></span>
        </div>
        <?php if ((int)$order['discount'] > 0): ?>
        <div class="summary-row">
          <span>Diskon / Bayar Poin</span>
          <span style="color: #34d399;">-Rp <?= number_format((int)$order['discount'], 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
        <?php if ($isDelivery): ?>
        <div class="summary-row">
          <span>Biaya Pengantaran (<?= number_format((float)$order['delivery_distance_km'], 2) ?> km)</span>
          <span><?= ((int)$order['delivery_fee'] === 0) ? 'GRATIS' : 'Rp ' . number_format((int)$order['delivery_fee'], 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row total">
          <span>Total Tagihan</span>
          <span style="color: var(--gold);">Rp <?= number_format((int)$order['total'], 0, ',', '.') ?></span>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="actions">
    <a href="../user/online-order.php" class="btn-action btn-primary">🛒 Buat Pesanan Baru</a>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', fo_normalize_phone($order['customer_phone'] ?? '081234567890')) ?>" target="_blank" class="btn-action">💬 Hubungi Outlet</a>
  </div>
</div>

</body>
</html>
