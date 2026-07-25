<?php
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/config/loyalty.php';

$pdo = Database::connection();
loyalty_ensure_tables($pdo);

echo "<!DOCTYPE html><html><head><title>Test Claims</title><style>body{font-family:sans-serif; padding:20px;} a{display:inline-block; margin-bottom:10px; padding:10px 15px; background:#0f172a; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold;} a:hover{background:#c41230;}</style></head><body>";
echo "<h2>Klik link di bawah untuk mencoba animasi kejutan!</h2>";
echo "<p><em>Note: Buka di tab biasa untuk akun lama, dan di Incognito untuk akun baru.</em></p><hr>";

for ($i = 1; $i <= 6; $i++) {
    // 1. Get Session ID
    $session = $pdo->query("SELECT id FROM daily_store_sessions ORDER BY id DESC LIMIT 1")->fetchColumn();
    $sessionId = $session ?: 1;

    // 2. Create Dummy Order
    $orderNo = 'TEST-' . date('His') . '-' . $i;
    $pdo->prepare("INSERT INTO orders (daily_store_session_id, order_number, payment_status, order_status, outlet_id, cashier_id) VALUES (?, ?, 'paid', 'completed', 1, 1)")
        ->execute([$sessionId, $orderNo]);
    $orderId = (int)$pdo->lastInsertId();

    // 3. Generate Claim Code
    $points = rand(15, 60);
    $code = loyalty_generate_claim_code($pdo);
    $pdo->prepare("INSERT INTO receipt_claims (transaction_id,claim_code,claim_points,status,expired_at) VALUES (?,?,?,'unclaimed',DATE_ADD(NOW(), INTERVAL 30 DAY))")
        ->execute([$orderId, $code, $points]);
    try{ $pdo->prepare("UPDATE orders SET loyalty_claim_code=?, loyalty_claim_points=?, loyalty_claim_status='unclaimed' WHERE id=?")->execute([$code, $points, $orderId]); }catch(Throwable $e){}

    // 4. Generate URL
    $url = url('/member/') . '?claim=' . urlencode($code);

    if ($i <= 3) {
        if ($i === 1) echo "<h3>Untuk Akun LAMA (Skenario 1 - Spin Wheel/Chest/Target)</h3>";
        echo "<a href=\"{$url}\" target=\"_blank\">Link Tes {$i} (Berisi {$points} Poin)</a><br>";
    } else {
        $x = $i - 3;
        if ($i === 4) echo "<hr><h3>Untuk Akun BARU (Buka di Incognito!)</h3>";
        echo "<a href=\"{$url}\" target=\"_blank\">Link Tes {$x} (Berisi {$points} Poin)</a><br>";
    }
}

echo "</body></html>";
