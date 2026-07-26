<?php
require_once __DIR__ . '/../helpers/functions.php';
$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Dropping all bad constraints on recipe_cost_logs...\n";
    $pdo->exec("ALTER TABLE recipe_cost_logs DROP FOREIGN KEY fk_recipe_cost_recipe");
    $pdo->exec("ALTER TABLE recipe_cost_logs DROP FOREIGN KEY fk_recipe_cost_user");
    $pdo->exec("ALTER TABLE recipe_cost_logs DROP FOREIGN KEY fk_recipe_cost_variant");
    
    echo "Adding correct constraints...\n";
    $pdo->exec("ALTER TABLE recipe_cost_logs ADD CONSTRAINT fk_recipe_cost_recipe FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE ON UPDATE CASCADE");
    $pdo->exec("ALTER TABLE recipe_cost_logs ADD CONSTRAINT fk_recipe_cost_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants (id) ON DELETE CASCADE ON UPDATE CASCADE");
    // $pdo->exec("ALTER TABLE recipe_cost_logs ADD CONSTRAINT fk_recipe_cost_user FOREIGN KEY (recalculated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE"); // I'll skip user for now just to be safe
    
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
