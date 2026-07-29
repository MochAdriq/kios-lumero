<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$phone = '0895338977816';
$stmt = $pdo->prepare("SELECT id FROM members WHERE phone = ?");
$stmt->execute([$phone]);
$memberId = $stmt->fetchColumn();

if (!$memberId) {
    die("Member not found.");
}

// Find a product prize
$stmt = $pdo->query("SELECT id FROM event_prizes WHERE prize_type = 'product' AND stock > 0 LIMIT 1");
$prizeId = $stmt->fetchColumn();

if (!$prizeId) {
    die("No product prize available.");
}

echo "Generating 2 prizes for member ID: $memberId\n";

for ($i = 0; $i < 2; $i++) {
    $qr = 'KAL-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $pdo->prepare("
        INSERT INTO reward_claims (user_id, prize_id, qr_code, status, expired_at) 
        VALUES (?, ?, ?, 'PENDING', DATE_ADD(NOW(), INTERVAL 7 DAY))
    ")->execute([$memberId, $prizeId, $qr]);
    echo "Inserted prize! QR: $qr\n";
}
echo "Done.\n";

