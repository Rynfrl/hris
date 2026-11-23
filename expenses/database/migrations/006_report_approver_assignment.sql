-- Migration: 006_report_approver_assignment.sql
-- Description: Add record-level manager assignment and authorization to expense reports

-- 1. Add approver_id to expense_reports if not exists
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'expense_reports' 
  AND COLUMN_NAME = 'approver_id';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE expense_reports ADD COLUMN approver_id BIGINT UNSIGNED NULL AFTER created_by, ADD CONSTRAINT fk_expense_reports_approver FOREIGN KEY (approver_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Create multi-approver or audit assignment table if not exists
CREATE TABLE IF NOT EXISTS `expense_report_approvers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `approver_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rep_approver` (`report_id`, `approver_id`),
  KEY `idx_rep_app_user` (`approver_id`),
  CONSTRAINT `fk_rep_app_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rep_app_user` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Backfill default manager (user_id = 2 or role MANAGER) for existing reports without approver
UPDATE `expense_reports`
SET `approver_id` = (
    SELECT u.user_id 
    FROM `users` u 
    JOIN `roles` r ON (r.role_id = u.role_id OR r.id_role = u.role_id)
    WHERE (r.role_name IN ('MANAGER', 'MANAGER_EXPENSE') OR u.username = 'rina_manager')
      AND u.deleted_at IS NULL
    ORDER BY u.user_id ASC
    LIMIT 1
)
WHERE `approver_id` IS NULL;

-- 4. Sync into expense_report_approvers
INSERT IGNORE INTO `expense_report_approvers` (`report_id`, `approver_id`, `assigned_at`)
SELECT `report_id`, `approver_id`, COALESCE(`created_at`, NOW())
FROM `expense_reports`
WHERE `approver_id` IS NOT NULL;
