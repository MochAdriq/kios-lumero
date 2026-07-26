<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/loyalty.php';

$pdo = Database::connection();
loyalty_ensure_tables($pdo);

echo "Generating 6 valid claim codes for testing...\n";
echo "----------------------------------------------\n";

for ($i = 1; $i <= 6; $i++) {
    // 1. Create a dummy order
    $session = $pdo->query("SELECT id FROM daily_store_sessions ORDER BY id DESC LIMIT 1")->fetchColumn();
    $sessionId = $session ?: 1;
    $orderNo = 'DUMMY-' . date('YmdHis') . '-' . $i;
    $pdo->prepare("INSERT INTO orders (daily_store_session_id, order_number, payment_status, order_status, outlet_id, cashier_id) VALUES (?, ?, 'paid', 'completed', 1, 1)")
        ->execute([$sessionId, $orderNo]);
    $orderId = (int)$pdo->lastInsertId();

    // 2. Generate claim code
    $points = rand(15, 60);
    $code = loyalty_generate_claim_code($pdo);
    $pdo->prepare("INSERT INTO receipt_claims (transaction_id,claim_code,claim_points,status,expired_at) VALUES (?,?,?,'unclaimed',DATE_ADD(NOW(), INTERVAL 30 DAY))")
        ->execute([$orderId,$code,$points]);
    try{ $pdo->prepare("UPDATE orders SET loyalty_claim_code=?, loyalty_claim_points=?, loyalty_claim_status='unclaimed' WHERE id=?")->execute([$code,$points,$orderId]); }catch(Throwable $e){}

    if ($i <= 3) {
        echo "Untuk Akun LAMA ($i/3): $code (Berisi $points Poin)\n";
    } else {
        $x = $i - 3;
        echo "Untuk Akun BARU ($x/3): $code (Berisi $points Poin)\n";
    }
}
echo "----------------------------------------------\n";
echo "Selesai! Silakan gunakan kode di atas.\n";
