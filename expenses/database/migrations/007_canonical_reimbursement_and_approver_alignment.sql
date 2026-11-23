-- Migration 007: Canonical Reimbursement Harmonization & Approver Authorization Alignment
-- Target: MySQL 8.0+ / MariaDB 10.4+
-- Safe and idempotent migration script

-- 1. Create canonical `reimbursements` table if not exists
CREATE TABLE IF NOT EXISTS `reimbursements` (
  `reimbursement_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` BIGINT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `recipient_name` VARCHAR(150) DEFAULT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `account_number` VARCHAR(100) DEFAULT NULL,
  `account_holder` VARCHAR(150) DEFAULT NULL,
  `proof_path` VARCHAR(255) DEFAULT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_reference` VARCHAR(150) DEFAULT NULL,
  `payment_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('PENDING','PROCESSING','PAID','FAILED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `processed_by` BIGINT UNSIGNED DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reimbursement_id`),
  UNIQUE KEY `uq_reimbursements_report` (`report_id`),
  KEY `idx_reimbursement_report` (`report_id`),
  KEY `idx_reimbursement_employee` (`employee_id`),
  KEY `idx_reimbursement_status` (`status`),
  KEY `fk_reimbursement_processed_by` (`processed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Ensure employee_id is nullable in reimbursements (no dummy 0 values)
SET @gt_mod_sql := (
    SELECT IF(
        IS_NULLABLE = 'NO',
        'ALTER TABLE `reimbursements` MODIFY COLUMN `employee_id` BIGINT UNSIGNED NULL DEFAULT NULL',
        'SELECT "reimbursements.employee_id is already nullable"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND column_name = 'employee_id'
);
PREPARE gt_mod_stmt FROM @gt_mod_sql;
EXECUTE gt_mod_stmt;
DEALLOCATE PREPARE gt_mod_stmt;

-- Convert any 0 employee_id to NULL
UPDATE `reimbursements` SET `employee_id` = NULL WHERE `employee_id` = 0;

-- 3. Ensure unique constraint on report_id in reimbursements (1 Report = 1 Consolidated Reimbursement)
SET @gt_uq_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `reimbursements` ADD UNIQUE KEY `uq_reimbursements_report` (`report_id`)',
        'SELECT "uq_reimbursements_report already exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND index_name = 'uq_reimbursements_report'
);
PREPARE gt_uq_stmt FROM @gt_uq_sql;
EXECUTE gt_uq_stmt;
DEALLOCATE PREPARE gt_uq_stmt;

-- 4. Ensure foreign key constraints on canonical reimbursements table
SET @gt_fk1_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `reimbursements` ADD CONSTRAINT `fk_reimbursement_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE',
        'SELECT "fk_reimbursement_report already exists"'
    )
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND constraint_name = 'fk_reimbursement_report'
);
PREPARE gt_fk1_stmt FROM @gt_fk1_sql;
EXECUTE gt_fk1_stmt;
DEALLOCATE PREPARE gt_fk1_stmt;

SET @gt_fk2_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `reimbursements` ADD CONSTRAINT `fk_reimbursement_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE',
        'SELECT "fk_reimbursement_employee already exists"'
    )
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND constraint_name = 'fk_reimbursement_employee'
);
PREPARE gt_fk2_stmt FROM @gt_fk2_sql;
EXECUTE gt_fk2_stmt;
DEALLOCATE PREPARE gt_fk2_stmt;

SET @gt_fk3_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `reimbursements` ADD CONSTRAINT `fk_reimbursement_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE',
        'SELECT "fk_reimbursement_processed_by already exists"'
    )
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND constraint_name = 'fk_reimbursement_processed_by'
);
PREPARE gt_fk3_stmt FROM @gt_fk3_sql;
EXECUTE gt_fk3_stmt;
DEALLOCATE PREPARE gt_fk3_stmt;

-- 5. Sync any missing consolidated records from legacy expense_reimbursements into canonical reimbursements
SET @has_legacy := (
    SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() AND table_name = 'expense_reimbursements'
);

SET @sync_sql := IF(
    @has_legacy > 0,
    'INSERT IGNORE INTO `reimbursements` (`report_id`, `employee_id`, `amount`, `status`, `notes`, `created_at`, `updated_at`)
     SELECT er.report_id, NULL, SUM(er.approved_amount),
            IF(MIN(er.status) = "PAID", "PAID", IF(MIN(er.status) = "PROCESSING", "PROCESSING", "PENDING")),
            "Sinkronisasi otomatis migrasi canonical reimbursements",
            MIN(er.created_at), MAX(er.updated_at)
     FROM `expense_reimbursements` er
     WHERE NOT EXISTS (SELECT 1 FROM `reimbursements` r WHERE r.report_id = er.report_id)
     GROUP BY er.report_id',
    'SELECT "No legacy expense_reimbursements table to sync"'
);
PREPARE sync_stmt FROM @sync_sql;
EXECUTE sync_stmt;
DEALLOCATE PREPARE sync_stmt;

-- 6. Ensure payments and failures tables point to canonical reimbursements
CREATE TABLE IF NOT EXISTS `expense_reimbursement_payments` (
  `payment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reimbursement_id` BIGINT UNSIGNED NOT NULL,
  `payment_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'BANK_TRANSFER',
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `bank_reference` VARCHAR(150) DEFAULT NULL,
  `proof_path` VARCHAR(255) DEFAULT NULL,
  `paid_by` BIGINT UNSIGNED NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_reimb_payment_number` (`payment_number`),
  KEY `idx_reimb_payments_reimb` (`reimbursement_id`),
  KEY `idx_reimb_payments_paid_by` (`paid_by`),
  KEY `idx_reimb_payments_date` (`payment_date`),
  CONSTRAINT `fk_reimb_payments_reimb` FOREIGN KEY (`reimbursement_id`) REFERENCES `reimbursements` (`reimbursement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_payments_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_reimbursement_failures` (
  `failure_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reimbursement_id` BIGINT UNSIGNED NOT NULL,
  `report_id` BIGINT UNSIGNED NOT NULL,
  `previous_status` VARCHAR(50) NOT NULL DEFAULT 'PROCESSING',
  `new_status` VARCHAR(50) NOT NULL DEFAULT 'FAILED',
  `failure_reason` TEXT NOT NULL,
  `failed_by` BIGINT UNSIGNED NOT NULL,
  `payment_reference` VARCHAR(150) DEFAULT NULL,
  `attempt_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`failure_id`),
  KEY `idx_reimb_fail_reimb` (`reimbursement_id`),
  KEY `idx_reimb_fail_report` (`report_id`),
  KEY `idx_reimb_fail_user` (`failed_by`),
  CONSTRAINT `fk_reimb_fail_reimb` FOREIGN KEY (`reimbursement_id`) REFERENCES `reimbursements` (`reimbursement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_fail_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_fail_user` FOREIGN KEY (`failed_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
