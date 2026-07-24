<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// ──── 1. Buat Outlet Khusus Midtrans ────
$existing = $pdo->query("SELECT id FROM outlets WHERE outlet_code = 'MIDTRANS'")->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $outletId = (int)$existing['id'];
    echo "Outlet Midtrans sudah ada (ID: {$outletId}). Skip.\n";
} else {
    $pdo->prepare("
        INSERT INTO outlets (company_id, outlet_code, slug, is_hq, name, type, address, phone, is_active, closing_hour, created_at, updated_at)
        VALUES (1, 'MIDTRANS', 'midtrans', 0, 'Outlet Midtrans (Verifikasi)', 'owned', 'Midtrans Verification Testing', '000000000000', 1, '23:59:00', NOW(), NOW())
    ")->execute();
    $outletId = (int)$pdo->lastInsertId();
    echo "Outlet Midtrans berhasil dibuat! (ID: {$outletId})\n";
}

// ──── 2. Update akun midtrans_tester ke Administrator + Outlet Midtrans ────
$adminRoleId = 2; // administrator

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'midtrans_tester'");
$stmt->execute();
$user_row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_row) {
    $pdo->prepare("UPDATE users SET outlet_id = ?, role_id = ?, password = ?, is_active = 1 WHERE id = ?")
        ->execute([$outletId, $adminRoleId, password_hash('Password123!', PASSWORD_DEFAULT), (int)$user_row['id']]);
    echo "Akun midtrans_tester diupdate -> Role: Administrator, Outlet: Midtrans (ID: {$outletId})\n";
} else {
    $pdo->prepare("
        INSERT INTO users (outlet_id, role_id, name, username, email, phone, password, daily_salary, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $outletId, $adminRoleId,
        'Midtrans Tester', 'midtrans_tester', 'tester@midtrans.com', '081234567890',
        password_hash('Password123!', PASSWORD_DEFAULT), 0, 1,
    ]);
    echo "Akun midtrans_tester dibuat baru -> Role: Administrator, Outlet: Midtrans (ID: {$outletId})\n";
}

// ──── 3. Verifikasi ────
echo "\n=== VERIFIKASI AKUN ===\n";
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.name, u.is_active, r.name AS role_name, r.code AS role_code, o.name AS outlet_name, o.outlet_code
    FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN outlets o ON o.id = u.outlet_id
    WHERE u.username = 'midtrans_tester'
");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n=== SEMUA OUTLETS ===\n";
$outlets = $pdo->query("SELECT id, name, outlet_code, is_hq, is_active FROM outlets ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($outlets as $o) {
    echo "  ID: {$o['id']} | Code: {$o['outlet_code']} | Name: {$o['name']} | HQ: {$o['is_hq']} | Active: {$o['is_active']}\n";
}
