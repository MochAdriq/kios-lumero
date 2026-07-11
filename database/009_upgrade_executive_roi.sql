-- ============================================================
-- 009: Upgrade Executive Module (Business Navigator / ROI)
-- Adds V1 business logic features: capital components detail,
-- ROI settings, cash allocation rules, trend keywords, experiments
-- ============================================================

-- 1. Enhance business_capitals with V1 detail fields (if table exists)
-- Adds category, component_name, payment_method, supplier, invoice_no, is_active
ALTER TABLE business_capitals
  ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT 'Modal Awal' AFTER capital_type,
  ADD COLUMN IF NOT EXISTS component_name VARCHAR(180) DEFAULT NULL AFTER category,
  ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS supplier VARCHAR(160) DEFAULT NULL AFTER payment_method,
  ADD COLUMN IF NOT EXISTS invoice_no VARCHAR(100) DEFAULT NULL AFTER supplier,
  ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER invoice_no,
  ADD INDEX IF NOT EXISTS idx_bc_active (is_active),
  ADD INDEX IF NOT EXISTS idx_bc_category (category);

-- Backfill component_name from description if empty
UPDATE business_capitals SET component_name = COALESCE(NULLIF(component_name,''), description, capital_type) WHERE component_name IS NULL OR component_name = '';

-- 2. Business ROI Settings (key-value store per outlet)
CREATE TABLE IF NOT EXISTS business_roi_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL DEFAULT 1,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_roi_setting (outlet_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO business_roi_settings (outlet_id, setting_key, setting_value) VALUES
  (1, 'business_start_date', '2026-05-17'),
  (1, 'projection_working_days_month', '30'),
  (1, 'daily_sales_target', '1000000'),
  (1, 'owner_reserve_percent', '5'),
  (1, 'roi_payback_percent', '15'),
  (1, 'growth_conservative_pct', '0'),
  (1, 'growth_base_pct', '8'),
  (1, 'growth_aggressive_pct', '18')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- 3. Owner Cash Allocation Rules
CREATE TABLE IF NOT EXISTS owner_cash_allocation_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL DEFAULT 1,
  rule_name VARCHAR(120) NOT NULL,
  allocation_type VARCHAR(40) NOT NULL,
  percent_of_sales DECIMAL(8,2) NOT NULL DEFAULT 0,
  fixed_amount INT NOT NULL DEFAULT 0,
  priority_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cash_alloc_outlet (outlet_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO owner_cash_allocation_rules (outlet_id, rule_name, allocation_type, percent_of_sales, fixed_amount, priority_order, is_active) VALUES
  (1, 'Simpan HPP untuk Restock', 'hpp_restock', 0, 0, 10, 1),
  (1, 'Dana Operasional Harian', 'operational', 0, 0, 20, 1),
  (1, 'Cadangan Darurat Outlet', 'emergency_reserve', 5, 0, 30, 1),
  (1, 'Setoran Balik Modal / ROI', 'roi_payback', 15, 0, 40, 1),
  (1, 'Uang Aman Ditarik Owner', 'owner_draw', 0, 0, 99, 1)
ON DUPLICATE KEY UPDATE rule_name = VALUES(rule_name);

-- 4. Market Trend Keywords
CREATE TABLE IF NOT EXISTS market_trend_keywords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(160) NOT NULL,
  product_idea VARCHAR(180) DEFAULT NULL,
  category VARCHAR(100) DEFAULT 'Ayam Crispy',
  source_note VARCHAR(255) DEFAULT NULL,
  base_hpp_estimate INT NOT NULL DEFAULT 0,
  suggested_price INT NOT NULL DEFAULT 0,
  complexity_score TINYINT NOT NULL DEFAULT 3,
  stock_fit_score TINYINT NOT NULL DEFAULT 3,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_market_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Menu Experiment Plans
CREATE TABLE IF NOT EXISTS menu_experiment_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL DEFAULT 1,
  experiment_name VARCHAR(180) NOT NULL,
  source_keyword VARCHAR(180) DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  target_orders_per_day INT NOT NULL DEFAULT 0,
  target_margin_pct DECIMAL(8,2) NOT NULL DEFAULT 0,
  estimated_hpp INT NOT NULL DEFAULT 0,
  suggested_price INT NOT NULL DEFAULT 0,
  status ENUM('planned','running','completed','stopped') NOT NULL DEFAULT 'planned',
  decision ENUM('pending','make_permanent','continue_test','stop') NOT NULL DEFAULT 'pending',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_experiment_outlet (outlet_id),
  INDEX idx_experiment_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
