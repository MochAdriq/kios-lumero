<?php
class CostingDiagnosticsModel extends Model
{
    private function outletId(): int
    {
        if (function_exists('current_outlet_id')) {
            return current_outlet_id();
        }
        $user = Auth::user() ?? [];
        return (int)($user['outlet_id'] ?? 1) ?: 1;
    }

    /**
     * Run all diagnostic checks and return results.
     */
    public function runDiagnostics(): array
    {
        return [
            'products_without_recipe'  => $this->productsWithoutRecipe(),
            'products_negative_margin' => $this->productsNegativeMargin(),
            'recipe_items_zero_cost'   => $this->recipeItemsZeroCost(),
            'duplicate_recipe_items'   => $this->duplicateRecipeItems(),
            'orphaned_recipes'         => $this->orphanedRecipes(),
            'hpp_vs_selling_mismatch'  => $this->hppVsSellingMismatch(),
            'raw_materials_no_cost'    => $this->rawMaterialsNoCost(),
        ];
    }

    /**
     * Calculate overall health score 0-100.
     */
    public function healthScore(array $diagnostics): int
    {
        $totalProducts = $this->countActiveProducts();
        if ($totalProducts <= 0) return 100;

        $criticalCount = count($diagnostics['products_negative_margin'] ?? [])
                       + count($diagnostics['duplicate_recipe_items'] ?? []);
        $warningCount  = count($diagnostics['products_without_recipe'] ?? [])
                       + count($diagnostics['recipe_items_zero_cost'] ?? [])
                       + count($diagnostics['hpp_vs_selling_mismatch'] ?? [])
                       + count($diagnostics['raw_materials_no_cost'] ?? []);
        $infoCount     = count($diagnostics['orphaned_recipes'] ?? []);

        // Weighted penalty: critical = 5 pts, warning = 2 pts, info = 0.5 pts
        $penalty = ($criticalCount * 5) + ($warningCount * 2) + ($infoCount * 0.5);
        $maxPenalty = $totalProducts * 5;
        $score = max(0, min(100, 100 - ($penalty / max(1, $maxPenalty) * 100)));

        return (int)round($score);
    }

    /**
     * Summary stats for the header.
     */
    public function stats(array $diagnostics): array
    {
        $critical = count($diagnostics['products_negative_margin'] ?? [])
                  + count($diagnostics['duplicate_recipe_items'] ?? []);
        $warning  = count($diagnostics['products_without_recipe'] ?? [])
                  + count($diagnostics['recipe_items_zero_cost'] ?? [])
                  + count($diagnostics['hpp_vs_selling_mismatch'] ?? [])
                  + count($diagnostics['raw_materials_no_cost'] ?? []);
        $info     = count($diagnostics['orphaned_recipes'] ?? []);

        return [
            'total_issues' => $critical + $warning + $info,
            'critical'     => $critical,
            'warning'      => $warning,
            'info'         => $info,
        ];
    }

    // ── Diagnostic Checks ─────────────────────────────────

    /**
     * Active products that have no recipe at all.
     */
    public function productsWithoutRecipe(): array
    {
        $outletId = $this->outletId();
        $pScope  = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);

        return $this->all("
            SELECT pv.id AS variant_id, pv.sku, pv.variant_name,
                   p.name AS product_name, pv.selling_price, pv.hpp
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN recipes r ON r.product_variant_id = pv.id
            WHERE p.is_active = 1 AND pv.is_active = 1
              AND r.id IS NULL
              AND {$pScope['sql']}
              AND {$pvScope['sql']}
            ORDER BY p.name, pv.variant_name
        ", array_merge($pScope['params'], $pvScope['params']));
    }

    /**
     * Products where HPP > selling price (negative margin).
     */
    public function productsNegativeMargin(): array
    {
        $outletId = $this->outletId();
        $pScope  = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);

        return $this->all("
            SELECT pv.id AS variant_id, pv.sku, pv.variant_name,
                   p.name AS product_name, pv.selling_price, pv.hpp,
                   (pv.selling_price - pv.hpp) AS margin,
                   CASE WHEN pv.selling_price > 0 
                        THEN ROUND((pv.selling_price - pv.hpp) / pv.selling_price * 100, 2)
                        ELSE 0 END AS margin_percent
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            WHERE p.is_active = 1 AND pv.is_active = 1
              AND pv.hpp > pv.selling_price
              AND pv.selling_price > 0
              AND {$pScope['sql']}
              AND {$pvScope['sql']}
            ORDER BY (pv.selling_price - pv.hpp) ASC
        ", array_merge($pScope['params'], $pvScope['params']));
    }

