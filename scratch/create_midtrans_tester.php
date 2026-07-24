<?php
/**
 * Script untuk membuat akun midtrans_tester di database PRODUCTION
 */
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

// Gunakan koneksi PRODUCTION
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Cek apakah sudah ada
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
$stmt->execute(['midtrans_tester']);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo "Akun midtrans_tester sudah ada (ID: {$existing['id']}). Mengupdate password...\n";
    $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE id = ?")
        ->execute([password_hash('Password123!', PASSWORD_DEFAULT), (int)$existing['id']]);
    echo "Password berhasil diupdate!\n";
} else {
    // Cari role_id untuk 'cashier'
    $role = $pdo->query("SELECT id FROM roles WHERE code = 'cashier' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$role) {
        // Fallback: cari role administrator
        $role = $pdo->query("SELECT id FROM roles WHERE code = 'administrator' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
    $roleId = $role ? (int)$role['id'] : 3; // fallback ke 3

    // Cari outlet_id pertama yang aktif
    $outlet = $pdo->query("SELECT id FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $outletId = $outlet ? (int)$outlet['id'] : 1;

    $pdo->prepare("
        INSERT INTO users (outlet_id, role_id, name, username, email, phone, password, daily_salary, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $outletId,
        $roleId,
        'Midtrans Tester',
        'midtrans_tester',
        'tester@midtrans.com',
        '081234567890',
        password_hash('Password123!', PASSWORD_DEFAULT),
        0,
        1,
    ]);

    echo "Akun midtrans_tester berhasil dibuat!\n";
    echo "  - Outlet ID: {$outletId}\n";
    echo "  - Role ID: {$roleId}\n";
    echo "  - Username: midtrans_tester\n";
    echo "  - Password: Password123!\n";
}

// Verifikasi
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.name, u.is_active, r.name AS role_name, o.name AS outlet_name
    FROM users u
    JOIN roles r ON r.id = u.role_id
    LEFT JOIN outlets o ON o.id = u.outlet_id
    WHERE u.username = ?
");
$stmt->execute(['midtrans_tester']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nVerifikasi:\n";
print_r($result);
