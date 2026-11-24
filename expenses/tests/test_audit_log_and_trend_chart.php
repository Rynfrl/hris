<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\Database;
use App\Core\View;
use App\Core\Auth;
use App\Repositories\AuditRepository;
use App\Services\DashboardService;
use App\Controllers\SettingController;

echo "====================================================================\n";
echo " TEST SUITE: AUDIT LOG SYSTEM & MONTHLY EXPENSE TREND CHART        \n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function runTest(string $code, string $description, bool $condition, ?string $detail = null): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $code: $description\n";
    } else {
        $failCount++;
        echo " [FAIL] $code: $description" . ($detail ? " ($detail)" : "") . "\n";
    }
}

// -------------------------------------------------------------------
// SECTION 1: AUDIT REPOSITORY & UNIFIED LOG RETRIEVAL
// -------------------------------------------------------------------
echo "--- SECTION 1: Audit Repository & Unified Log Retrieval ---\n";

$auditRepo = new AuditRepository();

// Test 1.1: Fetch all logs
$allLogs = $auditRepo->getAuditLogs(1, 25, null);
runTest(
    'AUD-01',
    'getAuditLogs returns unified records and total > 0',
    $allLogs['total'] > 0 && count($allLogs['items']) > 0,
    "total: " . $allLogs['total'] . ", items: " . count($allLogs['items'])
);

// Test 1.2: Check item structure
$firstItem = $allLogs['items'][0] ?? [];
$requiredKeys = ['log_id', 'user_id', 'action', 'module', 'record_id', 'old_value', 'new_value', 'summary', 'ip_address', 'created_at', 'username'];
$hasAllKeys = true;
foreach ($requiredKeys as $k) {
    if (!array_key_exists($k, $firstItem)) {
        $hasAllKeys = false;
        break;
    }
}
runTest(
    'AUD-02',
    'Unified log item has all required columns',
    $hasAllKeys,
    "keys: " . implode(', ', array_keys($firstItem))
);

// Test 1.3: Module filtering - expenses
$expenseLogs = $auditRepo->getAuditLogs(1, 25, 'expenses');
$allExpenseModule = true;
foreach ($expenseLogs['items'] as $item) {
    if ($item['module'] !== 'expenses') {
        $allExpenseModule = false;
        break;
    }
}
runTest(
    'AUD-03',
    'Filtering by module "expenses" returns only expenses records',
    $expenseLogs['total'] > 0 && $allExpenseModule,
    "total: " . $expenseLogs['total']
);

// Test 1.4: Module filtering - expense_reports
$reportLogs = $auditRepo->getAuditLogs(1, 25, 'expense_reports');
$allReportModule = true;
foreach ($reportLogs['items'] as $item) {
    if ($item['module'] !== 'expense_reports') {
        $allReportModule = false;
        break;
    }
}
runTest(
    'AUD-04',
    'Filtering by module "expense_reports" returns report, item, and employee activities',
    $reportLogs['total'] > 0 && $allReportModule,
    "total: " . $reportLogs['total']
);

// Test 1.5: Module filtering - reimbursements
$reimbLogs = $auditRepo->getAuditLogs(1, 25, 'reimbursements');
$allReimbModule = true;
foreach ($reimbLogs['items'] as $item) {
    if ($item['module'] !== 'reimbursements') {
        $allReimbModule = false;
        break;
    }
}
runTest(
    'AUD-05',
    'Filtering by module "reimbursements" returns reimbursement activities',
    $reimbLogs['total'] > 0 && $allReimbModule,
    "total: " . $reimbLogs['total']
);

// Test 1.6: Module filtering - users
$userLogs = $auditRepo->getAuditLogs(1, 25, 'users');
$allUserModule = true;
foreach ($userLogs['items'] as $item) {
    if ($item['module'] !== 'users') {
        $allUserModule = false;
        break;
    }
}
runTest(
    'AUD-06',
    'Filtering by module "users" returns user and auth (login/logout) activities',
    $userLogs['total'] > 0 && $allUserModule,
    "total: " . $userLogs['total']
);

