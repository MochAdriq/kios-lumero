<?php
define('SECRET_KEY', 'klb-fix-2026');

if (($_GET['key'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Database::connection();

    // Hitung dulu berapa yang perlu difix
    $countStmt = $pdo->query("
        SELECT COUNT(*) 
        FROM payments p 
        JOIN orders o ON o.id = p.order_id
        WHERE o.payment_status = 'paid' 
          AND p.status != 'paid'
    ");
    $count = (int)$countStmt->fetchColumn();
    echo "Ditemukan {$count} payments yang perlu difix (termasuk failed/expired dll).\n\n";

    if ($count === 0) {
        echo "Tidak ada yang perlu diupdate. Selesai.\n";
        exit;
    }

    // Jalankan update
    $updated = $pdo->exec("
        UPDATE payments p
        JOIN orders o ON o.id = p.order_id
        SET 
            p.status      = 'paid',
            p.paid_at     = COALESCE(p.paid_at, o.updated_at),
            p.updated_at  = NOW()
        WHERE o.payment_status = 'paid'
          AND p.status != 'paid'
    ");

    echo "Berhasil mengupdate {$updated} baris di tabel payments.\n";
    echo "Status: DONE\n\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
