<?php
/**
 * fix_payments_status.php
 * ONE-TIME cleanup: sinkronkan payments.status='paid' untuk semua order
 * yang orders.payment_status='paid' tapi payments.status masih 'pending'.
 *
 * Jalankan SEKALI di browser Hostinger, lalu DELETE file ini.
 * URL: https://lokapedia.id/lumero/scratch/fix_payments_status.php?key=klb-fix-2026
 */

define('SECRET_KEY', 'klb-fix-2026');

if (($_GET['key'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    die('403 Forbidden');
}

// Bootstrap aplikasi
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
          AND p.status IN ('pending', 'waiting_verification')
    ");
    $count = (int)$countStmt->fetchColumn();
    echo "Ditemukan {$count} payments yang perlu difix.\n\n";

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
          AND p.status IN ('pending', 'waiting_verification')
    ");

    echo "Berhasil mengupdate {$updated} baris di tabel payments.\n";
    echo "Status: DONE\n\n";
    echo "PENTING: Hapus file ini setelah selesai!\n";
    echo "Path: " . __FILE__ . "\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