    /**
     * Recipe items where the referenced material has zero cost.
     */
    public function recipeItemsZeroCost(): array
    {
        return $this->all("
            SELECT ri.id AS item_id, ri.recipe_id, ri.qty,
                   r.name AS recipe_name,
                   rm.name AS material_name, rm.sku AS material_sku,
                   rm.average_cost,
                   u.symbol AS unit_symbol
            FROM recipe_items ri
            JOIN recipes r ON r.id = ri.recipe_id
            LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
            LEFT JOIN units u ON u.id = ri.unit_id
            WHERE ri.item_type = 'raw_material'
              AND (rm.average_cost IS NULL OR rm.average_cost = 0)
              AND rm.is_active = 1
            ORDER BY r.name, rm.name
            LIMIT 100
        ");
    }

    /**
     * Duplicate materials within the same recipe.
     */
    public function duplicateRecipeItems(): array
    {
        return $this->all("
            SELECT ri.recipe_id, r.name AS recipe_name,
                   ri.raw_material_id, rm.name AS material_name,
                   COUNT(*) AS duplicate_count,
                   SUM(ri.qty) AS total_qty,
                   GROUP_CONCAT(ri.id ORDER BY ri.id) AS item_ids
            FROM recipe_items ri
            JOIN recipes r ON r.id = ri.recipe_id
            LEFT JOIN raw_materials rm ON rm.id = ri.raw_material_id
            WHERE ri.item_type = 'raw_material'
              AND ri.raw_material_id IS NOT NULL
            GROUP BY ri.recipe_id, ri.raw_material_id
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC
            LIMIT 50
        ");
    }

    /**
     * Recipes linked to inactive/deleted product variants.
     */
    public function orphanedRecipes(): array
    {
        return $this->all("
            SELECT r.id AS recipe_id, r.name AS recipe_name,
                   r.product_variant_id, r.total_hpp, r.recipe_type,
                   pv.variant_name, pv.is_active AS variant_active,
                   p.name AS product_name, p.is_active AS product_active
            FROM recipes r
            LEFT JOIN product_variants pv ON pv.id = r.product_variant_id
            LEFT JOIN products p ON p.id = pv.product_id
            WHERE r.recipe_type = 'final'
              AND r.product_variant_id IS NOT NULL
              AND (pv.id IS NULL OR pv.is_active = 0 OR p.is_active = 0)
            ORDER BY r.name
            LIMIT 50
        ");
    }

    /**
     * Variants where the recipe HPP doesn't match the stored HPP.
     */
    public function hppVsSellingMismatch(): array
    {
        $outletId = $this->outletId();
        $pScope  = outlet_scope_sql('p.outlet_id', $outletId);
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);

        return $this->all("
            SELECT pv.id AS variant_id, pv.sku, pv.variant_name,
                   p.name AS product_name,
                   pv.hpp AS variant_hpp,
                   r.total_hpp AS recipe_hpp,
                   ABS(pv.hpp - r.total_hpp) AS diff
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            JOIN recipes r ON r.product_variant_id = pv.id
            WHERE p.is_active = 1 AND pv.is_active = 1
              AND r.recipe_type = 'final'
              AND ABS(pv.hpp - r.total_hpp) > 1
              AND {$pScope['sql']}
              AND {$pvScope['sql']}
            ORDER BY ABS(pv.hpp - r.total_hpp) DESC
            LIMIT 50
        ", array_merge($pScope['params'], $pvScope['params']));
    }

    /**
     * Raw materials with stock > 0 but average_cost = 0.
     */
    public function rawMaterialsNoCost(): array
    {
        return $this->all("
            SELECT rm.id, rm.name, rm.sku, rm.stock_qty, rm.average_cost, rm.min_stock_qty,
                   rmc.name AS category_name, u.symbol AS unit_symbol
            FROM raw_materials rm
            LEFT JOIN raw_material_categories rmc ON rmc.id = rm.category_id
            LEFT JOIN units u ON u.id = rm.unit_id
            WHERE rm.is_active = 1
              AND (rm.average_cost IS NULL OR rm.average_cost = 0)
              AND rm.stock_qty > 0
            ORDER BY rm.name
            LIMIT 50
        ");
    }

    // ── Helpers ────────────────────────────────────────────

    private function countActiveProducts(): int
    {
        $outletId = $this->outletId();
        $pvScope = outlet_scope_sql('pv.outlet_id', $outletId);
        $row = $this->one("
            SELECT COUNT(*) AS cnt
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            WHERE p.is_active = 1 AND pv.is_active = 1
              AND {$pvScope['sql']}
        ", $pvScope['params']);
        return (int)($row['cnt'] ?? 0);
    }
}
