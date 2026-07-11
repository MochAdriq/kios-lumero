-- ============================================================
-- Migration: Add Sub-Recipe Support
-- Enables multi-level HPP calculation (raw_material → sub_recipe → final product)
-- ============================================================

-- 1. Recipes: add type (final vs sub_recipe), yield info
ALTER TABLE recipes 
    ADD COLUMN recipe_type ENUM('final','sub_recipe') NOT NULL DEFAULT 'final' AFTER name,
    ADD COLUMN yield_qty DECIMAL(18,4) NOT NULL DEFAULT 1.0000 AFTER recipe_type,
    ADD COLUMN yield_unit_id BIGINT UNSIGNED NULL AFTER yield_qty,
    ADD COLUMN yield_unit_label VARCHAR(50) NULL AFTER yield_unit_id,
    ADD COLUMN notes TEXT NULL AFTER is_active;

-- 2. Recipe Items: can reference raw_material OR sub_recipe
ALTER TABLE recipe_items
    ADD COLUMN item_type ENUM('raw_material','sub_recipe') NOT NULL DEFAULT 'raw_material' AFTER recipe_id,
    ADD COLUMN sub_recipe_id BIGINT UNSIGNED NULL AFTER raw_material_id;

-- 3. Index for performance
ALTER TABLE recipe_items ADD INDEX idx_sub_recipe (sub_recipe_id);
ALTER TABLE recipes ADD INDEX idx_recipe_type (recipe_type);

-- 4. Verify
SELECT 'Migration complete' AS status;
DESCRIBE recipes;
DESCRIBE recipe_items;
