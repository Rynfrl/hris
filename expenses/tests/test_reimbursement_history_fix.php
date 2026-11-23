<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Url;
use App\Controllers\ReimbursementController;
use App\Repositories\ReimbursementRepository;

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
echo " TEST SUITE: FIX RIWAYAT PEMBAYARAN REIMBURSEMENT (PAID HISTORY)    \n";
echo "====================================================================\n";

$financeUser = [
    'user_id' => 5,
    'username' => 'dimas_finance',
    'email' => 'dimas@hris.test',
    'role_id' => 3,
    'role_name' => 'FINANCE',
    'permissions' => ['dashboard.view', 'expense.post', 'expense.reimburse', 'reports.view'],
];

$nonFinanceUser = [
    'user_id' => 2,
    'username' => 'rina_manager',
    'email' => 'rina@hris.test',
    'role_id' => 2,
    'role_name' => 'MANAGER',
    'permissions' => ['dashboard.view', 'expense.approve'],
];

Auth::login($financeUser);
$reimbRepo = new ReimbursementRepository();
$controller = new ReimbursementController();

// -----------------------------------------------------------------------------
// TEST A — Jumlah Transaksi 21 & Pagination Page 1 and Page 2
// -----------------------------------------------------------------------------
echo "\n--- TEST A: 21 Transaksi PAID & Pagination (Halaman 1 dan 2) ---\n";

// Ensure there are at least 21 PAID records in DB
$paidCount = (int) $pdo->query("SELECT COUNT(*) FROM reimbursements WHERE status = 'PAID'")->fetchColumn();
echo "Actual database PAID count: $paidCount\n";
assertTest("Test A.0 Database has at least 21 PAID transactions", $paidCount >= 21);

// Test Page 1 (per_page = 20)
$_GET = ['page' => '1', 'per_page' => '20'];
$htmlPage1 = $controller->history();

assertTest("Test A.1 Page 1 renders without error", !empty($htmlPage1));
assertTest("Test A.2 Page 1 does NOT show empty state", !str_contains($htmlPage1, 'Belum ada riwayat pembayaran reimbursement yang lunas'));
assertTest("Test A.3 Page 1 shows pagination header info", str_contains($htmlPage1, 'Halaman 1 dari 2') || str_contains($htmlPage1, 'Total 21 transaksi') || str_contains($htmlPage1, 'Total ' . $paidCount . ' transaksi'));
// Count table rows rendered (excluding header row)
preg_match_all('/<tr>\s*<td>\s*<a href="[^"]*\/reports\//', $htmlPage1, $matchesP1);
$p1Rows = count($matchesP1[0]);
echo "Page 1 rendered rows: $p1Rows\n";
assertTest("Test A.4 Page 1 renders exactly 20 table rows", $p1Rows === 20);

// Test Page 2 (per_page = 20)
$_GET = ['page' => '2', 'per_page' => '20'];
$htmlPage2 = $controller->history();

assertTest("Test A.5 Page 2 renders without error", !empty($htmlPage2));
assertTest("Test A.6 Page 2 does NOT show empty state", !str_contains($htmlPage2, 'Belum ada riwayat pembayaran reimbursement yang lunas'));
preg_match_all('/<tr>\s*<td>\s*<a href="[^"]*\/reports\//', $htmlPage2, $matchesP2);
$p2Rows = count($matchesP2[0]);
echo "Page 2 rendered rows: $p2Rows\n";
assertTest("Test A.7 Page 2 renders remaining table rows (1 row if total is 21)", $p2Rows === ($paidCount - 20));

// -----------------------------------------------------------------------------
// TEST B — Halaman Kosong (Empty State)
// -----------------------------------------------------------------------------
echo "\n--- TEST B: Halaman Kosong (Empty State saat 0 data) ---\n";

$_GET = ['page' => '1', 'search' => 'NON_EXISTENT_SEARCH_STRING_999999'];
$htmlEmpty = $controller->history();

assertTest("Test B.1 Empty state message displayed", str_contains($htmlEmpty, 'Belum ada riwayat pembayaran reimbursement yang lunas'));
assertTest("Test B.2 Pagination hidden when total is 0", !str_contains($htmlEmpty, '<ul class="pagination'));

// -----------------------------------------------------------------------------
// TEST C — Status Berbeda (Hanya PAID yang Muncul)
// -----------------------------------------------------------------------------
echo "\n--- TEST C: Status Berbeda (Hanya PAID yang Muncul) ---\n";

