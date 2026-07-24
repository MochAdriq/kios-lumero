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

// Buat wrapper dummy untuk Model yang pake $pdo kita
class DummyModel {
    protected $db;
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    protected function one(string $sql, array $params = []): ?array {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); $row = $stmt->fetch(); return $row ?: null;
    }
    protected function all(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function getRecipe(int $id): ?array {
        // duplicate from RecipeModel
        $recipe = $this->one("
            SELECT r.*, 
                   pv.variant_name, pv.sku, pv.selling_price, pv.hpp variant_hpp,
                   p.name product_name,
                   u.name as yield_unit_label
            FROM recipes r
            LEFT JOIN product_variants pv ON pv.id = r.product_variant_id
            LEFT JOIN products p ON p.id = pv.product_id
            LEFT JOIN units u ON u.id = r.yield_unit_id
            WHERE r.id = ?
        ", [$id]);
        if (!$recipe) return null;
        $recipe['items'] = $this->all("
            SELECT ri.*, 
                   u.symbol unit_symbol,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN rm.name 
                       ELSE sr.name 
                   END as material_name,
                   CASE 
                       WHEN ri.item_type = 'raw_material' THEN COALESCE(NULLIF(orm.average_cost, 0), rm.average_cost, 0)
                       ELSE (sr.total_hpp / sr.yield_qty) 
                   END as current_unit_cost
            FROM recipe_items ri
            JOIN recipes r ON r.id = ri.recipe_id
            LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
            LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND (orm.outlet_id = r.outlet_id OR (r.outlet_id IS NULL AND orm.outlet_id = 1))
            LEFT JOIN recipes sr ON sr.id = ri.sub_recipe_id
            LEFT JOIN units u ON u.id = ri.unit_id
            WHERE ri.recipe_id = ?
            ORDER BY ri.item_type, material_name
        ", [$recipe['id']]);
        return $recipe;
    }
    public function explodeBOM(int $recipeId, float $qtyMultiplier = 1.0, array &$visited = []): array {
        if (in_array($recipeId, $visited)) return []; 
        $visited[] = $recipeId;
        $recipe = $this->getRecipe($recipeId);
        if (!$recipe) { array_pop($visited); return []; }
        $ratio = $qtyMultiplier / (float)($recipe['yield_qty'] > 0 ? $recipe['yield_qty'] : 1.0);
        $bom = [];
        foreach ($recipe['items'] as $item) {
            $requiredQty = (float)$item['qty'] * $ratio;
            if ($item['item_type'] === 'raw_material') {
                $rmId = (int)$item['raw_material_id'];
                if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
                $bom[$rmId] += $requiredQty;
            } elseif ($item['item_type'] === 'sub_recipe') {
                $subBom = $this->explodeBOM((int)$item['sub_recipe_id'], $requiredQty, $visited);
                foreach ($subBom as $rmId => $subQty) {
                    if (!isset($bom[$rmId])) $bom[$rmId] = 0.0;
                    $bom[$rmId] += $subQty;
                }
            }
        }
        array_pop($visited);
        return $bom;
    }
    public function calculateMaxYield(int $productVariantId, ?int $outletId = null): float {
        $recipe = $this->one("SELECT id FROM recipes WHERE product_variant_id = ? LIMIT 1", [$productVariantId]);
        if (!$recipe) return 0.0;
        $bom = $this->explodeBOM((int)$recipe['id'], 1.0);
        if (empty($bom)) return 0.0;
        $maxYield = PHP_FLOAT_MAX;
        foreach ($bom as $rmId => $qtyNeeded) {
            if ($qtyNeeded <= 0) continue;
            $rm = inventory_get_material_stock($this->db, $rmId, $outletId);
            $stock = $rm ? (float)$rm['stock_qty'] : 0.0;
            $possible = floor($stock / $qtyNeeded);
            if ($possible < $maxYield) $maxYield = $possible;
        }
        return $maxYield === PHP_FLOAT_MAX ? 0.0 : max(0.0, $maxYield);
    }
}

$dummyRecipe = new DummyModel($pdo);
$outletId = 7;
// Ambil varian
$stmt = $pdo->query("SELECT id, variant_name FROM product_variants WHERE outlet_id = 7 LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $var) {
    echo "Variant: {$var['variant_name']} | Max Yield: " . $dummyRecipe->calculateMaxYield((int)$var['id'], $outletId) . "\n";
}
