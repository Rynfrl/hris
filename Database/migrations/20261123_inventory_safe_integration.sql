-- =============================================================================
-- SAFE INVENTORY INTEGRATION MIGRATION FOR HRIS & ERP SYSTEM
-- Migration: 20261123_inventory_safe_integration.sql
-- Database: hris
-- Description: Adds required tables & columns for upgraded Inventory module
--              WITHOUT data loss, WITHOUT stock reset, WITHOUT foreign key bypass.
-- =============================================================================

USE `hris`;

-- 1. Table settings (Ensure company & inventory preferences exist without overwriting HRIS identity)
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_npwp', '01.234.567.8-901.000'),
('theme_pref', 'light'),
('date_format', 'd-m-y'),
('default_threshold', '5'),
('notify_low', '1');

-- 2. Table storage_locations (Required by locations.php and product warehouse mapping)
CREATE TABLE IF NOT EXISTS `storage_locations` (
  `location_id` int unsigned NOT NULL AUTO_INCREMENT,
  `location_code` varchar(30) NOT NULL,
  `location_name` varchar(150) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `uk_storage_locations_code` (`location_code`),
  KEY `idx_storage_locations_name` (`location_name`),
  KEY `idx_storage_locations_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-seed storage locations matching existing product master data in HRIS
INSERT IGNORE INTO `storage_locations` (`location_code`, `location_name`, `area`, `is_active`) VALUES
('RAK-IT-A1', 'Rak IT-A1', 'Area Elektronik & IT', 1),
('RAK-IT-A2', 'Rak IT-A2', 'Area Elektronik & IT', 1),
('RAK-IT-A3', 'Rak IT-A3', 'Area Elektronik & IT', 1),
('RAK-IT-A4', 'Rak IT-A4', 'Area Elektronik & IT', 1),
('RAK-IT-A5', 'Rak IT-A5', 'Area Elektronik & IT', 1),
('RAK-ATK-B1', 'Rak ATK-B1', 'Area Alat Tulis Kantor', 1),
('RAK-ATK-B2', 'Rak ATK-B2', 'Area Alat Tulis Kantor', 1),
('RAK-ATK-B3', 'Rak ATK-B3', 'Area Alat Tulis Kantor', 1),
('RAK-ATK-B4', 'Rak ATK-B4', 'Area Alat Tulis Kantor', 1),
('RAK-ATK-B5', 'Rak ATK-B5', 'Area Alat Tulis Kantor', 1),
('GDG-F-01', 'Gudang F-01', 'Area Furniture & Kantor', 1),
('GDG-F-02', 'Gudang F-02', 'Area Furniture & Kantor', 1),
('GDG-LOG-01', 'Gudang Log-01', 'Area Logistik & Pengemasan', 1),
('GDG-LOG-02', 'Gudang Log-02', 'Area Logistik & Pengemasan', 1),
('GDG-LOG-03', 'Gudang Log-03', 'Area Logistik & Pengemasan', 1);

-- 3. Column status in stock_movements (aktif, karantina, draft, transfer, deleted)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_movements' AND COLUMN_NAME = 'status');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `stock_movements` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT "aktif" AFTER `reference_no`', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill any existing stock_movements with NULL or empty status to 'aktif'
UPDATE `stock_movements` SET `status` = 'aktif' WHERE `status` IS NULL OR `status` = '';

-- 4. Audit & soft-delete columns in categories
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'creation_reason');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `creation_reason` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'deletion_reason');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `deletion_reason` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'created_by');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `created_by` int unsigned DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'deleted_by');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `deleted_by` int unsigned DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'deleted_at');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `deleted_at` datetime DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'original_code');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `categories` ADD COLUMN `original_code` varchar(50) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Audit & soft-delete columns in suppliers
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'creation_reason');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `creation_reason` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'deletion_reason');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `deletion_reason` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'deletion_notes');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `deletion_notes` text DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'created_by');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `created_by` int unsigned DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'deleted_by');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `deleted_by` int unsigned DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'original_code');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `suppliers` ADD COLUMN `original_code` varchar(50) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Audit & soft-delete columns in products
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deletion_reason');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `products` ADD COLUMN `deletion_reason` varchar(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deletion_notes');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `products` ADD COLUMN `deletion_notes` text DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deleted_by');
SET @sql_stmt := IF(@col_exists = 0, 'ALTER TABLE `products` ADD COLUMN `deleted_by` int unsigned DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql_stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;
