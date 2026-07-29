<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$memberId = 1;

$prizes = [32, 34]; // Handphone, Tablet

foreach ($prizes as $prizeId) {
    $qr = 'KAL-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $pdo->prepare("
        INSERT INTO reward_claims (user_id, prize_id, qr_code, status, expired_at) 
        VALUES (?, ?, ?, 'PENDING', DATE_ADD(NOW(), INTERVAL 7 DAY))
    ")->execute([$memberId, $prizeId, $qr]);
    echo "Inserted prize $prizeId! QR: $qr\n";
}
echo "Done.\n";
