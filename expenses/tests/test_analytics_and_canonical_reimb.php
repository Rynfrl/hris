<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Url;
use App\Controllers\AnalyticsController;
use App\Services\AuthorizationService;

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

echo "========================================================\n";
echo " TEST SUITE: ANALYTICS, AUTHORIZATION & CANONICAL REIMB \n";
echo "========================================================\n";

// -----------------------------------------------------------------------------
// TEST GROUP 1: Prioritas 1 - Analytics & CSV Scoping (No Unknown Column Errors)
// -----------------------------------------------------------------------------
echo "\n--- Group 1: Analytics & CSV Role Scoping ---\n";

$managerUser = [
    'user_id' => 2,
    'username' => 'rina_manager',
    'role_name' => 'MANAGER',
    'permissions' => ['dashboard.view', 'expense.approve', 'reports.view'],
];

$financeUser = [
    'user_id' => 5,
    'username' => 'dimas_finance',
    'role_name' => 'FINANCE',
    'permissions' => ['dashboard.view', 'expense.post', 'expense.reimburse', 'reports.view'],
];

$adminUser = [
    'user_id' => 1,
    'username' => 'admin_gte',
    'role_name' => 'ADMIN',
    'permissions' => ['*'],
];

// 1.1 Manager Analytics Page Load
try {
    Auth::login($managerUser);
    $_GET = ['start_date' => '2026-01-01', 'end_date' => '2026-12-31'];
    $analyticsCtrl = new AnalyticsController();
    $html = $analyticsCtrl->index();
    assertTest("1.1 Manager Analytics index executes without SQL error", str_contains($html, 'Analitik') && !str_contains($html, 'Unknown column'));
} catch (\Throwable $e) {
    assertTest("1.1 Manager Analytics index executes without SQL error", false, $e->getMessage());
}

// 1.2 Finance Analytics Page Load
try {
    Auth::login($financeUser);
    $_GET = ['start_date' => '2026-01-01', 'end_date' => '2026-12-31'];
    $analyticsCtrl = new AnalyticsController();
    $html = $analyticsCtrl->index();
    assertTest("1.2 Finance Analytics index executes without SQL error", str_contains($html, 'Analitik') && !str_contains($html, 'Unknown column'));
} catch (\Throwable $e) {
    assertTest("1.2 Finance Analytics index executes without SQL error", false, $e->getMessage());
}

