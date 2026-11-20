-- Migration 003: Multi-Employee Expense Management System
-- MySQL 8.0+. Safe and idempotent migration using dynamic SQL and IF NOT EXISTS.
-- Tracked in schema_migrations.

-- 1. Create table expense_report_employees
CREATE TABLE IF NOT EXISTS `expense_report_employees` (
    `report_employee_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` BIGINT UNSIGNED NOT NULL,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `employee_number_snapshot` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `employee_name_snapshot` VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    `department_name_snapshot` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_report_employee` (`report_id`, `employee_id`),
    KEY `idx_rep_emp_report` (`report_id`),
    KEY `idx_rep_emp_employee` (`employee_id`),
    CONSTRAINT `fk_rep_emp_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rep_emp_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Modify expense_reports to make employee_id nullable and add created_by
SET @gt_sql := (
    SELECT IF(
        IS_NULLABLE = 'NO',
        'ALTER TABLE expense_reports MODIFY COLUMN employee_id BIGINT UNSIGNED NULL',
        'SELECT "expense_reports.employee_id is already nullable"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'expense_reports'
      AND column_name = 'employee_id'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expense_reports ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER description, ADD CONSTRAINT fk_expense_reports_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE',
        'SELECT "created_by already exists in expense_reports"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'expense_reports'
      AND column_name = 'created_by'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

-- 3. Add report_employee_id column to expense_report_items
SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expense_report_items ADD COLUMN report_employee_id BIGINT UNSIGNED NULL AFTER expense_id, ADD CONSTRAINT fk_report_item_report_employee FOREIGN KEY (report_employee_id) REFERENCES expense_report_employees(report_employee_id) ON DELETE CASCADE ON UPDATE CASCADE',
        'SELECT "report_employee_id already exists in expense_report_items"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'expense_report_items'
      AND column_name = 'report_employee_id'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

-- 4. Backfill existing expense_reports into expense_report_employees
INSERT IGNORE INTO expense_report_employees (report_id, employee_id, employee_number_snapshot, employee_name_snapshot, department_name_snapshot)
SELECT 
    r.report_id, 
    r.employee_id, 
    COALESCE(e.employee_number, CONCAT('EMP-', r.employee_id)), 
    COALESCE(e.full_name, 'Unknown Employee'), 
    d.department_name
FROM expense_reports r
JOIN employees e ON e.employee_id = r.employee_id
LEFT JOIN departments d ON d.department_id = e.department_id
WHERE r.employee_id IS NOT NULL;

-- 5. Backfill report_employee_id in expense_report_items
UPDATE expense_report_items eri
JOIN expense_report_employees ere ON ere.report_id = eri.report_id
JOIN expenses exp ON exp.expense_id = eri.expense_id AND exp.employee_id = ere.employee_id
SET eri.report_employee_id = ere.report_employee_id
WHERE eri.report_employee_id IS NULL;

UPDATE expense_report_items eri
JOIN expense_report_employees ere ON ere.report_id = eri.report_id
SET eri.report_employee_id = ere.report_employee_id
WHERE eri.report_employee_id IS NULL;

-- 6. Create table expense_postings
CREATE TABLE IF NOT EXISTS `expense_postings` (
    `posting_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` BIGINT UNSIGNED NOT NULL,
    `journal_reference` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `posting_date` DATE NOT NULL,
    `posted_by` BIGINT UNSIGNED NOT NULL,
    `posted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    UNIQUE KEY `uq_expense_postings_report` (`report_id`),
    KEY `idx_expense_postings_date` (`posting_date`),
    KEY `idx_expense_postings_posted_by` (`posted_by`),
    CONSTRAINT `fk_expense_postings_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_expense_postings_user` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create table expense_reimbursements
CREATE TABLE IF NOT EXISTS `expense_reimbursements` (
    `reimbursement_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` BIGINT UNSIGNED NOT NULL,
    `report_employee_id` BIGINT UNSIGNED NOT NULL,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `approved_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
    `status` ENUM('PENDING','PROCESSING','PARTIALLY_PAID','PAID','FAILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_report_employee_reimb` (`report_id`, `employee_id`),
    KEY `idx_expense_reimb_report` (`report_id`),
    KEY `idx_expense_reimb_report_emp` (`report_employee_id`),
    KEY `idx_expense_reimb_employee` (`employee_id`),
    KEY `idx_expense_reimb_status` (`status`),
    CONSTRAINT `fk_expense_reimb_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_expense_reimb_report_employee` FOREIGN KEY (`report_employee_id`) REFERENCES `expense_report_employees` (`report_employee_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_expense_reimb_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create table expense_reimbursement_payments
CREATE TABLE IF NOT EXISTS `expense_reimbursement_payments` (
    `payment_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reimbursement_id` BIGINT UNSIGNED NOT NULL,
    `payment_number` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BANK_TRANSFER',
    `bank_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
    `bank_reference` VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL,
    `proof_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
    `paid_by` BIGINT UNSIGNED NOT NULL,
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_reimb_payment_number` (`payment_number`),
    KEY `idx_reimb_payments_reimb` (`reimbursement_id`),
    KEY `idx_reimb_payments_paid_by` (`paid_by`),
    KEY `idx_reimb_payments_date` (`payment_date`),
    CONSTRAINT `fk_reimb_payments_reimb` FOREIGN KEY (`reimbursement_id`) REFERENCES `expense_reimbursements` (`reimbursement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reimb_payments_user` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Backfill existing reimbursements into expense_reimbursements
INSERT IGNORE INTO expense_reimbursements (reimbursement_id, report_id, report_employee_id, employee_id, approved_amount, paid_amount, status, notes, created_at, updated_at)
SELECT 
    r.reimbursement_id,
    r.report_id,
    COALESCE(ere.report_employee_id, 1),
    r.employee_id,
    r.amount,
    CASE WHEN r.status = 'PAID' THEN r.amount ELSE 0.00 END,
    r.status,
    r.notes,
    r.created_at,
    r.updated_at
FROM reimbursements r
LEFT JOIN expense_report_employees ere ON ere.report_id = r.report_id AND ere.employee_id = r.employee_id;

-- 10. Backfill existing reimbursements payments
INSERT IGNORE INTO expense_reimbursement_payments (reimbursement_id, payment_number, payment_date, amount, payment_method, bank_reference, paid_by, notes, created_at)
SELECT 
    r.reimbursement_id,
    CONCAT('PAY-', r.reimbursement_id, '-', UNIX_TIMESTAMP(COALESCE(r.processed_at, r.created_at))),
    COALESCE(r.payment_date, DATE(r.processed_at), CURDATE()),
    r.amount,
    COALESCE(r.payment_method, 'BANK_TRANSFER'),
    r.payment_reference,
    COALESCE(r.processed_by, 1),
    r.notes,
    COALESCE(r.processed_at, r.created_at)
FROM reimbursements r
WHERE r.status = 'PAID';

-- 11. Backfill existing postings
INSERT IGNORE INTO expense_postings (report_id, journal_reference, posting_date, posted_by, posted_at, notes)
SELECT 
    r.report_id,
    CONCAT('JRN-EXP-', r.report_id),
    COALESCE(DATE(r.posted_at), CURDATE()),
    COALESCE(
        (SELECT approver_id FROM expense_approvals WHERE report_id = r.report_id AND action = 'POSTED' ORDER BY approval_id DESC LIMIT 1),
        (SELECT user_id FROM users WHERE role = 'FINANCE' OR username = 'finance' LIMIT 1),
        1
    ),
    COALESCE(r.posted_at, CURRENT_TIMESTAMP),
    'Migrated posting record'
FROM expense_reports r
WHERE r.status IN ('POSTED', 'REIMBURSEMENT_PENDING', 'PAID')
  AND NOT EXISTS (SELECT 1 FROM expense_postings ep WHERE ep.report_id = r.report_id);
