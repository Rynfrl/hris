-- GT Expense module seed for the supplied erp_database.sql.
-- MySQL 8.0+. Safe to re-run for role/permission mapping due to INSERT IGNORE.
-- Does not create users and never stores plaintext passwords.

USE erp_database;

INSERT IGNORE INTO roles (role_name, description) VALUES
('EMPLOYEE', 'Pengguna karyawan untuk modul GT Expense');

INSERT IGNORE INTO permissions (permission_key, description) VALUES
('expense.post', 'Mem-posting laporan expense yang telah disetujui'),
('expense.reimburse', 'Memproses reimbursement expense');

-- Module-level permissions. Record ownership/scope must still be enforced in PHP.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('dashboard.view', 'expenses.manage', 'reports.view')
WHERE r.role_name = 'EMPLOYEE';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('dashboard.view', 'expenses.manage', 'expense.approve', 'reports.view')
WHERE r.role_name = 'MANAGER';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('dashboard.view', 'expenses.manage', 'reports.view', 'payments.manage', 'expense.post', 'expense.reimburse')
WHERE r.role_name = 'FINANCE';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
JOIN permissions p ON p.permission_key IN ('dashboard.view', 'users.manage', 'employees.manage', 'expenses.manage', 'reports.view', 'settings.manage')
WHERE r.role_name = 'ADMIN';

-- Deliberately do not grant expense.approve or expense.reimburse to ADMIN by default.
-- Map each user's role_id and employee_id through the application/admin UI.