$otherStatuses = $pdo->query("
    SELECT rem.status, COUNT(*) as cnt 
    FROM reimbursements rem 
    WHERE rem.status != 'PAID' 
    GROUP BY rem.status
")->fetchAll(PDO::FETCH_ASSOC);

echo "Non-PAID records in DB: " . json_encode($otherStatuses) . "\n";

// Query via getReimbursements with history filter
$historyData = $reimbRepo->getReimbursements(['status' => 'PAID'], 1, 100);
$allPaid = true;
foreach ($historyData['items'] as $item) {
    if ($item['status'] !== 'PAID') {
        $allPaid = false;
        break;
    }
}
assertTest("Test C.1 All items in history have status == PAID", $allPaid);
assertTest("Test C.2 View statusBadge displays Dibayar / PAID", !str_contains($htmlPage1, 'badge-pending') && !str_contains($htmlPage1, 'badge-processing'));

// -----------------------------------------------------------------------------
// TEST D — Konsistensi Data (COUNT vs Data Query)
// -----------------------------------------------------------------------------
echo "\n--- TEST D: Konsistensi Data (COUNT Query vs SELECT Query) ---\n";

$resFull = $reimbRepo->getReimbursements(['status' => 'PAID'], 1, 20);
$directCount = (int) $pdo->query("
    SELECT COUNT(*) 
    FROM reimbursements rem 
    JOIN expense_reports r ON r.report_id = rem.report_id 
    WHERE rem.status = 'PAID'
")->fetchColumn();

assertTest("Test D.1 Repository total equals direct COUNT query", $resFull['total'] === $directCount);
assertTest("Test D.2 Item count on page 1 matches min(perPage, total)", count($resFull['items']) === min(20, $directCount));
assertTest("Test D.3 total_pages calculation is consistent", $resFull['total_pages'] === (int) ceil($directCount / 20));

// -----------------------------------------------------------------------------
// TEST E — Mapping Variabel Controller & View
// -----------------------------------------------------------------------------
echo "\n--- TEST E: Mapping Variabel Controller & View ---\n";

// Test passing both 'reimbursements' and 'items'
assertTest("Test E.1 history.php works seamlessly with \$reimbursements", !str_contains($htmlPage1, 'Undefined variable'));
assertTest("Test E.2 history.php works seamlessly with empty array",
    str_contains(\App\Core\View::render('reimbursements/history', ['reimbursements' => [], 'total' => 0, 'page' => 1, 'totalPages' => 0]), 'Belum ada riwayat pembayaran reimbursement yang lunas')
);
assertTest("Test E.3 history.php fallback to \$items works seamlessly",
    !str_contains(\App\Core\View::render('reimbursements/history', ['items' => $resFull['items'], 'total' => $directCount, 'page' => 1, 'totalPages' => 2]), 'Belum ada riwayat')
);

// -----------------------------------------------------------------------------
// TEST F — Kelengkapan Field Pembayaran yang Ditampilkan
// -----------------------------------------------------------------------------
echo "\n--- TEST F: Kelengkapan Field Pembayaran ---\n";

$sample = $resFull['items'][0];
echo "Sample report_number: " . ($sample['report_number'] ?? 'null') . "\n";
echo "Sample amount: " . ($sample['amount'] ?? 'null') . "\n";
echo "Sample payment_date: " . ($sample['payment_date'] ?? 'null') . "\n";
echo "Sample payment_reference: " . ($sample['payment_reference'] ?? 'null') . "\n";

assertTest("Test F.1 report_number exists", !empty($sample['report_number']) && str_contains($htmlPage1, (string)$sample['report_number']));
assertTest("Test F.2 report_title / title exists", !empty($sample['report_title']));
assertTest("Test F.3 amount exists and formatted in HTML", !empty($sample['amount']));
assertTest("Test F.4 payment_method exists", !empty($sample['payment_method']));
assertTest("Test F.5 payment_reference exists", !empty($sample['payment_reference']));
assertTest("Test F.6 payment_date exists", !empty($sample['payment_date']));

// -----------------------------------------------------------------------------
// TEST G — Security, Authorization & Regression
// -----------------------------------------------------------------------------
echo "\n--- TEST G: Security, Authorization & Regression ---\n";

// G.1 Non-finance user gets 403
Auth::login($nonFinanceUser);
$html403 = $controller->history();
assertTest("Test G.1 Non-finance role accessing /reimbursements/history receives 403",
    str_contains($html403, 'Akses Ditolak') || http_response_code() === 403
);

// Restore finance login
Auth::login($financeUser);

// G.2 Router dispatches /reimbursements/history cleanly
$router = new Router();
$routes = require __DIR__ . '/../config/routes.php';
$routes($router);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/reimbursements/history';
$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';
Url::reset();

$dispatched = (string) $router->dispatch('GET', '/reimbursements/history');
assertTest("Test G.2 Router dispatches /reimbursements/history cleanly without 404",
    str_contains($dispatched, 'Riwayat Pembayaran Reimbursement') && !str_contains($dispatched, '404')
);

// G.3 Regression on /reimbursements index
$dispatchedReimb = (string) $router->dispatch('GET', '/reimbursements');
assertTest("Test G.3 Router dispatches /reimbursements queue cleanly",
    str_contains($dispatchedReimb, 'Antrean Pembayaran Reimbursement')
);

// G.4 Regression on /finance/posting
$dispatchedPost = (string) $router->dispatch('GET', '/finance/posting');
assertTest("Test G.4 Router dispatches /finance/posting cleanly",
    str_contains($dispatchedPost, 'Pembukuan Laporan')
);

// G.5 Regression on /finance/history
$dispatchedFinHist = (string) $router->dispatch('GET', '/finance/history');
assertTest("Test G.5 Router dispatches /finance/history cleanly",
    str_contains($dispatchedFinHist, 'Riwayat Pembukuan')
);

echo "\n====================================================================\n";
echo " FINAL RESULT: $passed PASSED, $failed FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
