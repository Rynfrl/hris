<?php

declare(strict_types=1);

/**
 * Clean & Comprehensive Indonesian Corporate Dummy Data Seeder for HRIS & ERP
 */

$dbConfig = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage() . "\n");
}

echo "=== MENGHAPUS SEMUA DATA LAMA SECARA BERSIH ===\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

$tablesToTruncate = [
    'activity_logs',
    'audit_logs',
    'login_attempts',
    'notifications',
    'expense_comments',
    'expense_reimbursement_payments',
    'expense_reimbursements',
    'expense_postings',
    'expense_approvals',
    'expense_report_items',
    'expense_report_employees',
    'expense_reports',
    'reimbursements',
    'expenses',
    'expense_categories',
    'payrolls',
    'payments',
    'invoices',
    'sales_order_items',
    'sales_orders',
    'customers',
    'vendor_invoices',
    'purchase_returns',
    'goods_receipt_details',
    'goods_receipts',
    'purchase_transaction_details',
    'purchase_transactions',
    'purchase_requisition_details',
    'purchase_requisitions',
    'stock_movements',
    'products',
    'categories',
    'suppliers',
    'projects',
    'users',
    'employees',
    'departments',
    'role_permissions',
    'permissions',
    'roles',
    'settings',
    'schema_migrations',
];

foreach ($tablesToTruncate as $tbl) {
    $pdo->exec("TRUNCATE TABLE `{$tbl}`;");
    echo "  - Table `{$tbl}` dikosongkan (AUTO_INCREMENT direset ke 1).\n";
}

echo "Seluruh data lama berhasil dihapus secara bersih.\n\n";

echo "=== MEMASUKKAN DATA DUMMY PERUSAHAAN (INDONESIA) ===\n";

$pdo->beginTransaction();

// -------------------------------------------------------------
// 1. SETTINGS & SCHEMA MIGRATIONS
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO settings (setting_id, setting_key, setting_value, updated_at) VALUES
(1, 'company_name', 'PT Sinar Nusantara Mandiri', '2026-11-20 08:00:00'),
(2, 'company_email', 'kontak@sinarnusantara.co.id', '2026-11-20 08:00:00'),
(3, 'company_phone', '021-5889012', '2026-11-20 08:00:00'),
(4, 'company_address', 'Gedung Sinar Tower Lt. 12, Jl. Jend. Sudirman Kav. 45, Jakarta Selatan', '2026-11-20 08:00:00'),
(5, 'currency', 'IDR', '2026-11-20 08:00:00'),
(6, 'tax_rate', '11', '2026-11-20 08:00:00'),
(7, 'expense_approval_threshold', '5000000', '2026-11-20 08:00:00'),
(8, 'purchase_approval_threshold', '5000000', '2026-11-20 08:00:00');
");

