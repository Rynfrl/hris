-- ============================================================
-- ERP DATABASE
-- MySQL 8.0+
-- Charset: utf8mb4
-- Engine: InnoDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS erp_database
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE erp_database;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. CORE / AUTHENTICATION
-- ============================================================

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    role_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    permission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. HR
-- ============================================================

CREATE TABLE departments (
    department_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(30) NOT NULL UNIQUE,
    department_name VARCHAR(100) NOT NULL,
    manager_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_departments_manager (manager_id)
) ENGINE=InnoDB;

CREATE TABLE employees (
    employee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL UNIQUE,
    phone VARCHAR(50) NULL,
    department_id BIGINT UNSIGNED NULL,
    manager_id BIGINT UNSIGNED NULL,
    position VARCHAR(100) NULL,
    bank_name VARCHAR(100) NULL,
    bank_account VARCHAR(100) NULL,
    join_date DATE NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    photo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employees_department (department_id),
    INDEX idx_employees_manager (manager_id),
    CONSTRAINT fk_employees_department
        FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_employees_manager
        FOREIGN KEY (manager_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Circular dependency: departments.manager_id -> employees
ALTER TABLE departments
    ADD CONSTRAINT fk_departments_manager
    FOREIGN KEY (manager_id) REFERENCES employees(employee_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE payrolls (
    payroll_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    period CHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
    basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    allowance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    deduction DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_date DATE NULL,
    status ENUM('DRAFT','PROCESSED','PAID','CANCELLED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_employee_period (employee_id, period),
    INDEX idx_payroll_period (period),
    CONSTRAINT fk_payroll_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Users depends on employees and roles
CREATE TABLE users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_users_employee (employee_id),
    INDEX idx_users_role (role_id),
    CONSTRAINT fk_users_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3. MASTER DATA
-- ============================================================

CREATE TABLE customers (
    customer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_customers_name (customer_name)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    supplier_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    supplier_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_suppliers_name (supplier_name)
) ENGINE=InnoDB;

CREATE TABLE products (
    product_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    product_name VARCHAR(150) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    stock DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    minimum_stock DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_products_name (product_name),
    INDEX idx_products_stock (stock)
) ENGINE=InnoDB;

CREATE TABLE projects (
    project_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(50) NOT NULL UNIQUE,
    project_name VARCHAR(150) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projects_customer (customer_id),
    CONSTRAINT fk_projects_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. PURCHASING - PURCHASE REQUISITION
-- ============================================================

CREATE TABLE purchase_requisitions (
    purchase_requisition_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pr_no VARCHAR(40) NOT NULL UNIQUE,
    requester_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    needed_date DATE NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'DRAFT',
        'SUBMITTED',
        'APPROVED',
        'REJECTED',
        'CONVERTED',
        'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    notes TEXT NULL,
    approval_threshold_snapshot DECIMAL(15,2) NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_pr_requester (requester_id),
    INDEX idx_pr_department (department_id),
    INDEX idx_pr_status (status),
    CONSTRAINT fk_pr_requester
        FOREIGN KEY (requester_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pr_department
        FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pr_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE purchase_requisition_details (
    purchase_requisition_detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_requisition_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    qty DECIMAL(15,3) NOT NULL,
    estimated_unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    INDEX idx_pr_detail_pr (purchase_requisition_id),
    INDEX idx_pr_detail_product (product_id),
    CONSTRAINT fk_pr_detail_header
        FOREIGN KEY (purchase_requisition_id) REFERENCES purchase_requisitions(purchase_requisition_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pr_detail_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. PURCHASING - PURCHASE ORDER / TRANSACTION
-- ============================================================

CREATE TABLE purchase_transactions (
    purchase_transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_no VARCHAR(40) NOT NULL UNIQUE,
    purchase_requisition_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    transaction_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'DRAFT',
        'SENT',
        'CONFIRMED',
        'PARTIALLY_RECEIVED',
        'RECEIVED',
        'INVOICED',
        'COMPLETED',
        'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_po_pr (purchase_requisition_id),
    INDEX idx_po_supplier (supplier_id),
    INDEX idx_po_user (user_id),
    INDEX idx_po_status (status),
    CONSTRAINT fk_po_pr
        FOREIGN KEY (purchase_requisition_id) REFERENCES purchase_requisitions(purchase_requisition_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_po_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_po_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE purchase_transaction_details (
    purchase_transaction_detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_transaction_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    qty DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    INDEX idx_po_detail_header (purchase_transaction_id),
    INDEX idx_po_detail_product (product_id),
    CONSTRAINT fk_po_detail_header
        FOREIGN KEY (purchase_transaction_id) REFERENCES purchase_transactions(purchase_transaction_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_po_detail_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. INVENTORY - GOODS RECEIPT
-- ============================================================

CREATE TABLE goods_receipts (
    goods_receipt_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grn_no VARCHAR(40) NOT NULL UNIQUE,
    purchase_transaction_id BIGINT UNSIGNED NOT NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    received_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_gr_po (purchase_transaction_id),
    INDEX idx_gr_received_by (received_by),
    CONSTRAINT fk_gr_po
        FOREIGN KEY (purchase_transaction_id) REFERENCES purchase_transactions(purchase_transaction_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_gr_received_by
        FOREIGN KEY (received_by) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE goods_receipt_details (
    goods_receipt_detail_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    purchase_transaction_detail_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    qty_received DECIMAL(15,3) NOT NULL,
    condition_note VARCHAR(255) NULL,
    INDEX idx_gr_detail_header (goods_receipt_id),
    INDEX idx_gr_detail_po_detail (purchase_transaction_detail_id),
    INDEX idx_gr_detail_product (product_id),
    CONSTRAINT fk_gr_detail_header
        FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(goods_receipt_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_gr_detail_po_detail
        FOREIGN KEY (purchase_transaction_detail_id) REFERENCES purchase_transaction_details(purchase_transaction_detail_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_gr_detail_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. INVENTORY - STOCK MOVEMENTS
-- ============================================================

CREATE TABLE stock_movements (
    stock_movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    type ENUM(
        'PURCHASE',
        'PURCHASE_RETURN',
        'SALE',
        'SALES_RETURN',
        'ADJUSTMENT_IN',
        'ADJUSTMENT_OUT'
    ) NOT NULL,
    qty DECIMAL(15,3) NOT NULL,
    reference_no VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_reference_product (reference_no, product_id),
    INDEX idx_stock_product (product_id),
    INDEX idx_stock_type (type),
    CONSTRAINT fk_stock_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. PURCHASING - VENDOR INVOICE
-- ============================================================

CREATE TABLE vendor_invoices (
    vendor_invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(60) NOT NULL,
    purchase_transaction_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    invoice_date DATE NOT NULL,
    entered_by BIGINT UNSIGNED NOT NULL,
    match_status ENUM('PENDING','MATCHED','MISMATCHED','EXCEPTION') NOT NULL DEFAULT 'PENDING',
    match_notes TEXT NULL,
    status ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED','PAID','CANCELLED') NOT NULL DEFAULT 'DRAFT',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    approval_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_vendor_invoice_transaction_no (purchase_transaction_id, invoice_no),
    INDEX idx_vendor_invoice_entered_by (entered_by),
    INDEX idx_vendor_invoice_status (status),
    CONSTRAINT fk_vendor_invoice_po
        FOREIGN KEY (purchase_transaction_id) REFERENCES purchase_transactions(purchase_transaction_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_invoice_entered_by
        FOREIGN KEY (entered_by) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_invoice_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. PURCHASING - PURCHASE RETURN
-- ============================================================

CREATE TABLE purchase_returns (
    purchase_return_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_no VARCHAR(40) NOT NULL UNIQUE,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    qty_returned DECIMAL(15,3) NOT NULL,
    `condition` VARCHAR(100) NULL,
    reason TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_return_gr (goods_receipt_id),
    INDEX idx_return_product (product_id),
    CONSTRAINT fk_return_gr
        FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(goods_receipt_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_return_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_return_created_by
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. SALES
-- ============================================================

CREATE TABLE sales_orders (
    sales_order_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(40) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    order_date DATE NOT NULL,
    status ENUM(
        'DRAFT',
        'CONFIRMED',
        'INVOICED',
        'PAID',
        'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sales_customer (customer_id),
    INDEX idx_sales_user (user_id),
    INDEX idx_sales_status (status),
    CONSTRAINT fk_sales_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sales_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sales_order_items (
    sales_order_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    INDEX idx_sales_item_order (sales_order_id),
    INDEX idx_sales_item_product (product_id),
    CONSTRAINT fk_sales_item_order
        FOREIGN KEY (sales_order_id) REFERENCES sales_orders(sales_order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sales_item_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE invoices (
    invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(40) NOT NULL UNIQUE,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('UNPAID','PARTIAL','PAID','CANCELLED') NOT NULL DEFAULT 'UNPAID',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_order (sales_order_id),
    INDEX idx_invoice_status (status),
    CONSTRAINT fk_invoice_order
        FOREIGN KEY (sales_order_id) REFERENCES sales_orders(sales_order_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    method ENUM('CASH','TRANSFER','CARD') NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_invoice (invoice_id),
    CONSTRAINT fk_payment_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. EXPENSE
-- ============================================================

CREATE TABLE expense_categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(30) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    default_amount DECIMAL(15,2) NULL,
    is_fixed_price TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE expenses (
    expense_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(40) NOT NULL UNIQUE,
    employee_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'IDR',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'CASH',
    merchant VARCHAR(150) NULL,
    department_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    project_id BIGINT UNSIGNED NULL,
    is_reimbursable TINYINT(1) NOT NULL DEFAULT 1,
    is_reinvoiceable TINYINT(1) NOT NULL DEFAULT 0,
    reinvoice_status ENUM('NOT_INVOICED','READY_TO_INVOICE','INVOICED') NOT NULL DEFAULT 'NOT_INVOICED',
    status ENUM(
        'DRAFT',
        'SUBMITTED',
        'APPROVED',
        'REFUSED',
        'POSTED',
        'REIMBURSEMENT_PENDING',
        'PAID',
        'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    receipt_original_name VARCHAR(255) NULL,
    receipt_stored_name VARCHAR(255) NULL,
    receipt_path VARCHAR(255) NULL,
    receipt_mime VARCHAR(100) NULL,
    receipt_size BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    rejected_by BIGINT UNSIGNED NULL,
    rejected_at DATETIME NULL,
    rejection_reason TEXT NULL,
    paid_by BIGINT UNSIGNED NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expense_employee (employee_id),
    INDEX idx_expense_category (category_id),
    INDEX idx_expense_department (department_id),
    INDEX idx_expense_customer (customer_id),
    INDEX idx_expense_project (project_id),
    INDEX idx_expense_status (status),
    INDEX idx_expense_created_by (created_by),
    CONSTRAINT fk_expense_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_category
        FOREIGN KEY (category_id) REFERENCES expense_categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_expense_department
        FOREIGN KEY (department_id) REFERENCES departments(department_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_project
        FOREIGN KEY (project_id) REFERENCES projects(project_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_created_by
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_expense_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_rejected_by
        FOREIGN KEY (rejected_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_expense_paid_by
        FOREIGN KEY (paid_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expense_reports (
    report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_number VARCHAR(40) NOT NULL UNIQUE,
    employee_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'DRAFT',
        'SUBMITTED',
        'APPROVED',
        'REFUSED',
        'POSTED',
        'REIMBURSEMENT_PENDING',
        'PAID',
        'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    posted_at DATETIME NULL,
    paid_at DATETIME NULL,
    refused_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expense_report_employee (employee_id),
    INDEX idx_expense_report_status (status),
    CONSTRAINT fk_expense_report_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expense_report_items (
    report_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    expense_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_report_expense (report_id, expense_id),
    CONSTRAINT fk_report_item_report
        FOREIGN KEY (report_id) REFERENCES expense_reports(report_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_report_item_expense
        FOREIGN KEY (expense_id) REFERENCES expenses(expense_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE expense_approvals (
    approval_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    approver_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(30) NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_approval_report (report_id),
    INDEX idx_expense_approval_approver (approver_id),
    CONSTRAINT fk_expense_approval_report
        FOREIGN KEY (report_id) REFERENCES expense_reports(report_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_expense_approval_approver
        FOREIGN KEY (approver_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reimbursements (
    reimbursement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(150) NULL,
    payment_date DATE NULL,
    notes TEXT NULL,
    status ENUM('PENDING','PROCESSING','PAID','FAILED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    processed_by BIGINT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reimbursement_report (report_id),
    INDEX idx_reimbursement_employee (employee_id),
    INDEX idx_reimbursement_status (status),
    CONSTRAINT fk_reimbursement_report
        FOREIGN KEY (report_id) REFERENCES expense_reports(report_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reimbursement_employee
        FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reimbursement_processed_by
        FOREIGN KEY (processed_by) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 12. SYSTEM / AUDIT
-- ============================================================

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_identifier (identifier),
    INDEX idx_login_attempted_at (attempted_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    summary VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_entity (entity_type, entity_id),
    INDEX idx_activity_created_at (created_at),
    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    record_id BIGINT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_module_record (module, record_id),
    INDEX idx_audit_created_at (created_at),
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_user_read (user_id, is_read),
    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 13. SEED MASTER DATA
-- ============================================================

INSERT INTO roles (role_name, description) VALUES
('ADMIN', 'Administrator sistem'),
('HR', 'Pengelola HR dan payroll'),
('PURCHASING', 'Pengelola purchasing/procurement'),
('WAREHOUSE', 'Pengelola gudang dan inventory'),
('SALES', 'Pengelola penjualan'),
('FINANCE', 'Pengelola finance dan expense'),
('MANAGER', 'Approver/manajer');

INSERT INTO permissions (permission_key, description) VALUES
('dashboard.view', 'Melihat dashboard'),
('users.manage', 'Mengelola user'),
('employees.manage', 'Mengelola employee'),
('payrolls.manage', 'Mengelola payroll'),
('products.manage', 'Mengelola product'),
('customers.manage', 'Mengelola customer'),
('suppliers.manage', 'Mengelola supplier'),
('purchasing.manage', 'Mengelola purchasing'),
('inventory.manage', 'Mengelola inventory'),
('sales.manage', 'Mengelola sales'),
('invoices.manage', 'Mengelola invoice'),
('payments.manage', 'Mengelola payment'),
('expenses.manage', 'Mengelola expense'),
('expense.approve', 'Menyetujui expense'),
('reports.view', 'Melihat laporan'),
('settings.manage', 'Mengelola pengaturan');

INSERT INTO expense_categories
(category_code, category_name, description, is_active)
VALUES
('TRAVEL', 'Travel', 'Biaya perjalanan dinas', 1),
('MEAL', 'Meal', 'Biaya makan', 1),
('TRANSPORT', 'Transport', 'Biaya transportasi', 1),
('OFFICE', 'Office', 'Biaya kebutuhan kantor', 1),
('OTHER', 'Other', 'Biaya lainnya', 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'ERP Company'),
('currency', 'IDR'),
('tax_rate', '11');

-- ============================================================
-- 14. VIEWS
-- ============================================================

CREATE OR REPLACE VIEW v_product_stock AS
SELECT
    p.product_id,
    p.sku,
    p.product_name,
    p.unit,
    p.stock,
    p.minimum_stock,
    CASE
        WHEN p.stock <= p.minimum_stock THEN 1
        ELSE 0
    END AS is_low_stock
FROM products p
WHERE p.deleted_at IS NULL
  AND p.is_active = 1;

CREATE OR REPLACE VIEW v_sales_summary AS
SELECT
    so.sales_order_id,
    so.order_no,
    so.order_date,
    c.customer_code,
    c.customer_name,
    so.status,
    so.subtotal,
    so.discount,
    so.tax,
    so.total_amount,
    u.username AS created_by
FROM sales_orders so
JOIN customers c ON c.customer_id = so.customer_id
JOIN users u ON u.user_id = so.user_id;

CREATE OR REPLACE VIEW v_purchase_summary AS
SELECT
    pt.purchase_transaction_id,
    pt.transaction_no,
    pt.transaction_date,
    s.supplier_code,
    s.supplier_name,
    pt.status,
    pt.subtotal,
    pt.tax,
    pt.grand_total,
    u.username AS created_by
FROM purchase_transactions pt
JOIN suppliers s ON s.supplier_id = pt.supplier_id
JOIN users u ON u.user_id = pt.user_id;

CREATE OR REPLACE VIEW v_employee_summary AS
SELECT
    e.employee_id,
    e.employee_number,
    e.full_name,
    e.position,
    d.department_code,
    d.department_name,
    e.status,
    u.username,
    r.role_name
FROM employees e
LEFT JOIN departments d ON d.department_id = e.department_id
LEFT JOIN users u ON u.employee_id = e.employee_id
LEFT JOIN roles r ON r.role_id = u.role_id;

-- ============================================================
-- 15. TRIGGERS FOR BASIC STOCK SYNCHRONIZATION
-- ============================================================

DROP TRIGGER IF EXISTS trg_stock_movement_after_insert;

DELIMITER $$

CREATE TRIGGER trg_stock_movement_after_insert
AFTER INSERT ON stock_movements
FOR EACH ROW
BEGIN
    IF NEW.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN
        UPDATE products
        SET stock = stock + NEW.qty
        WHERE product_id = NEW.product_id;
    ELSEIF NEW.type IN ('PURCHASE_RETURN', 'SALE', 'ADJUSTMENT_OUT') THEN
        UPDATE products
        SET stock = stock - NEW.qty
        WHERE product_id = NEW.product_id;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 16. BASIC CHECK CONSTRAINTS
-- ============================================================

ALTER TABLE purchase_requisition_details
    ADD CONSTRAINT chk_pr_detail_qty CHECK (qty > 0);

ALTER TABLE purchase_transaction_details
    ADD CONSTRAINT chk_po_detail_qty CHECK (qty > 0);

ALTER TABLE goods_receipt_details
    ADD CONSTRAINT chk_gr_detail_qty CHECK (qty_received > 0);

ALTER TABLE purchase_returns
    ADD CONSTRAINT chk_purchase_return_qty CHECK (qty_returned > 0);

ALTER TABLE sales_order_items
    ADD CONSTRAINT chk_sales_item_qty CHECK (quantity > 0);

ALTER TABLE payments
    ADD CONSTRAINT chk_payment_amount CHECK (amount > 0);

ALTER TABLE stock_movements
    ADD CONSTRAINT chk_stock_movement_qty CHECK (qty > 0);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF ERP DATABASE
-- ============================================================
