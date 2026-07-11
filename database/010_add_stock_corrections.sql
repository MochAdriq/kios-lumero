-- ============================================================
-- Migration 010: Stock Corrections table
-- Tracks order voids, item adjustments, and manual stock corrections
-- ============================================================

CREATE TABLE IF NOT EXISTS stock_corrections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    outlet_id INT NOT NULL,
    correction_type ENUM('order_void','order_adjust','stock_addition','stock_reduction') NOT NULL,
    reference_type VARCHAR(50) NULL COMMENT 'order, order_item, raw_material',
    reference_id INT NULL COMMENT 'ID of the referenced record',
    raw_material_id INT NULL,
    qty DECIMAL(12,4) NOT NULL DEFAULT 0,
    old_value DECIMAL(12,4) NULL,
    new_value DECIMAL(12,4) NULL,
    reason TEXT NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_correction_outlet (outlet_id),
    KEY idx_correction_type (correction_type),
    KEY idx_correction_ref (reference_type, reference_id),
    KEY idx_correction_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add voided status to orders if not already present
-- (orders.order_status is VARCHAR so 'voided' is valid)
