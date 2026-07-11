-- ============================================================
-- 007: Multi-Branch Management
-- Adds dynamic branch/slug support to outlets,
-- product_branch_overrides for per-branch price/active override,
-- and business-day closing hour per outlet.
-- ============================================================

-- 1. Extend outlets table with branch management columns
-- ------------------------------------------------------------

-- slug: URL path prefix for branch (empty = root / HQ)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'slug');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE outlets ADD COLUMN slug VARCHAR(50) DEFAULT NULL AFTER outlet_code',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- is_hq: flag to mark the headquarters outlet
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'is_hq');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE outlets ADD COLUMN is_hq TINYINT(1) NOT NULL DEFAULT 0 AFTER slug',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- is_active: soft-delete flag
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'is_active');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE outlets ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- closing_hour: business day cutoff time (default 21:00)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'closing_hour');
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE outlets ADD COLUMN closing_hour TIME NOT NULL DEFAULT '21:00:00'",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- address (if missing)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'address');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE outlets ADD COLUMN address TEXT DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- phone (if missing)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND COLUMN_NAME = 'phone');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE outlets ADD COLUMN phone VARCHAR(30) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- unique index on slug (skip if already exists)
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outlets' AND INDEX_NAME = 'idx_outlets_slug');
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE outlets ADD UNIQUE KEY idx_outlets_slug (slug)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Seed existing outlets
-- ------------------------------------------------------------
UPDATE outlets SET slug = '', is_hq = 1, closing_hour = '21:00:00' WHERE id = 1 AND (slug IS NULL OR slug = '');
UPDATE outlets SET slug = 'kb', is_hq = 0, closing_hour = '21:00:00' WHERE id = 2 AND (slug IS NULL OR slug = '');

-- 3. Product branch overrides table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_branch_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    outlet_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NOT NULL,
    selling_price DECIMAL(15,2) DEFAULT NULL COMMENT 'NULL = use master price',
    hpp DECIMAL(15,2) DEFAULT NULL COMMENT 'NULL = use master HPP',
    is_active TINYINT(1) DEFAULT NULL COMMENT 'NULL = use master active flag',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_branch_variant (outlet_id, product_variant_id),
    KEY idx_pbo_variant (product_variant_id),
    CONSTRAINT fk_pbo_outlet FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pbo_variant FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
