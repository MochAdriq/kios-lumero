<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';
require_once __DIR__ . '/../helpers/functions.php';


$memberId = (int)($_SESSION['member_id'] ?? 0);
if ($memberId <= 0) {
    header('Location: login.php');
    exit;
}

$claimId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($claimId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name as prize_name 
        FROM reward_claims c 
        JOIN event_prizes p ON c.prize_id = p.id 
        WHERE c.user_id = ? AND c.id = ?
        LIMIT 1
    ");
    $stmt->execute([$memberId, $claimId]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name as prize_name 
        FROM reward_claims c 
        JOIN event_prizes p ON c.prize_id = p.id 
        WHERE c.user_id = ? AND c.status = 'PENDING'
        ORDER BY c.created_at DESC LIMIT 1
    ");
    $stmt->execute([$memberId]);
}
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil info member untuk pesan WA
$memberInfo = loyalty_member_by_id($pdo, $memberId);
$memberName = htmlspecialchars(trim((string)($memberInfo['name'] ?? 'Member')));
$memberPhone = htmlspecialchars(trim((string)($memberInfo['phone'] ?? '')));

// Nomor WA toko dari settings
$storeWa = get_setting('store_whatsapp', '');
if ($storeWa === '') {
    $sysWa = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='store_whatsapp' AND setting_value != '' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($sysWa) {
        $storeWa = $sysWa;
    } else {
        $outPhone = $pdo->query("SELECT phone FROM outlets WHERE phone != '' AND phone IS NOT NULL ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($outPhone) $storeWa = $outPhone;
    }
}
$storeWaClean = preg_replace('/[^0-9]/', '', (string)$storeWa);
if ($storeWaClean !== '' && str_starts_with($storeWaClean, '0')) {
    $storeWaClean = '62' . substr($storeWaClean, 1);
}


if (!$claim) {
    header('Location: dashboard.php');
    exit;
}

if ($claim['status'] === 'PENDING' && strtotime($claim['expired_at']) < time()) {
    $pdo->prepare("UPDATE reward_claims SET status = 'EXPIRED' WHERE id = ?")->execute([$claim['id']]);
    $claim['status'] = 'EXPIRED';
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Kupon Hadiah Grand Opening</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
        :root { --red: #c41230; --ink: #0f0e0d; --cream: #fbf9f5; }
        body { background: var(--ink); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; text-align: center; padding: 40px 20px; margin: 0; }
        .ticket {
            background: #fff; color: var(--ink);
            max-width: 400px; margin: 0 auto;
            border-radius: 24px; padding: 40px 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            position: relative;
        }
        .ticket::before, .ticket::after {
            content: ''; position: absolute; top: 50%; width: 30px; height: 30px;
            background: var(--ink); border-radius: 50%; transform: translateY(-50%);
        }
        .ticket::before { left: -15px; }
        .ticket::after { right: -15px; }
        
        .qr-wrapper {
            width: 240px; height: 240px; margin: 24px auto;
            background: #fff; padding: 16px; border-radius: 16px;
            border: 2px dashed #ccc; display: flex; justify-content: center; align-items: center;
        }
        .btn {
            display: inline-block; background: var(--red); color: #fff;
            padding: 16px 32px; border-radius: 99px; text-decoration: none; font-weight: 800; margin-top: 24px;
        }
        .btn-wa {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: #25D366; color: #fff;
            padding: 16px 28px; border-radius: 99px; text-decoration: none; font-weight: 800; margin-top: 16px;
            font-size: 16px; max-width: 360px; margin-left: auto; margin-right: auto;
            box-shadow: 0 8px 20px rgba(37,211,102,0.35);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-wa:active { transform: scale(0.97); box-shadow: 0 4px 10px rgba(37,211,102,0.2); }
        .btn-wa svg { flex-shrink: 0; }

        .stamp {
            position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg);
            border: 4px solid #c41230; color: #c41230; font-size: 28px; font-weight: 900; letter-spacing: 2px;
            padding: 12px 24px; border-radius: 8px; z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <h2 style="margin-bottom: 30px; color: #ffc72c;">Grand Opening Kalibunder</h2>
    
    <div class="ticket">
        <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Tunjukkan ke Kasir</h3>
        <p style="color: #666; font-size: 14px;">Outlet Lumero Kalibunder</p>
        
        <?php if ($claim['status'] === 'PENDING'): ?>
            <div class="qr-wrapper" id="qrcode"></div>
            <div style="font-size: 20px; font-weight: 800; letter-spacing: 2px; color: var(--red);"><?= htmlspecialchars($claim['qr_code']) ?></div>
        <?php else: ?>
            <div class="qr-wrapper" style="opacity: 0.1; filter: grayscale(100%);" id="qrcode"></div>
            <div style="font-size: 20px; font-weight: 800; letter-spacing: 2px; color: #999; text-decoration: line-through;"><?= htmlspecialchars($claim['qr_code']) ?></div>
            
            <?php if ($claim['status'] === 'CLAIMED'): ?>
                <div class="stamp" style="border-color: #16a34a; color: #16a34a;">SUDAH DITUKAR</div>
            <?php else: ?>
                <div class="stamp">HANGUS</div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px dashed #eee;">
            <p style="font-size: 13px; color: #666; margin-bottom: 4px;">HADIAH ANDA</p>
            <p style="font-size: 20px; font-weight: 800; color: var(--ink);"><?= htmlspecialchars($claim['prize_name']) ?></p>
        </div>
        <?php if ($claim['status'] === 'PENDING'): ?>
            <div style="margin-top: 20px; padding: 12px; border-radius: 12px; background: #fffaf0; border: 1px dashed var(--gold);">
                <p style="margin:0 0 6px; font-size: 11px; font-weight: 800; color: var(--gold); text-transform: uppercase;">Kupon Berakhir Dalam</p>
                <div id="countdown" style="font-size: 20px; font-weight: 900; color: var(--red); font-variant-numeric: tabular-nums;">-- : -- : --</div>
            </div>
        <?php else: ?>
            <p style="font-size: 11px; color: #999; margin-top: 20px; font-weight: 600;">Berlaku sampai: <?= date('d M Y H:i', strtotime($claim['expired_at'])) ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($claim['status'] === 'PENDING'): ?>
        <div id="wa-section" style="max-width:400px; margin:20px auto 0; text-align:center;">
            <p style="color:#aaa; font-size:13px; font-weight:600; margin-bottom:12px;">Kirim konfirmasi ke Kasir via WhatsApp, lalu tunjukkan pesan yang terkirim beserta QR Code ini.</p>
            <?php 
            $prizeName = htmlspecialchars($claim['prize_name']);
            $qrCode    = htmlspecialchars($claim['qr_code']);
            $waText = rawurlencode(
                "Halo! Saya {$memberName} ingin mengambil hadiah: {$prizeName}. Kode kupon saya: {$qrCode}. Mohon konfirmasi ya Kak! 🙏"
            );
            $waLink = $storeWaClean !== '' ? "https://wa.me/{$storeWaClean}?text={$waText}" : "https://wa.me/?text={$waText}";
            ?>
            <a href="<?= $waLink ?>" target="_blank" rel="noopener" class="btn-wa">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                Kirim WhatsApp ke Kasir
            </a>
            <p style="color:#888; font-size:11px; margin-top:10px; font-weight:600;">Setelah kirim WA, tunjukkan pesan terkirim + QR di atas ke Kasir</p>
        </div>
    <?php endif; ?>

    <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
    
    <script>
        <?php if ($claim['status'] === 'PENDING'): ?>
        const expiredAt = new Date("<?= date('Y-m-d\TH:i:s', strtotime($claim['expired_at'])) ?>").getTime();
        const cdEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const distance = expiredAt - now;
            if (distance < 0) {
                clearInterval(timer);
                cdEl.innerHTML = "WAKTU HABIS";
                setTimeout(() => window.location.reload(), 2000);
                return;
            }
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            let out = "";
            if (days > 0) out += days + " Hari ";
            out += String(hours).padStart(2, '0') + "j ";
            out += String(minutes).padStart(2, '0') + "m ";
            out += String(seconds).padStart(2, '0') + "d";
            cdEl.innerHTML = out;
        }, 1000);
        <?php endif; ?>

        new QRCode(document.getElementById("qrcode"), {
            text: "<?= addslashes($claim['qr_code']) ?>",
            width: 200,
            height: 200,
            colorDark : "#0f0e0d",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
