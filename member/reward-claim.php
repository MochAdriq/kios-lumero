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
    WHERE c.user_id = ? AND c.status = 'PENDING' 
    ORDER BY c.created_at DESC LIMIT 1
");
$stmt->execute([$memberId]);
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$claim) {
    header('Location: dashboard.php');
    exit;
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
    </style>
</head>
<body>
    <h2 style="margin-bottom: 30px; color: #ffc72c;">Grand Opening Kalibunder</h2>
    
    <div class="ticket">
        <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Tunjukkan ke Kasir</h3>
        <p style="color: #666; font-size: 14px;">Outlet Lumero Kalibunder</p>
        
        <div class="qr-wrapper" id="qrcode"></div>
        <div style="font-size: 20px; font-weight: 800; letter-spacing: 2px; color: var(--red);"><?= htmlspecialchars($claim['qr_code']) ?></div>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px dashed #eee;">
            <p style="font-size: 13px; color: #666; margin-bottom: 4px;">HADIAH ANDA</p>
            <p style="font-size: 20px; font-weight: 800; color: var(--ink);"><?= htmlspecialchars($claim['prize_name']) ?></p>
        </div>
        <p style="font-size: 11px; color: #999; margin-top: 20px;">Berlaku sampai: <?= date('d M Y H:i', strtotime($claim['expired_at'])) ?></p>
    </div>
    
    <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
    
    <script>
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
