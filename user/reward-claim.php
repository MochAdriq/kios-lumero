<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__ . '/../config/loyalty.php';

$memberId = (int)($_SESSION['member_id'] ?? 0);
if ($memberId <= 0) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, p.name as prize_name 
    FROM reward_claims c 
    JOIN event_prizes p ON c.prize_id = p.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC LIMIT 1
");
$stmt->execute([$memberId]);
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

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
