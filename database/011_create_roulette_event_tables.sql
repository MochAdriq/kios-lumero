-- 011_create_roulette_event_tables.sql
-- Table for storing event configurations and prizes
CREATE TABLE IF NOT EXISTS `event_prizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(50) NOT NULL DEFAULT 'kalibunder_go',
  `name` varchar(100) NOT NULL,
  `chance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `stock` int(11) NOT NULL DEFAULT '0',
  `is_default_fallback` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event` (`event_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for storing user claims from the event
CREATE TABLE IF NOT EXISTS `reward_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `prize_id` int(11) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `status` enum('PENDING','CLAIMED') NOT NULL DEFAULT 'PENDING',
  `claimed_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_qr_code` (`qr_code`),
  KEY `idx_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial dummy data for Kalibunder Grand Opening
INSERT INTO `event_prizes` (`event_id`, `name`, `chance_percentage`, `stock`, `is_default_fallback`, `is_active`) VALUES
('kalibunder_go', 'Smartphone', 1.00, 1, 0, 1),
('kalibunder_go', 'Paket Ayam Utuh', 5.00, 5, 0, 1),
('kalibunder_go', 'Paket Ayam + Saus Favorit', 20.00, 100, 0, 1),
('kalibunder_go', 'Exclusive Lumero Tumbler', 30.00, 200, 0, 1),
('kalibunder_go', 'Es Krim Lumero Soft Serve', 44.00, 9999, 1, 1);
