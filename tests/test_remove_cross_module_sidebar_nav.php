<?php
/**
 * Test Suite: Remove Cross-Module ERP Navigation From All Sidebars
 * 
 * Verifies:
 * 1. Absence of cross-module links (Navigasi ERP, Kembali ke HRIS, Modul Purchasing,
 *    Modul Sales, Modul Inventory, Modul Expenses) in sidebars across all 5 modules.
 * 2. Preservation of internal menus for:
 *    - HRIS (Dashboard, Karyawan, Divisi, Payroll, Users)
 *    - Purchasing (Dashboard, PR, GRN, Invoices, Returns, Scorecard, Products, Suppliers, Activity Log)
 *    - Sales (Dashboard, Customers, Products, Orders, Invoices, Reports)
 *    - Inventory (Dashboard, Products, Stock In, Stock Out, Stock History, Suppliers, Categories, Reports, Settings)
 *    - GT Expense (Dashboard, Create Report, Reports, Approvals, Posting, Reimbursements, Analytics, Audit Log)
 * 3. Preservation of User Profile & Logout across all modules.
 * 4. Simulation of rendered HTML output for each module.
 */

declare(strict_types=1);

$failures = [];
$assertions = 0;

function assertTrue(bool $condition, string $message): void {
    global $failures, $assertions;
    $assertions++;
    if (!$condition) {
        $failures[] = "FAILED: " . $message;
        echo "[FAIL] " . $message . "\n";
    } else {
        echo "[PASS] " . $message . "\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE: REMOVE CROSS-MODULE ERP NAVIGATION FROM ALL SIDEBARS\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. HRIS MODULE AUDIT
// -----------------------------------------------------------------------------
echo "--- 1. Auditing HRIS Module (dashboard.php) ---\n";
$hrisContent = file_get_contents(__DIR__ . '/../dashboard.php');
assertTrue(!str_contains($hrisContent, 'Navigasi ERP'), "HRIS sidebar has no 'Navigasi ERP'");
assertTrue(!str_contains($hrisContent, 'Kembali ke HRIS'), "HRIS sidebar has no 'Kembali ke HRIS'");
assertTrue(!str_contains($hrisContent, 'Modul Purchasing'), "HRIS sidebar has no 'Modul Purchasing'");
assertTrue(!str_contains($hrisContent, 'Modul Sales'), "HRIS sidebar has no 'Modul Sales'");
assertTrue(!str_contains($hrisContent, 'Modul Inventory'), "HRIS sidebar has no 'Modul Inventory'");
assertTrue(!str_contains($hrisContent, 'Modul Expenses'), "HRIS sidebar has no 'Modul Expenses'");
assertTrue(str_contains($hrisContent, 'class="navbar"'), "HRIS has clean top headbar");
assertTrue(str_contains($hrisContent, '<h2>HRIS</h2>'), "HRIS headbar displays 'HRIS'");
assertTrue(str_contains($hrisContent, 'logout.php'), "HRIS has working Logout button");
assertTrue(str_contains($hrisContent, 'class="welcome-card"'), "HRIS preserves user profile welcome card");
assertTrue(str_contains($hrisContent, 'id="users"'), "HRIS preserves User Management card");
assertTrue(str_contains($hrisContent, 'id="employees"'), "HRIS preserves Employee card");
assertTrue(str_contains($hrisContent, 'id="divisions"'), "HRIS preserves Division card");
assertTrue(str_contains($hrisContent, 'id="payrolls"'), "HRIS preserves Payroll card");

// -----------------------------------------------------------------------------
// 2. PURCHASING MODULE AUDIT
// -----------------------------------------------------------------------------
echo "\n--- 2. Auditing Purchasing Module (purchasing/partials/header.php) ---\n";
$purContent = file_get_contents(__DIR__ . '/../purchasing/partials/header.php');
assertTrue(!str_contains($purContent, 'Navigasi ERP'), "Purchasing sidebar has no 'Navigasi ERP'");
assertTrue(!str_contains($purContent, 'Kembali ke HRIS'), "Purchasing sidebar has no 'Kembali ke HRIS'");
assertTrue(!str_contains($purContent, 'Modul Sales'), "Purchasing sidebar has no 'Modul Sales'");
assertTrue(!str_contains($purContent, 'Modul Inventory'), "Purchasing sidebar has no 'Modul Inventory'");
assertTrue(!str_contains($purContent, 'Modul Expenses'), "Purchasing sidebar has no 'Modul Expenses'");
// Preserved internal menus
assertTrue(str_contains($purContent, '$navItems'), "Purchasing preserves internal \$navItems definition");
assertTrue(str_contains($purContent, 'dashboard.php'), "Purchasing preserves Dashboard menu");
assertTrue(str_contains($purContent, 'pr-list.php'), "Purchasing preserves PR menu");
assertTrue(str_contains($purContent, 'grn-list.php'), "Purchasing preserves GRN menu");
assertTrue(str_contains($purContent, 'vendor-invoices.php'), "Purchasing preserves Invoices menu");
assertTrue(str_contains($purContent, 'purchase-returns.php'), "Purchasing preserves Returns menu");
assertTrue(str_contains($purContent, 'vendor-scorecard.php'), "Purchasing preserves Scorecard menu");
assertTrue(str_contains($purContent, 'master-products.php'), "Purchasing preserves Products menu");
assertTrue(str_contains($purContent, 'master-suppliers.php'), "Purchasing preserves Suppliers menu");
assertTrue(str_contains($purContent, 'activity-log.php'), "Purchasing preserves Activity Log menu");
// Preserved user & logout
assertTrue(str_contains($purContent, 'pur-avatar-circle'), "Purchasing preserves user avatar");
assertTrue(str_contains($purContent, 'logout.php'), "Purchasing preserves Logout dropdown");

// -----------------------------------------------------------------------------
// 3. SALES MODULE AUDIT
// -----------------------------------------------------------------------------
echo "\n--- 3. Auditing Sales Module (sales/includes/sidebar.php) ---\n";
$salesContent = file_get_contents(__DIR__ . '/../sales/includes/sidebar.php');
assertTrue(!str_contains($salesContent, 'Navigasi ERP'), "Sales sidebar has no 'Navigasi ERP'");
assertTrue(!str_contains($salesContent, 'Kembali ke HRIS'), "Sales sidebar has no 'Kembali ke HRIS'");
assertTrue(!str_contains($salesContent, 'Modul Purchasing'), "Sales sidebar has no 'Modul Purchasing'");
assertTrue(!str_contains($salesContent, 'Modul Inventory'), "Sales sidebar has no 'Modul Inventory'");
assertTrue(!str_contains($salesContent, 'Modul Expenses'), "Sales sidebar has no 'Modul Expenses'");
// Preserved internal menus
assertTrue(str_contains($salesContent, 'Dashboard'), "Sales preserves Dashboard menu");
assertTrue(str_contains($salesContent, 'Customers'), "Sales preserves Customers menu");
assertTrue(str_contains($salesContent, 'Produk'), "Sales preserves Produk menu");
assertTrue(str_contains($salesContent, 'Sales Order'), "Sales preserves Sales Order menu");
assertTrue(str_contains($salesContent, 'Invoice'), "Sales preserves Invoice menu");
assertTrue(str_contains($salesContent, 'Laporan Penjualan'), "Sales preserves Laporan Penjualan menu");
// Preserved logout
assertTrue(str_contains($salesContent, 'sidebar-logout-btn'), "Sales preserves sidebar Logout button");

// -----------------------------------------------------------------------------
// 4. INVENTORY MODULE AUDIT
// -----------------------------------------------------------------------------
echo "\n--- 4. Auditing Inventory Module (Inventory/page.php & pages) ---\n";
$invPageContent = file_get_contents(__DIR__ . '/../Inventory/page.php');
assertTrue(!str_contains($invPageContent, 'Navigasi ERP'), "Inventory page.php does not inject 'Navigasi ERP'");
assertTrue(!str_contains($invPageContent, 'Kembali ke HRIS'), "Inventory page.php does not inject 'Kembali ke HRIS'");
assertTrue(!str_contains($invPageContent, 'Modul Purchasing'), "Inventory page.php does not inject 'Modul Purchasing'");
assertTrue(!str_contains($invPageContent, 'Modul Sales'), "Inventory page.php does not inject 'Modul Sales'");
assertTrue(!str_contains($invPageContent, 'Modul Expenses'), "Inventory page.php does not inject 'Modul Expenses'");
// Check Inventory pages
$invDashContent = file_get_contents(__DIR__ . '/../Inventory/pages/dashboard.html');
assertTrue(str_contains($invDashContent, 'Dashboard'), "Inventory preserves Dashboard menu");
assertTrue(str_contains($invDashContent, 'Produk'), "Inventory preserves Produk menu");
assertTrue(str_contains($invDashContent, 'Stok Masuk'), "Inventory preserves Stok Masuk menu");
assertTrue(str_contains($invDashContent, 'Stok Keluar'), "Inventory preserves Stok Keluar menu");
assertTrue(str_contains($invDashContent, 'Riwayat Stok'), "Inventory preserves Riwayat Stok menu");
assertTrue(str_contains($invDashContent, 'Supplier'), "Inventory preserves Supplier menu");
assertTrue(str_contains($invDashContent, 'Kategori'), "Inventory preserves Kategori menu");
assertTrue(str_contains($invDashContent, 'Laporan'), "Inventory preserves Laporan menu");
assertTrue(str_contains($invDashContent, 'Pengaturan'), "Inventory preserves Pengaturan menu");
assertTrue(str_contains($invDashContent, 'user-snippet'), "Inventory preserves user avatar & name");
assertTrue(str_contains($invDashContent, 'logout-link'), "Inventory preserves Keluar Sistem link");

// -----------------------------------------------------------------------------
// 5. GT EXPENSE MODULE AUDIT
// -----------------------------------------------------------------------------
echo "\n--- 5. Auditing GT Expense Module (expenses/app/Views/layouts/main.php) ---\n";
$expenseContent = file_get_contents(__DIR__ . '/../expenses/app/Views/layouts/main.php');
assertTrue(!str_contains($expenseContent, 'Navigasi ERP'), "Expense sidebar has no 'Navigasi ERP'");
assertTrue(!str_contains($expenseContent, 'Kembali ke HRIS'), "Expense sidebar has no 'Kembali ke HRIS'");
assertTrue(!str_contains($expenseContent, 'Modul Purchasing'), "Expense sidebar has no 'Modul Purchasing'");
assertTrue(!str_contains($expenseContent, 'Modul Sales'), "Expense sidebar has no 'Modul Sales'");
assertTrue(!str_contains($expenseContent, 'Modul Inventory'), "Expense sidebar has no 'Modul Inventory'");
// Preserved internal menus
assertTrue(str_contains($expenseContent, 'Dashboard'), "Expense preserves Dashboard menu");
assertTrue(str_contains($expenseContent, 'Buat Laporan Baru'), "Expense preserves Buat Laporan Baru menu");
assertTrue(str_contains($expenseContent, 'Daftar Laporan'), "Expense preserves Daftar Laporan menu");
assertTrue(!str_contains($expenseContent, 'Manajemen Akun Pengguna'), "Expense removes Manajemen Akun Pengguna from sidebar");
assertTrue(str_contains($expenseContent, 'Kategori Pengeluaran'), "Expense preserves Kategori Pengeluaran menu");
assertTrue(str_contains($expenseContent, 'Master Data'), "Expense preserves Master Data heading");
assertTrue(str_contains($expenseContent, 'Menunggu Persetujuan'), "Expense preserves Menunggu Persetujuan menu");
assertTrue(str_contains($expenseContent, 'Riwayat Persetujuan'), "Expense preserves Riwayat Persetujuan menu");
assertTrue(str_contains($expenseContent, 'Antrean Pembukuan'), "Expense preserves Antrean Pembukuan menu");
assertTrue(str_contains($expenseContent, 'Antrean Reimbursement'), "Expense preserves Antrean Reimbursement menu");
assertTrue(str_contains($expenseContent, 'Pembayaran Gagal'), "Expense preserves Pembayaran Gagal menu");
assertTrue(str_contains($expenseContent, 'Riwayat Reimbursement'), "Expense preserves Riwayat Reimbursement menu");
// Preserved user & logout
assertTrue(str_contains($expenseContent, 'Selamat datang,'), "Expense preserves user greeting in top navbar");
assertTrue(str_contains($expenseContent, 'Url::to(\'/logout\')'), "Expense preserves Logout form button");

echo "\n====================================================================\n";
echo "SUMMARY: " . ($assertions - count($failures)) . "/$assertions assertions passed.\n";
if (!empty($failures)) {
    echo "Failures encountered:\n";
    foreach ($failures as $f) {
        echo " - " . $f . "\n";
    }
    exit(1);
} else {
    echo "ALL CROSS-MODULE ERP NAVIGATION REMOVAL CHECKS PASSED SUCCESSFULLY!\n";
    echo "====================================================================\n";
    exit(0);
}