$pdo->exec("
INSERT INTO schema_migrations (migration_id, migration_name, applied_at) VALUES
(1, '001_gt_expense_integrity.sql', '2026-10-17 08:00:00'),
(2, '002_add_bank_name_to_expenses.sql', '2026-10-17 08:00:00'),
(3, '20261017_expenses_integration.sql', '2026-10-17 08:00:00'),
(4, '003_multi_employee_expense_management.sql', '2026-11-20 08:00:00');
");

// -------------------------------------------------------------
// 2. ROLES, PERMISSIONS & ROLE_PERMISSIONS
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO roles (id_role, role_id, role_name, description, created_at) VALUES
(1, 1, 'Admin_HR', 'Administrator HR & Kepegawaian', '2026-10-17 08:00:00'),
(2, 2, 'Admin_Purchase', 'Manajer Pengadaan & Purchasing', '2026-10-17 08:00:00'),
(3, 3, 'Purchasing_Staff', 'Staf Pengadaan & Logistik Masuk', '2026-10-17 08:00:00'),
(4, 4, 'Admin_Sales', 'Manajer Penjualan & Sales ERP', '2026-10-17 08:00:00'),
(5, 5, 'Marketing_Staff', 'Staf Penjualan & Pemasaran', '2026-10-17 08:00:00'),
(6, 6, 'Admin_Warehouse', 'Kepala Gudang & Inventaris', '2026-10-17 08:00:00'),
(7, 7, 'Warehouse_Staff', 'Staf Gudang & Pergerakan Stok', '2026-10-17 08:00:00'),
(8, 8, 'ADMIN', 'Administrator Sistem GT Expense', '2026-10-17 08:00:00'),
(13, 13, 'FINANCE', 'Staf Keuangan & Pembukuan Pengeluaran', '2026-10-17 08:00:00'),
(14, 14, 'MANAGER', 'Manajer / Approver Laporan Pengeluaran', '2026-10-17 08:00:00'),
(15, 15, 'EMPLOYEE', 'Pengguna Karyawan Modul GT Expense', '2026-10-17 08:00:00');
");

$pdo->exec("
INSERT INTO permissions (permission_id, permission_key, description) VALUES
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
");

$pdo->exec("
INSERT INTO role_permissions (role_id, permission_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 13), (1, 15), (1, 16),
(2, 1), (2, 7), (2, 8), (2, 15),
(3, 1), (3, 7), (3, 8),
(4, 1), (4, 6), (4, 10), (4, 11), (4, 15),
(5, 1), (5, 6), (5, 10),
(6, 1), (6, 5), (6, 9), (6, 15),
(7, 1), (7, 5), (7, 9),
(8, 1), (8, 2), (8, 3), (8, 13), (8, 15), (8, 16),
(13, 1), (13, 11), (13, 12), (13, 13), (13, 15), (13, 17), (13, 18),
(14, 1), (14, 13), (14, 14), (14, 15),
(15, 1), (15, 13), (15, 15);
");

// -------------------------------------------------------------
// 3. DEPARTMENTS
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO departments (department_id, department_code, department_name, manager_id, description, is_active, created_at) VALUES
(1, 'DEPT-IT', 'Teknologi Informasi', 1, 'Divisi IT, Rekayasa Perangkat Lunak, dan Infrastruktur', 1, '2026-10-15 08:00:00'),
(2, 'DEPT-FIN', 'Keuangan & Akuntansi', 2, 'Divisi Keuangan, Kasir, Pajak, dan Pembukuan', 1, '2026-10-15 08:00:00'),
(3, 'DEPT-HR', 'Human Resources', 6, 'Divisi Sumber Daya Manusia, Rekrutmen, dan Personalia', 1, '2026-10-15 08:00:00'),
(4, 'DEPT-PUR', 'Pengadaan & Purchasing', 7, 'Divisi Pembelian Barang, Vendor, dan Kontrak Suplai', 1, '2026-10-15 08:00:00'),
(5, 'DEPT-SLS', 'Penjualan & Pemasaran', 9, 'Divisi Sales Komersial, Hubungan Klien, dan Pemasaran', 1, '2026-10-15 08:00:00'),
(6, 'DEPT-WRH', 'Gudang & Logistik', 11, 'Divisi Pengelolaan Stok, Pergudangan, dan Distribusi', 1, '2026-10-15 08:00:00'),
(7, 'DEPT-OPS', 'Operasional Bisnis', 2, 'Divisi Operasional Umum dan Tata Kelola Fasilitas', 1, '2026-10-15 08:00:00');
");

// -------------------------------------------------------------
// 4. EMPLOYEES & USERS
// -------------------------------------------------------------
// Kata sandi terstandarisasi untuk semua akun demo: Password123!
$hashedPassword = password_hash('Password123!', PASSWORD_BCRYPT);

$employees = [
    // [id, emp_no, full_name, email, phone, dept_id, manager_id, position, bank, account, join_date, salary]
    [1, 'EMP-0101', 'Hendra Wijaya', 'admin@example.test', '08123456001', 1, NULL, 'IT Infrastructure Manager', 'BCA', '1234567890', '2025-01-10', 16500000.00],
    [2, 'EMP-0102', 'Rina Manajer', 'manager@example.test', '08123456002', 2, NULL, 'Finance & Accounting Manager', 'Mandiri', '1400012345678', '2025-01-15', 18000000.00],
    [3, 'EMP-0103', 'Budi Santoso', 'budi@example.test', '08123456003', 1, 1, 'Senior Software Engineer', 'BCA', '2345678901', '2025-03-01', 12000000.00],
    [4, 'EMP-0104', 'Sari Putri', 'sari@example.test', '08123456004', 2, 2, 'Junior Financial Analyst', 'BNI', '0234567891', '2025-04-10', 7500000.00],
    [5, 'EMP-0105', 'Dimas Finance', 'finance@example.test', '08123456005', 2, 2, 'Treasury & Disbursement Officer', 'BCA', '3456789012', '2025-02-01', 9500000.00],
    [6, 'EMP-0106', 'Yudha Dwi Pratama', 'yudha@gmail.com', '08123456006', 3, NULL, 'HR Director', 'Mandiri', '1400098765432', '2024-11-01', 20000000.00],
    [7, 'EMP-0107', 'Salma Suhaimah', 'salma@gmail.com', '08123456007', 4, NULL, 'Purchasing Manager', 'BCA', '4567890123', '2025-01-05', 14000000.00],
    [8, 'EMP-0108', 'Arafat Hamid', 'arafat@gmail.com', '08123456008', 4, 7, 'Purchasing Officer', 'BRI', '0123010045678', '2025-05-15', 8000000.00],
    [9, 'EMP-0109', 'Afsaal Fayyaz', 'afsaal@gmail.com', '08123456009', 5, NULL, 'Sales Director', 'BCA', '5678901234', '2024-10-01', 19500000.00],
    [10, 'EMP-0110', 'Zakie Kurniawan', 'zakie@gmail.com', '08123456010', 5, 9, 'Senior Account Executive', 'Mandiri', '1400055443322', '2025-03-20', 8500000.00],
    [11, 'EMP-0111', 'Sabiq Hibatul', 'sabiq@gmail.com', '08123456011', 6, NULL, 'Warehouse Supervisor', 'BCA', '6789012345', '2025-01-20', 11000000.00],
    [12, 'EMP-0112', 'Halim Thio', 'thio@gmail.com', '08123456012', 6, 11, 'Warehouse Operator', 'BNI', '0345678912', '2025-06-01', 6000000.00],
    [13, 'EMP-0113', 'Retno Wulandari', 'retno@gmail.com', '08123456013', 3, 6, 'HR Recruitment Specialist', 'BCA', '7890123456', '2025-02-15', 8500000.00],
    [14, 'EMP-0114', 'Citra Amelia', 'citra@example.test', '08123456014', 2, 2, 'Senior Tax & Audit Specialist', 'Mandiri', '1400088997766', '2025-02-10', 10500000.00],
    [15, 'EMP-0115', 'Andi Pratama', 'andi.pratama@example.test', '08123456015', 1, 1, 'DevOps & System Administrator', 'BCA', '8901234567', '2025-03-15', 11500000.00],
];

$stmtEmp = $pdo->prepare("
INSERT INTO employees (
    id_employee, employee_id, employee_number, full_name, email, phone,
    department_id, manager_id, position, bank_name, bank_account,
    join_date, status, id_user, nama, divisi, jabatan, gaji, tanggal_masuk
) VALUES (
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, 'ACTIVE', ?, ?, ?, ?, ?, ?
)
");

$deptNames = [
    1 => 'Teknologi Informasi',
    2 => 'Keuangan & Akuntansi',
    3 => 'Human Resources',
    4 => 'Pengadaan & Purchasing',
    5 => 'Penjualan & Pemasaran',
    6 => 'Gudang & Logistik',
    7 => 'Operasional Bisnis'
];

foreach ($employees as $e) {
    $stmtEmp->execute([
        $e[0], $e[0], $e[1], $e[2], $e[3], $e[4],
        $e[5], $e[6], $e[7], $e[8], $e[9],
        $e[10], $e[0], $e[2], $deptNames[$e[5]], $e[7], $e[11], $e[10]
    ]);
}

$users = [
    // [id, name, username, email, role_name, role_id, emp_id]
    [1, 'Hendra Wijaya (Admin GT Expense)', 'admin_gte', 'admin@example.test', 'ADMIN', 8, 1],
    [2, 'Rina Manajer (Manager Finance)', 'rina_manager', 'manager@example.test', 'MANAGER', 14, 2],
    [3, 'Budi Santoso (Staff IT)', 'budi_employee', 'budi@example.test', 'EMPLOYEE', 15, 3],
    [4, 'Sari Putri (Staff Finance)', 'sari_employee', 'sari@example.test', 'EMPLOYEE', 15, 4],
    [5, 'Dimas Finance (Treasury)', 'dimas_finance', 'finance@example.test', 'FINANCE', 13, 5],
    [6, 'Yudha Dwi Pratama (Admin HR)', 'Yuraa_n', 'yudha@gmail.com', 'Admin_HR', 1, 6],
    [7, 'Salma Suhaimah (Admin Purchase)', 'saruma', 'salma@gmail.com', 'Admin_Purchase', 2, 7],
    [8, 'Arafat Hamid (Purchasing Staff)', 'alfckqih', 'arafat@gmail.com', 'Purchasing_Staff', 3, 8],
    [9, 'Afsaal Fayyaz (Admin Sales)', 'fayyaz', 'afsaal@gmail.com', 'Admin_Sales', 4, 9],
    [10, 'Zakie Kurniawan (Marketing Staff)', 'zakiee', 'zakie@gmail.com', 'Marketing_Staff', 5, 10],
    [11, 'Sabiq Hibatul (Admin Warehouse)', 'masbiku', 'sabiq@gmail.com', 'Admin_Warehouse', 6, 11],
    [12, 'Halim Thio (Warehouse Staff)', 'halimawan', 'thio@gmail.com', 'Warehouse_Staff', 7, 12],
    [13, 'Retno Wulandari (HR Recruiter)', 'retno_hr', 'retno@gmail.com', 'Admin_HR', 1, 13],
    [14, 'Citra Amelia (Senior Tax)', 'citra_finance', 'citra@example.test', 'FINANCE', 13, 14],
    [15, 'Andi Pratama (IT Specialist)', 'andi_it', 'andi.pratama@example.test', 'EMPLOYEE', 15, 15],
];

$stmtUser = $pdo->prepare("
INSERT INTO users (
    id, user_id, employee_id, role_id, name, username, email, password_hash, role, is_active, created_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '2026-10-17 08:00:00'
)
");

foreach ($users as $u) {
    $stmtUser->execute([
        $u[0], $u[0], $u[6], $u[5], $u[1], $u[2], $u[3], $hashedPassword, $u[4]
    ]);
}

// -------------------------------------------------------------
// 5. PAYROLLS (Penggajian Periode Berjalan & Sebelumnya)
// -------------------------------------------------------------
$stmtPayroll = $pdo->prepare("
INSERT INTO payrolls (id_employee, periode, total_gaji, tanggal_bayar) VALUES
(?, ?, ?, ?)
");

$periods = ['2026-10-01', '2026-11-01'];
foreach ($periods as $prd) {
    $tglBayar = $prd === '2026-10-01' ? '2026-10-28' : '2026-11-25';
    foreach ($employees as $e) {
        $stmtPayroll->execute([$e[0], $prd, $e[11], $tglBayar]);
    }
}

// -------------------------------------------------------------
// 6. MASTER SUPPLIERS, CUSTOMERS & PROJECTS
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO suppliers (supplier_id, supplier_code, supplier_name, phone, email, address, is_active, created_at) VALUES
(1, 'SUP-001', 'PT Mitra Solusi Tekno', '021-5552345', 'sales@mitrasolusi.co.id', 'Kawasan Industri Pulogadung Blok B No. 12, Jakarta Timur', 1, '2026-10-01 08:00:00'),
(2, 'SUP-002', 'CV Graha Grafika Kertas', '021-7789012', 'order@grahagrafika.id', 'Jl. Otista Raya No. 45, Jakarta Timur', 1, '2026-10-01 08:00:00'),
(3, 'SUP-003', 'PT Sumber Abadi Logistik Pack', '021-8891234', 'halo@sumberabadi.co.id', 'Jl. Daan Mogot KM 14 No. 8, Jakarta Barat', 1, '2026-10-01 08:00:00'),
(4, 'SUP-004', 'CV Ergonomi Sentosa Abadi', '021-6678901', 'furniture@ergonomisentosa.com', 'Kawasan Industri Jababeka 1, Cikarang, Bekasi', 1, '2026-10-01 08:00:00');
");

$pdo->exec("
INSERT INTO customers (id, customer_id, customer_code, customer_name, code, name, email, phone, address, city, is_active, created_at) VALUES
(1, 1, 'CUST-0001', 'PT Nusantara Solusindo Data', 'CUST-0001', 'PT Nusantara Solusindo Data', 'procurement@nusantara-solusindo.co.id', '021-5790123', 'Menara Palma Lt. 8, Jl. H.R. Rasuna Said, Jakarta Selatan', 'Jakarta Selatan', 1, '2026-10-01 08:00:00'),
(2, 2, 'CUST-0002', 'CV Bintang Niaga Sejahtera', 'CUST-0002', 'CV Bintang Niaga Sejahtera', 'purchasing@bintangniaga.id', '021-8890234', 'Ruko BSD Golden Boulevard Blok W No. 5, Tangerang Selatan', 'Tangerang Selatan', 1, '2026-10-01 08:00:00'),
(3, 3, 'CUST-0003', 'PT Digital Sinergi Kreasi', 'CUST-0003', 'PT Digital Sinergi Kreasi', 'finance@sinergikreasi.com', '022-7234567', 'Dago Prima Techno Park Kav. 14, Bandung', 'Bandung', 1, '2026-10-01 08:00:00'),
(4, 4, 'CUST-0004', 'Toko Mandiri Berkah Abadi', 'CUST-0004', 'Toko Mandiri Berkah Abadi', 'mandiriberkah@gmail.com', '021-3901234', 'Jl. Kramat Raya No. 102, Jakarta Pusat', 'Jakarta Pusat', 1, '2026-10-01 08:00:00');
");

$pdo->exec("
INSERT INTO projects (project_id, project_code, project_name, customer_id, description, status, budget, start_date, end_date, created_at) VALUES
(1, 'PRJ-2026-001', 'Implementasi Sistem ERP Korporat', 1, 'Proyek kustomisasi dan implementasi sistem ERP & HRIS multi-divisi', 'ACTIVE', 75000000.00, '2026-10-01', '2026-12-31', '2026-10-01 08:00:00'),
(2, 'PRJ-2026-002', 'Pengembangan Portal E-Procurement', 3, 'Pembangunan modul procurement dan katalog barang digital', 'ACTIVE', 45000000.00, '2026-10-15', '2027-01-31', '2026-10-15 08:00:00');
");

// -------------------------------------------------------------
// 7. INVENTORY: CATEGORIES, PRODUCTS, STOCK MOVEMENTS
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO categories (id, code, name, description, created_at) VALUES
(1, 'CAT-ELC', 'Perangkat Elektronik & Komputer', 'Laptop, monitor, mouse, keyboard, dan perangkat TI kantor', '2026-10-01 08:00:00'),
(2, 'CAT-ATK', 'Alat Tulis & Kebutuhan Kantor', 'Kertas HVS, pulpen gel, toner cartridge, ordner dokumen', '2026-10-01 08:00:00'),
(3, 'CAT-FUR', 'Perabot & Furnitur Ergonomis', 'Kursi ergonomis, meja kerja kantor, partisi ruangan', '2026-10-01 08:00:00'),
(4, 'CAT-LOG', 'Kemasan & Perlengkapan Gudang', 'Kardus packing, lakban, bubble wrap, plastik wrap', '2026-10-01 08:00:00');
");

$products = [
    // [id, cat_id, sku, name, desc, unit, sell_price, buy_price, stock, min_stock, loc]
    [1, 1, 'PRD-ELC-001', 'Laptop Asus ExpertBook B1400', 'Laptop bisnis Intel i5 16GB RAM 512GB SSD', 'unit', 11500000.00, 9200000.00, 15, 5, 'Rak IT-A1'],
    [2, 1, 'PRD-ELC-002', 'Monitor Dell 24 Inch IPS FHD', 'Monitor kantor bezel-less 1080p 75Hz Eye Care', 'unit', 2450000.00, 1900000.00, 20, 5, 'Rak IT-A2'],
    [3, 1, 'PRD-ELC-003', 'Mouse Wireless Logitech M185', 'Mouse optik nirkabel hemat daya baterai AA', 'pcs', 185000.00, 125000.00, 50, 10, 'Rak IT-A3'],
    [4, 1, 'PRD-ELC-004', 'Keyboard Mechanical Logitech K835', 'Keyboard mekanik switch tactile nyaman untuk mengetik', 'unit', 790000.00, 570000.00, 25, 8, 'Rak IT-A4'],
    [5, 2, 'PRD-ATK-001', 'Kertas HVS PaperOne A4 80gsm', 'Kertas HVS putih bersih ukuran A4 isi 500 lembar (rim)', 'rim', 62000.00, 48000.00, 150, 30, 'Rak ATK-B1'],
    [6, 2, 'PRD-ATK-002', 'Kertas HVS PaperOne F4 75gsm', 'Kertas HVS folio putih ukuran F4 isi 500 lembar (rim)', 'rim', 66000.00, 52000.00, 100, 20, 'Rak ATK-B2'],
    [7, 2, 'PRD-ATK-003', 'Toner Laserjet HP 85A Black', 'Toner cartridge hitam pekat untuk printer HP Laserjet', 'unit', 650000.00, 480000.00, 18, 5, 'Rak ATK-B3'],
    [8, 2, 'PRD-ATK-004', 'Pulpen Gel Pilot G2 0.5 Black', 'Pulpen tinta gel hitam pekat anti macet 1 lusin (box)', 'box', 185000.00, 140000.00, 40, 10, 'Rak ATK-B4'],
    [9, 2, 'PRD-ATK-005', 'Ordner Bantex Folio 7cm Blue', 'Map ordner file tebal kokoh ukuran folio warna biru', 'pcs', 42000.00, 30000.00, 75, 15, 'Rak ATK-B5'],
    [10, 3, 'PRD-FUR-001', 'Kursi Kerja Ergonomis Mesh Jaring', 'Kursi putar jaring lumbar support hidrolik', 'unit', 1450000.00, 1050000.00, 12, 4, 'Gudang F-01'],
    [11, 3, 'PRD-FUR-002', 'Meja Kantor Minimalis 120x60 cm', 'Meja kerja rangka baja kokoh kayu MDF partisi', 'unit', 1750000.00, 1250000.00, 8, 3, 'Gudang F-02'],
    [12, 4, 'PRD-LOG-001', 'Kardus Packing Double Wall 40x30x20', 'Kotak karton gelombang tebal untuk ekspedisi paket', 'pcs', 18000.00, 12000.00, 300, 50, 'Gudang Log-01'],
    [13, 4, 'PRD-LOG-002', 'Lakban Bening Daimaru 2 Inch x 90m', 'Lakban perekat kuat serbaguna untuk packing karton', 'roll', 22000.00, 15000.00, 180, 40, 'Gudang Log-02'],
    [14, 4, 'PRD-LOG-003', 'Bubble Wrap Roll 50m Hitam', 'Plastik gelembung pelindung paket barang pecah belah', 'roll', 165000.00, 115000.00, 20, 5, 'Gudang Log-03'],
    [15, 1, 'PRD-ELC-005', 'Flashdisk SanDisk Ultra 64GB USB 3.0', 'Media penyimpanan data portabel kecepatan transfer tinggi', 'unit', 120000.00, 80000.00, 45, 10, 'Rak IT-A5'],
];

// Matikan trigger stock movement saat seeding awal agar angka stock terkendali presisi
$pdo->exec("DROP TRIGGER IF EXISTS trg_stock_movement_after_insert;");

$stmtProd = $pdo->prepare("
INSERT INTO products (
    product_id, category_id, sku, product_name, description, unit,
    selling_price, purchase_price, stock, minimum_stock, location, is_active, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, '2026-10-10 08:00:00')
");

foreach ($products as $p) {
    $stmtProd->execute([
        $p[0], $p[1], $p[2], $p[3], $p[4], $p[5],
        $p[6], $p[7], $p[8], $p[9], $p[10]
    ]);
}

// Catat riwayat stock movement awal (ADJUSTMENT_IN)
$stmtStock = $pdo->prepare("
INSERT INTO stock_movements (
    product_id, type, qty, reference_no, supplier_id, created_by, notes, created_at
) VALUES (?, 'ADJUSTMENT_IN', ?, 'INIT-STOCK-2026', NULL, 11, 'Saldo awal persediaan fisik gudang', '2026-10-10 08:30:00')
");

foreach ($products as $p) {
    $stmtStock->execute([$p[0], $p[8]]);
}

// Aktifkan kembali trigger stock_movement_after_insert
$pdo->exec("
CREATE TRIGGER `trg_stock_movement_after_insert` AFTER INSERT ON `stock_movements` FOR EACH ROW UPDATE `products`
SET `stock` = `stock` + IF(
  NEW.`type` IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'),
  NEW.`qty`,
  -NEW.`qty`
)
WHERE `product_id` = NEW.`product_id`;
");

// -------------------------------------------------------------
// 8. PURCHASING TRANSACTIONS (PR -> PO -> GRN -> AP Invoices)
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO purchase_requisitions (
    purchase_requisition_id, pr_no, requester_id, department, needed_date,
    subtotal, tax, grand_total, status, notes, approval_threshold_snapshot,
    approved_by, approved_at, created_at
) VALUES
(1, 'PR-202611-0001', 7, 'Pengadaan & Purchasing', '2026-11-15', 23000000.00, 2530000.00, 25530000.00, 'CONVERTED', 'Pengadaan 2 unit Laptop Asus & 5 unit Monitor Dell untuk tim baru', 5000000.00, 7, '2026-11-05 10:00:00', '2026-11-05 09:15:00'),
(2, 'PR-202611-0002', 8, 'Pengadaan & Purchasing', '2026-11-20', 3840000.00, 422400.00, 4262400.00, 'APPROVED', 'Restock rutin kertas HVS A4 & F4 bulanan kantor', 5000000.00, 7, '2026-11-08 14:00:00', '2026-11-08 11:30:00');
");

$pdo->exec("
INSERT INTO purchase_requisition_details (
    purchase_requisition_detail_id, purchase_requisition_id, product_id, qty, estimated_unit_price, subtotal
) VALUES
(1, 1, 1, 2.00, 9200000.00, 18400000.00),
(2, 1, 2, 5.00, 1900000.00, 9500000.00),
(3, 2, 5, 50.00, 48000.00, 2400000.00),
(4, 2, 6, 25.00, 52000.00, 1300000.00);
");

$pdo->exec("
INSERT INTO purchase_transactions (
    purchase_transaction_id, transaction_no, purchase_requisition_id, supplier_id, user_id,
    transaction_date, expected_delivery_date, subtotal, tax, grand_total, status, notes, created_at, completed_at
) VALUES
(1, 'PO-202611-0001', 1, 1, 7, '2026-11-06', '2026-11-12', 23000000.00, 2530000.00, 25530000.00, 'COMPLETED', 'Pesanan PO pengadaan perangkat keras IT ke PT Mitra Solusi Tekno', '2026-11-06 10:30:00', '2026-11-12 15:00:00'),
(2, 'PO-202611-0002', 2, 2, 8, '2026-11-09', '2026-11-14', 3840000.00, 422400.00, 4262400.00, 'CONFIRMED', 'Pesanan PO restock kertas HVS ke CV Graha Grafika Kertas', '2026-11-09 13:00:00', NULL);
");

$pdo->exec("
INSERT INTO purchase_transaction_details (
    purchase_transaction_detail_id, purchase_transaction_id, product_id, qty, unit_price, subtotal
) VALUES
(1, 1, 1, 2.00, 9200000.00, 18400000.00),
(2, 1, 2, 5.00, 1900000.00, 9500000.00),
(3, 2, 5, 50.00, 48000.00, 2400000.00),
(4, 2, 6, 25.00, 52000.00, 1300000.00);
");

$pdo->exec("
INSERT INTO goods_receipts (
    goods_receipt_id, grn_no, purchase_transaction_id, received_by, received_date, notes, created_at
) VALUES
(1, 'GRN-202611-0001', 1, 11, '2026-11-12 14:00:00', 'Barang tiba lengkap, segel utuh, fungsi unit normal', '2026-11-12 14:30:00');
");

$pdo->exec("
INSERT INTO goods_receipt_details (
    goods_receipt_detail_id, goods_receipt_id, purchase_transaction_detail_id, product_id, qty_received, condition_note
) VALUES
(1, 1, 1, 1, 2.00, 'Good'),
(2, 1, 2, 2, 5.00, 'Good');
");

$pdo->exec("
INSERT INTO vendor_invoices (
    vendor_invoice_id, invoice_no, purchase_transaction_id, amount, invoice_date,
    entered_by, match_status, match_notes, status, approved_by, approved_at, approval_notes, created_at
) VALUES
(1, 'INV-MST-202611-089', 1, 25530000.00, '2026-11-12', 7, 'MATCHED', 'Sesuai dengan PO-202611-0001 dan GRN-202611-0001 (3-way match valid)', 'APPROVED', 2, '2026-11-13 10:00:00', 'Disetujui untuk pembayaran via transfer Mandiri', '2026-11-12 16:00:00');
");

// -------------------------------------------------------------
// 9. SALES TRANSACTIONS (SO -> Invoices -> Payments)
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO sales_orders (
    id, order_no, customer_id, order_date, status, subtotal, discount, tax, total_amount, notes, created_by, created_at
) VALUES
(1, 'SO-202611-0001', 1, '2026-11-10', 'paid', 27900000.00, 0.00, 3069000.00, 30969000.00, 'Pengadaan paket workstation IT untuk PT Nusantara Solusindo Data', 9, '2026-11-10 09:00:00'),
(2, 'SO-202611-0002', 2, '2026-11-14', 'confirmed', 5800000.00, 0.00, 638000.00, 6438000.00, 'Pesanan 4 unit kursi ergonomis kantor untuk CV Bintang Niaga', 10, '2026-11-14 11:20:00'),
(3, 'SO-202611-0003', 3, '2026-11-18', 'draft', 3700000.00, 0.00, 407000.00, 4107000.00, 'Penyediaan perlengkapan ATK dan ordner arsip PT Digital Sinergi Kreasi', 10, '2026-11-18 15:45:00');
");

$pdo->exec("
INSERT INTO sales_order_items (id, order_id, product_id, quantity, price, subtotal) VALUES
(1, 1, 1, 2, 11500000.00, 23000000.00),
(2, 1, 2, 2, 2450000.00, 4900000.00),
(3, 2, 10, 4, 1450000.00, 5800000.00),
(4, 3, 5, 20, 62000.00, 1240000.00),
(5, 3, 8, 10, 185000.00, 1850000.00),
(6, 3, 9, 15, 42000.00, 630000.00);
");

$pdo->exec("
INSERT INTO invoices (id, invoice_no, order_id, invoice_date, due_date, amount, status, created_at) VALUES
(1, 'INV-202611-0001', 1, '2026-11-10', '2026-11-24', 30969000.00, 'paid', '2026-11-10 10:00:00'),
(2, 'INV-202611-0002', 2, '2026-11-14', '2026-11-28', 6438000.00, 'unpaid', '2026-11-14 13:00:00');
");

$pdo->exec("
INSERT INTO payments (id, invoice_id, payment_date, amount, method, notes, created_at) VALUES
(1, 1, '2026-11-15', 30969000.00, 'transfer', 'Pelunasan faktur INV-202611-0001 PT Nusantara Solusindo Data (Ref: BCA-990812)', '2026-11-15 11:30:00');
");

// -------------------------------------------------------------
// 10. EXPENSE MODULE (GT EXPENSE)
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO expense_categories (
    category_id, category_code, category_name, description, default_amount, is_fixed_price, is_active, created_at
) VALUES
(1, 'EXP-CAT-TRN', 'Transportasi & Perjalanan Dinas', 'Tiket pesawat, kereta api eksekutif, taksi bandara, dan BBM dinas', 250000.00, 0, 1, '2026-10-15 08:00:00'),
(2, 'EXP-CAT-AKM', 'Akomodasi & Penginapan', 'Biaya sewa kamar hotel dinas luar kota', 850000.00, 0, 1, '2026-10-15 08:00:00'),
(3, 'EXP-CAT-ML', 'Konsumsi & Jamuan Klien', 'Jamuan makan siang meeting klien, konsumsi rapat tim internal', 150000.00, 0, 1, '2026-10-15 08:00:00'),
(4, 'EXP-CAT-ATK', 'Perlengkapan & Keperluan Kantor', 'Pembelian ATK darurat, materai resmi, dan operasional kerja', 100000.00, 0, 1, '2026-10-15 08:00:00'),
(5, 'EXP-CAT-KOM', 'Komunikasi & Internet', 'Paket data darurat kunjungan lapangan dan pulsa koordinasi', 150000.00, 0, 1, '2026-10-15 08:00:00'),
(6, 'EXP-CAT-TRN-DEV', 'Pelatihan & Sertifikasi IT', 'Biaya keikutsertaan workshop teknis dan sertifikasi staf', 1500000.00, 0, 1, '2026-10-15 08:00:00');
");

$expenses = [
    // [id, exp_no, emp_id, cat_id, date, desc, amount, pay_method, bank, merchant, status]
    [1, 'EXP-202611-0001', 3, 1, '2026-11-02', 'Tiket Kereta Argo Parahyangan Gambir - Bandung PP', 350000.00, 'BANK_TRANSFER', 'BCA', 'PT Kereta Api Indonesia', 'PAID'],
    [2, 'EXP-202611-0002', 3, 2, '2026-11-02', 'Hotel Ibis Pasteur Bandung 2 Malam Kunjungan Klien', 1100000.00, 'BANK_TRANSFER', 'BCA', 'Ibis Hotel Bandung', 'PAID'],
    [3, 'EXP-202611-0003', 3, 3, '2026-11-03', 'Makan Siang & Diskusi Teknis Tim PIC Digital Sinergi', 380000.00, 'CASH', 'BCA', 'Restoran Pagi Sore Pasteur', 'PAID'],
    [4, 'EXP-202611-0004', 3, 1, '2026-11-03', 'Grab Korporat PP Stasiun - Kantor Klien Dago', 120000.00, 'CASH', 'BCA', 'Grab Indonesia', 'PAID'],
    [5, 'EXP-202611-0005', 4, 4, '2026-11-06', 'Pembelian Materai Elektronik & Fisik 10.000 untuk Kontrak', 160000.00, 'CASH', 'BNI', 'Kantor Pos Sudirman', 'PAID'],
    [6, 'EXP-202611-0006', 4, 3, '2026-11-07', 'Konsumsi Rapat Koordinasi Rekonsiliasi Pajak Q3', 240000.00, 'BANK_TRANSFER', 'BNI', 'Kopi Kenangan Tower', 'PAID'],
    [7, 'EXP-202611-0007', 15, 6, '2026-11-10', 'Biaya Sertifikasi Kubernetes Administrator (CKA)', 2100000.00, 'BANK_TRANSFER', 'BCA', 'The Linux Foundation', 'APPROVED'],
    [8, 'EXP-202611-0008', 15, 1, '2026-11-11', 'Taksi Online Onsite Perbaikan Server Datacenter Cyber', 180000.00, 'CASH', 'BCA', 'Bluebird Taksi', 'APPROVED'],
    [9, 'EXP-202611-0009', 14, 4, '2026-11-15', 'Langganan Software Tax & E-Faktur Konsultan', 850000.00, 'BANK_TRANSFER', 'Mandiri', 'Ortax Media Solusindo', 'APPROVED'],
    [10, 'EXP-202611-0010', 3, 5, '2026-11-18', 'Paket Data Darurat Remote Support Deployment', 150000.00, 'CASH', 'BCA', 'Telkomsel MyEnterprise', 'DRAFT'],
];

$stmtExp = $pdo->prepare("
INSERT INTO expenses (
    expense_id, expense_number, employee_id, category_id, expense_date, description,
    amount, currency, payment_method, bank_name, merchant, department_id,
    is_reimbursable, is_reinvoiceable, status, created_by, created_at
) VALUES (
    ?, ?, ?, ?, ?, ?,
    ?, 'IDR', ?, ?, ?, 1,
    1, 0, ?, 3, '2026-11-02 08:00:00'
)
");

foreach ($expenses as $x) {
    $stmtExp->execute([
        $x[0], $x[1], $x[2], $x[3], $x[4], $x[5],
        $x[6], $x[7], $x[8], $x[9], $x[10]
    ]);
}

// Laporan Biaya Gabungan & Terbit
$pdo->exec("
INSERT INTO expense_reports (
    report_id, report_number, employee_id, title, description, created_by,
    start_date, end_date, total_amount, status, submitted_at, approved_at, posted_at, paid_at, created_at
) VALUES
(1, 'EXP-202611-0001', 3, 'Laporan Biaya Perjalanan Dinas Implementasi Klien Bandung', 'Perjalanan dinas meeting teknis dan deploy sistem di PT Digital Sinergi Bandung', 3, '2026-11-02', '2026-11-04', 1950000.00, 'PAID', '2026-11-04 10:00:00', '2026-11-04 14:30:00', '2026-11-05 09:00:00', '2026-11-05 15:30:00', '2026-11-04 09:00:00'),
(2, 'EXP-202611-0002', 4, 'Laporan Operasional Tim Keuangan & Materai Pajak', 'Klaim operasional pembelian materai resmi dan konsumsi rapat tim keuangan', 4, '2026-11-06', '2026-11-08', 400000.00, 'PAID', '2026-11-08 11:00:00', '2026-11-08 15:00:00', '2026-11-09 10:00:00', '2026-11-09 16:00:00', '2026-11-08 10:00:00'),
(3, 'EXP-202611-0003', NULL, 'Laporan Pengeluaran Gabungan Operasional Q4 (Multi-Karyawan)', 'Pengajuan pengeluaran gabungan sertifikasi IT Andi Pratama dan software pajak Citra Amelia', 1, '2026-11-10', '2026-11-16', 3130000.00, 'APPROVED', '2026-11-16 14:00:00', '2026-11-17 11:00:00', NULL, NULL, '2026-11-16 11:00:00');
");

$pdo->exec("
INSERT INTO expense_report_employees (
    report_employee_id, report_id, employee_id, employee_number_snapshot, employee_name_snapshot, department_name_snapshot, created_at
) VALUES
(1, 1, 3, 'EMP-0103', 'Budi Santoso', 'Teknologi Informasi', '2026-11-04 09:00:00'),
(2, 2, 4, 'EMP-0104', 'Sari Putri', 'Keuangan & Akuntansi', '2026-11-08 10:00:00'),
(3, 3, 15, 'EMP-0115', 'Andi Pratama', 'Teknologi Informasi', '2026-11-16 11:00:00'),
(4, 3, 14, 'EMP-0114', 'Citra Amelia', 'Keuangan & Akuntansi', '2026-11-16 11:00:00');
");

$pdo->exec("
INSERT INTO expense_report_items (report_item_id, report_id, expense_id, report_employee_id) VALUES
(1, 1, 1, 1),
(2, 1, 2, 1),
(3, 1, 3, 1),
(4, 1, 4, 1),
(5, 2, 5, 2),
(6, 2, 6, 2),
(7, 3, 7, 3),
(8, 3, 8, 3),
(9, 3, 9, 4);
");

$pdo->exec("
INSERT INTO expense_approvals (approval_id, report_id, approver_id, action, comment, created_at) VALUES
(1, 1, 2, 'APPROVE', 'Pengeluaran dinas Bandung disetujui, bukti tiket dan kuitansi hotel lengkap', '2026-11-04 14:30:00'),
(2, 2, 2, 'APPROVE', 'Klaim ATK dan konsumsi tim disetujui', '2026-11-08 15:00:00'),
(3, 3, 2, 'APPROVE', 'Disetujui untuk peningkatan kompetensi tim IT dan kebutuhan software pajak', '2026-11-17 11:00:00');
");

$pdo->exec("
INSERT INTO expense_postings (posting_id, report_id, journal_reference, posting_date, posted_by, posted_at, notes) VALUES
(1, 1, 'JRN-EXP-202611-0001', '2026-11-05', 5, '2026-11-05 09:00:00', 'Pembukuan beban perjalanan dinas Bandung EXP-202611-0001'),
(2, 2, 'JRN-EXP-202611-0002', '2026-11-09', 5, '2026-11-09 10:00:00', 'Pembukuan beban operasional ATK kantor EXP-202611-0002');
");

$pdo->exec("
INSERT INTO expense_reimbursements (
    reimbursement_id, report_id, report_employee_id, employee_id, approved_amount, paid_amount, status, notes, created_at
) VALUES
(1, 1, 1, 3, 1950000.00, 1950000.00, 'PAID', 'Kewajiban klaim Budi Santoso perjalanan dinas Bandung', '2026-11-05 09:00:00'),
(2, 2, 2, 4, 400000.00, 400000.00, 'PAID', 'Kewajiban klaim Sari Putri operasional kantor', '2026-11-09 10:00:00'),
(3, 3, 3, 15, 2280000.00, 0.00, 'PENDING', 'Kewajiban klaim Andi Pratama (Sertifikasi & Taksi Onsite)', '2026-11-17 11:00:00'),
(4, 3, 4, 14, 850000.00, 0.00, 'PENDING', 'Kewajiban klaim Citra Amelia (Software Pajak)', '2026-11-17 11:00:00');
");

$pdo->exec("
INSERT INTO expense_reimbursement_payments (
    payment_id, reimbursement_id, payment_number, payment_date, amount,
    payment_method, bank_name, bank_reference, proof_path, paid_by, notes, created_at
) VALUES
(1, 1, 'PAY-202611-0001', '2026-11-05', 1950000.00, 'BANK_TRANSFER', 'BCA', 'TRX-BCA-9812401', NULL, 5, 'Transfer pencairan klaim dinas Budi Santoso ke rekening BCA', '2026-11-05 15:30:00'),
(2, 2, 'PAY-202611-0002', '2026-11-09', 400000.00, 'BANK_TRANSFER', 'BNI', 'TRX-BNI-7712390', NULL, 5, 'Transfer pencairan klaim ATK Sari Putri ke rekening BNI', '2026-11-09 16:00:00');
");

// Tabel reimbursements legacy
$pdo->exec("
INSERT INTO reimbursements (
    reimbursement_id, report_id, employee_id, amount, payment_method,
    payment_reference, payment_date, notes, status, processed_by, processed_at, created_at
) VALUES
(1, 1, 3, 1950000.00, 'BANK_TRANSFER', 'TRX-BCA-9812401', '2026-11-05', 'Reimbursement dinas Budi Santoso', 'PAID', 5, '2026-11-05 15:30:00', '2026-11-05 09:00:00'),
(2, 2, 4, 400000.00, 'BANK_TRANSFER', 'TRX-BNI-7712390', '2026-11-09', 'Reimbursement ATK Sari Putri', 'PAID', 5, '2026-11-09 16:00:00', '2026-11-09 10:00:00');
");

$pdo->exec("
INSERT INTO expense_comments (comment_id, report_id, author_id, comment_body, is_system, created_at) VALUES
(1, 1, 3, 'Mohon ditinjau laporannya Bu Rina, semua struk tiket dan hotel sudah saya lampirkan.', 0, '2026-11-04 10:05:00'),
(2, 1, 2, 'Baik Mas Budi, rincian sudah sesuai kebijakan plafon dinas luar kota. Sudah saya setujui.', 0, '2026-11-04 14:31:00'),
(3, 1, 5, 'Dana klaim sebesar Rp 1.950.000 telah ditransfer ke rekening BCA Mas Budi.', 0, '2026-11-05 15:31:00');
");

// -------------------------------------------------------------
// 11. ACTIVITY LOGS (Jejak Audit Realistis)
// -------------------------------------------------------------
$pdo->exec("
INSERT INTO activity_logs (activity_id, user_id, action, entity_type, entity_id, summary, ip_address, created_at) VALUES
(1, 6, 'LOGIN', 'auth', 6, 'Login Yuraa_n (HR Director) berhasil', '127.0.0.1', '2026-11-20 08:15:00'),
(2, 1, 'LOGIN', 'auth', 1, 'Login admin_gte (Administrator GT Expense) berhasil', '127.0.0.1', '2026-11-20 08:30:00'),
(3, 2, 'LOGIN', 'auth', 2, 'Login rina_manager (Manager Finance) berhasil', '127.0.0.1', '2026-11-20 08:45:00'),
(4, 3, 'LOGIN', 'auth', 3, 'Login budi_employee (Staff IT) berhasil', '127.0.0.1', '2026-11-20 09:00:00'),
(5, 5, 'LOGIN', 'auth', 5, 'Login dimas_finance (Treasury) berhasil', '127.0.0.1', '2026-11-20 09:15:00'),
(6, 7, 'CREATE', 'purchase_requisition', 1, 'Membuat pengajuan PR-202611-0001 kebutuhan IT', '127.0.0.1', '2026-11-20 09:30:00'),
(7, 2, 'APPROVE', 'expense_reports', 1, 'Menyetujui laporan biaya perjalanan dinas EXP-202611-0001', '127.0.0.1', '2026-11-20 10:00:00'),
(8, 5, 'POST', 'expense_reports', 1, 'Membukukan laporan EXP-202611-0001 ke jurnal umum JRN-EXP-202611-0001', '127.0.0.1', '2026-11-20 10:30:00'),
(9, 5, 'PAY', 'expense_reimbursements', 1, 'Menyelesaikan pembayaran reimbursement PAY-202611-0001 sebesar Rp 1.950.000', '127.0.0.1', '2026-11-20 11:00:00'),
(10, 11, 'STOCK_ADJUSTMENT', 'products', 1, 'Verifikasi fisik stok persediaan barang gudang', '127.0.0.1', '2026-11-20 11:30:00');
");

if ($pdo->inTransaction()) {
    $pdo->commit();
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "Data dummy komprehensif berhasil dimasukkan ke seluruh tabel!\n";