// 1.3 Admin Analytics Page Load
try {
    Auth::login($adminUser);
    $_GET = ['start_date' => '2026-01-01', 'end_date' => '2026-12-31'];
    $analyticsCtrl = new AnalyticsController();
    $html = $analyticsCtrl->index();
    assertTest("1.3 Admin Analytics index executes without SQL error", str_contains($html, 'Analitik') && !str_contains($html, 'Unknown column'));
} catch (\Throwable $e) {
    assertTest("1.3 Admin Analytics index executes without SQL error", false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST GROUP 2: Prioritas 3 - Authorization Deny-By-Default
// -----------------------------------------------------------------------------
echo "\n--- Group 2: Authorization Deny-By-Default ---\n";

$authService = new AuthorizationService();

// 2.1 Invalid report ID (<= 0)
assertTest("2.1 isReportApprover rejects report_id <= 0",
    $authService->isReportApprover($managerUser, ['report_id' => 0]) === false
);
assertTest("2.2 isReportApprover rejects negative report_id",
    $authService->isReportApprover($managerUser, ['report_id' => -5]) === false
);
assertTest("2.3 canDecideReport rejects report_id <= 0",
    $authService->canDecideReport($managerUser, ['report_id' => 0, 'status' => 'SUBMITTED', 'created_by' => 99]) === false
);

// 2.4 Unassigned report (Deny by default)
$unassignedReport = [
    'report_id' => 999999, // non-existent in DB
    'approver_id' => null,
    'status' => 'SUBMITTED',
    'created_by' => 99,
];
assertTest("2.4 isReportApprover rejects unassigned non-existent report",
    $authService->isReportApprover($managerUser, $unassignedReport) === false
);
assertTest("2.5 canDecideReport rejects unassigned report",
    $authService->canDecideReport($managerUser, $unassignedReport) === false
);

// 2.6 Anti Self-Approval
$selfReport = [
    'report_id' => 1,
    'approver_id' => $managerUser['user_id'],
    'status' => 'SUBMITTED',
    'created_by' => $managerUser['user_id'], // created by self!
];
assertTest("2.6 canDecideReport rejects self-created report (Anti Self-Approval)",
    $authService->canDecideReport($managerUser, $selfReport) === false
);

// 2.7 Assigned report allows decision
$assignedReport = [
    'report_id' => 1,
    'approver_id' => $managerUser['user_id'],
    'status' => 'SUBMITTED',
    'created_by' => 1, // created by admin
];
assertTest("2.7 canDecideReport permits assigned manager on submitted report",
    $authService->canDecideReport($managerUser, $assignedReport) === true
);

// 2.8 Another manager cannot decide
$otherManager = [
    'user_id' => 999,
    'username' => 'other_manager',
    'role_name' => 'MANAGER',
    'permissions' => ['expense.approve'],
];
assertTest("2.8 canDecideReport rejects unassigned other manager",
    $authService->canDecideReport($otherManager, $assignedReport) === false
);

// -----------------------------------------------------------------------------
// TEST GROUP 3: Prioritas 2 & 4 - Canonical Reimbursement Harmonization
// -----------------------------------------------------------------------------
echo "\n--- Group 3: Canonical Reimbursements Schema ---\n";

// 3.1 Check reimbursements table columns
$cols = $pdo->query("SHOW COLUMNS FROM `reimbursements`")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'Field');
assertTest("3.1 Canonical table `reimbursements` exists", in_array('reimbursement_id', $colNames, true));
assertTest("3.2 `reimbursements` has `report_id`", in_array('report_id', $colNames, true));
assertTest("3.3 `reimbursements` has `amount`", in_array('amount', $colNames, true));
assertTest("3.4 `reimbursements` has `status`", in_array('status', $colNames, true));
assertTest("3.5 `reimbursements` has `payment_method`", in_array('payment_method', $colNames, true));

// Check employee_id nullable
$empCol = null;
foreach ($cols as $c) {
    if ($c['Field'] === 'employee_id') {
        $empCol = $c;
        break;
    }
}
assertTest("3.6 `employee_id` in `reimbursements` is NULLABLE", $empCol !== null && $empCol['Null'] === 'YES');

// Check UNIQUE constraint on report_id
$indexes = $pdo->query("SHOW INDEX FROM `reimbursements` WHERE Column_name = 'report_id'")->fetchAll(PDO::FETCH_ASSOC);
$hasUniqueReportId = false;
foreach ($indexes as $idx) {
    if ($idx['Non_unique'] == 0) {
        $hasUniqueReportId = true;
        break;
    }
}
assertTest("3.7 `report_id` in `reimbursements` has UNIQUE constraint (1 Report = 1 Reimbursement)", $hasUniqueReportId);

// -----------------------------------------------------------------------------
// TEST GROUP 4: Router Integrity (Prevent Collision on {id})
// -----------------------------------------------------------------------------
echo "\n--- Group 4: Router Integrity ---\n";

$router = new Router();
$routes = require __DIR__ . '/../config/routes.php';
$routes($router);

// 4.1 Route candidate matching: /expenses/reports does NOT match /expenses/{id}
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/expenses/reports';
$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';
Url::reset();
Auth::login($adminUser);

try {
    $res = $router->dispatch('GET', '/expenses/reports');
    assertTest("4.1 /expenses/reports dispatches to ReportController (not ExpenseController 404)",
        str_contains((string)$res, 'Laporan Pengeluaran') && !str_contains((string)$res, 'Pengeluaran Tidak Ditemukan')
    );
} catch (\Throwable $e) {
    assertTest("4.1 /expenses/reports dispatches cleanly", false, $e->getMessage());
}

// 4.2 Route /expenses/reports?status=POSTED
try {
    Url::reset();
    $res = $router->dispatch('GET', '/expenses/reports?status=POSTED');
    assertTest("4.2 /expenses/reports?status=POSTED dispatches cleanly to ReportController",
        str_contains((string)$res, 'Laporan Pengeluaran') && !str_contains((string)$res, 'Pengeluaran Tidak Ditemukan')
    );
} catch (\Throwable $e) {
    assertTest("4.2 /expenses/reports?status=POSTED dispatches cleanly", false, $e->getMessage());
}

// 4.3 Route /reports/{id} with integer ID (e.g. 14)
try {
    Url::reset();
    $res = $router->dispatch('GET', '/expenses/reports/14');
    assertTest("4.3 /expenses/reports/14 dispatches cleanly with full HTML layout",
        strlen((string)$res) > 5000 && str_contains((string)$res, '<html') && str_contains((string)$res, 'EXP-')
    );
} catch (\Throwable $e) {
    assertTest("4.3 /expenses/reports/14 dispatches cleanly", false, $e->getMessage());
}

echo "\n========================================================\n";
echo " SUMMARY: $passed PASSED, $failed FAILED\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
