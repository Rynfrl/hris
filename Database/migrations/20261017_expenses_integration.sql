-- Apply once to an existing hris database to install the Expenses module schema.
-- GT Expense integrates with shared HRIS users, employees, departments, and roles.

-- 1. ROLES ENHANCEMENT
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `roles` ADD COLUMN `role_id` int unsigned NULL AFTER `id_role`',
        'SELECT "roles.role_id already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'roles' AND column_name = 'role_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `roles` SET `role_id` = `id_role` WHERE `role_id` IS NULL;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `roles` ADD UNIQUE KEY `uq_roles_role_id` (`role_id`)',
        'SELECT "uq_roles_role_id already exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'roles' AND index_name = 'uq_roles_role_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `roles` ADD COLUMN `description` varchar(255) NULL AFTER `role_name`',
        'SELECT "roles.description already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'roles' AND column_name = 'description'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `roles` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `description`',
        'SELECT "roles.created_at already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'roles' AND column_name = 'created_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'ADMIN', 'Administrator sistem'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'ADMIN');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'HR', 'Pengelola HR dan payroll'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'HR');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'PURCHASING', 'Pengelola purchasing/procurement'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'PURCHASING');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'WAREHOUSE', 'Pengelola gudang dan inventory'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'WAREHOUSE');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'SALES', 'Pengelola penjualan'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'SALES');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'FINANCE', 'Pengelola finance dan expense'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'FINANCE');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'MANAGER', 'Approver/manajer'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'MANAGER');

INSERT INTO `roles` (`role_name`, `description`)
SELECT 'EMPLOYEE', 'Pengguna karyawan untuk modul GT Expense'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'EMPLOYEE');

UPDATE `roles` SET `role_id` = `id_role` WHERE `role_id` IS NULL;

-- 2. PERMISSIONS & ROLE PERMISSIONS
CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permissions` (`permission_id`, `permission_key`, `description`) VALUES
(1, 'dashboard.view', 'Melihat dashboard'),
(2, 'users.manage', 'Mengelola user'),
(3, 'employees.manage', 'Mengelola employee'),
(4, 'payrolls.manage', 'Mengelola payroll'),
(5, 'products.manage', 'Mengelola product'),
(6, 'customers.manage', 'Mengelola customer'),
(7, 'suppliers.manage', 'Mengelola supplier'),
(8, 'purchasing.manage', 'Mengelola purchasing'),
(9, 'inventory.manage', 'Mengelola inventory'),
(10, 'sales.manage', 'Mengelola sales'),
(11, 'invoices.manage', 'Mengelola invoice'),
(12, 'payments.manage', 'Mengelola payment'),
(13, 'expenses.manage', 'Mengelola expense'),
(14, 'expense.approve', 'Menyetujui expense'),
(15, 'reports.view', 'Melihat laporan'),
(16, 'settings.manage', 'Mengelola pengaturan'),
(17, 'expense.post', 'Mem-posting laporan expense yang telah disetujui'),
(18, 'expense.reimburse', 'Memproses reimbursement expense');

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('dashboard.view', 'expenses.manage', 'reports.view')
WHERE r.`role_name` = 'EMPLOYEE';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('dashboard.view', 'expenses.manage', 'expense.approve', 'reports.view')
WHERE r.`role_name` = 'MANAGER';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('dashboard.view', 'expenses.manage', 'reports.view', 'payments.manage', 'expense.post', 'expense.reimburse')
WHERE r.`role_name` = 'FINANCE';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p ON p.`permission_key` IN ('dashboard.view', 'users.manage', 'employees.manage', 'expenses.manage', 'reports.view', 'settings.manage')
WHERE r.`role_name` = 'ADMIN';

-- 3. DEPARTMENTS
CREATE TABLE IF NOT EXISTS `departments` (
  `department_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code` (`department_code`),
  KEY `idx_departments_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `departments` (`department_id`, `department_code`, `department_name`, `description`, `is_active`) VALUES
(1, 'DEPT-IT', 'Teknologi Informasi', 'Divisi IT & Sistem', 1),
(2, 'DEPT-FIN', 'Keuangan & Akuntansi', 'Divisi Finance & Tax', 1),
(3, 'DEPT-OPS', 'Operasional & Bisnis', 'Divisi Operasional', 1);

