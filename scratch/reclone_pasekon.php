<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== MENGHAPUS SEMUA RESEP PASEKON ===\n";
$pdo->exec("DELETE ri FROM recipe_items ri JOIN recipes r ON r.id = ri.recipe_id WHERE r.outlet_id = 8");
$pdo->exec("DELETE FROM recipes WHERE outlet_id = 8");
echo "Berhasil dihapus.\n\n";

echo "=== MENJALANKAN ULANG SCRIPT CLONE ===\n";
passthru('php ' . __DIR__ . '/clone_recipes_pasekon.php');
passthru('php ' . __DIR__ . '/clone_remaining_recipes.php');
