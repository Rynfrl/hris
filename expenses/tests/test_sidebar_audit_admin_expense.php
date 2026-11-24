<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Url;
use App\Core\View;

$pdo = Database::connect();

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $passed++;
    } else {
        echo "![FAIL] $name: $detail\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo " TEST SUITE: AUDIT SIDEBAR ADMIN EXPENSE, MANAGER & FINANCE         \n";
echo "====================================================================\n";

$adminUser = [
    'user_id' => 1,
    'username' => 'admin_gte',
    'email' => 'admin@hris.test',
    'role_id' => 1,
    'role_name' => 'ADMIN',
    'permissions' => ['*'],
];

$managerUser = [
    'user_id' => 2,
    'username' => 'rina_manager',
    'email' => 'rina@hris.test',
    'role_id' => 2,
    'role_name' => 'MANAGER',
    'permissions' => ['dashboard.view', 'expense.approve', 'reports.view'],
];

$financeUser = [
    'user_id' => 5,
    'username' => 'dimas_finance',
    'email' => 'dimas@hris.test',
    'role_id' => 3,
    'role_name' => 'FINANCE',
    'permissions' => ['dashboard.view', 'expense.post', 'expense.reimburse', 'reports.view'],
];

$router = new Router();
$routes = require __DIR__ . '/../config/routes.php';
$routes($router);

// -----------------------------------------------------------------------------
// TEST 1: Admin Expense Sidebar Verification
// -----------------------------------------------------------------------------
echo "\n--- TEST 1: Sidebar Admin Expense ---\n";
Auth::login($adminUser);
Url::reset();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/dashboard';
$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';

$adminHtml = (string) $router->dispatch('GET', '/dashboard');

assertTest("1.1 Dashboard menu exists in Admin sidebar", str_contains($adminHtml, 'Dashboard'));
assertTest("1.2 Buat Laporan Baru exists in Admin sidebar", str_contains($adminHtml, 'Buat Laporan Baru'));
assertTest("1.3 Daftar Laporan exists in Admin sidebar", str_contains($adminHtml, 'Daftar Laporan'));
assertTest("1.4 Daftar Transaksi REMOVED from Admin sidebar", !str_contains($adminHtml, 'Daftar Transaksi'));
assertTest("1.5 Manajemen Akun Pengguna REMOVED from Admin sidebar", !str_contains($adminHtml, 'Manajemen Akun Pengguna'));
assertTest("1.6 Kategori Pengeluaran exists in Admin sidebar", str_contains($adminHtml, 'Kategori Pengeluaran'));
assertTest("1.7 Analitik & Ekspor exists in Admin sidebar", str_contains($adminHtml, 'Analitik &amp; Ekspor') || str_contains($adminHtml, 'Analitik & Ekspor'));
assertTest("1.8 Master Data heading exists in Admin sidebar", str_contains($adminHtml, 'Master Data'));

// -----------------------------------------------------------------------------
// TEST 2: Manager Expense Sidebar Verification (Untouched)
// -----------------------------------------------------------------------------
echo "\n--- TEST 2: Sidebar Manager Expense (Untouched) ---\n";
Auth::login($managerUser);
Url::reset();
$_SERVER['REQUEST_URI'] = '/dashboard';

$mgrHtml = (string) $router->dispatch('GET', '/dashboard');

assertTest("2.1 Menunggu Persetujuan exists in Manager sidebar", str_contains($mgrHtml, 'Menunggu Persetujuan'));
assertTest("2.2 Riwayat Persetujuan exists in Manager sidebar", str_contains($mgrHtml, 'Riwayat Persetujuan'));
assertTest("2.3 Semua Laporan exists in Manager sidebar", str_contains($mgrHtml, 'Semua Laporan'));
assertTest("2.4 Analitik Pengeluaran exists in Manager sidebar", str_contains($mgrHtml, 'Analitik Pengeluaran'));
assertTest("2.5 Daftar Transaksi does NOT appear in Manager sidebar", !str_contains($mgrHtml, 'Daftar Transaksi'));

// -----------------------------------------------------------------------------
// TEST 3: Finance Sidebar Verification (Untouched)
// -----------------------------------------------------------------------------
echo "\n--- TEST 3: Sidebar Finance (Untouched) ---\n";
Auth::login($financeUser);
Url::reset();
$_SERVER['REQUEST_URI'] = '/dashboard';

$finHtml = (string) $router->dispatch('GET', '/dashboard');

assertTest("3.1 Antrean Pembukuan exists in Finance sidebar", str_contains($finHtml, 'Antrean Pembukuan'));
assertTest("3.2 Riwayat Pembukuan exists in Finance sidebar", str_contains($finHtml, 'Riwayat Pembukuan'));
assertTest("3.3 Antrean Reimbursement exists in Finance sidebar", str_contains($finHtml, 'Antrean Reimbursement'));
assertTest("3.4 Pembayaran Gagal exists in Finance sidebar", str_contains($finHtml, 'Pembayaran Gagal'));
assertTest("3.5 Riwayat Reimbursement exists in Finance sidebar", str_contains($finHtml, 'Riwayat Reimbursement'));
assertTest("3.6 Daftar Laporan exists in Finance sidebar", str_contains($finHtml, 'Daftar Laporan'));
assertTest("3.7 Laporan Pengeluaran exists in Finance sidebar", str_contains($finHtml, 'Laporan Pengeluaran'));
assertTest("3.8 Daftar Transaksi does NOT appear in Finance sidebar", !str_contains($finHtml, 'Daftar Transaksi'));

// -----------------------------------------------------------------------------
// TEST 4: Navigation Integrity & Routes
// -----------------------------------------------------------------------------
echo "\n--- TEST 4: Navigation Integrity & Transaction Details ---\n";
Auth::login($adminUser);
Url::reset();

// 4.1 /reports/create
$resCreate = (string) $router->dispatch('GET', '/reports/create');
assertTest("4.1 Route /reports/create opens Buat Laporan Baru page",
    str_contains($resCreate, 'Buat Laporan Pengeluaran Baru') || str_contains($resCreate, 'Buat Laporan')
);

// 4.2 /reports
$resReports = (string) $router->dispatch('GET', '/reports');
assertTest("4.2 Route /reports opens Daftar Laporan page",
    str_contains($resReports, 'Laporan Pengeluaran')
);

// 4.3 Report detail with transactions table
$latestReportId = (int) $pdo->query("SELECT report_id FROM expense_reports ORDER BY report_id DESC LIMIT 1")->fetchColumn();
if ($latestReportId > 0) {
    $resDetail = (string) $router->dispatch('GET', '/reports/' . $latestReportId);
    assertTest("4.3 Route /reports/{id} renders transaction items table in consolidated report",
        str_contains($resDetail, 'Daftar Transaksi Pengeluaran') || str_contains($resDetail, 'Rincian Pengeluaran') || str_contains($resDetail, 'EXP-') || str_contains($resDetail, 'GTE-')
    );
}

echo "\n====================================================================\n";
echo " FINAL RESULT: $passed PASSED, $failed FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
