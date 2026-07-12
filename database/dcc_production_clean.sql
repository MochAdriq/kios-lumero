-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: kios_lumero_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_outlet_user` (`outlet_id`,`user_id`),
  KEY `idx_audit_table_record` (`table_name`,`record_id`),
  KEY `idx_audit_created_at` (`created_at`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bundle_items`
--

DROP TABLE IF EXISTS `bundle_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bundle_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bundle_product_id` bigint(20) unsigned NOT NULL,
  `item_product_variant_id` bigint(20) unsigned NOT NULL,
  `qty` decimal(15,3) NOT NULL DEFAULT 1.000,
  PRIMARY KEY (`id`),
  KEY `idx_bundle_product` (`bundle_product_id`),
  KEY `idx_bundle_item_variant` (`item_product_variant_id`),
  CONSTRAINT `fk_bundle_item_variant` FOREIGN KEY (`item_product_variant_id`) REFERENCES `product_variants` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bundle_product` FOREIGN KEY (`bundle_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bundle_items`
--

LOCK TABLES `bundle_items` WRITE;
/*!40000 ALTER TABLE `bundle_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bundle_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_capitals`
--

DROP TABLE IF EXISTS `business_capitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_capitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `capital_type` enum('initial_capital','additional_capital') DEFAULT 'initial_capital',
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `capital_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Modal Awal',
  `component_name` varchar(180) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `supplier` varchar(160) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_capital_outlet_date` (`outlet_id`,`capital_date`),
  CONSTRAINT `fk_capital_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_capitals`
--