-- 4. CUSTOMERS ENHANCEMENT
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `customers` ADD COLUMN `customer_id` int unsigned NULL AFTER `id`',
        'SELECT "customers.customer_id already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'customer_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `customers` SET `customer_id` = `id` WHERE `customer_id` IS NULL;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `customers` ADD UNIQUE KEY `uq_customers_customer_id` (`customer_id`)',
        'SELECT "uq_customers_customer_id already exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'customers' AND index_name = 'uq_customers_customer_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `customers` ADD COLUMN `customer_code` varchar(50) NULL AFTER `customer_id`, ADD COLUMN `customer_name` varchar(150) NULL AFTER `customer_code`',
        'SELECT "customers customer_code/name already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'customer_code'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `customers` SET `customer_code` = `code`, `customer_name` = `name` WHERE `customer_code` IS NULL;

-- 5. PROJECTS
CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PLANNING','ACTIVE','COMPLETED','ON_HOLD','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `idx_projects_customer` (`customer_id`),
  CONSTRAINT `fk_projects_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. EMPLOYEES ENHANCEMENT
SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `employees`
            ADD COLUMN `employee_id` bigint unsigned NULL AFTER `id_employee`,
            ADD COLUMN `employee_number` varchar(50) NULL AFTER `employee_id`,
            ADD COLUMN `full_name` varchar(150) NULL AFTER `employee_number`,
            ADD COLUMN `email` varchar(190) NULL AFTER `full_name`,
            ADD COLUMN `phone` varchar(50) NULL AFTER `email`,
            ADD COLUMN `department_id` bigint unsigned NULL AFTER `phone`,
            ADD COLUMN `manager_id` bigint unsigned NULL AFTER `department_id`,
            ADD COLUMN `position` varchar(100) NULL AFTER `manager_id`,
            ADD COLUMN `bank_name` varchar(100) NULL AFTER `position`,
            ADD COLUMN `bank_account` varchar(100) NULL AFTER `bank_name`,
            ADD COLUMN `join_date` date NULL AFTER `bank_account`,
            ADD COLUMN `status` enum(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\' AFTER `join_date`,
            ADD COLUMN `photo` varchar(255) NULL AFTER `status`,
            ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `photo`,
            ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`',
        'SELECT "employees columns already exist"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'employees' AND column_name = 'employee_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `employees`
SET `employee_id` = `id_employee`,
    `full_name` = IFNULL(`full_name`, `nama`),
    `position` = IFNULL(`position`, `jabatan`),
    `join_date` = IFNULL(`join_date`, `tanggal_masuk`),
    `employee_number` = IFNULL(`employee_number`, CONCAT('EMP-HRIS-', LPAD(`id_employee`, 4, '0')))
WHERE `employee_id` IS NULL;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `employees` ADD UNIQUE KEY `uq_employees_employee_id` (`employee_id`)',
        'SELECT "uq_employees_employee_id exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'employees' AND index_name = 'uq_employees_employee_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `employees` ADD UNIQUE KEY `uq_employees_employee_number` (`employee_number`)',
        'SELECT "uq_employees_employee_number exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'employees' AND index_name = 'uq_employees_employee_number'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. USERS ENHANCEMENT