// Test 1.7: Pagination
$page1 = $auditRepo->getAuditLogs(1, 10, null);
$page2 = $auditRepo->getAuditLogs(2, 10, null);
$differentIds = ($page1['items'][0]['log_id'] ?? '') !== ($page2['items'][0]['log_id'] ?? '');
runTest(
    'AUD-07',
    'Pagination produces distinct pages without duplicate offset',
    count($page1['items']) === 10 && count($page2['items']) === 10 && $differentIds,
    "page 1 first: {$page1['items'][0]['log_id']}, page 2 first: {$page2['items'][0]['log_id']}"
);

// Test 1.8: Total pages calculation
runTest(
    'AUD-08',
    'Total pages calculation is mathematically accurate',
    $allLogs['total_pages'] === (int) ceil($allLogs['total'] / 25),
    "total_pages: {$allLogs['total_pages']}, expected: " . ceil($allLogs['total'] / 25)
);

// -------------------------------------------------------------------
// SECTION 2: VIEW HELPERS & AUDIT LOG TABLE RENDERING
// -------------------------------------------------------------------
echo "\n--- SECTION 2: View Helpers & Audit Log Table Rendering ---\n";

// Test 2.1: Action badge helper
$bLogin = View::auditActionBadge('LOGIN');
$bApproved = View::auditActionBadge('REPORT_APPROVED');
$bFailed = View::auditActionBadge('REIMBURSEMENT_FAILED');
$bRetry = View::auditActionBadge('REIMBURSEMENT_RETRY');
$bPosted = View::auditActionBadge('REPORT_POSTED');

runTest(
    'BADGE-01',
    'View::auditActionBadge returns colored badge with action text',
    str_contains($bLogin, 'bg-secondary') &&
    str_contains($bApproved, 'bg-success') &&
    str_contains($bFailed, 'bg-danger') &&
    str_contains($bRetry, 'bg-warning') &&
    str_contains($bPosted, 'bg-primary')
);

// Test 2.2: Diff & summary formatter
$summaryOnly = View::formatAuditChange('Mengajukan laporan GTE-001', null, null);
runTest(
    'DIFF-01',
    'View::formatAuditChange displays summary text',
    str_contains($summaryOnly, 'Mengajukan laporan GTE-001')
);

$jsonDiff = View::formatAuditChange(
    null,
    json_encode(['status' => 'SUBMITTED', 'amount' => 50000]),
    json_encode(['status' => 'APPROVED', 'amount' => 50000])
);
runTest(
    'DIFF-02',
    'View::formatAuditChange formats old vs new diff with del and ins tags',
    str_contains($jsonDiff, '<del') && str_contains($jsonDiff, 'SUBMITTED') &&
    str_contains($jsonDiff, '<ins') && str_contains($jsonDiff, 'APPROVED')
);

$both = View::formatAuditChange(
    'Laporan disetujui',
    json_encode(['status' => 'SUBMITTED']),
    json_encode(['status' => 'APPROVED'])
);
runTest(
    'DIFF-03',
    'View::formatAuditChange combines summary and diff when both are provided',
    str_contains($both, 'Laporan disetujui') && str_contains($both, 'APPROVED')
);

// Test 2.3: Render audit_logs view template
$renderedView = View::render('settings/audit_logs', [
    'title' => 'Audit Log Sistem',
    'logs' => $allLogs['items'],
    'total' => $allLogs['total'],
    'page' => $allLogs['page'],
    'totalPages' => $allLogs['total_pages'],
    'module' => null,
    'user' => ['role_name' => 'ADMIN', 'username' => 'admin_test'],
], null);

runTest(
    'VIEW-01',
    'settings/audit_logs view renders table headers and records without error',
    str_contains($renderedView, 'Audit Log Sistem') &&
    str_contains($renderedView, 'table-gt') &&
    str_contains($renderedView, $allLogs['items'][0]['action']) &&
    !str_contains($renderedView, 'Belum ada audit log tercatat.')
);

runTest(
    'VIEW-02',
    'settings/audit_logs view has module filter dropdown with all 4 required modules',
    str_contains($renderedView, 'value="expenses"') &&
    str_contains($renderedView, 'value="expense_reports"') &&
    str_contains($renderedView, 'value="reimbursements"') &&
    str_contains($renderedView, 'value="users"')
);

// -------------------------------------------------------------------
// SECTION 3: MONTHLY EXPENSE TREND CHART DATA & RENDERING
// -------------------------------------------------------------------
echo "\n--- SECTION 3: Monthly Expense Trend Chart Data & Rendering ---\n";