LOCK TABLES `business_capitals` WRITE;
/*!40000 ALTER TABLE `business_capitals` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_capitals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_insights`
--

DROP TABLE IF EXISTS `business_insights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_insights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `insight_date` date NOT NULL,
  `insight_type` enum('sales','hpp','profit','stock','forecast','bep','roi','warning') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('info','success','warning','danger') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_insights_outlet_date` (`outlet_id`,`insight_date`),
  CONSTRAINT `fk_insights_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_insights`
--

LOCK TABLES `business_insights` WRITE;
/*!40000 ALTER TABLE `business_insights` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_insights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_roi_settings`
--

DROP TABLE IF EXISTS `business_roi_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_roi_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outlet_id` int(11) NOT NULL DEFAULT 1,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roi_setting` (`outlet_id`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_roi_settings`
--

LOCK TABLES `business_roi_settings` WRITE;
/*!40000 ALTER TABLE `business_roi_settings` DISABLE KEYS */;
INSERT INTO `business_roi_settings` VALUES (1,1,'business_start_date','2026-05-17','2026-06-25 04:27:13'),(2,1,'projection_working_days_month','30','2026-06-25 04:27:13'),(3,1,'daily_sales_target','1000000','2026-06-25 04:27:13'),(4,1,'owner_reserve_percent','5','2026-06-25 04:27:13'),(5,1,'roi_payback_percent','15','2026-06-25 04:27:13'),(6,1,'growth_conservative_pct','0','2026-06-25 04:27:13'),(7,1,'growth_base_pct','8','2026-06-25 04:27:13'),(8,1,'growth_aggressive_pct','18','2026-06-25 04:27:13');
/*!40000 ALTER TABLE `business_roi_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_targets`
--

DROP TABLE IF EXISTS `business_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `target_type` enum('daily','weekly','monthly','yearly') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_revenue` decimal(15,2) DEFAULT 0.00,
  `target_net_profit` decimal(15,2) DEFAULT 0.00,
  `target_transactions` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_targets_outlet_period` (`outlet_id`,`period_start`,`period_end`),
  CONSTRAINT `fk_targets_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_targets`
--

LOCK TABLES `business_targets` WRITE;
/*!40000 ALTER TABLE `business_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_drawer_movements`
--

DROP TABLE IF EXISTS `cash_drawer_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_drawer_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cashier_shift_id` bigint(20) unsigned NOT NULL,
  `movement_type` enum('opening_cash','cash_sale','cash_out','cash_in','closing_cash','correction') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cash_drawer_shift` (`cashier_shift_id`),
  KEY `idx_cash_drawer_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `fk_cash_drawer_shift` FOREIGN KEY (`cashier_shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_drawer_movements`
--

LOCK TABLES `cash_drawer_movements` WRITE;
/*!40000 ALTER TABLE `cash_drawer_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_drawer_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashier_shifts`
--

DROP TABLE IF EXISTS `cashier_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashier_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `daily_store_session_id` bigint(20) unsigned NOT NULL,
  `cashier_id` bigint(20) unsigned NOT NULL,
  `shift_code` varchar(100) NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` decimal(15,2) DEFAULT 0.00,
  `system_cash` decimal(15,2) DEFAULT 0.00,
  `physical_cash` decimal(15,2) DEFAULT 0.00,
  `cash_difference` decimal(15,2) DEFAULT 0.00,
  `status` enum('open','closed') DEFAULT 'open',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cashier_shift_code` (`shift_code`),
  KEY `idx_cashier_shift_session` (`daily_store_session_id`),
  KEY `idx_cashier_shift_cashier` (`cashier_id`),
  KEY `fk_shift_outlet` (`outlet_id`),
  CONSTRAINT `fk_shift_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_shift_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shift_session` FOREIGN KEY (`daily_store_session_id`) REFERENCES `daily_store_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_shifts`
--

LOCK TABLES `cashier_shifts` WRITE;
/*!40000 ALTER TABLE `cashier_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashier_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `legal_name` varchar(200) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'DCC Group',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-14 14:12:58','2026-06-14 14:12:58');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_closing_reports`
--

DROP TABLE IF EXISTS `daily_closing_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_closing_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `daily_store_session_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `gross_sales` decimal(15,2) DEFAULT 0.00,
  `discount_total` decimal(15,2) DEFAULT 0.00,
  `net_sales` decimal(15,2) DEFAULT 0.00,
  `tax_total` decimal(15,2) DEFAULT 0.00,
  `service_total` decimal(15,2) DEFAULT 0.00,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `total_hpp` decimal(15,2) DEFAULT 0.00,
  `gross_profit` decimal(15,2) DEFAULT 0.00,
  `payroll_expense` decimal(15,2) DEFAULT 0.00,
  `operational_expense` decimal(15,2) DEFAULT 0.00,
  `wastage_loss` decimal(15,2) DEFAULT 0.00,
  `total_expense` decimal(15,2) DEFAULT 0.00,
  `net_profit` decimal(15,2) DEFAULT 0.00,
  `cash_sales` decimal(15,2) DEFAULT 0.00,
  `qris_sales` decimal(15,2) DEFAULT 0.00,
  `debit_credit_sales` decimal(15,2) DEFAULT 0.00,
  `ewallet_sales` decimal(15,2) DEFAULT 0.00,
  `total_transactions` int(11) DEFAULT 0,
  `total_items_sold` decimal(15,3) DEFAULT 0.000,
  `cash_system` decimal(15,2) DEFAULT 0.00,
  `cash_physical` decimal(15,2) DEFAULT 0.00,
  `cash_difference` decimal(15,2) DEFAULT 0.00,
  `analysis_summary` text DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_closing_session` (`daily_store_session_id`),
  UNIQUE KEY `uq_closing_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_closing_closed_by` (`closed_by`),
  CONSTRAINT `fk_closing_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_closing_session` FOREIGN KEY (`daily_store_session_id`) REFERENCES `daily_store_sessions` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_closing_user` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_closing_reports`
--

LOCK TABLES `daily_closing_reports` WRITE;
/*!40000 ALTER TABLE `daily_closing_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_closing_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_product_stock_movements`
--

DROP TABLE IF EXISTS `daily_product_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_product_stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `movement_type` enum('opening','production_in','sale_out','wastage','adjustment_in','adjustment_out','closing') NOT NULL,
  `qty` decimal(15,3) NOT NULL,
  `hpp_per_unit` decimal(15,2) DEFAULT 0.00,
  `total_hpp` decimal(15,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_daily_stock_mov_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_daily_stock_mov_variant` (`product_variant_id`),
  KEY `idx_daily_stock_mov_reference` (`reference_type`,`reference_id`),
  KEY `fk_daily_stock_mov_user` (`created_by`),
  CONSTRAINT `fk_daily_stock_mov_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_stock_mov_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_stock_mov_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_product_stock_movements`
--

LOCK TABLES `daily_product_stock_movements` WRITE;
/*!40000 ALTER TABLE `daily_product_stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_product_stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_product_stocks`
--

DROP TABLE IF EXISTS `daily_product_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_product_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `opening_qty` decimal(15,3) DEFAULT 0.000,
  `produced_qty` decimal(15,3) DEFAULT 0.000,
  `sold_qty` decimal(15,3) DEFAULT 0.000,
  `wasted_qty` decimal(15,3) DEFAULT 0.000,
  `closing_qty` decimal(15,3) DEFAULT 0.000,
  `status` enum('available','low','sold_out','inactive') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_product_stock` (`outlet_id`,`business_date`,`product_variant_id`),
  KEY `idx_daily_stock_variant` (`product_variant_id`),
  CONSTRAINT `fk_daily_stock_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_daily_stock_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_product_stocks`
--

LOCK TABLES `daily_product_stocks` WRITE;
/*!40000 ALTER TABLE `daily_product_stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_product_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_staff_attendance`
--

DROP TABLE IF EXISTS `daily_staff_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_staff_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `daily_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('present','absent','half_day') DEFAULT 'present',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_user_date` (`outlet_id`,`business_date`,`user_id`),
  KEY `idx_attendance_user` (`user_id`),
  KEY `fk_attendance_created_by` (`created_by`),
  CONSTRAINT `fk_attendance_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_staff_attendance`
--

LOCK TABLES `daily_staff_attendance` WRITE;
/*!40000 ALTER TABLE `daily_staff_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_staff_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_store_sessions`
--

DROP TABLE IF EXISTS `daily_store_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_store_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `opened_by` bigint(20) unsigned NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` decimal(15,2) DEFAULT 0.00,
  `closing_cash_system` decimal(15,2) DEFAULT 0.00,
  `closing_cash_physical` decimal(15,2) DEFAULT 0.00,
  `cash_difference` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_session` (`outlet_id`,`business_date`),
  KEY `idx_sessions_opened_by` (`opened_by`),
  KEY `idx_sessions_closed_by` (`closed_by`),
  CONSTRAINT `fk_sessions_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessions_opened_by` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sessions_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_store_sessions`
--

LOCK TABLES `daily_store_sessions` WRITE;
/*!40000 ALTER TABLE `daily_store_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_store_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('operational','payroll','marketing','maintenance','other') DEFAULT 'operational',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expense_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Gaji Karyawan','payroll'),(2,'Operasional','operational');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `is_auto` tinyint(1) NOT NULL DEFAULT 0,
  `auto_key` varchar(120) DEFAULT NULL,
  `source_ref` varchar(120) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_expense_category` (`category`),
  KEY `idx_expense_created_by` (`created_by`),
  KEY `idx_expense_auto_key` (`auto_key`),
  KEY `idx_expense_source_ref` (`source_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_accounts`
--

DROP TABLE IF EXISTS `financial_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','cogs','expense') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_financial_account_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_accounts`
--

LOCK TABLES `financial_accounts` WRITE;
/*!40000 ALTER TABLE `financial_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_order_items`
--

DROP TABLE IF EXISTS `free_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `free_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `free_order_id` int(11) NOT NULL,
  `item_type` varchar(50) DEFAULT 'menu',
  `chicken_part_id` int(11) DEFAULT NULL,
  `chicken_style` varchar(100) DEFAULT NULL,
  `sauce_id` int(11) DEFAULT NULL,
  `with_rice` tinyint(1) DEFAULT 0,
  `matcha_variant_id` int(11) DEFAULT NULL,
  `kentang_variant_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `price` int(11) DEFAULT 0,
  `hpp` int(11) DEFAULT 0,
  `line_total` int(11) DEFAULT 0,
  `line_hpp` int(11) DEFAULT 0,
  `payload_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_free_order_id` (`free_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_order_items`
--

LOCK TABLES `free_order_items` WRITE;
/*!40000 ALTER TABLE `free_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_orders`
--

DROP TABLE IF EXISTS `free_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `free_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pre_order_no` varchar(64) NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `pickup_type` varchar(50) DEFAULT 'dine_in',
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `payment_method` enum('qris','transfer','cash','point') NOT NULL DEFAULT 'qris',
  `payment_status` varchar(50) DEFAULT 'pending',
  `order_status` varchar(50) DEFAULT 'waiting',
  `subtotal` int(11) DEFAULT 0,
  `discount` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  `total_hpp` int(11) DEFAULT 0,
  `loyalty_points_redeemed` int(11) DEFAULT 0,
  `loyalty_point_value` int(11) DEFAULT 0,
  `loyalty_redeem_amount` int(11) DEFAULT 0,
  `nominal_point` int(11) DEFAULT 0,
  `customer_note` text DEFAULT NULL,
  `cart_json` longtext DEFAULT NULL,
  `stock_reserved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_orders`
--

LOCK TABLES `free_orders` WRITE;
/*!40000 ALTER TABLE `free_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `raw_material_id` bigint(20) unsigned NOT NULL,
  `movement_date` datetime NOT NULL,
  `business_date` date DEFAULT NULL,
  `movement_type` enum('purchase','production_usage','sales_usage','wastage','adjustment_in','adjustment_out','transfer_in','transfer_out','opening_balance') NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `qty_in` decimal(18,4) DEFAULT 0.0000,
  `qty_out` decimal(18,4) DEFAULT 0.0000,
  `unit_cost` decimal(15,4) DEFAULT 0.0000,
  `total_cost` decimal(15,2) DEFAULT 0.00,
  `stock_after` decimal(18,4) DEFAULT 0.0000,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_material_date` (`raw_material_id`,`movement_date`),
  KEY `idx_inventory_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_inventory_reference` (`reference_type`,`reference_id`),
  KEY `idx_inventory_created_by` (`created_by`),
  CONSTRAINT `fk_inventory_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_journal_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_journal_reference` (`reference_type`,`reference_id`),
  KEY `fk_journal_user` (`created_by`),
  CONSTRAINT `fk_journal_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_journal_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_journal_lines_entry` (`journal_entry_id`),
  KEY `idx_journal_lines_account` (`account_id`),
  CONSTRAINT `fk_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_journal_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entry_lines`
--

LOCK TABLES `journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `journal_entry_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_settings`
--

DROP TABLE IF EXISTS `loyalty_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loyalty_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `earn_amount` int(11) NOT NULL DEFAULT 1000,
  `earn_point` int(11) NOT NULL DEFAULT 1,
  `redeem_point_value` int(11) NOT NULL DEFAULT 100,
  `minimum_redeem_points` int(11) NOT NULL DEFAULT 10,
  `maximum_redeem_percent` int(11) NOT NULL DEFAULT 100,
  `claim_expiry_days` int(11) NOT NULL DEFAULT 14,
  `profile_bonus_points` int(11) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_settings`
--

LOCK TABLES `loyalty_settings` WRITE;
/*!40000 ALTER TABLE `loyalty_settings` DISABLE KEYS */;
INSERT INTO `loyalty_settings` VALUES (1,1000,1,100,10,100,14,2,1,'2026-07-11 15:17:26');
/*!40000 ALTER TABLE `loyalty_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `market_trend_keywords`
--

DROP TABLE IF EXISTS `market_trend_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `market_trend_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(160) NOT NULL,
  `product_idea` varchar(180) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Ayam Crispy',
  `source_note` varchar(255) DEFAULT NULL,
  `base_hpp_estimate` int(11) NOT NULL DEFAULT 0,
  `suggested_price` int(11) NOT NULL DEFAULT 0,
  `complexity_score` tinyint(4) NOT NULL DEFAULT 3,
  `stock_fit_score` tinyint(4) NOT NULL DEFAULT 3,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_market_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `market_trend_keywords`
--

LOCK TABLES `market_trend_keywords` WRITE;
/*!40000 ALTER TABLE `market_trend_keywords` DISABLE KEYS */;
/*!40000 ALTER TABLE `market_trend_keywords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_activity_logs`
--

DROP TABLE IF EXISTS `member_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `activity_type` varchar(60) NOT NULL,
  `ip_address` varchar(80) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_member_activity_member` (`member_id`),
  KEY `idx_member_activity_phone` (`phone`),
  KEY `idx_member_activity_type` (`activity_type`),
  KEY `idx_member_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_activity_logs`
--

LOCK TABLES `member_activity_logs` WRITE;
/*!40000 ALTER TABLE `member_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_login_otps`
--

DROP TABLE IF EXISTS `member_login_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_login_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(30) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expired_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_member_login_otps_phone` (`phone`),
  KEY `idx_member_login_otps_expired` (`expired_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_login_otps`
--

LOCK TABLES `member_login_otps` WRITE;
/*!40000 ALTER TABLE `member_login_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_login_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_point_logs`
--

DROP TABLE IF EXISTS `member_point_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_point_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `receipt_claim_id` int(11) DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `points_in` int(11) NOT NULL DEFAULT 0,
  `points_out` int(11) NOT NULL DEFAULT 0,
  `balance_after` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_member_point_logs_member` (`member_id`),
  KEY `idx_member_point_logs_transaction` (`transaction_id`),
  KEY `idx_member_point_logs_type` (`type`),
  KEY `idx_member_point_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_point_logs`
--

LOCK TABLES `member_point_logs` WRITE;
/*!40000 ALTER TABLE `member_point_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_point_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` varchar(40) DEFAULT NULL,
  `name` varchar(120) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `pin_hash` varchar(255) DEFAULT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `total_spent` int(11) NOT NULL DEFAULT 0,
  `total_transactions` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  `profile_completed_at` datetime DEFAULT NULL,
  `profile_bonus_awarded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_members_phone` (`phone`),
  UNIQUE KEY `uniq_members_code` (`member_code`),
  KEY `idx_members_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_experiment_plans`
--

DROP TABLE IF EXISTS `menu_experiment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_experiment_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outlet_id` int(11) NOT NULL DEFAULT 1,
  `experiment_name` varchar(180) NOT NULL,
  `source_keyword` varchar(180) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `target_orders_per_day` int(11) NOT NULL DEFAULT 0,
  `target_margin_pct` decimal(8,2) NOT NULL DEFAULT 0.00,
  `estimated_hpp` int(11) NOT NULL DEFAULT 0,
  `suggested_price` int(11) NOT NULL DEFAULT 0,
  `status` enum('planned','running','completed','stopped') NOT NULL DEFAULT 'planned',
  `decision` enum('pending','make_permanent','continue_test','stop') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_experiment_outlet` (`outlet_id`),
  KEY `idx_experiment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_experiment_plans`
--

LOCK TABLES `menu_experiment_plans` WRITE;
/*!40000 ALTER TABLE `menu_experiment_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `menu_experiment_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'005_create_default_admin_if_empty.sql','2026-06-25 08:20:20'),(2,'006_add_product_outlet_scope.sql','2026-06-25 08:23:19'),(3,'007_add_branch_management.sql','2026-06-25 08:23:19'),(4,'008_add_sub_recipe_support.sql','2026-06-25 08:23:19'),(5,'009_upgrade_executive_roi.sql','2026-06-25 08:23:19'),(7,'010_add_stock_corrections.sql','2026-06-25 08:23:26');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operational_expenses`
--

DROP TABLE IF EXISTS `operational_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operational_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','qris','ewallet','other') DEFAULT 'cash',
  `description` text DEFAULT NULL,
  `reference_file` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_expenses_category` (`category_id`),
  KEY `fk_expenses_created_by` (`created_by`),
  CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expenses_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operational_expenses`
--

LOCK TABLES `operational_expenses` WRITE;
/*!40000 ALTER TABLE `operational_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `operational_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `product_name_snapshot` varchar(150) NOT NULL,
  `variant_name_snapshot` varchar(150) DEFAULT NULL,
  `qty` decimal(15,3) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL,
  `hpp_per_unit` decimal(15,2) DEFAULT 0.00,
  `total_hpp` decimal(15,2) DEFAULT 0.00,
  `gross_profit` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_variant` (`product_variant_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `daily_store_session_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(100) NOT NULL,
  `order_source` enum('cashier','self_order','gofood','grabfood','shopeefood','manual') DEFAULT 'cashier',
  `order_type` enum('dine_in','takeaway','delivery') DEFAULT 'takeaway',
  `business_date` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `service_amount` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `total_hpp` decimal(15,2) DEFAULT 0.00,
  `gross_profit` decimal(15,2) DEFAULT 0.00,
  `loyalty_points_earned` int(11) NOT NULL DEFAULT 0,
  `loyalty_points_redeemed` int(11) NOT NULL DEFAULT 0,
  `loyalty_point_value` int(11) NOT NULL DEFAULT 0,
  `loyalty_redeem_amount` int(11) NOT NULL DEFAULT 0,
  `nominal_point` int(11) NOT NULL DEFAULT 0,
  `loyalty_claim_code` varchar(40) DEFAULT NULL,
  `loyalty_claim_points` int(11) NOT NULL DEFAULT 0,
  `loyalty_claim_status` enum('none','unclaimed','claimed','expired','cancelled') NOT NULL DEFAULT 'none',
  `payment_status` enum('unpaid','waiting_verification','paid','partial','refunded','void') DEFAULT 'unpaid',
  `order_status` enum('draft','pending','processing','completed','cancelled','void') DEFAULT 'pending',
  `cashier_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  UNIQUE KEY `uniq_orders_loyalty_claim_code` (`loyalty_claim_code`),
  KEY `idx_orders_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_orders_session` (`daily_store_session_id`),
  KEY `idx_orders_status` (`payment_status`,`order_status`),
  KEY `idx_orders_cashier` (`cashier_id`),
  KEY `fk_orders_customer` (`customer_id`),
  KEY `idx_orders_member_id` (`member_id`),
  KEY `idx_orders_customer_phone` (`customer_phone`),
  CONSTRAINT `fk_orders_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_session` FOREIGN KEY (`daily_store_session_id`) REFERENCES `daily_store_sessions` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outlets`
--

DROP TABLE IF EXISTS `outlets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outlets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `outlet_code` varchar(50) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `is_hq` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(150) NOT NULL,
  `type` enum('owned','partnership','franchise') DEFAULT 'owned',
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `closing_hour` time NOT NULL DEFAULT '21:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_outlets_code` (`outlet_code`),
  UNIQUE KEY `idx_outlets_slug` (`slug`),
  KEY `idx_outlets_company` (`company_id`),
  CONSTRAINT `fk_outlets_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outlets`
--

LOCK TABLES `outlets` WRITE;
/*!40000 ALTER TABLE `outlets` DISABLE KEYS */;
INSERT INTO `outlets` VALUES (1,1,'DCP',NULL,1,'D\'Celup Pasekon','owned','Pasekon','',1,'2026-05-19 16:13:43','2026-06-03 09:06:46','21:00:00'),(2,1,'KB','kb',0,'D\'Celup Kalibunder','owned','Kalibunder','',0,'2026-05-25 15:46:39','2026-06-14 14:40:44','21:00:00'),(4,1,'CBRJ',NULL,0,'Cibaraja','owned','Jl cibaraja\r\nKp. Selaawi RT004 002 CISAAT SUKABUMI','089567827654',0,'2026-06-03 09:04:06','2026-06-03 09:04:06','21:00:00'),(5,1,'KLB','klb',0,'Kalibunder','franchise','kalibunder','089567827654',1,'2026-07-12 01:56:10','2026-07-12 01:56:10','21:00:00');
/*!40000 ALTER TABLE `outlets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_cash_allocation_rules`
--

DROP TABLE IF EXISTS `owner_cash_allocation_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_cash_allocation_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outlet_id` int(11) NOT NULL DEFAULT 1,
  `rule_name` varchar(120) NOT NULL,
  `allocation_type` varchar(40) NOT NULL,
  `percent_of_sales` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fixed_amount` int(11) NOT NULL DEFAULT 0,
  `priority_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cash_alloc_outlet` (`outlet_id`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_cash_allocation_rules`
--

LOCK TABLES `owner_cash_allocation_rules` WRITE;
/*!40000 ALTER TABLE `owner_cash_allocation_rules` DISABLE KEYS */;
INSERT INTO `owner_cash_allocation_rules` VALUES (1,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 04:27:13','2026-06-25 04:27:13'),(2,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 04:27:13','2026-06-25 04:27:13'),(3,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 04:27:13','2026-06-25 04:27:13'),(4,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 04:27:13','2026-06-25 04:27:13'),(5,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 04:27:13','2026-06-25 04:27:13'),(6,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 04:51:20','2026-06-25 04:51:20'),(7,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 04:51:20','2026-06-25 04:51:20'),(8,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 04:51:20','2026-06-25 04:51:20'),(9,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 04:51:20','2026-06-25 04:51:20'),(10,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 04:51:20','2026-06-25 04:51:20'),(11,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 07:22:51','2026-06-25 07:22:51'),(12,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 07:22:51','2026-06-25 07:22:51'),(13,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 07:22:51','2026-06-25 07:22:51'),(14,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 07:22:51','2026-06-25 07:22:51'),(15,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 07:22:51','2026-06-25 07:22:51'),(16,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:08:10','2026-06-25 08:08:10'),(17,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:08:10','2026-06-25 08:08:10'),(18,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:08:10','2026-06-25 08:08:10'),(19,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:08:10','2026-06-25 08:08:10'),(20,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:08:10','2026-06-25 08:08:10'),(21,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:08:46','2026-06-25 08:08:46'),(22,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:08:46','2026-06-25 08:08:46'),(23,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:08:46','2026-06-25 08:08:46'),(24,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:08:46','2026-06-25 08:08:46'),(25,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:08:46','2026-06-25 08:08:46'),(26,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:09:00','2026-06-25 08:09:00'),(27,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:09:00','2026-06-25 08:09:00'),(28,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:09:00','2026-06-25 08:09:00'),(29,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:09:00','2026-06-25 08:09:00'),(30,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:09:00','2026-06-25 08:09:00'),(31,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:10:01','2026-06-25 08:10:01'),(32,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:10:01','2026-06-25 08:10:01'),(33,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:10:01','2026-06-25 08:10:01'),(34,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:10:01','2026-06-25 08:10:01'),(35,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:10:01','2026-06-25 08:10:01'),(36,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:10:03','2026-06-25 08:10:03'),(37,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:10:03','2026-06-25 08:10:03'),(38,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:10:03','2026-06-25 08:10:03'),(39,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:10:03','2026-06-25 08:10:03'),(40,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:10:03','2026-06-25 08:10:03'),(41,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:10:06','2026-06-25 08:10:06'),(42,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:10:06','2026-06-25 08:10:06'),(43,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:10:06','2026-06-25 08:10:06'),(44,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:10:06','2026-06-25 08:10:06'),(45,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:10:06','2026-06-25 08:10:06'),(46,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:10:07','2026-06-25 08:10:07'),(47,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:10:07','2026-06-25 08:10:07'),(48,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:10:07','2026-06-25 08:10:07'),(49,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:10:07','2026-06-25 08:10:07'),(50,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:10:07','2026-06-25 08:10:07'),(51,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:10:09','2026-06-25 08:10:09'),(52,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:10:09','2026-06-25 08:10:09'),(53,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:10:09','2026-06-25 08:10:09'),(54,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:10:09','2026-06-25 08:10:09'),(55,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:10:09','2026-06-25 08:10:09'),(56,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:15:58','2026-06-25 08:15:58'),(57,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:15:58','2026-06-25 08:15:58'),(58,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:15:58','2026-06-25 08:15:58'),(59,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:15:58','2026-06-25 08:15:58'),(60,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:15:58','2026-06-25 08:15:58'),(61,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-06-25 08:17:37','2026-06-25 08:17:37'),(62,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-06-25 08:17:37','2026-06-25 08:17:37'),(63,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-06-25 08:17:37','2026-06-25 08:17:37'),(64,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-06-25 08:17:37','2026-06-25 08:17:37'),(65,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-06-25 08:17:37','2026-06-25 08:17:37'),(66,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-07-11 16:55:58','2026-07-11 16:55:58'),(67,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-07-11 16:55:58','2026-07-11 16:55:58'),(68,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-07-11 16:55:58','2026-07-11 16:55:58'),(69,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-07-11 16:55:58','2026-07-11 16:55:58'),(70,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-07-11 16:55:58','2026-07-11 16:55:58'),(71,1,'Simpan HPP untuk Restock','hpp_restock',0.00,0,10,1,'2026-07-11 16:56:01','2026-07-11 16:56:01'),(72,1,'Dana Operasional Harian','operational',0.00,0,20,1,'2026-07-11 16:56:01','2026-07-11 16:56:01'),(73,1,'Cadangan Darurat Outlet','emergency_reserve',5.00,0,30,1,'2026-07-11 16:56:01','2026-07-11 16:56:01'),(74,1,'Setoran Balik Modal / ROI','roi_payback',15.00,0,40,1,'2026-07-11 16:56:01','2026-07-11 16:56:01'),(75,1,'Uang Aman Ditarik Owner','owner_draw',0.00,0,99,1,'2026-07-11 16:56:01','2026-07-11 16:56:01');
/*!40000 ALTER TABLE `owner_cash_allocation_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateway_configs`
--

DROP TABLE IF EXISTS `payment_gateway_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_gateway_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(100) NOT NULL,
  `mode` enum('sandbox','production') DEFAULT 'sandbox',
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `merchant_id` varchar(255) DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `private_key` text DEFAULT NULL,
  `callback_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gateway_outlet_provider` (`outlet_id`,`provider`),
  CONSTRAINT `fk_gateway_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateway_configs`
--

LOCK TABLES `payment_gateway_configs` WRITE;
/*!40000 ALTER TABLE `payment_gateway_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_gateway_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `payment_method` enum('cash','debit','credit','qris','ewallet','bank_transfer','other') NOT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum('pending','waiting_verification','paid','failed','expired','refunded') DEFAULT 'pending',
  `gateway_reference` varchar(500) DEFAULT NULL,
  `gateway_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_payload`)),
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_verified_by` (`verified_by`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_expenses`
--

DROP TABLE IF EXISTS `payroll_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `source` enum('auto_open_store','manual_adjustment') DEFAULT 'auto_open_store',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_outlet_date` (`outlet_id`,`business_date`),
  KEY `idx_payroll_user` (`user_id`),
  CONSTRAINT `fk_payroll_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payroll_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_expenses`
--

LOCK TABLES `payroll_expenses` WRITE;
/*!40000 ALTER TABLE `payroll_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_reward_products`
--

DROP TABLE IF EXISTS `point_reward_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_reward_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reward_code` varchar(40) DEFAULT NULL,
  `source_menu_item_id` int(11) DEFAULT NULL,
  `source_price` int(11) DEFAULT NULL,
  `source_hpp` int(11) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `terms` varchar(255) DEFAULT NULL,
  `required_points` int(11) NOT NULL DEFAULT 0,
  `nominal_value` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `visible_from` datetime DEFAULT NULL,
  `visible_until` datetime DEFAULT NULL,
  `max_redeem_per_member` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_point_rewards_active` (`is_active`,`sort_order`),
  KEY `idx_point_reward_source_menu` (`source_menu_item_id`),
  KEY `idx_point_rewards_category` (`category`),
  KEY `idx_point_rewards_visibility` (`is_active`,`visible_from`,`visible_until`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_reward_products`
--

LOCK TABLES `point_reward_products` WRITE;
/*!40000 ALTER TABLE `point_reward_products` DISABLE KEYS */;
INSERT INTO `point_reward_products` VALUES (1,NULL,NULL,NULL,NULL,'Gratis Kentang Kriwil','Tukar point dengan 1 porsi Kentang Kriwil.',NULL,NULL,20,NULL,'assets/img/kentang-kriwil.png',NULL,1,10,NULL,NULL,NULL,'2026-07-11 15:23:25','2026-07-11 15:23:25'),(2,NULL,NULL,NULL,NULL,'Gratis Matcha','Tukar point dengan 1 cup Matcha pilihan.',NULL,NULL,26,NULL,'assets/img/matcha.png',NULL,1,20,NULL,NULL,NULL,'2026-07-11 15:23:25','2026-07-11 15:23:25'),(3,NULL,NULL,NULL,NULL,'Gratis Ayam Original','Tukar point dengan 1 pcs ayam original.',NULL,NULL,24,NULL,'assets/img/original.png',NULL,1,30,NULL,NULL,NULL,'2026-07-11 15:23:25','2026-07-11 15:23:25');
/*!40000 ALTER TABLE `point_reward_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `point_reward_redemptions`
--

DROP TABLE IF EXISTS `point_reward_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `point_reward_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `reward_product_id` int(11) NOT NULL,
  `redemption_code` varchar(40) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `points_used` int(11) NOT NULL DEFAULT 0,
  `status` enum('requested','approved','completed','cancelled') NOT NULL DEFAULT 'requested',
  `note` varchar(255) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_point_redemption_code` (`redemption_code`),
  KEY `idx_point_reward_member` (`member_id`),
  KEY `idx_point_reward_status` (`status`),
  KEY `idx_point_reward_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `point_reward_redemptions`
--

LOCK TABLES `point_reward_redemptions` WRITE;
/*!40000 ALTER TABLE `point_reward_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `point_reward_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_branch_overrides`
--

DROP TABLE IF EXISTS `product_branch_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_branch_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL COMMENT 'NULL = use master price',
  `hpp` decimal(15,2) DEFAULT NULL COMMENT 'NULL = use master HPP',
  `is_active` tinyint(1) DEFAULT NULL COMMENT 'NULL = use master active flag',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branch_variant` (`outlet_id`,`product_variant_id`),
  KEY `idx_pbo_variant` (`product_variant_id`),
  CONSTRAINT `fk_pbo_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pbo_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_branch_overrides`
--

LOCK TABLES `product_branch_overrides` WRITE;
/*!40000 ALTER TABLE `product_branch_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_branch_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_category_scope_slug` (`outlet_id`,`slug`),
  KEY `idx_product_categories_outlet` (`outlet_id`),
  CONSTRAINT `fk_product_categories_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=130013 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,1,'Ayam 1 Ekor','ayam_1_ekor',1,0,NULL,'2026-05-19 18:01:57'),(2,1,'Ayam Crispy','ayam_crispy',2,0,NULL,'2026-05-19 18:01:57'),(3,1,'Cut Series','cut_series',3,0,NULL,'2026-05-19 18:01:57'),(4,1,'Kentang','kentang',4,0,NULL,'2026-05-19 18:01:57'),(5,1,'Minuman','minuman',5,0,NULL,'2026-05-19 18:01:57'),(6,1,'Kopi & Minuman Sachet','kopi_minuman_sachet',6,0,NULL,'2026-05-19 18:01:57'),(130001,1,'Ayam Crispy','ayam-crispy',1,1,NULL,'2026-05-19 18:24:06'),(130002,1,'Kentang Kriwil','kentang-kriwil',3,1,NULL,'2026-06-18 18:09:40'),(130003,1,'Matcha','matcha',2,1,NULL,'2026-06-18 18:09:37'),(130004,1,'Kopi & Minuman Sachet','kopi-minuman-sachet',4,1,NULL,'2026-05-19 18:24:06'),(130005,1,'Menu Tambahan','menu-tambahan',5,1,NULL,'2026-05-19 18:24:06'),(130006,1,'Promo/Bundle','promo-bundle',6,1,NULL,'2026-05-19 18:24:06'),(130007,1,'Makanan','makanan',0,1,NULL,NULL),(130012,1,'Kopi & Minuman','kopi---minuman',0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_pricing_rules`
--

DROP TABLE IF EXISTS `product_pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_pricing_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `channel` enum('dine_in','takeaway','self_order','gofood','grabfood','shopeefood','custom') DEFAULT 'dine_in',
  `selling_price` decimal(15,2) NOT NULL,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `service_percent` decimal(5,2) DEFAULT 0.00,
  `discount_type` enum('none','percent','fixed') DEFAULT 'none',
  `discount_value` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pricing_variant_channel` (`product_variant_id`,`channel`,`is_active`),
  CONSTRAINT `fk_pricing_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_pricing_rules`
--

LOCK TABLES `product_pricing_rules` WRITE;
/*!40000 ALTER TABLE `product_pricing_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_pricing_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `variant_name` varchar(150) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `hpp` decimal(15,2) DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `margin_amount` decimal(15,2) DEFAULT 0.00,
  `margin_percent` decimal(10,2) DEFAULT 0.00,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_variants_sku` (`sku`),
  KEY `idx_variants_product` (`product_id`),
  KEY `idx_variants_active` (`is_active`),
  KEY `idx_product_variants_outlet` (`outlet_id`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,NULL,'VAR-745113-c03f','Default','images/pos-products/nasi.png',0.00,3000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(2,2,NULL,'VAR-657300-3b79','Default','images/pos-products/celup-saus.png',0.00,3000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(3,3,NULL,'VAR-280207-be0a','Default','images/pos-products/original.png',210.90,66000.00,65789.10,99.68,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(4,4,NULL,'VAR-931038-4349','Default','images/pos-products/celup-saus.png',14802.90,76000.00,61197.10,80.52,0,1,'2026-06-18 17:38:15','2026-06-19 08:05:22'),(5,5,NULL,'VAR-483192-80cc','Default','images/pos-products/original.png',0.00,13000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(6,6,NULL,'VAR-434585-7c74','Default','images/pos-products/original.png',0.00,13000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(7,7,NULL,'VAR-196161-f92e','Default','images/pos-products/kopi.png',0.00,13000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(8,8,NULL,'VAR-170116-1df1','Default','images/pos-products/original.png',0.00,13000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(9,9,NULL,'VAR-886825-f94d','Default','images/pos-products/original.png',0.00,13000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(10,10,NULL,'VAR-689177-b3a6','Default','images/pos-products/original.png',0.00,20000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(11,11,NULL,'VAR-227245-ec76','Default','images/pos-products/original.png',0.00,22000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(12,12,NULL,'VAR-401440-9c5d','Default','images/pos-products/nasi.png',0.00,15000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(13,13,NULL,'VAR-690123-0666','Default','images/pos-products/nasi.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(14,14,NULL,'VAR-712076-8396','Default','images/pos-products/original.png',0.00,25000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(15,15,NULL,'VAR-897347-ab0b','Default','images/pos-products/original.png',0.00,30000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(16,16,NULL,'VAR-430215-b84e','Default','images/pos-products/original.png',0.00,12500.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(17,17,NULL,'VAR-568789-3727','Kapal Api','images/pos-products/matcha.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(18,17,NULL,'VAR-942163-7181','Good Day Mocacino','images/pos-products/matcha.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(19,17,NULL,'VAR-889002-3b19','Good Day Capucino','images/pos-products/matcha.png',0.00,6000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(20,17,NULL,'VAR-386364-3c5b','ABC Susu','images/pos-products/matcha.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(21,17,NULL,'VAR-841630-4bf9','Torabika Creamy Late','images/pos-products/matcha.png',0.00,6000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(22,17,NULL,'VAR-430241-2901','Wdank','images/pos-products/matcha.png',0.00,6000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(23,17,NULL,'VAR-669559-d170','Mix Tea Ice','images/pos-products/matcha.png',0.00,7000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(24,17,NULL,'VAR-768409-45ca','Luwak White Koffe','images/pos-products/matcha.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(25,17,NULL,'VAR-412155-d5d9','Nescafe Klasik','images/pos-products/matcha.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(26,17,NULL,'VAR-142932-e4ea','Genus Water','images/pos-products/matcha.png',0.00,4000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(27,17,NULL,'VAR-110153-1b63','Mix Tea Hot','images/pos-products/matcha.png',0.00,6000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(28,17,NULL,'VAR-392362-c0fc','Iced Palm Latte','images/pos-products/matcha.png',0.00,10000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(29,17,NULL,'VAR-871205-9e27','Iced Latte','images/pos-products/matcha.png',0.00,10000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(30,18,NULL,'VAR-739254-3938','Original Reguler','images/pos-products/kentang-kriwil.png',388.00,8000.00,7612.00,95.15,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(31,18,NULL,'VAR-193766-9c23','Saus Sadis Reguler','images/pos-products/kentang-kriwil.png',1931.75,10000.00,8068.25,80.68,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(32,18,NULL,'VAR-969612-7ffe','Saus Barbeque Spicy Reguler','images/pos-products/kentang-kriwil.png',1908.00,10000.00,8092.00,80.92,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(33,18,NULL,'VAR-139111-568f','Saus Teriyaki Reguler','images/pos-products/kentang-kriwil.png',1813.00,10000.00,8187.00,81.87,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(34,18,NULL,'VAR-142511-ff5d','Saus Lada Hitam Reguler','images/pos-products/kentang-kriwil.png',388.00,10000.00,9612.00,96.12,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(35,18,NULL,'VAR-680424-2fd2','Saus Keju Reguler','images/pos-products/kentang-kriwil.png',1718.00,10000.00,8282.00,82.82,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(36,18,NULL,'VAR-346610-552e','Saus Mentai Reguler','images/pos-products/kentang-kriwil.png',2050.50,10000.00,7949.50,79.50,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(37,18,NULL,'VAR-927797-030e','Sambal Master Reguler','images/pos-products/kentang-kriwil.png',388.00,10000.00,9612.00,96.12,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(38,18,NULL,'VAR-469897-2a94','Smocky Saus Mentai Reguler','images/pos-products/kentang-kriwil.png',2050.50,16000.00,13949.50,87.18,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(39,18,NULL,'VAR-484926-1efc','Smocky Keju Mozzarella Reguler','images/pos-products/kentang-kriwil.png',388.00,16000.00,15612.00,97.58,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(40,18,NULL,'VAR-841462-4d7c','Saus Garlic Reguler','images/pos-products/kentang-kriwil.png',1908.00,10000.00,8092.00,80.92,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(41,19,NULL,'VAR-506125-4564','Dada Original Tanpa Nasi','images/pos-products/original.png',388.00,12000.00,11612.00,96.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(42,19,NULL,'VAR-852611-ee8e','Paha Atas Original Tanpa Nasi','images/pos-products/original.png',388.00,12000.00,11612.00,96.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(43,19,NULL,'VAR-894519-9202','Paha Bawah Original Tanpa Nasi','images/pos-products/original.png',388.00,10000.00,9612.00,96.12,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(44,19,NULL,'VAR-608686-af1f','Sayap Original Tanpa Nasi','images/pos-products/original.png',388.00,8000.00,7612.00,95.15,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(45,19,NULL,'VAR-604250-81c8','Dada Original + Nasi','images/pos-products/original.png',1022.73,15000.00,13977.27,93.18,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(46,19,NULL,'VAR-544162-b591','Paha Atas Original + Nasi','images/pos-products/original.png',1022.73,15000.00,13977.27,93.18,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(47,19,NULL,'VAR-927183-1008','Paha Bawah Original + Nasi','images/pos-products/original.png',1022.73,13000.00,11977.27,92.13,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(48,19,NULL,'VAR-690581-3fa1','Sayap Original + Nasi','images/pos-products/original.png',1022.73,11000.00,9977.27,90.70,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(49,19,NULL,'VAR-204956-7b6d','Dada BBQ Spicy Tanpa Nasi','images/pos-products/original.png',3850.00,15000.00,11150.00,74.33,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(50,19,NULL,'VAR-835323-ae9f','Paha Atas BBQ Spicy Tanpa Nasi','images/pos-products/original.png',3850.00,15000.00,11150.00,74.33,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(51,19,NULL,'VAR-472837-e208','Paha Bawah BBQ Spicy Tanpa Nasi','images/pos-products/original.png',3850.00,13000.00,9150.00,70.38,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(52,19,NULL,'VAR-763551-5763','Sayap BBQ Spicy Tanpa Nasi','images/pos-products/original.png',3850.00,11000.00,7150.00,65.00,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(53,19,NULL,'VAR-904085-73f8','Dada Keju Tanpa Nasi','images/pos-products/original.png',3470.00,15000.00,11530.00,76.87,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(54,19,NULL,'VAR-631725-2a43','Paha Atas Keju Tanpa Nasi','images/pos-products/original.png',3470.00,15000.00,11530.00,76.87,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(55,19,NULL,'VAR-607577-867a','Paha Bawah Keju Tanpa Nasi','images/pos-products/original.png',3470.00,13000.00,9530.00,73.31,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(56,19,NULL,'VAR-159018-d4a7','Sayap Keju Tanpa Nasi','images/pos-products/original.png',3470.00,11000.00,7530.00,68.45,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(57,19,NULL,'VAR-788135-6e46','Dada Lada Hitam Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(58,19,NULL,'VAR-461613-a95e','Paha Atas Lada Hitam Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(59,19,NULL,'VAR-419097-cc98','Paha Bawah Lada Hitam Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(60,19,NULL,'VAR-750053-6715','Sayap Lada Hitam Tanpa Nasi','images/pos-products/original.png',810.00,11000.00,10190.00,92.64,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(61,19,NULL,'VAR-350876-e7fe','Dada Sadis Tanpa Nasi','images/pos-products/original.png',3897.50,15000.00,11102.50,74.02,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(62,19,NULL,'VAR-757163-3a4a','Paha Atas Sadis Tanpa Nasi','images/pos-products/original.png',3897.50,15000.00,11102.50,74.02,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(63,19,NULL,'VAR-631308-2f0d','Paha Bawah Sadis Tanpa Nasi','images/pos-products/original.png',3897.50,13000.00,9102.50,70.02,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(64,19,NULL,'VAR-397629-cee3','Sayap Sadis Tanpa Nasi','images/pos-products/original.png',3897.50,11000.00,7102.50,64.57,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(65,19,NULL,'VAR-392921-6161','Dada Sambal Geprek Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(66,19,NULL,'VAR-335860-babf','Paha Atas Sambal Geprek Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:09'),(67,19,NULL,'VAR-602921-c5fc','Paha Bawah Sambal Geprek Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:10'),(68,19,NULL,'VAR-116517-dac9','Sayap Sambal Geprek Tanpa Nasi','images/pos-products/original.png',810.00,11000.00,10190.00,92.64,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:10'),(69,19,NULL,'VAR-259219-0752','Dada Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,15000.00,10865.00,72.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(70,19,NULL,'VAR-601294-7a1c','Paha Atas Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,15000.00,10865.00,72.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(71,19,NULL,'VAR-832885-dada','Paha Bawah Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,13000.00,8865.00,68.19,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(72,19,NULL,'VAR-496844-b067','Sayap Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,11000.00,6865.00,62.41,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(73,19,NULL,'VAR-796320-ad89','Dada Teriyaki Tanpa Nasi','images/pos-products/original.png',3660.00,15000.00,11340.00,75.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(74,19,NULL,'VAR-194764-42fd','Paha Atas Teriyaki Tanpa Nasi','images/pos-products/original.png',3660.00,15000.00,11340.00,75.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(75,19,NULL,'VAR-417927-7402','Paha Bawah Teriyaki Tanpa Nasi','images/pos-products/original.png',3660.00,13000.00,9340.00,71.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(76,19,NULL,'VAR-894852-e322','Sayap Teriyaki Tanpa Nasi','images/pos-products/original.png',3660.00,11000.00,7340.00,66.73,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(77,19,NULL,'VAR-395082-daff','Dada Geprek Extra Mozzarella Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:10'),(78,19,NULL,'VAR-868918-33c4','Paha Atas Geprek Extra Mozzarella Tanpa Nasi','images/pos-products/original.png',810.00,15000.00,14190.00,94.60,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(79,19,NULL,'VAR-696842-c612','Paha Bawah Geprek Extra Mozzarella Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(80,19,NULL,'VAR-176573-5970','Sayap Geprek Extra Mozzarella Tanpa Nasi','images/pos-products/original.png',810.00,11000.00,10190.00,92.64,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(81,19,NULL,'VAR-568413-ec6a','Dada Geprek Extra Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,15000.00,10865.00,72.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(82,19,NULL,'VAR-287979-a335','Paha Atas Geprek Extra Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,15000.00,10865.00,72.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(83,19,NULL,'VAR-829418-3174','Paha Bawah Geprek Extra Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,13000.00,8865.00,68.19,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(84,19,NULL,'VAR-646245-c058','Sayap Geprek Extra Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,11000.00,6865.00,62.41,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(85,19,NULL,'VAR-341744-96c0','Dada BBQ Spicy + Nasi','images/pos-products/original.png',4062.73,18000.00,13937.27,77.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(86,19,NULL,'VAR-338705-a826','Paha Atas BBQ Spicy + Nasi','images/pos-products/original.png',4062.73,18000.00,13937.27,77.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(87,19,NULL,'VAR-659091-2d14','Paha Bawah BBQ Spicy + Nasi','images/pos-products/original.png',4062.73,16000.00,11937.27,74.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(88,19,NULL,'VAR-132454-0713','Sayap BBQ Spicy + Nasi','images/pos-products/original.png',4062.73,13000.00,8937.27,68.75,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(89,19,NULL,'VAR-429980-3dda','Dada Keju + Nasi','images/pos-products/original.png',3682.73,18000.00,14317.27,79.54,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(90,19,NULL,'VAR-539374-7ed6','Paha Atas Keju + Nasi','images/pos-products/original.png',3682.73,18000.00,14317.27,79.54,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:42'),(91,19,NULL,'VAR-115582-d312','Paha Bawah Keju + Nasi','images/pos-products/original.png',3682.73,16000.00,12317.27,76.98,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(92,19,NULL,'VAR-607546-f304','Sayap Keju + Nasi','images/pos-products/original.png',3682.73,13000.00,9317.27,71.67,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(93,19,NULL,'VAR-230849-da21','Dada Lada Hitam + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(94,19,NULL,'VAR-441242-2f6e','Paha Atas Lada Hitam + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(95,19,NULL,'VAR-412967-f34e','Paha Bawah Lada Hitam + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(96,19,NULL,'VAR-257936-5d74','Sayap Lada Hitam + Nasi','images/pos-products/original.png',1022.73,13000.00,11977.27,92.13,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(97,19,NULL,'VAR-114033-8cae','Dada Sadis + Nasi','images/pos-products/original.png',4110.23,18000.00,13889.77,77.17,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(98,19,NULL,'VAR-508141-ec38','Paha Atas Sadis + Nasi','images/pos-products/original.png',4110.23,18000.00,13889.77,77.17,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(99,19,NULL,'VAR-280165-2564','Paha Bawah Sadis + Nasi','images/pos-products/original.png',4110.23,16000.00,11889.77,74.31,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(100,19,NULL,'VAR-278939-8b0e','Sayap Sadis + Nasi','images/pos-products/original.png',4110.23,13000.00,8889.77,68.38,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(101,19,NULL,'VAR-581949-e156','Dada Sambal Geprek + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(102,19,NULL,'VAR-238468-4ed5','Paha Atas Sambal Geprek + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(103,19,NULL,'VAR-769629-671f','Paha Bawah Sambal Geprek + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(104,19,NULL,'VAR-398165-d186','Sayap Sambal Geprek + Nasi','images/pos-products/original.png',1022.73,13000.00,11977.27,92.13,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(105,19,NULL,'VAR-227267-d1d4','Dada Mentai + Nasi','images/pos-products/original.png',4347.73,18000.00,13652.27,75.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(106,19,NULL,'VAR-642611-4a76','Paha Atas Mentai + Nasi','images/pos-products/original.png',4347.73,18000.00,13652.27,75.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(107,19,NULL,'VAR-260373-a23f','Paha Bawah Mentai + Nasi','images/pos-products/original.png',4347.73,16000.00,11652.27,72.83,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(108,19,NULL,'VAR-435870-7862','Sayap Mentai + Nasi','images/pos-products/original.png',4347.73,13000.00,8652.27,66.56,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(109,19,NULL,'VAR-800238-8f5e','Dada Teriyaki + Nasi','images/pos-products/original.png',3872.73,18000.00,14127.27,78.48,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(110,19,NULL,'VAR-703518-3fc3','Paha Atas Teriyaki + Nasi','images/pos-products/original.png',3872.73,18000.00,14127.27,78.48,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(111,19,NULL,'VAR-291560-dc8d','Paha Bawah Teriyaki + Nasi','images/pos-products/original.png',3872.73,16000.00,12127.27,75.80,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(112,19,NULL,'VAR-257776-b093','Sayap Teriyaki + Nasi','images/pos-products/original.png',3872.73,13000.00,9127.27,70.21,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(113,19,NULL,'VAR-889969-b6a0','Dada Geprek Extra Mozzarella + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(114,19,NULL,'VAR-812726-2a62','Paha Atas Geprek Extra Mozzarella + Nasi','images/pos-products/original.png',1022.73,18000.00,16977.27,94.32,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:43'),(115,19,NULL,'VAR-182647-b234','Paha Bawah Geprek Extra Mozzarella + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(116,19,NULL,'VAR-648297-0b62','Sayap Geprek Extra Mozzarella + Nasi','images/pos-products/original.png',1022.73,14000.00,12977.27,92.69,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(117,19,NULL,'VAR-602226-d3e5','Dada Geprek Extra Mentai + Nasi','images/pos-products/original.png',4347.73,18000.00,13652.27,75.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(118,19,NULL,'VAR-474486-f236','Paha Atas Geprek Extra Mentai + Nasi','images/pos-products/original.png',4347.73,18000.00,13652.27,75.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(119,19,NULL,'VAR-281335-6401','Paha Bawah Geprek Extra Mentai + Nasi','images/pos-products/original.png',4347.73,16000.00,11652.27,72.83,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(120,19,NULL,'VAR-785831-4ce8','Sayap Geprek Extra Mentai + Nasi','images/pos-products/original.png',4347.73,14000.00,9652.27,68.94,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(121,19,NULL,'VAR-331416-6c52','Kentang Kriwil Original Saus Sachet','images/pos-products/original.png',0.00,8000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(122,20,NULL,'VAR-741232-10f6','Minuman Matcha Latte','images/pos-products/matcha.png',3880.00,13000.00,9120.00,70.15,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:14'),(123,20,NULL,'VAR-731684-34e3','Minuman Matcha Taro','images/pos-products/matcha.png',5170.00,15000.00,9830.00,65.53,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:14'),(124,20,NULL,'VAR-866741-907c','Minuman Matcha Coklat','images/pos-products/matcha.png',3880.00,15000.00,11120.00,74.13,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:14'),(125,19,NULL,'VAR-859619-962f','Mix Tea','images/pos-products/original.png',0.00,5000.00,0.00,0.00,0,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(126,19,NULL,'VAR-761115-c3cf','Chicken Crips Original Tanpa Nasi','images/pos-products/original.png',388.00,10000.00,9612.00,96.12,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(127,19,NULL,'VAR-100959-7c82','Chicken Crips Original + Nasi','images/pos-products/original.png',1022.73,13000.00,11977.27,92.13,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(128,19,NULL,'VAR-731754-ec94','Chicken Crips BBQ Tanpa Nasi','images/pos-products/original.png',3850.00,13000.00,9150.00,70.38,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(129,19,NULL,'VAR-156057-a97f','Chicken Crips Keju Tanpa Nasi','images/pos-products/original.png',3470.00,13000.00,9530.00,73.31,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(130,19,NULL,'VAR-264532-168b','Chicken Crips Lada Hitam Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(131,19,NULL,'VAR-385304-ccbe','Chicken Crips Sadis Tanpa Nasi','images/pos-products/original.png',3897.50,13000.00,9102.50,70.02,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:12'),(132,19,NULL,'VAR-153831-a078','Chicken Crips Geprek Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(133,19,NULL,'VAR-879075-005d','Chicken Crips Mentai Tanpa Nasi','images/pos-products/original.png',4135.00,13000.00,8865.00,68.19,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(134,19,NULL,'VAR-291713-870b','Chicken Crips Teriyaki Tanpa Nasi','images/pos-products/original.png',3660.00,13000.00,9340.00,71.85,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(135,19,NULL,'VAR-144245-8fff','Chicken Crips Geprek Extra Mozzarella Tanpa Nasi','images/pos-products/original.png',810.00,13000.00,12190.00,93.77,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:11'),(136,19,NULL,'VAR-985207-cfcf','Chicken Crips Geprek Extra Mentai Tanpa Nasi','images/pos-products/original.png',2472.50,13000.00,10527.50,80.98,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(137,19,NULL,'VAR-266595-2f45','Chicken Crips BBQ + Nasi','images/pos-products/original.png',4062.73,16000.00,11937.27,74.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(138,19,NULL,'VAR-997620-858f','Chicken Crips Keju + Nasi','images/pos-products/original.png',3682.73,16000.00,12317.27,76.98,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(139,19,NULL,'VAR-805224-c4e4','Chicken Crips Lada Hitam + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(140,19,NULL,'VAR-833112-cbc8','Chicken Crips Sadis + Nasi','images/pos-products/original.png',4110.23,16000.00,11889.77,74.31,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(141,19,NULL,'VAR-893726-6760','Chicken Crips Geprek + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(142,19,NULL,'VAR-803248-3acd','Chicken Crips Mentai + Nasi','images/pos-products/original.png',4347.73,16000.00,11652.27,72.83,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(143,19,NULL,'VAR-591889-013b','Chicken Crips Teriyaki + Nasi','images/pos-products/original.png',3872.73,16000.00,12127.27,75.80,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(144,19,NULL,'VAR-323957-ed4a','Chicken Crips Geprek Extra Mozzarella + Nasi','images/pos-products/original.png',1022.73,16000.00,14977.27,93.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(145,19,NULL,'VAR-213753-fa79','Chicken Crips Geprek Extra Mentai + Nasi','images/pos-products/original.png',2685.23,16000.00,13314.77,83.22,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(146,19,NULL,'VAR-462607-14ab','Dada Garlic Tanpa Nasi','images/pos-products/original.png',3850.00,15000.00,11150.00,74.33,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(147,19,NULL,'VAR-888298-b707','Paha Atas Garlic Tanpa Nasi','images/pos-products/original.png',3850.00,15000.00,11150.00,74.33,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(148,19,NULL,'VAR-445260-0b0f','Paha Bawah Garlic Tanpa Nasi','images/pos-products/original.png',3850.00,13000.00,9150.00,70.38,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(149,19,NULL,'VAR-745452-03bb','Sayap Garlic Tanpa Nasi','images/pos-products/original.png',3850.00,11000.00,7150.00,65.00,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(150,19,NULL,'VAR-273954-4986','Chicken Crips Garlic Tanpa Nasi','images/pos-products/original.png',3850.00,13000.00,9150.00,70.38,0,1,'2026-06-18 17:38:15','2026-06-19 07:08:13'),(151,19,NULL,'VAR-706795-31e3','Dada Garlic + Nasi','images/pos-products/original.png',4062.73,18000.00,13937.27,77.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:44'),(152,19,NULL,'VAR-948726-ccd1','Paha Atas Garlic + Nasi','images/pos-products/original.png',4062.73,18000.00,13937.27,77.43,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:45'),(153,19,NULL,'VAR-959649-2642','Paha Bawah Garlic + Nasi','images/pos-products/original.png',4062.73,16000.00,11937.27,74.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:45'),(154,19,NULL,'VAR-545210-f3dd','Sayap Garlic + Nasi','images/pos-products/original.png',4062.73,13000.00,8937.27,68.75,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:45'),(155,19,NULL,'VAR-667002-b970','Chicken Crips Garlic + Nasi','images/pos-products/original.png',4062.73,16000.00,11937.27,74.61,0,1,'2026-06-18 17:38:15','2026-06-19 07:13:45'),(156,18,NULL,'KK-L-6a34e24a02ed1','Original Large','images/pos-products/kentang-kriwil.png',810.00,0.00,-810.00,0.00,0,1,NULL,'2026-06-19 07:08:10'),(157,18,NULL,'KK-L-6a34e24a2d6e5','Saus Sadis Large','images/pos-products/kentang-kriwil.png',3897.50,0.00,-3897.50,0.00,0,1,NULL,'2026-06-19 07:08:12'),(158,18,NULL,'KK-L-6a34e24a4d0a5','Saus Barbeque Spicy Large','images/pos-products/kentang-kriwil.png',3850.00,0.00,-3850.00,0.00,0,1,NULL,'2026-06-19 07:08:12'),(159,18,NULL,'KK-L-6a34e24a7ce70','Saus Teriyaki Large','images/pos-products/kentang-kriwil.png',3660.00,0.00,-3660.00,0.00,0,1,NULL,'2026-06-19 07:08:13'),(160,18,NULL,'KK-L-6a34e24b405c1','Saus Lada Hitam Large','images/pos-products/kentang-kriwil.png',810.00,0.00,-810.00,0.00,0,1,NULL,'2026-06-19 07:08:10'),(161,18,NULL,'KK-L-6a34e24b7824f','Saus Keju Large','images/pos-products/kentang-kriwil.png',3470.00,0.00,-3470.00,0.00,0,1,NULL,'2026-06-19 07:08:12'),(162,18,NULL,'KK-L-6a34e24b902b8','Saus Mentai Large','images/pos-products/kentang-kriwil.png',4135.00,0.00,-4135.00,0.00,0,1,NULL,'2026-06-19 07:08:13'),(163,18,NULL,'KK-L-6a34e24badd0e','Sambal Master Large','images/pos-products/kentang-kriwil.png',810.00,0.00,-810.00,0.00,0,1,NULL,'2026-06-19 07:08:10'),(164,18,NULL,'KK-L-6a34e24bc7546','Smocky Saus Mentai Large','images/pos-products/kentang-kriwil.png',4135.00,0.00,-4135.00,0.00,0,1,NULL,'2026-06-19 07:08:13'),(165,18,NULL,'KK-L-6a34e24be30e4','Smocky Keju Mozzarella Large','images/pos-products/kentang-kriwil.png',810.00,0.00,-810.00,0.00,0,1,NULL,'2026-06-19 07:08:10'),(166,18,NULL,'KK-L-6a34e24c2401c','Saus Garlic Large','images/pos-products/kentang-kriwil.png',3850.00,0.00,-3850.00,0.00,0,1,NULL,'2026-06-19 07:08:13');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `product_type` enum('single','variant_parent','bundle') DEFAULT 'single',
  `unit_name` varchar(50) DEFAULT 'porsi',
  `base_hpp` decimal(15,2) DEFAULT 0.00,
  `base_price` decimal(15,2) DEFAULT 0.00,
  `margin_amount` decimal(15,2) DEFAULT 0.00,
  `margin_percent` decimal(10,2) DEFAULT 0.00,
  `lifetime_qty_sold` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_outlet` (`outlet_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,130005,NULL,'PRD-491783','Nasi',NULL,'images/pos-products/nasi.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(2,130005,NULL,'PRD-980004','Saus',NULL,'images/pos-products/celup-saus.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(3,130005,NULL,'PRD-447564','1 ekor ayam original',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,1.00,1,'2026-06-18 17:38:15','2026-07-11 21:50:02'),(4,130005,NULL,'PRD-902486','1 ekor ayam + saus',NULL,'images/pos-products/celup-saus.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(5,130005,NULL,'PRD-294297','Thai Tea Lumut',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(6,130005,NULL,'PRD-389004','Korean Strawberry',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(7,130005,NULL,'PRD-958195','Coffee Latte Ice',NULL,'images/pos-products/kopi.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(8,130005,NULL,'PRD-757296','Taro Ice',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(9,130005,NULL,'PRD-542990','Cokelat Ice',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(10,130005,NULL,'PRD-609605','Paket 20rb',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(11,130005,NULL,'PRD-312810','Paket 22rb',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(12,130005,NULL,'PRD-471597','Tanpa Nasi 15rb',NULL,'images/pos-products/nasi.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(13,130005,NULL,'PRD-395381','Nasi 5rb',NULL,'images/pos-products/nasi.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(14,130005,NULL,'PRD-666787','Lumer 25RB',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(15,130005,NULL,'PRD-105944','Lumer 30rb',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(16,130005,NULL,'PRD-860826','Paket 12.500',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:38:15','2026-06-18 17:38:15'),(17,130012,NULL,'PRD-397664','Minuman Seduh',NULL,'images/pos-products/matcha.png','variant_parent','porsi',0.00,0.00,0.00,0.00,6.00,1,'2026-06-18 17:38:15','2026-07-08 19:13:41'),(18,130002,NULL,'PRD-535125','Kentang Kriwil',NULL,'images/pos-products/kentang-kriwil.png','variant_parent','porsi',0.00,0.00,0.00,0.00,1.00,1,'2026-06-18 17:38:15','2026-07-11 22:06:29'),(19,130001,NULL,'PRD-141308','Ayam Crispy',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,2.00,1,'2026-06-18 17:38:15','2026-07-11 12:42:24'),(20,130003,NULL,'PRD-MAT','Matcha Series',NULL,'images/pos-products/matcha.png','variant_parent','porsi',0.00,0.00,0.00,0.00,22.00,1,'2026-06-18 17:47:01','2026-07-12 01:54:24'),(21,130006,NULL,'PRD-PRO','Paket Promo',NULL,'images/pos-products/original.png','variant_parent','porsi',0.00,0.00,0.00,0.00,0.00,1,'2026-06-18 17:47:01',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `raw_material_id` bigint(20) unsigned NOT NULL,
  `qty` decimal(18,4) NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_order` (`purchase_order_id`),
  KEY `idx_poi_material` (`raw_material_id`),
  KEY `fk_poi_unit` (`unit_id`),
  CONSTRAINT `fk_poi_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_poi_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_poi_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `po_number` varchar(100) NOT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_status` enum('paid','unpaid','partial') DEFAULT 'paid',
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `discount` decimal(15,2) DEFAULT 0.00,
  `tax` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `debt_amount` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchase_order_number` (`po_number`),
  KEY `idx_po_outlet_date` (`outlet_id`,`purchase_date`),
  KEY `idx_po_vendor` (`vendor_id`),
  KEY `fk_po_created_by` (`created_by`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_po_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_po_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_recommendations`
--

DROP TABLE IF EXISTS `purchase_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `recommendation_date` date NOT NULL,
  `raw_material_id` bigint(20) unsigned NOT NULL,
  `current_stock` decimal(18,4) DEFAULT 0.0000,
  `min_stock` decimal(18,4) DEFAULT 0.0000,
  `forecast_usage` decimal(18,4) DEFAULT 0.0000,
  `recommended_qty` decimal(18,4) DEFAULT 0.0000,
  `urgency` enum('low','medium','high','critical') DEFAULT 'medium',
  `reason` text DEFAULT NULL,
  `status` enum('open','ordered','ignored','completed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_rec_outlet_date` (`outlet_id`,`recommendation_date`),
  KEY `idx_purchase_rec_material` (`raw_material_id`),
  CONSTRAINT `fk_purchase_rec_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_purchase_rec_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_recommendations`
--

LOCK TABLES `purchase_recommendations` WRITE;
/*!40000 ALTER TABLE `purchase_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_material_categories`
--

DROP TABLE IF EXISTS `raw_material_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raw_material_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_raw_material_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=110013 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_material_categories`
--

LOCK TABLES `raw_material_categories` WRITE;
/*!40000 ALTER TABLE `raw_material_categories` DISABLE KEYS */;
INSERT INTO `raw_material_categories` VALUES (1,'Ayam',1),(2,'Bahan Pelengkap',2),(3,'Kentang',3),(5,'Minuman',5),(8,'Saus',8),(9,'Tepung',9),(110002,'Bumbu & Tepung',2),(110006,'Lain-lain',99),(110007,'Bahan Pokok',0),(110009,'Bahan Makanan',0),(110010,'Bumbu',0),(110011,'Operasional',0),(110012,'Kemasan',0);
/*!40000 ALTER TABLE `raw_material_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_materials`
--

DROP TABLE IF EXISTS `raw_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raw_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `stock_qty` decimal(18,4) DEFAULT 0.0000,
  `average_cost` decimal(15,4) DEFAULT 0.0000,
  `min_stock_qty` decimal(18,4) DEFAULT 0.0000,
  `lead_time_days` int(11) DEFAULT 0,
  `is_long_lead_time` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_raw_materials_sku` (`sku`),
  KEY `idx_raw_materials_category` (`category_id`),
  KEY `idx_raw_materials_unit` (`unit_id`),
  KEY `idx_raw_materials_stock` (`stock_qty`,`min_stock_qty`),
  CONSTRAINT `fk_raw_materials_category` FOREIGN KEY (`category_id`) REFERENCES `raw_material_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_raw_materials_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_materials`
--

LOCK TABLES `raw_materials` WRITE;
/*!40000 ALTER TABLE `raw_materials` DISABLE KEYS */;
INSERT INTO `raw_materials` VALUES (1,5,2,'RM-001','Air',-2229.9992,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(2,1,5,'RM-002','Ayam 1 Ekor',-0.1000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 21:50:02'),(3,110007,1,'RM-003','Beras',-200.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 12:42:24'),(4,5,1,'RM-004','Bubuk Coklat',750.0000,102.6667,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:13'),(5,5,1,'RM-005','Bubuk Matcha',60.0000,137.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(6,5,1,'RM-006','Bubuk Taro',425.0000,86.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(7,110010,1,'RM-007','Bumbu 1 (2 sdm)',1500.0000,70.3000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:09'),(8,110010,1,'RM-008','Bumbu Marinasi Ayam',4998.0000,105.4500,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 21:50:02'),(9,110009,1,'RM-009','Cabe Kriting Merah',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(10,110009,1,'RM-010','Cabe Rawit Merah',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(11,110012,3,'RM-011','Cup 14 oz',-22.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(12,1,7,'RM-012','Dada Mentah',-1.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 12:42:24'),(13,5,1,'RM-013','Es Batu',-22.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(14,110011,9,'RM-014','Gas 3kg untuk 200 potong ayam',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(15,110009,1,'RM-015','Gula Pasir',-500.0006,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(16,2,1,'RM-016','Keju Mozzarella',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(17,3,1,'RM-017','Kentang Kriwil',-1.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 22:06:29'),(18,110012,6,'RM-018','Kertas Nasi',400.0000,270.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:11'),(19,1,7,'RM-019','Kulit Mentah',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(20,110007,2,'RM-020','Minyak Goreng',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(21,110012,3,'RM-021','Packaging Alumunium Foil',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(22,110012,3,'RM-022','Packaging Ayam',-1.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 21:50:02'),(23,110012,3,'RM-023','Packaging Box Besar',498.0000,1022.7272,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 12:42:24'),(24,110012,3,'RM-024','Packaging Retail',299.0000,388.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 22:06:29'),(25,1,7,'RM-025','Paha Atas Mentah',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(26,1,7,'RM-026','Paha Bawah Mentah',-1.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-08 19:08:28'),(27,8,1,'RM-027','Sambal Geprek',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(28,8,1,'RM-028','Saus Barbeque Spicy',4500.0000,60.8000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:12'),(29,8,1,'RM-029','Saus Keju',2950.0000,53.2000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 12:42:24'),(30,8,1,'RM-030','Saus Lada Hitam',-50.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-08 19:08:28'),(31,8,1,'RM-031','Saus Mentai',1000.0000,66.5000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:12'),(32,8,3,'RM-032','Saus Sachet Cabe/Tomat',-6.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-11 21:50:02'),(33,8,1,'RM-033','Saus Sadis',3000.0000,61.7500,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:12'),(34,8,1,'RM-034','Saus Teriyaki',1000.0000,57.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-19 07:08:13'),(35,1,7,'RM-035','Sayap Mentah',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(36,110012,3,'RM-036','Sedotan',-202.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(37,5,2,'RM-037','Sticky Milky',-120.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:44:59'),(38,5,2,'RM-038','Susu Full Cream (Diganti Sub-Resep)',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(39,5,1,'RM-039','T.e.h (16 sendok takar)',0.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-06-18 15:16:52'),(40,110012,3,'RM-040','Tutup Cup',-22.0000,0.0000,0.0000,0,0,1,'2026-06-18 15:16:52','2026-07-12 01:54:24'),(41,9,1,'RM-041','Tepung Krispy',35000.0000,18.1014,0.0000,0,0,1,'2026-06-18 15:20:57','2026-06-19 07:08:09'),(42,1,2,'','Air Panas untuk larutan Matcha',0.0000,0.0000,0.0000,0,0,1,'2026-06-19 03:52:55','2026-06-19 03:52:55'),(44,1,2,'AP-MATCHA-1781841191','Air Panas untuk larutan Matcha',-1100.0000,0.0000,0.0000,0,0,1,'2026-06-19 03:53:11','2026-07-12 01:54:24'),(45,1,2,'AP-COKLAT-1781841191','Air Panas u/ larut bubuk coklat',-200.0000,0.0000,0.0000,0,0,1,'2026-06-19 03:53:11','2026-07-12 01:44:59'),(46,1,2,'AP-TARO-1781841191','Air Panas u/ larut bubuk taro',-125.0000,0.0000,0.0000,0,0,1,'2026-06-19 03:53:11','2026-07-12 01:54:24'),(47,1,6,'PK-KECIL-1781843465','Packaging Box Kecil',300.0000,810.0000,0.0000,0,0,1,'2026-06-19 04:31:05','2026-06-19 07:08:09'),(48,1,1,'RM-SM-1781850243','Sambal Master',-25.0000,0.0000,0.0000,0,0,1,'2026-06-19 06:24:03','2026-07-11 22:06:29'),(49,1,1,'RM-SG-1781850243','Saus Garlic',500.0000,60.8000,0.0000,0,0,1,'2026-06-19 06:24:03','2026-06-19 07:08:13'),(50,1,1,'RM-SUSU-1781852869','Susu Bubuk',280.0000,114.0000,0.0000,0,0,1,'2026-06-19 07:07:49','2026-07-12 01:54:24'),(52,110012,3,'PKG-JUMBO-1781853219','Packaging DCC Jumbo',50.0000,1800.0000,0.0000,0,0,1,'2026-06-19 07:13:39','2026-06-19 07:13:39');
/*!40000 ALTER TABLE `raw_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipt_claims`
--

DROP TABLE IF EXISTS `receipt_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `claim_code` varchar(40) NOT NULL,
  `claim_points` int(11) NOT NULL DEFAULT 0,
  `status` enum('unclaimed','claimed','expired','cancelled') NOT NULL DEFAULT 'unclaimed',
  `claimed_by_member_id` int(11) DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_receipt_claim_code` (`claim_code`),
  UNIQUE KEY `uniq_receipt_claim_transaction` (`transaction_id`),
  KEY `idx_receipt_claims_status` (`status`),
  KEY `idx_receipt_claims_member` (`claimed_by_member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_claims`
--

LOCK TABLES `receipt_claims` WRITE;
/*!40000 ALTER TABLE `receipt_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipt_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_cost_logs`
--

DROP TABLE IF EXISTS `recipe_cost_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_cost_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned NOT NULL,
  `old_hpp` decimal(15,2) DEFAULT 0.00,
  `new_hpp` decimal(15,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `recalculated_by` bigint(20) unsigned DEFAULT NULL,
  `recalculated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipe_cost_recipe` (`recipe_id`),
  KEY `idx_recipe_cost_variant` (`product_variant_id`),
  KEY `fk_recipe_cost_user` (`recalculated_by`),
  CONSTRAINT `fk_recipe_cost_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipe_cost_user` FOREIGN KEY (`recalculated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_recipe_cost_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_cost_logs`
--

LOCK TABLES `recipe_cost_logs` WRITE;
/*!40000 ALTER TABLE `recipe_cost_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_cost_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_items`
--

DROP TABLE IF EXISTS `recipe_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `item_type` enum('raw_material','sub_recipe') NOT NULL DEFAULT 'raw_material',
  `raw_material_id` bigint(20) unsigned DEFAULT NULL,
  `sub_recipe_id` bigint(20) unsigned DEFAULT NULL,
  `qty` decimal(18,4) NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `cost_per_unit` decimal(15,4) DEFAULT 0.0000,
  `total_cost` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipe_items_recipe` (`recipe_id`),
  KEY `idx_recipe_items_material` (`raw_material_id`),
  KEY `idx_recipe_items_unit` (`unit_id`),
  KEY `idx_sub_recipe` (`sub_recipe_id`),
  CONSTRAINT `fk_recipe_items_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_recipe_items_recipe` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipe_items_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=583 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_items`
--

LOCK TABLES `recipe_items` WRITE;
/*!40000 ALTER TABLE `recipe_items` DISABLE KEYS */;
INSERT INTO `recipe_items` VALUES (1,1,'raw_material',28,NULL,250.0000,1,60.8000,15200.00,NULL),(2,1,'raw_material',1,NULL,75.0000,2,0.0000,0.00,NULL),(3,2,'raw_material',29,NULL,250.0000,1,53.2000,13300.00,NULL),(4,2,'raw_material',1,NULL,125.0000,2,0.0000,0.00,NULL),(5,3,'raw_material',28,NULL,250.0000,1,60.8000,15200.00,NULL),(6,3,'raw_material',1,NULL,75.0000,2,0.0000,0.00,NULL),(7,4,'raw_material',29,NULL,250.0000,1,53.2000,13300.00,NULL),(8,4,'raw_material',1,NULL,125.0000,2,0.0000,0.00,NULL),(9,5,'raw_material',30,NULL,250.0000,1,0.0000,0.00,NULL),(10,5,'raw_material',1,NULL,75.0000,2,0.0000,0.00,NULL),(11,6,'raw_material',33,NULL,250.0000,1,61.7500,15437.50,NULL),(12,6,'raw_material',1,NULL,75.0000,2,0.0000,0.00,NULL),(13,7,'raw_material',34,NULL,250.0000,1,57.0000,14250.00,NULL),(14,7,'raw_material',1,NULL,75.0000,2,0.0000,0.00,NULL),(15,8,'raw_material',39,NULL,70.0000,1,0.0000,0.00,NULL),(16,8,'raw_material',1,NULL,2000.0000,2,0.0000,0.00,NULL),(17,9,'raw_material',15,NULL,1000.0000,1,0.0000,0.00,NULL),(18,9,'raw_material',1,NULL,500.0000,2,0.0000,0.00,NULL),(19,10,'raw_material',17,NULL,500.0000,1,0.0000,0.00,NULL),(20,10,'raw_material',20,NULL,20.0000,2,0.0000,0.00,NULL),(21,10,'raw_material',14,NULL,1.0000,9,0.0000,0.00,NULL),(22,11,'raw_material',3,NULL,1000.0000,1,0.0000,0.00,NULL),(23,12,'raw_material',2,NULL,1.0000,5,0.0000,0.00,NULL),(24,12,'raw_material',8,NULL,20.0000,1,105.4500,2109.00,NULL),(25,15,'raw_material',22,NULL,1.0000,6,0.0000,0.00,NULL),(27,15,'sub_recipe',NULL,12,1.0000,5,210.9000,210.90,NULL),(29,16,'sub_recipe',NULL,12,1.0000,5,210.9000,210.90,NULL),(30,16,'raw_material',32,NULL,6.0000,3,0.0000,0.00,NULL),(31,16,'raw_material',22,NULL,1.0000,6,0.0000,0.00,NULL),(32,18,'raw_material',36,NULL,20.0000,1,0.0000,0.00,NULL),(34,18,'raw_material',5,NULL,20.0000,1,137.0000,2740.00,NULL),(35,18,'raw_material',44,NULL,50.0000,2,0.0000,0.00,NULL),(36,18,'sub_recipe',NULL,9,25.0000,2,0.0000,0.00,NULL),(37,18,'sub_recipe',NULL,152,100.0000,2,11.4000,1140.00,NULL),(38,18,'raw_material',13,NULL,1.0000,8,0.0000,0.00,NULL),(39,18,'raw_material',11,NULL,1.0000,3,0.0000,0.00,NULL),(40,18,'raw_material',40,NULL,1.0000,3,0.0000,0.00,NULL),(41,18,'raw_material',36,NULL,1.0000,3,0.0000,0.00,NULL),(42,17,'raw_material',5,NULL,20.0000,1,137.0000,2740.00,NULL),(43,17,'raw_material',44,NULL,50.0000,2,0.0000,0.00,NULL),(44,17,'sub_recipe',NULL,9,25.0000,2,0.0000,0.00,NULL),(45,17,'raw_material',37,NULL,15.0000,1,0.0000,0.00,NULL),(46,17,'raw_material',45,NULL,25.0000,2,0.0000,0.00,NULL),(47,17,'sub_recipe',NULL,152,100.0000,2,11.4000,1140.00,NULL),(48,17,'raw_material',13,NULL,1.0000,8,0.0000,0.00,NULL),(49,17,'raw_material',11,NULL,1.0000,3,0.0000,0.00,NULL),(50,17,'raw_material',40,NULL,1.0000,3,0.0000,0.00,NULL),(51,17,'raw_material',36,NULL,1.0000,3,0.0000,0.00,NULL),(52,19,'raw_material',5,NULL,20.0000,1,137.0000,2740.00,NULL),(53,19,'raw_material',44,NULL,50.0000,2,0.0000,0.00,NULL),(54,19,'sub_recipe',NULL,9,25.0000,2,0.0000,0.00,NULL),(55,19,'raw_material',6,NULL,15.0000,1,86.0000,1290.00,NULL),(56,19,'raw_material',46,NULL,25.0000,2,0.0000,0.00,NULL),(57,19,'sub_recipe',NULL,152,100.0000,2,11.4000,1140.00,NULL),(58,19,'raw_material',13,NULL,1.0000,8,0.0000,0.00,NULL),(59,19,'raw_material',11,NULL,1.0000,3,0.0000,0.00,NULL),(60,19,'raw_material',40,NULL,1.0000,3,0.0000,0.00,NULL),(61,19,'raw_material',36,NULL,1.0000,3,0.0000,0.00,NULL),(62,20,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(63,20,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(64,20,'raw_material',24,NULL,1.0000,6,388.0000,388.00,NULL),(65,21,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(66,21,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(67,21,'raw_material',24,NULL,1.0000,6,388.0000,388.00,NULL),(68,22,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(69,22,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(70,22,'raw_material',24,NULL,1.0000,6,388.0000,388.00,NULL),(71,23,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(72,23,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(73,23,'raw_material',24,NULL,1.0000,6,388.0000,388.00,NULL),(74,24,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(75,24,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(76,24,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(78,25,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(79,25,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(80,25,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(81,26,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(82,26,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(83,26,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(84,27,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(85,27,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(86,27,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(87,28,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(88,28,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(89,28,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(90,29,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(91,29,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(92,29,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(94,30,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(95,30,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(96,30,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(97,31,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(98,31,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(99,31,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(100,32,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(101,32,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(102,32,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(103,33,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(104,33,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(105,33,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(106,34,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(107,34,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(108,34,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(109,35,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(110,35,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(111,35,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(112,36,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(113,36,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(114,36,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(115,37,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(116,37,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(117,37,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(118,38,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(119,38,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(120,38,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(121,39,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(122,39,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(123,39,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(124,40,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(125,40,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(126,40,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(127,41,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(128,41,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(129,41,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(130,42,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(131,42,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(132,42,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(133,43,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(134,43,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(135,43,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(136,44,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(137,44,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(138,44,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(139,45,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(140,45,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(141,45,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(142,46,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(143,46,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(144,46,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(145,47,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(146,47,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(147,47,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(148,48,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(149,48,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(150,48,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(151,49,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(152,49,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(153,49,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(154,50,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(155,50,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(156,50,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(157,51,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(158,51,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(159,51,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(160,52,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(161,52,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(162,52,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(163,53,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(164,53,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(165,53,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(166,54,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(167,54,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(168,54,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(169,55,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(170,55,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(171,55,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(172,56,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(173,56,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(174,56,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(175,57,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(176,57,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(177,57,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(178,14,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(179,14,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(181,14,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(182,58,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(183,58,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(185,58,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(186,59,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(187,59,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(189,59,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(190,60,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(191,60,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(193,60,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(194,61,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(195,61,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(197,61,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(198,62,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(199,62,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(201,62,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(202,63,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(203,63,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(205,63,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(206,64,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(207,64,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(209,64,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(210,65,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(211,65,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(213,65,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(214,66,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(215,66,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(217,66,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(218,67,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(219,67,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(221,67,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(222,68,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(223,68,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(225,68,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(226,69,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(227,69,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(229,69,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(230,70,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(231,70,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(233,70,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(234,71,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(235,71,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(237,71,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(238,72,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(239,72,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(241,72,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(242,73,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(243,73,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(245,73,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(246,74,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(247,74,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(249,74,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(250,75,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(251,75,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(253,75,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(254,76,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(255,76,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(257,76,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(258,77,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(259,77,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(261,77,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(262,78,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(263,78,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(265,78,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(266,79,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(267,79,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(269,79,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(270,80,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(271,80,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(273,80,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(274,81,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(275,81,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(277,81,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(278,82,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(279,82,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(281,82,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(282,83,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(283,83,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(285,83,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(286,84,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(287,84,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(289,84,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(290,85,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(291,85,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(292,85,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(293,86,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(294,86,'raw_material',33,NULL,25.0000,1,61.7500,1543.75,NULL),(295,86,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(296,87,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(297,87,'raw_material',28,NULL,25.0000,1,60.8000,1520.00,NULL),(298,87,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(299,88,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(300,88,'raw_material',34,NULL,25.0000,1,57.0000,1425.00,NULL),(301,88,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(302,89,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(303,89,'raw_material',30,NULL,25.0000,1,0.0000,0.00,NULL),(304,89,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(305,90,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(306,90,'raw_material',29,NULL,25.0000,1,53.2000,1330.00,NULL),(307,90,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(308,91,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(309,91,'raw_material',31,NULL,25.0000,1,66.5000,1662.50,NULL),(310,91,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(311,92,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(312,92,'raw_material',48,NULL,25.0000,1,0.0000,0.00,NULL),(313,92,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(314,93,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(315,93,'raw_material',31,NULL,25.0000,1,66.5000,1662.50,NULL),(316,93,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(317,94,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(318,94,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(319,94,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(320,95,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(321,95,'raw_material',49,NULL,25.0000,1,60.8000,1520.00,NULL),(322,95,'raw_material',24,NULL,1.0000,3,388.0000,388.00,NULL),(323,96,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(324,96,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(325,96,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(326,97,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(327,97,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(328,97,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(329,98,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(330,98,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(331,98,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(332,99,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(333,99,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(334,99,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(335,100,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(336,100,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(337,100,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(338,101,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(339,101,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(340,101,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(341,102,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(342,102,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(343,102,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(344,103,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(345,103,'raw_material',48,NULL,50.0000,1,0.0000,0.00,NULL),(346,103,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(347,104,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(348,104,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(349,104,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(350,105,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(351,105,'raw_material',16,NULL,50.0000,1,0.0000,0.00,NULL),(352,105,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(353,106,'raw_material',17,NULL,1.0000,4,0.0000,0.00,NULL),(354,106,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(355,106,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(356,109,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(357,109,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(358,109,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(360,110,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(361,110,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(362,110,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(364,111,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(365,111,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(366,111,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(368,112,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(369,112,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(370,112,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(371,112,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(372,113,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(373,113,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(374,113,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(375,113,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(376,114,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(377,114,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(378,114,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(379,114,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(380,115,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(381,115,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(382,115,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(383,115,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(384,116,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(385,116,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(386,116,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(388,117,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(389,117,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(390,117,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(392,118,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(393,118,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(394,118,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(396,119,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(397,119,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(398,119,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(399,119,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(401,120,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(402,120,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(403,120,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(404,120,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(406,121,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(407,121,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(408,121,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(409,121,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(411,122,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(412,122,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(413,122,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(414,122,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(416,123,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(417,123,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(418,123,'raw_material',24,NULL,1.0000,6,388.0000,388.00,NULL),(419,124,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(420,124,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(421,124,'raw_material',32,NULL,1.0000,3,0.0000,0.00,NULL),(423,125,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(424,125,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(425,125,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(426,126,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(427,126,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(428,126,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(429,127,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(430,127,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(431,127,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(432,128,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(433,128,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(434,128,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(435,129,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(436,129,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(437,129,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(438,130,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(439,130,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(440,130,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(441,131,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(442,131,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(443,131,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(444,132,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(445,132,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(446,132,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(447,132,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(448,133,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(449,133,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(450,133,'raw_material',31,NULL,25.0000,1,66.5000,1662.50,NULL),(451,133,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(452,13,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(453,13,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(454,13,'raw_material',28,NULL,50.0000,1,60.8000,3040.00,NULL),(456,134,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(457,134,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(458,134,'raw_material',29,NULL,50.0000,1,53.2000,2660.00,NULL),(460,135,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(461,135,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(462,135,'raw_material',30,NULL,50.0000,1,0.0000,0.00,NULL),(464,136,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(465,136,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(466,136,'raw_material',33,NULL,50.0000,1,61.7500,3087.50,NULL),(468,137,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(469,137,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(470,137,'raw_material',27,NULL,50.0000,1,0.0000,0.00,NULL),(472,138,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(473,138,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(474,138,'raw_material',31,NULL,50.0000,1,66.5000,3325.00,NULL),(476,139,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(477,139,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(478,139,'raw_material',34,NULL,50.0000,1,57.0000,2850.00,NULL),(480,140,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(481,140,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(482,140,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(483,140,'raw_material',16,NULL,25.0000,1,0.0000,0.00,NULL),(485,141,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(486,141,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(487,141,'raw_material',27,NULL,25.0000,1,0.0000,0.00,NULL),(488,141,'raw_material',31,NULL,25.0000,1,66.5000,1662.50,NULL),(490,142,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(491,142,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(492,142,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(493,143,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(494,143,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(495,143,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(496,144,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(497,144,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(498,144,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(499,145,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(500,145,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(501,145,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(502,146,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(503,146,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(504,146,'raw_material',47,NULL,1.0000,6,810.0000,810.00,NULL),(505,147,'raw_material',12,NULL,1.0000,7,0.0000,0.00,NULL),(506,147,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(507,147,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(509,148,'raw_material',25,NULL,1.0000,7,0.0000,0.00,NULL),(510,148,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(511,148,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(513,149,'raw_material',26,NULL,1.0000,7,0.0000,0.00,NULL),(514,149,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(515,149,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(517,150,'raw_material',35,NULL,1.0000,7,0.0000,0.00,NULL),(518,150,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(519,150,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(521,151,'raw_material',19,NULL,1.0000,7,0.0000,0.00,NULL),(522,151,'sub_recipe',NULL,11,1.0000,4,0.0000,0.00,NULL),(523,151,'raw_material',49,NULL,50.0000,1,60.8000,3040.00,NULL),(525,152,'raw_material',50,NULL,100.0000,1,114.0000,11400.00,NULL),(526,152,'raw_material',1,NULL,900.0000,2,0.0000,0.00,NULL),(527,24,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(528,109,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(529,110,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(530,111,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(531,14,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(532,58,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(533,59,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(534,60,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(535,29,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(536,116,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(537,117,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(538,118,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(539,61,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(540,62,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(541,63,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(542,64,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(543,65,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(544,66,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(545,67,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(546,68,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(547,69,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(548,70,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(549,71,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(550,72,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(551,73,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(552,74,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(553,75,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(554,76,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(555,77,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(556,78,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(557,79,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(558,80,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(559,119,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(560,120,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(561,121,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(562,122,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(563,81,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(564,82,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(565,83,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(566,84,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(567,124,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(568,13,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(569,134,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(570,135,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(571,136,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(572,137,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(573,138,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(574,139,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(575,140,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(576,141,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(577,147,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(578,148,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(579,149,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(580,150,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(581,151,'raw_material',23,NULL,1.0000,3,1022.7272,1022.73,NULL),(582,15,'raw_material',28,NULL,240.0000,1,60.8000,14592.00,NULL);
/*!40000 ALTER TABLE `recipe_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `recipe_type` enum('final','sub_recipe') NOT NULL DEFAULT 'final',
  `yield_qty` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `yield_unit_id` bigint(20) unsigned DEFAULT NULL,
  `yield_unit_label` varchar(50) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `total_hpp` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipes_variant` (`product_variant_id`),
  KEY `idx_recipes_active` (`product_variant_id`,`is_active`),
  KEY `idx_recipe_type` (`recipe_type`),
  CONSTRAINT `fk_recipes_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,NULL,'Saus Barbeque Spicy','sub_recipe',1.0000,1,NULL,1,15200.00,1,NULL,'2026-06-18 15:31:25','2026-06-19 07:08:12'),(2,NULL,'Saus Keju','sub_recipe',1.0000,1,NULL,1,13300.00,1,NULL,'2026-06-18 15:34:49','2026-06-19 07:08:12'),(3,NULL,'Saus Barbeque Spicy (Sub-Resep)','sub_recipe',330.0000,1,NULL,1,15200.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 07:08:12'),(4,NULL,'Saus Keju (Sub-Resep)','sub_recipe',370.0000,1,NULL,1,13300.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 07:08:12'),(5,NULL,'Saus Lada Hitam (Sub-Resep)','sub_recipe',330.0000,1,NULL,1,0.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 02:35:22'),(6,NULL,'Saus Sadis (Sub-Resep)','sub_recipe',330.0000,1,NULL,1,15437.50,1,NULL,'2026-06-18 15:56:29','2026-06-19 07:08:12'),(7,NULL,'Saus Teriyaki (Sub-Resep)','sub_recipe',330.0000,1,NULL,1,14250.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 07:08:13'),(8,NULL,'T.e.h Matang','sub_recipe',1800.0000,2,NULL,1,0.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 02:35:22'),(9,NULL,'Gula Cair','sub_recipe',1100.0000,2,NULL,1,0.00,1,NULL,'2026-06-18 15:56:29','2026-06-19 02:35:22'),(10,NULL,'Kentang Goreng Matang','sub_recipe',307.0000,1,NULL,1,0.00,1,NULL,'2026-06-18 15:56:30','2026-06-19 02:35:22'),(11,NULL,'Nasi Matang','sub_recipe',10.0000,4,NULL,1,0.00,1,NULL,'2026-06-18 15:56:30','2026-06-19 02:35:22'),(12,NULL,'Ayam Dimarinasi','sub_recipe',10.0000,7,NULL,1,2109.00,1,NULL,'2026-06-18 15:56:30','2026-06-19 07:08:09'),(13,137,'Ayam Crispy - Chicken Crips BBQ + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 02:23:38','2026-06-19 07:13:44'),(14,85,'Ayam Crispy - Dada BBQ Spicy + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 02:30:39','2026-06-19 07:13:42'),(15,4,'1 ekor ayam + saus - Default','final',1.0000,4,NULL,1,14802.90,1,NULL,'2026-06-19 03:24:14','2026-06-19 08:05:22'),(16,3,'1 ekor ayam original - Default','final',1.0000,4,NULL,1,210.90,1,NULL,'2026-06-19 03:25:26','2026-06-19 07:08:09'),(17,124,'Matcha Series - Minuman Matcha Coklat','final',1.0000,4,NULL,1,3880.00,1,NULL,'2026-06-19 03:27:40','2026-06-19 07:08:14'),(18,122,'Matcha Series - Minuman Matcha Latte','final',1.0000,4,NULL,1,3880.00,1,NULL,'2026-06-19 03:51:20','2026-06-19 07:08:14'),(19,123,'Matcha Series - Minuman Matcha Taro','final',1.0000,4,NULL,1,5170.00,1,NULL,'2026-06-19 03:53:12','2026-06-19 07:08:14'),(20,41,'Ayam Crispy - Dada Original Tanpa Nasi','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 04:27:58','2026-06-19 07:08:11'),(21,44,'Ayam Crispy - Sayap Original Tanpa Nasi','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 04:27:59','2026-06-19 07:08:11'),(22,42,'Ayam Crispy - Paha Atas Original Tanpa Nasi','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 04:27:59','2026-06-19 07:08:11'),(23,43,'Ayam Crispy - Paha Bawah Original Tanpa Nasi','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 04:27:59','2026-06-19 07:08:11'),(24,45,'Ayam Crispy - Dada Original + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:27:59','2026-06-19 07:13:42'),(25,53,'Ayam Crispy - Dada Keju Tanpa Nasi','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 04:31:05','2026-06-19 07:08:12'),(26,56,'Ayam Crispy - Sayap Keju Tanpa Nasi','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 04:31:05','2026-06-19 07:08:12'),(27,54,'Ayam Crispy - Paha Atas Keju Tanpa Nasi','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 04:31:05','2026-06-19 07:08:12'),(28,55,'Ayam Crispy - Paha Bawah Keju Tanpa Nasi','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 04:31:06','2026-06-19 07:08:12'),(29,89,'Ayam Crispy - Dada Keju + Nasi','final',1.0000,4,NULL,1,3682.73,1,NULL,'2026-06-19 04:31:06','2026-06-19 07:13:42'),(30,49,'Ayam Crispy - Dada BBQ Spicy Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(31,50,'Ayam Crispy - Paha Atas BBQ Spicy Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(32,51,'Ayam Crispy - Paha Bawah BBQ Spicy Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(33,52,'Ayam Crispy - Sayap BBQ Spicy Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(34,57,'Ayam Crispy - Dada Lada Hitam Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:09'),(35,58,'Ayam Crispy - Paha Atas Lada Hitam Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:09'),(36,59,'Ayam Crispy - Paha Bawah Lada Hitam Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:09'),(37,60,'Ayam Crispy - Sayap Lada Hitam Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:09'),(38,61,'Ayam Crispy - Dada Sadis Tanpa Nasi','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(39,62,'Ayam Crispy - Paha Atas Sadis Tanpa Nasi','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 04:36:55','2026-06-19 07:08:12'),(40,63,'Ayam Crispy - Paha Bawah Sadis Tanpa Nasi','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:12'),(41,64,'Ayam Crispy - Sayap Sadis Tanpa Nasi','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:12'),(42,65,'Ayam Crispy - Dada Sambal Geprek Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:09'),(43,66,'Ayam Crispy - Paha Atas Sambal Geprek Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:09'),(44,67,'Ayam Crispy - Paha Bawah Sambal Geprek Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:10'),(45,68,'Ayam Crispy - Sayap Sambal Geprek Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:10'),(46,69,'Ayam Crispy - Dada Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:12'),(47,70,'Ayam Crispy - Paha Atas Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(48,71,'Ayam Crispy - Paha Bawah Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(49,72,'Ayam Crispy - Sayap Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(50,73,'Ayam Crispy - Dada Teriyaki Tanpa Nasi','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(51,74,'Ayam Crispy - Paha Atas Teriyaki Tanpa Nasi','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(52,75,'Ayam Crispy - Paha Bawah Teriyaki Tanpa Nasi','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(53,76,'Ayam Crispy - Sayap Teriyaki Tanpa Nasi','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 04:36:56','2026-06-19 07:08:13'),(54,81,'Ayam Crispy - Dada Geprek Extra Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:08:13'),(55,82,'Ayam Crispy - Paha Atas Geprek Extra Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:08:13'),(56,83,'Ayam Crispy - Paha Bawah Geprek Extra Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:08:13'),(57,84,'Ayam Crispy - Sayap Geprek Extra Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:08:13'),(58,86,'Ayam Crispy - Paha Atas BBQ Spicy + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:42'),(59,87,'Ayam Crispy - Paha Bawah BBQ Spicy + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:42'),(60,88,'Ayam Crispy - Sayap BBQ Spicy + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:42'),(61,93,'Ayam Crispy - Dada Lada Hitam + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(62,94,'Ayam Crispy - Paha Atas Lada Hitam + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(63,95,'Ayam Crispy - Paha Bawah Lada Hitam + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(64,96,'Ayam Crispy - Sayap Lada Hitam + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(65,97,'Ayam Crispy - Dada Sadis + Nasi','final',1.0000,4,NULL,1,4110.23,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(66,98,'Ayam Crispy - Paha Atas Sadis + Nasi','final',1.0000,4,NULL,1,4110.23,1,NULL,'2026-06-19 04:36:57','2026-06-19 07:13:43'),(67,99,'Ayam Crispy - Paha Bawah Sadis + Nasi','final',1.0000,4,NULL,1,4110.23,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(68,100,'Ayam Crispy - Sayap Sadis + Nasi','final',1.0000,4,NULL,1,4110.23,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(69,101,'Ayam Crispy - Dada Sambal Geprek + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(70,102,'Ayam Crispy - Paha Atas Sambal Geprek + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(71,103,'Ayam Crispy - Paha Bawah Sambal Geprek + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(72,104,'Ayam Crispy - Sayap Sambal Geprek + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(73,105,'Ayam Crispy - Dada Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(74,106,'Ayam Crispy - Paha Atas Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(75,107,'Ayam Crispy - Paha Bawah Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(76,108,'Ayam Crispy - Sayap Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(77,109,'Ayam Crispy - Dada Teriyaki + Nasi','final',1.0000,4,NULL,1,3872.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(78,110,'Ayam Crispy - Paha Atas Teriyaki + Nasi','final',1.0000,4,NULL,1,3872.73,1,NULL,'2026-06-19 04:36:58','2026-06-19 07:13:43'),(79,111,'Ayam Crispy - Paha Bawah Teriyaki + Nasi','final',1.0000,4,NULL,1,3872.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:43'),(80,112,'Ayam Crispy - Sayap Teriyaki + Nasi','final',1.0000,4,NULL,1,3872.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:43'),(81,117,'Ayam Crispy - Dada Geprek Extra Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:44'),(82,118,'Ayam Crispy - Paha Atas Geprek Extra Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:44'),(83,119,'Ayam Crispy - Paha Bawah Geprek Extra Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:44'),(84,120,'Ayam Crispy - Sayap Geprek Extra Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 04:36:59','2026-06-19 07:13:44'),(85,30,'Kentang Kriwil - Original','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 06:24:03','2026-06-19 07:08:11'),(86,31,'Kentang Kriwil - Saus Sadis','final',1.0000,4,NULL,1,1931.75,1,NULL,'2026-06-19 06:24:03','2026-06-19 07:08:12'),(87,32,'Kentang Kriwil - Saus Barbeque Spicy','final',1.0000,4,NULL,1,1908.00,1,NULL,'2026-06-19 06:24:03','2026-06-19 07:08:12'),(88,33,'Kentang Kriwil - Saus Teriyaki','final',1.0000,4,NULL,1,1813.00,1,NULL,'2026-06-19 06:24:03','2026-06-19 07:08:13'),(89,34,'Kentang Kriwil - Saus Lada Hitam','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:11'),(90,35,'Kentang Kriwil - Saus Keju','final',1.0000,4,NULL,1,1718.00,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:12'),(91,36,'Kentang Kriwil - Saus Mentai','final',1.0000,4,NULL,1,2050.50,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:13'),(92,37,'Kentang Kriwil - Sambal Master','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:12'),(93,38,'Kentang Kriwil - Smocky Saus Mentai','final',1.0000,4,NULL,1,2050.50,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:13'),(94,39,'Kentang Kriwil - Smocky Keju Mozzarella','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:12'),(95,40,'Kentang Kriwil - Saus Garlic','final',1.0000,4,NULL,1,1908.00,1,NULL,'2026-06-19 06:24:04','2026-06-19 07:08:13'),(96,156,'Kentang Kriwil - Original Large','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:31:38','2026-06-19 07:08:10'),(97,157,'Kentang Kriwil - Saus Sadis Large','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 06:31:38','2026-06-19 07:08:12'),(98,158,'Kentang Kriwil - Saus Barbeque Spicy Large','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:31:38','2026-06-19 07:08:12'),(99,159,'Kentang Kriwil - Saus Teriyaki Large','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 06:31:38','2026-06-19 07:08:13'),(100,160,'Kentang Kriwil - Saus Lada Hitam Large','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:10'),(101,161,'Kentang Kriwil - Saus Keju Large','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:12'),(102,162,'Kentang Kriwil - Saus Mentai Large','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:13'),(103,163,'Kentang Kriwil - Sambal Master Large','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:10'),(104,164,'Kentang Kriwil - Smocky Saus Mentai Large','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:13'),(105,165,'Kentang Kriwil - Smocky Keju Mozzarella Large','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:31:39','2026-06-19 07:08:10'),(106,166,'Kentang Kriwil - Saus Garlic Large','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:31:40','2026-06-19 07:08:13'),(107,121,'Ayam Crispy - Kentang Kriwil Original Saus Sachet','final',1.0000,4,NULL,1,0.00,1,NULL,'2026-06-19 06:41:43','2026-06-19 06:41:43'),(108,25,'Minuman Seduh - Nescafe Klasik','final',1.0000,4,NULL,1,0.00,1,NULL,'2026-06-19 06:41:55','2026-06-19 06:41:55'),(109,46,'Ayam Crispy - Paha Atas Original + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:13','2026-06-19 07:13:42'),(110,47,'Ayam Crispy - Paha Bawah Original + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:13','2026-06-19 07:13:42'),(111,48,'Ayam Crispy - Sayap Original + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:13:42'),(112,77,'Ayam Crispy - Dada Geprek Extra Mozzarella Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:08:10'),(113,78,'Ayam Crispy - Paha Atas Geprek Extra Mozzarella Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:08:11'),(114,79,'Ayam Crispy - Paha Bawah Geprek Extra Mozzarella Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:08:11'),(115,80,'Ayam Crispy - Sayap Geprek Extra Mozzarella Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:08:11'),(116,90,'Ayam Crispy - Paha Atas Keju + Nasi','final',1.0000,4,NULL,1,3682.73,1,NULL,'2026-06-19 06:45:14','2026-06-19 07:13:42'),(117,91,'Ayam Crispy - Paha Bawah Keju + Nasi','final',1.0000,4,NULL,1,3682.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:43'),(118,92,'Ayam Crispy - Sayap Keju + Nasi','final',1.0000,4,NULL,1,3682.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:43'),(119,113,'Ayam Crispy - Dada Geprek Extra Mozzarella + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:43'),(120,114,'Ayam Crispy - Paha Atas Geprek Extra Mozzarella + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:43'),(121,115,'Ayam Crispy - Paha Bawah Geprek Extra Mozzarella + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:43'),(122,116,'Ayam Crispy - Sayap Geprek Extra Mozzarella + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:13:44'),(123,126,'Ayam Crispy - Chicken Crips Original Tanpa Nasi','final',1.0000,4,NULL,1,388.00,1,NULL,'2026-06-19 06:45:15','2026-06-19 07:08:12'),(124,127,'Ayam Crispy - Chicken Crips Original + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:13:44'),(125,128,'Ayam Crispy - Chicken Crips BBQ Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:12'),(126,129,'Ayam Crispy - Chicken Crips Keju Tanpa Nasi','final',1.0000,4,NULL,1,3470.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:12'),(127,130,'Ayam Crispy - Chicken Crips Lada Hitam Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:11'),(128,131,'Ayam Crispy - Chicken Crips Sadis Tanpa Nasi','final',1.0000,4,NULL,1,3897.50,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:12'),(129,132,'Ayam Crispy - Chicken Crips Geprek Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:11'),(130,133,'Ayam Crispy - Chicken Crips Mentai Tanpa Nasi','final',1.0000,4,NULL,1,4135.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:13'),(131,134,'Ayam Crispy - Chicken Crips Teriyaki Tanpa Nasi','final',1.0000,4,NULL,1,3660.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:13'),(132,135,'Ayam Crispy - Chicken Crips Geprek Extra Mozzarella Tanpa Nasi','final',1.0000,4,NULL,1,810.00,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:11'),(133,136,'Ayam Crispy - Chicken Crips Geprek Extra Mentai Tanpa Nasi','final',1.0000,4,NULL,1,2472.50,1,NULL,'2026-06-19 06:45:16','2026-06-19 07:08:13'),(134,138,'Ayam Crispy - Chicken Crips Keju + Nasi','final',1.0000,4,NULL,1,3682.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(135,139,'Ayam Crispy - Chicken Crips Lada Hitam + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(136,140,'Ayam Crispy - Chicken Crips Sadis + Nasi','final',1.0000,4,NULL,1,4110.23,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(137,141,'Ayam Crispy - Chicken Crips Geprek + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(138,142,'Ayam Crispy - Chicken Crips Mentai + Nasi','final',1.0000,4,NULL,1,4347.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(139,143,'Ayam Crispy - Chicken Crips Teriyaki + Nasi','final',1.0000,4,NULL,1,3872.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(140,144,'Ayam Crispy - Chicken Crips Geprek Extra Mozzarella + Nasi','final',1.0000,4,NULL,1,1022.73,1,NULL,'2026-06-19 06:45:17','2026-06-19 07:13:44'),(141,145,'Ayam Crispy - Chicken Crips Geprek Extra Mentai + Nasi','final',1.0000,4,NULL,1,2685.23,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:13:44'),(142,146,'Ayam Crispy - Dada Garlic Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:08:13'),(143,147,'Ayam Crispy - Paha Atas Garlic Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:08:13'),(144,148,'Ayam Crispy - Paha Bawah Garlic Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:08:13'),(145,149,'Ayam Crispy - Sayap Garlic Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:08:13'),(146,150,'Ayam Crispy - Chicken Crips Garlic Tanpa Nasi','final',1.0000,4,NULL,1,3850.00,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:08:13'),(147,151,'Ayam Crispy - Dada Garlic + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:13:44'),(148,152,'Ayam Crispy - Paha Atas Garlic + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:13:45'),(149,153,'Ayam Crispy - Paha Bawah Garlic + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 06:45:18','2026-06-19 07:13:45'),(150,154,'Ayam Crispy - Sayap Garlic + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 06:45:19','2026-06-19 07:13:45'),(151,155,'Ayam Crispy - Chicken Crips Garlic + Nasi','final',1.0000,4,NULL,1,4062.73,1,NULL,'2026-06-19 06:45:19','2026-06-19 07:13:45'),(152,NULL,'Susu Full Cream','sub_recipe',1000.0000,2,NULL,1,11400.00,1,NULL,NULL,'2026-06-19 07:08:14');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super_admin','Full akses ke seluruh sistem, termasuk laporan owner, keuangan, strategi, sistem, dan audit.'),(2,'Administrator','administrator','Akses operasional, produk, stok, pembelian, dan laporan operasional tanpa laporan laba rugi bisnis.'),(3,'Cashier','cashier','Akses kasir/POS, stok harian, dan laporan transaksi shift sendiri.');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_forecasts`
--

DROP TABLE IF EXISTS `sales_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_forecasts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `forecast_date` date NOT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `raw_material_id` bigint(20) unsigned DEFAULT NULL,
  `forecast_qty` decimal(18,4) DEFAULT 0.0000,
  `recommended_purchase_qty` decimal(18,4) DEFAULT 0.0000,
  `confidence_level` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_forecast_outlet_date` (`outlet_id`,`forecast_date`),
  KEY `idx_forecast_variant` (`product_variant_id`),
  KEY `idx_forecast_material` (`raw_material_id`),
  CONSTRAINT `fk_forecast_material` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_forecast_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_forecast_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_forecasts`
--

LOCK TABLES `sales_forecasts` WRITE;
/*!40000 ALTER TABLE `sales_forecasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_forecasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_corrections`
--

DROP TABLE IF EXISTS `stock_corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_corrections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outlet_id` int(11) NOT NULL,
  `correction_type` enum('order_void','order_adjust','stock_addition','stock_reduction') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `raw_material_id` int(11) DEFAULT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `old_value` decimal(12,4) DEFAULT NULL,
  `new_value` decimal(12,4) DEFAULT NULL,
  `reason` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_correction_outlet` (`outlet_id`),
  KEY `idx_correction_type` (`correction_type`),
  KEY `idx_correction_ref` (`reference_type`,`reference_id`),
  KEY `idx_correction_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_corrections`
--

LOCK TABLES `stock_corrections` WRITE;
/*!40000 ALTER TABLE `stock_corrections` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_corrections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_notifications`
--

DROP TABLE IF EXISTS `system_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `notification_type` enum('stock','purchase_due','payment','payroll','system','forecast','warning') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `severity` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_outlet_read` (`outlet_id`,`is_read`),
  KEY `idx_notifications_type` (`notification_type`),
  CONSTRAINT `fk_notifications_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_notifications`
--

LOCK TABLES `system_notifications` WRITE;
/*!40000 ALTER TABLE `system_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_outlet_key` (`outlet_id`,`setting_key`),
  KEY `idx_settings_outlet` (`outlet_id`),
  CONSTRAINT `fk_settings_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=311003 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,1,'pb1_tax_percent','10','2026-05-19 16:13:43','2026-05-19 16:13:43'),(2,1,'service_charge_percent','0','2026-05-19 16:13:43','2026-05-19 16:13:43'),(3,1,'daily_salary_auto_expense','true','2026-05-19 16:13:43','2026-05-19 16:13:43'),(4,1,'printer_enabled','true','2026-05-19 16:13:43','2026-05-19 16:13:43'),(5,1,'cash_drawer_enabled','false','2026-05-19 16:13:43','2026-05-19 16:13:43'),(6,1,'payment_gateway_mode','sandbox','2026-05-19 16:13:43','2026-05-19 16:13:43'),(7,1,'qris_manual_verification','true','2026-05-19 16:13:43','2026-05-19 16:13:43'),(8,1,'stock_negative_allowed','false','2026-05-19 16:13:43','2026-05-19 16:13:43'),(9,1,'business_name','D?Celup Chicken Crispy','2026-05-19 16:13:43','2026-05-19 16:13:43'),(10,1,'currency','IDR','2026-05-19 16:13:43','2026-05-19 16:13:43'),(310002,1,'inventory_block_negative','0',NULL,NULL),(310003,1,'inventory_purchase_cutoff_date','2026-05-13',NULL,NULL),(310004,1,'payment_bank_account','Nomor rekening belum diatur',NULL,NULL),(310005,1,'payment_dana_number','Nomor DANA belum diatur',NULL,NULL),(310006,1,'payment_note','Setelah melakukan pembayaran online, silakan tunjukkan bukti pembayaran kepada kasir.',NULL,NULL),(310007,1,'payment_qris_image','assets/img/payment/qris-20260512-212418.jpg',NULL,NULL),(310008,1,'payment_qris_info','Scan QRIS outlet di kasir',NULL,NULL),(310009,1,'purchase_cost_method_default','weighted_average',NULL,NULL),(310010,1,'qris_dana_auto_payment','off',NULL,NULL),(310011,1,'receipt_footer','Terima kasih sudah memesan di D\'Celup Chicken Crispy',NULL,NULL),(310012,1,'store_name','D\'Celup Chicken Crispy',NULL,NULL),(310013,1,'tax_percent','0',NULL,NULL),(310014,1,'voucher_code','DCELUP',NULL,NULL),(310015,1,'voucher_enabled','1',NULL,NULL),(310016,1,'voucher_type','nominal',NULL,NULL),(310017,1,'voucher_value','5000',NULL,NULL),(311000,1,'roi_business_start_date','2026-05-17',NULL,'2026-05-18 14:18:19'),(311001,1,'roi_projection_working_days_month','20',NULL,'2026-05-18 17:18:14'),(311002,1,'roi_roi_note','ROI dihitung dari laba bersih transaksi setelah dikurangi pengeluaran operasional. BEP tercapai saat akumulasi laba bersih >= total modal aktif.',NULL,'2026-05-18 14:18:19');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_units_symbol` (`symbol`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'gr','gr'),(2,'ml','ml'),(3,'pcs','pcs'),(4,'porsi','porsi'),(5,'Ekor','Ekor'),(6,'lembar','lembar'),(7,'potong ','potong '),(8,'cup','cup'),(9,'Pemakaian','Pemakaian');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_module` (`role_id`,`module`),
  KEY `idx_permissions_role` (`role_id`),
  CONSTRAINT `fk_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `daily_salary` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_outlet` (`outlet_id`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,1,'Super Admin','admin','admin@simresto.local',NULL,'$2y$10$KEvnFSlp3RRZUpRp/I.v6ec03mQRWW73RIK5c4.GAWVq5gtQPg58m',0.00,1,'2026-06-12 07:11:24','2026-06-12 14:11:33'),(2,5,2,'Admin Kalibunder','admin-klb','','','$2y$10$0QW8RxD.7pnfKDewkyVWe.3B7vWfCgsOcKe/gpC51TIvACe1wKIIm',0.00,1,'2026-07-12 01:56:10','2026-07-12 01:56:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payables`
--

DROP TABLE IF EXISTS `vendor_payables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_payables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payables_outlet_status` (`outlet_id`,`status`),
  KEY `idx_payables_vendor` (`vendor_id`),
  KEY `idx_payables_po` (`purchase_order_id`),
  CONSTRAINT `fk_payables_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payables_po` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payables_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payables`
--

LOCK TABLES `vendor_payables` WRITE;
/*!40000 ALTER TABLE `vendor_payables` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(100) NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'fixed',
  `discount_value` decimal(15,2) DEFAULT 0.00,
  `min_subtotal` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code_outlet` (`outlet_id`,`code`),
  KEY `idx_vouchers_outlet` (`outlet_id`),
  CONSTRAINT `fk_vouchers_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-12 11:27:39
