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

echo "=== ALL TABLES IN LUMERO PROD ===\n";
$tbls = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tbls as $t) {
    echo "  $t\n";
}

echo "\n=== CATEGORY TABLE (Lumero) ===\n";
// Cari tabel yang mungkin adalah kategori produk
foreach ($tbls as $t) {
    if (stripos($t, 'categor') !== false) {
        echo "Found: $t\n";
        $stmt = $pdo->query("DESCRIBE `$t`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  {$c['Field']} | {$c['Type']}\n";
        }
        $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  Total rows: $cnt\n";
    }
}
