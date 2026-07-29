-- Migration: Add print queue columns to `orders` table
-- This allows automatic background printing via a Local Print Agent

ALTER TABLE `orders` 
ADD COLUMN `print_status` ENUM('none', 'waiting', 'printing', 'printed', 'failed') NOT NULL DEFAULT 'none' AFTER `order_status`,
ADD COLUMN `print_error` TEXT NULL AFTER `print_status`,
ADD COLUMN `print_attempt` INT NOT NULL DEFAULT 0 AFTER `print_error`;
