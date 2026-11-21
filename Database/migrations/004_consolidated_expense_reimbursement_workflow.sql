-- Migration 004: Consolidated Multi-Employee Expense Management & Single Reimbursement Workflow
-- Target: MySQL 8.0+ / MariaDB 10.4+
-- Safe and idempotent migration script

-- 1. Adjust reimbursements table: make employee_id nullable and add payment recipient details
SET @gt_sql := (
    SELECT IF(
        IS_NULLABLE = 'NO',
        'ALTER TABLE reimbursements MODIFY COLUMN employee_id BIGINT UNSIGNED NULL',
        'SELECT "reimbursements.employee_id is already nullable"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND column_name = 'employee_id'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

-- Add recipient columns to reimbursements table if they do not exist
SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE reimbursements ADD COLUMN recipient_name VARCHAR(150) NULL AFTER amount, ADD COLUMN bank_name VARCHAR(100) NULL AFTER recipient_name, ADD COLUMN account_number VARCHAR(100) NULL AFTER bank_name, ADD COLUMN account_holder VARCHAR(150) NULL AFTER account_number, ADD COLUMN proof_path VARCHAR(255) NULL AFTER account_holder',
        'SELECT "recipient columns already exist in reimbursements"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND column_name = 'recipient_name'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

-- 2. Adjust role_permissions:
-- Revoke expense submission/management from EMPLOYEE role
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id_role = rp.role_id
JOIN permissions p ON p.permission_id = rp.permission_id
WHERE r.role_name = 'EMPLOYEE' AND p.permission_key IN ('expenses.manage', 'expense.approve', 'expense.post', 'expense.reimburse');

-- Ensure ADMIN role permissions (Admin Expense: manages expenses & reports, does not approve/post/reimburse)
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id_role = rp.role_id
JOIN permissions p ON p.permission_id = rp.permission_id
WHERE r.role_name = 'ADMIN' AND p.permission_key IN ('expense.approve', 'expense.post', 'expense.reimburse');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id_role, p.permission_id
FROM roles r, permissions p
WHERE r.role_name = 'ADMIN' AND p.permission_key IN ('dashboard.view', 'expenses.manage', 'reports.view', 'settings.manage', 'users.manage', 'employees.manage');

-- Ensure MANAGER role permissions (Manager Expense: reviews & approves/refuses reports)
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id_role = rp.role_id
JOIN permissions p ON p.permission_id = rp.permission_id
WHERE r.role_name = 'MANAGER' AND p.permission_key IN ('expenses.manage', 'expense.post', 'expense.reimburse');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id_role, p.permission_id
FROM roles r, permissions p
WHERE r.role_name = 'MANAGER' AND p.permission_key IN ('dashboard.view', 'expense.approve', 'reports.view');

-- Ensure FINANCE role permissions (Finance: posts and reimbursements full payment)
DELETE rp FROM role_permissions rp
JOIN roles r ON r.id_role = rp.role_id
JOIN permissions p ON p.permission_id = rp.permission_id
WHERE r.role_name = 'FINANCE' AND p.permission_key IN ('expenses.manage', 'expense.approve');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id_role, p.permission_id
FROM roles r, permissions p
WHERE r.role_name = 'FINANCE' AND p.permission_key IN ('dashboard.view', 'expense.post', 'expense.reimburse', 'reports.view', 'invoices.manage', 'payments.manage');