$dashService = new DashboardService();
$adminMetrics = $dashService->getMetrics(['role_name' => 'ADMIN', 'user_id' => 1]);
$trend = $adminMetrics['monthly_trend'] ?? [];

// Test 3.1: Exactly 6 rolling months
runTest(
    'CHART-01',
    'monthly_trend returns array of exactly 6 rolling months',
    is_array($trend) && count($trend) === 6,
    "count: " . count($trend)
);

// Test 3.2: Check keys in each trend item
$allHaveKeys = true;
$chronological = true;
$prevYm = '';
foreach ($trend as $i => $item) {
    if (!isset($item['ym']) || !isset($item['label']) || !isset($item['month']) || !array_key_exists('total', $item)) {
        $allHaveKeys = false;
        break;
    }
    if (!is_numeric($item['total'])) {
        $allHaveKeys = false;
        break;
    }
    if ($prevYm !== '' && strcmp($item['ym'], $prevYm) <= 0) {
        $chronological = false;
    }
    $prevYm = $item['ym'];
}

runTest(
    'CHART-02',
    'Each monthly_trend item contains ym, label, month, and numeric total',
    $allHaveKeys,
    "sample: " . json_encode($trend[0] ?? [])
);

runTest(
    'CHART-03',
    'monthly_trend items are strictly in ascending chronological order',
    $chronological,
    "yms: " . implode(', ', array_column($trend, 'ym'))
);

// Test 3.3: Current month is the 6th month and contains actual sum
$currentYm = date('Y-m');
$lastTrendItem = end($trend);
runTest(
    'CHART-04',
    'The 6th item in monthly_trend corresponds to current month ('. $currentYm .') with actual nominal',
    $lastTrendItem['ym'] === $currentYm && $lastTrendItem['total'] > 0,
    "last ym: {$lastTrendItem['ym']}, total: {$lastTrendItem['total']}"
);

// Test 3.4: Label is non-empty and contains month & year
$sampleLabel = $lastTrendItem['label'] ?? '';
runTest(
    'CHART-05',
    'Label has valid human-readable format (e.g. "Nov 2026")',
    strlen($sampleLabel) >= 7 && str_contains($sampleLabel, date('Y')),
    "label: $sampleLabel"
);

// Test 3.5: Dashboard view template scripts
$dashView = View::render('dashboard/index', [
    'title' => 'Dashboard',
    'metrics' => $adminMetrics,
    'user' => ['role_name' => 'ADMIN', 'username' => 'admin_test'],
], null);

runTest(
    'CHART-06',
    'dashboard/index view initializes dashboardTrendChart with resilient label fallback',
    str_contains($dashView, 'dashboardTrendChart') &&
    str_contains($dashView, 'd.label || d.month || d.ym') &&
    str_contains($dashView, 'GTCharts.createTrendChart')
);

// Test 3.6: app.js contains chart destroy guard
$appJs = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
runTest(
    'CHART-07',
    'assets/js/app.js contains existing chart destroy guard against canvas reuse errors',
    str_contains($appJs, 'Chart.getChart(element)') &&
    str_contains($appJs, 'existing.destroy()')
);

// -------------------------------------------------------------------
// SECTION 4: INTEGRITY OF OTHER ERP MODULES & WORKFLOW
// -------------------------------------------------------------------
echo "\n--- SECTION 4: Integrity of Other ERP Modules & Workflow ---\n";

// Test 4.1: Manager metrics also has valid monthly_trend
$managerMetrics = $dashService->getMetrics(['role_name' => 'MANAGER', 'user_id' => 2]);
runTest(
    'MGR-01',
    'Manager dashboard metrics also includes valid 6-month trend',
    count($managerMetrics['monthly_trend'] ?? []) === 6
);

// Test 4.2: Finance metrics also has valid monthly_trend
$financeMetrics = $dashService->getMetrics(['role_name' => 'FINANCE', 'user_id' => 5]);
runTest(
    'FIN-01',
    'Finance dashboard metrics also includes valid 6-month trend',
    count($financeMetrics['monthly_trend'] ?? []) === 6
);

// Clean up temporary inspection script if present
if (file_exists(__DIR__ . '/inspect_data.php')) {
    unlink(__DIR__ . '/inspect_data.php');
}

echo "\n====================================================================\n";
echo " FINAL RESULT: $passCount PASSED, $failCount FAILED\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