ALTER TABLE `users` MODIFY `user_id` bigint unsigned NULL;
ALTER TABLE `users` MODIFY `employee_id` bigint unsigned NULL;
UPDATE `users` SET `user_id` = `id` WHERE `user_id` IS NULL;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_user_id` (`user_id`)',
        'SELECT "uq_users_user_id exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'uq_users_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. EXPENSE CATEGORIES
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `category_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. EXPENSES
CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `customer_id` int unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `is_reimbursable` tinyint(1) NOT NULL DEFAULT '1',
  `is_reinvoiceable` tinyint(1) NOT NULL DEFAULT '0',
  `reinvoice_status` enum('NOT_INVOICED','READY_TO_INVOICE','INVOICED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_INVOICED',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `receipt_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_stored_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_size` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `paid_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  UNIQUE KEY `expense_number` (`expense_number`),
  KEY `idx_expense_employee` (`employee_id`),
  KEY `idx_expense_category` (`category_id`),
  KEY `idx_expense_department` (`department_id`),
  KEY `idx_expense_customer` (`customer_id`),
  KEY `idx_expense_project` (`project_id`),
  KEY `idx_expense_status` (`status`),
  KEY `idx_expense_created_by` (`created_by`),
  KEY `fk_expense_updated_by` (`updated_by`),
  KEY `fk_expense_approved_by` (`approved_by`),
  KEY `fk_expense_rejected_by` (`rejected_by`),
  KEY `fk_expense_paid_by` (`paid_by`),
  CONSTRAINT `fk_expense_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. EXPENSE REPORTS & ITEMS
CREATE TABLE IF NOT EXISTS `expense_reports` (
  `report_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('DRAFT','SUBMITTED','APPROVED','REFUSED','POSTED','REIMBURSEMENT_PENDING','PAID','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refused_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `report_number` (`report_number`),
  KEY `idx_expense_report_employee` (`employee_id`),
  KEY `idx_expense_report_status` (`status`),
  CONSTRAINT `fk_expense_report_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_report_items` (
  `report_item_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `expense_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`report_item_id`),
  UNIQUE KEY `uq_report_expense` (`report_id`,`expense_id`),
  UNIQUE KEY `uq_expense_report_items_expense` (`expense_id`),
  CONSTRAINT `fk_report_item_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`expense_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_report_item_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. APPROVALS, REIMBURSEMENTS, COMMENTS
CREATE TABLE IF NOT EXISTS `expense_approvals` (
  `approval_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `approver_id` bigint unsigned NOT NULL,
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  KEY `idx_expense_approval_report` (`report_id`),
  KEY `idx_expense_approval_approver` (`approver_id`),
  CONSTRAINT `fk_expense_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_approval_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reimbursements` (
  `reimbursement_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PENDING','PROCESSING','PAID','FAILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processed_by` bigint unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reimbursement_id`),
  UNIQUE KEY `uq_reimbursements_report` (`report_id`),
  KEY `idx_reimbursement_report` (`report_id`),
  KEY `idx_reimbursement_employee` (`employee_id`),
  KEY `idx_reimbursement_status` (`status`),
  KEY `fk_reimbursement_processed_by` (`processed_by`),
  CONSTRAINT `fk_reimbursement_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_reimbursement_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reimbursement_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_comments` (
  `comment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned NOT NULL,
  `comment_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_expense_comments_report_created` (`report_id`,`created_at`),
  CONSTRAINT `fk_expense_comments_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_comments_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. SETTINGS, AUDIT LOGS, NOTIFICATIONS, SCHEMA MIGRATIONS
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_id`, `setting_key`, `setting_value`) VALUES
(1, 'company_name', 'ERP Company'),
(2, 'currency', 'IDR'),
(3, 'tax_rate', '11');

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module_record` (`module`,`record_id`),
  KEY `idx_audit_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_user_read` (`user_id`,`is_read`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `migration_id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migration_id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `schema_migrations` (`migration_name`) VALUES
('001_gt_expense_integrity.sql'),
('002_add_bank_name_to_expenses.sql'),
('20261017_expenses_integration.sql');

-- 13. ACTIVITY LOGS COLUMN RESIZE
ALTER TABLE `activity_logs` MODIFY `action` varchar(100) NOT NULL;

-- 14. ANALYTICAL VIEWS
CREATE OR REPLACE VIEW `v_employee_summary` AS
SELECT
    e.`employee_id`,
    e.`employee_number`,
    e.`full_name`,
    e.`position`,
    d.`department_code`,
    d.`department_name`,
    e.`status`,
    u.`username`,
    r.`role_name`
FROM `employees` e
LEFT JOIN `departments` d ON d.`department_id` = e.`department_id`
LEFT JOIN `users` u ON u.`employee_id` = e.`employee_id`
LEFT JOIN `roles` r ON r.`role_id` = u.`role_id`;

CREATE OR REPLACE VIEW `v_product_stock` AS
SELECT
    p.`product_id`,
    p.`sku`,
    p.`product_name`,
    p.`unit`,
    p.`stock`,
    p.`minimum_stock`,
    CASE
        WHEN p.`stock` <= p.`minimum_stock` THEN 1
        ELSE 0
    END AS `is_low_stock`
FROM `products` p;
