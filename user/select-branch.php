<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();

if (empty($_SESSION['welcome_passed'])) {
    header('Location: welcome.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}

$stOutlets = $pdo->query("SELECT id, slug, name, outlet_code AS code, is_hq, is_active, closing_hour, address, phone, latitude, longitude FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC, name ASC");
$activeOutletsList = $stOutlets ? $stOutlets->fetchAll(PDO::FETCH_ASSOC) : [];
if (empty($activeOutletsList)) {
    $bc = app_branch_config();
    if (!empty($bc['default'])) $activeOutletsList[] = $bc['default'];
    foreach (($bc['map'] ?? []) as $b) {
        $activeOutletsList[] = $b;
    }
}
if (!empty($activeOutletsList)) {
    foreach ($activeOutletsList as &$o) {
        $st = check_outlet_operating_status((int)$o['id'], $o);
        $o['is_open'] = $st['is_open'];
        $o['open_time'] = $st['opening_time'];
        $o['close_time'] = $st['closing_time'];
        $o['status_reason'] = $st['reason'];
    }
    unset($o);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Cabang - Lumero Self-Order</title>
    <link rel="stylesheet" href="../public/assets/css/self-order-ui.css">
    <style>
        :root {
            --dp-gradient: linear-gradient(135deg, #FF2D55 0%, #FF6B00 100%);
            --dp-radius: 22px;
            --dp-surface: rgba(24, 24, 34, 0.96);
            --dp-glass-border: rgba(255, 255, 255, 0.28);
        }
        body {
            margin: 0; padding: 28px 18px; font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
            background: #09090b radial-gradient(circle at 50% 0%, rgba(255,45,85,0.22) 0%, rgba(9,9,11,1) 68%);
            color: #ffffff;
        }
        .container {
            width: 100%; max-width: 560px; margin: 0 auto;
        }
        .header-box {
            text-align: center; margin-bottom: 26px;
        }
        .header-box h1 {
            font-size: 32px; font-weight: 950; margin: 0 0 10px; letter-spacing: -0.03em; color: #FFFFFF; text-shadow: 0 4px 20px rgba(0,0,0,0.8);
        }
        .header-box p {
            font-size: 15px; color: #F3F4F6; margin: 0 auto; max-width: 480px; line-height: 1.6; font-weight: 600;
        }
        .detecting-bar {
            background: rgba(255, 199, 44, 0.15); border: 1.5px solid #FACC15; border-radius: 16px; padding: 14px 18px; margin-bottom: 24px; font-size: 14px; font-weight: 700; color: #FEF08A; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }
        .detecting-bar.success {
            background: rgba(16, 185, 129, 0.22); border-color: #34D399; color: #A7F3D0;
        }
        .detecting-bar.warn {
            background: rgba(239, 68, 68, 0.2); border-color: #F87171; color: #FECACA;
        }
        .cards-list {
            display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;
        }
        .branch-card {
            background: var(--dp-surface); border: 1.5px solid var(--dp-glass-border); border-radius: var(--dp-radius); padding: 22px; transition: all .25s cubic-bezier(.4,0,.2,1); display: flex; flex-direction: column; gap: 10px; cursor: pointer; position: relative; overflow: hidden; box-shadow: 0 14px 38px rgba(0,0,0,0.75);
        }
        .branch-card:hover {
            transform: translateY(-4px); border-color: #FF2D55; box-shadow: 0 22px 50px rgba(0,0,0,0.9), 0 0 32px rgba(255,45,85,0.3);
        }
        .badge-closest {
            display: inline-block; background: linear-gradient(135deg, #FF2D55 0%, #FF6B00 100%); color: #FFFFFF; font-size: 12px; font-weight: 900; padding: 6px 14px; border-radius: 999px; box-shadow: 0 4px 16px rgba(255,45,85,0.45); text-transform: uppercase; letter-spacing: 0.04em; align-self: flex-start;
        }
        .badge-dist {
            display: inline-block; background: rgba(255, 255, 255, 0.16); color: #FFFFFF; font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.35); align-self: flex-start;
        }
        .branch-name {
            font-size: 22px; font-weight: 950; color: #FFFFFF; margin-top: 4px; letter-spacing: -0.02em;
        }
        .branch-address {
            font-size: 14.5px; color: #E5E7EB; line-height: 1.55; font-weight: 600;
        }
        .branch-footer {
            display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 14px; border-top: 1px dashed rgba(255, 255, 255, 0.28);
        }
        .branch-hours {
            font-size: 13.5px; color: #FDE047; font-weight: 750; display: flex; align-items: center; gap: 6px;
        }
        .select-btn {
            background: var(--dp-gradient); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.3); padding: 12px 22px; border-radius: 14px; font-size: 14px; font-weight: 900; cursor: pointer; box-shadow: 0 8px 24px rgba(255,45,85,0.4); transition: transform .2s ease;
        }
        .select-btn:hover {
            transform: scale(1.04);
        }
        .back-link {
            color: #F3F4F6; font-size: 14.5px; text-decoration: underline; font-weight: 700; transition: color .2s ease;
        }
        .back-link:hover {
            color: #FF2D55;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <div style="font-size: 48px; margin-bottom: 10px;">📍</div>
        <h1>Pilih Cabang Lumero</h1>
        <p>Pilih cabang terdekat dari lokasi Anda untuk jangkauan delivery & kecepatan pelayanan maksimal.</p>
    </div>

    <div id="branchDetectingBar" class="detecting-bar">
        <span>⏳ Mengukur jarak GPS ke lokasi Anda...</span>
        <button type="button" onclick="detectBranchGPS()" style="background:transparent; border:none; color:#FFFFFF; font-weight:800; cursor:pointer; font-size:13px; text-decoration:underline;">Deteksi Ulang</button>
    </div>

    <div id="branchCardsContainer" class="cards-list">
        <!-- Rendered dynamically -->
    </div>

    <div style="text-align: center; margin-bottom: 24px;">
        <a href="welcome.php" class="back-link">&larr; Kembali ke Layar Sambutan</a>
    </div>
</div>

<script>
const outletsData = <?= json_encode($activeOutletsList, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

// 1. Ubah nama D'Celup jadi Lumero
let outlets = outletsData.map(o => {
    if (o.name) o.name = o.name.replace(/D'Celup/gi, 'Lumero');
    return o;
});

// 2. Tambah 10 dummy cabang Sukabumi
const dummies = [
    { id: 'dummy_cisaat', name: 'Lumero Cisaat', address: 'Jl. Raya Cisaat, Kab. Sukabumi', lat: '-6.9171', lng: '106.8996' },
    { id: 'dummy_cibadak', name: 'Lumero Cibadak', address: 'Jl. Raya Cibadak, Kab. Sukabumi', lat: '-6.8860', lng: '106.7865' },
    { id: 'dummy_sukaraja', name: 'Lumero Sukaraja', address: 'Jl. Raya Sukaraja, Kab. Sukabumi', lat: '-6.9175', lng: '106.9697' },
    { id: 'dummy_cikole', name: 'Lumero Cikole', address: 'Kec. Cikole, Kota Sukabumi', lat: '-6.9150', lng: '106.9270' },
    { id: 'dummy_baros', name: 'Lumero Baros', address: 'Kec. Baros, Kota Sukabumi', lat: '-6.9530', lng: '106.9280' },
    { id: 'dummy_gunungpuyuh', name: 'Lumero Gunung Puyuh', address: 'Kec. Gunung Puyuh, Kota Sukabumi', lat: '-6.9065', lng: '106.9180' },
    { id: 'dummy_citamiang', name: 'Lumero Citamiang', address: 'Kec. Citamiang, Kota Sukabumi', lat: '-6.9310', lng: '106.9250' },
    { id: 'dummy_lembursitu', name: 'Lumero Lembursitu', address: 'Kec. Lembursitu, Kota Sukabumi', lat: '-6.9580', lng: '106.9080' },
    { id: 'dummy_warudoyong', name: 'Lumero Warudoyong', address: 'Kec. Warudoyong, Kota Sukabumi', lat: '-6.9270', lng: '106.9110' },
    { id: 'dummy_cikembar', name: 'Lumero Cikembar', address: 'Kec. Cikembar, Kab. Sukabumi', lat: '-6.9650', lng: '106.7720' }
];
dummies.forEach(d => {
    outlets.push({
        id: d.id, name: d.name, address: d.address, latitude: d.lat, longitude: d.lng,
        is_open: true, open_time: '10:00', close_time: '21:00', is_dummy: true
    });
});

let currentPage = 1;
const pageSize = 5;
let lastUserLat = null;
let lastUserLng = null;

function haversineDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function detectBranchGPS() {
    const detectingBar = document.getElementById('branchDetectingBar');
    if (detectingBar) {
        detectingBar.className = 'detecting-bar';
        detectingBar.innerHTML = '<span>⏳ Mengukur jarak GPS ke lokasi Anda...</span>';
    }
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                if (detectingBar) {
                    detectingBar.className = 'detecting-bar success';
                    detectingBar.innerHTML = '<span>✅ Lokasi GPS akurat ditemukan</span><button type="button" onclick="detectBranchGPS()" style="background:transparent; border:none; color:#FFFFFF; font-weight:800; cursor:pointer; font-size:13px; text-decoration:underline;">Deteksi Ulang</button>';
                }
                renderBranchCards(userLat, userLng);
            },
            function(error) {
                if (detectingBar) {
                    detectingBar.className = 'detecting-bar warn';
                    detectingBar.innerHTML = '<span>⚠️ GPS tidak aktif / izin ditolak. Menampilkan semua cabang.</span><button type="button" onclick="detectBranchGPS()" style="background:transparent; border:none; color:#FFFFFF; font-weight:800; cursor:pointer; font-size:13px; text-decoration:underline;">Coba Lagi</button>';
                }
                renderBranchCards(null, null);
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    } else {
        if (detectingBar) {
            detectingBar.className = 'detecting-bar warn';
            detectingBar.innerHTML = '<span>⚠️ Browser Anda tidak mendukung GPS.</span>';
        }
        renderBranchCards(null, null);
    }
}

function renderBranchCards(userLat, userLng) {
    if (userLat !== undefined && userLng !== undefined) {
        lastUserLat = userLat;
        lastUserLng = userLng;
        currentPage = 1; // Reset to page 1 on new GPS detect
    } else {
        userLat = lastUserLat;
        userLng = lastUserLng;
    }
    
    const container = document.getElementById('branchCardsContainer');
    if (!container) return;

    if (outlets.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:30px; color:#E5E7EB; font-size:15px; font-weight:600;">Belum ada data cabang aktif di sistem.</div>';
        return;
    }

    outlets.forEach(o => {
        o.distance = null;
        if (userLat !== null && userLng !== null && o.latitude && o.longitude) {
            o.distance = haversineDistanceKm(userLat, userLng, Number(o.latitude), Number(o.longitude));
        }
    });

    if (userLat !== null && userLng !== null) {
        outlets.sort((a, b) => {
            if (a.distance === null && b.distance === null) return 0;
            if (a.distance === null) return 1;
            if (b.distance === null) return -1;
            return a.distance - b.distance;
        });
    }

    let html = '';
    const paginatedOutlets = outlets.slice(0, currentPage * pageSize);
    
    paginatedOutlets.forEach((o, index) => {
        const distText = o.distance !== null ? `${o.distance.toFixed(1)} km dari Anda` : `Cabang Lumero`;
        const isClosest = (userLat !== null && index === 0 && o.distance !== null && o.is_open);
        
        let badgeHtml = isClosest ? `<div class="badge-closest">🌟 REKOMENDASI TERDEKAT (${distText})</div>` : `<div class="badge-dist">📍 ${distText}</div>`;
        if (!o.is_open) {
            badgeHtml = `<div class="badge-dist" style="background:rgba(239,68,68,0.25); border-color:#F87171; color:#FECACA;">🚫 SEDANG TUTUP (${distText})</div>`;
        }
        
        const nameLower = o.name.toLowerCase();
        const isKalibunder = nameLower.includes('kalibunder');
        const isPasekon = nameLower.includes('pasekon');
        const isOnlineAllowed = isKalibunder || isPasekon;
        
        let borderStyle = isClosest ? 'border: 2px solid #FF2D55; box-shadow: 0 16px 42px rgba(255,45,85,0.3);' : '';
        if (!o.is_open) {
            borderStyle = 'opacity:0.85; border-color:rgba(239,68,68,0.3);';
        }
        
        const btnText = o.is_open ? 'Pilih Cabang Ini &rarr;' : `Tutup (${o.open_time || '10:00'}-${o.close_time || '21:00'})`;
        
        let btnStyle = '';
        if (!o.is_open) {
            btnStyle = 'background:rgba(255,255,255,0.1); color:#D1D5DB; border-color:rgba(255,255,255,0.2); box-shadow:none;';
        } else if (!isOnlineAllowed) {
            // Disabled style for branches not allowed to take online orders
            btnStyle = 'background:rgba(255,255,255,0.05); color:#9CA3AF; border-color:rgba(255,255,255,0.15); box-shadow:none;';
        }
        
        const safeName = (o.name || 'Cabang ini').replace(/'/g, "\\'");

        html += `
        <div class="branch-card" style="${borderStyle}" onclick="selectBranchItem('${o.id}', ${o.is_open ? 'true' : 'false'}, '${safeName}', '${o.open_time || '10:00'}', '${o.close_time || '21:00'}', ${isOnlineAllowed ? 'true' : 'false'})">
            <div>${badgeHtml}</div>
            <div class="branch-name">${o.name || 'Cabang Lumero'}</div>
            <div class="branch-address">${o.address || 'Alamat cabang belum dicantumkan'}</div>
            <div class="branch-footer">
                <div class="branch-hours" style="${!o.is_open ? 'color:#FCA5A5;' : ''}">🕒 Jam Buka: ${o.open_time || '10:00'} - ${o.close_time || '21:00'}</div>
                <button type="button" class="select-btn" style="${btnStyle}">${btnText}</button>
            </div>
        </div>`;
    });

    if (currentPage * pageSize < outlets.length) {
        html += `<div style="text-align:center; margin-top:16px;">
            <button type="button" onclick="loadMoreBranches()" style="background:rgba(255,255,255,0.08); color:#E5E7EB; border:1px solid rgba(255,255,255,0.2); padding:12px 24px; border-radius:99px; font-weight:700; cursor:pointer; font-size:14px; transition:all 0.2s;">Muat Lebih Banyak &darr;</button>
        </div>`;
    }

    container.innerHTML = html;
}

function loadMoreBranches() {
    currentPage++;
    renderBranchCards();
}

function selectBranchItem(outletId, isOpen, outletName, openTime, closeTime, isOnlineAllowed) {
    if (!isOpen) {
        alert(`Maaf, ${outletName} saat ini sedang tutup (Jam operasional: ${openTime} - ${closeTime} WIB).\n\nSilakan pilih cabang lain yang sedang buka atau kembali saat jam operasional berlangsung.`);
        return;
    }
    if (!isOnlineAllowed) {
        alert(`Mohon maaf, toko ini tidak menyediakan online order. Silakan pilih cabang Kalibunder atau Pasekon.`);
        return;
    }
    window.location.href = 'online-order.php?outlet_id=' + outletId;
}

document.addEventListener('DOMContentLoaded', () => {
    detectBranchGPS();
});

// Idle timeout: 120 seconds (2 minutes) of no interaction -> redirect to welcome
(function(){
  let idleTimer;
  const resetTimer = () => {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => { window.location.href = 'welcome.php?idle=1'; }, 120000);
  };
  ['mousemove','keydown','scroll','touchstart','click'].forEach(e => document.addEventListener(e, resetTimer, true));
  resetTimer();
})();
</script>
</body>
</html>
