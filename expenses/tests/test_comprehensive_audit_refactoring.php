<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\ReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\ReimbursementRepository;
use App\Services\AuthorizationService;
use App\Services\ReportWorkflowService;
use App\Services\ExpenseService;
use App\Controllers\FinanceController;
use App\Controllers\ReimbursementController;

echo "=== START COMPREHENSIVE 12-POINTS TEST ===\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] " . $desc . "\n";
        $passCount++;
    } else {
        echo "[FAIL] " . $desc . "\n";
        $failCount++;
    }
}

// 1. Syntax check on all modified files
$filesToCheck = [
    'expenses/app/Controllers/ApprovalController.php',
    'expenses/app/Controllers/ExpenseController.php',
    'expenses/app/Controllers/FinanceController.php',
    'expenses/app/Controllers/ReimbursementController.php',
    'expenses/app/Core/Auth.php',
    'expenses/app/Repositories/ExpenseRepository.php',
    'expenses/app/Repositories/ReimbursementRepository.php',
    'expenses/app/Repositories/ReportRepository.php',
    'expenses/app/Services/AuthorizationService.php',
    'expenses/app/Services/ExpenseService.php',
    'expenses/app/Services/ReportWorkflowService.php',
    'expenses/app/Views/approvals/index.php',
    'expenses/app/Views/finance/posting.php',
    'expenses/app/Views/finance/history.php',
    'expenses/app/Views/layouts/main.php',
    'expenses/app/Views/reimbursements/index.php',
    'expenses/app/Views/reports/index.php',
];

$allSyntaxOk = true;
foreach ($filesToCheck as $file) {
    $full = dirname(__DIR__, 2) . '/' . $file;
    $output = shell_exec('php -l ' . escapeshellarg($full));
    if (!str_contains($output, 'No syntax errors detected')) {
        echo "Syntax error in $file: $output\n";
        $allSyntaxOk = false;
    }
}
assertTest("Point 0: Syntax check of all modified files", $allSyntaxOk);

// Setup database connection
$pdo = Database::pdo();
$authService = new AuthorizationService();
$workflowService = new ReportWorkflowService();
$reimbRepo = new ReimbursementRepository();
$reportRepo = new ReportRepository();
$expenseRepo = new ExpenseRepository();
$expenseService = new ExpenseService();

// Mock users
$adminUser = ['user_id' => 1, 'username' => 'admin', 'email' => 'admin@test.com', 'role_id' => 8, 'role_name' => 'ADMIN', 'permissions' => ['expenses.manage', 'settings.manage']];
$managerUser = ['user_id' => 14, 'username' => 'manager', 'email' => 'manager@test.com', 'role_id' => 14, 'role_name' => 'MANAGER', 'permissions' => ['expense.approve']];
$financeUser = ['user_id' => 13, 'username' => 'finance', 'email' => 'finance@test.com', 'role_id' => 13, 'role_name' => 'FINANCE', 'permissions' => ['expense.post', 'expense.reimburse']];
$employeeUser = ['user_id' => 15, 'username' => 'employee', 'email' => 'emp@test.com', 'role_id' => 15, 'role_name' => 'EMPLOYEE', 'permissions' => []];

// POINT 1 & 4: Access boundaries and Report creation allowlist
assertTest("Point 4: Admin can create report", $authService->canCreateReport($adminUser) === true);
assertTest("Point 4: Finance CANNOT create report", $authService->canCreateReport($financeUser) === false);
assertTest("Point 4: Manager CANNOT create report", $authService->canCreateReport($managerUser) === false);
assertTest("Point 4: Employee CANNOT create report", $authService->canCreateReport($employeeUser) === false);

$workflowBlocked = false;
try {
    $workflowService->createReport(['title' => 'Finance Report Attempt'], $financeUser);
} catch (RuntimeException $e) {
    $workflowBlocked = str_contains($e->getMessage(), 'tidak diizinkan');
}
assertTest("Point 4: ReportWorkflowService rejects report creation by Finance", $workflowBlocked);

// POINT 1: FinanceController & ReimbursementController 403 for Admin
Auth::login($adminUser);
$financeCtrl = new FinanceController();
ob_start();
$outPosting = $financeCtrl->postingQueue();
ob_end_clean();
assertTest("Point 1: Admin accessing Finance postingQueue gets 403", str_contains($outPosting, 'Akses Ditolak'));

$reimbCtrl = new ReimbursementController();
ob_start();
$outReimb = $reimbCtrl->index();
ob_end_clean();
assertTest("Point 1: Admin accessing Reimbursement index gets 403", str_contains($outReimb, 'Akses Ditolak'));

// Reset auth to Finance
Auth::login($financeUser);

// POINT 2: Workflow reimbursement cannot run before posting
// Create a dummy test report with status APPROVED
$testReportId = $reportRepo->create([
    'title' => 'Test Workflow Enforce Posting Before Reimburse',
    'created_by' => 1,
    'employee_ids' => [1],
]);
$pdo->prepare("UPDATE expense_reports SET status = 'APPROVED' WHERE report_id = :id")->execute(['id' => $testReportId]);

$queueBlocked = false;
try {
    $workflowService->queueReimbursement($testReportId, $financeUser);
} catch (RuntimeException $e) {
    $queueBlocked = str_contains($e->getMessage(), 'Laporan belum dibukukan');
}
assertTest("Point 2: queueReimbursement rejects APPROVED report before posting", $queueBlocked);

// POINT 5: Idempotency of Reimbursement creation
// Create a consolidated reimbursement and set to PAID
$reimbId = $reimbRepo->createConsolidated([
    'report_id' => $testReportId,
    'amount' => '750000.00',
    'status' => 'PENDING',
    'notes' => 'Test idempotency',
]);
$pdo->prepare("UPDATE reimbursements SET status = 'PAID', amount = '750000.00' WHERE reimbursement_id = :id")->execute(['id' => $reimbId]);

// Calling createConsolidated again must NOT reset status or amount!
$reimbId2 = $reimbRepo->createConsolidated([
    'report_id' => $testReportId,
    'amount' => '100.00',
    'status' => 'PENDING',
    'notes' => 'Attempt overwrite',
]);
$checkReimb = $reimbRepo->findById($reimbId);
assertTest("Point 5: createConsolidated does not reset PAID status", $reimbId2 === $reimbId && $checkReimb['status'] === 'PAID' && (float)$checkReimb['amount'] == 750000.00);

// POINT 6: Decimal precision and BCMath full payment rule
$bcmathAvailable = extension_loaded('bcmath');
assertTest("Point 6: BCMath extension is active", $bcmathAvailable);

$testReimbId2 = $reimbRepo->createConsolidated([
    'report_id' => $testReportId,
    'amount' => '125000.50',
    'status' => 'PENDING',
]);
$pdo->prepare("UPDATE reimbursements SET status = 'PENDING', amount = '125000.50' WHERE reimbursement_id = :id")->execute(['id' => $testReimbId2]);

$partialBlocked = false;
try {
    $reimbRepo->recordPayment($testReimbId2, [
        'amount' => '125000.49', // 1 cent difference
        'recipient_name' => 'Andi',
        'payment_method' => 'BANK_TRANSFER',
        'bank_name' => 'BCA',
        'bank_reference' => 'TEST-REF-001',
    ], 13);
} catch (InvalidArgumentException $e) {
    $partialBlocked = str_contains($e->getMessage(), 'Pembayaran harus satu kali penuh');
}
assertTest("Point 6: Decimal precision prevents partial / mismatched payment", $partialBlocked);

// Clean full payment
$payHistId = $reimbRepo->recordPayment($testReimbId2, [
    'amount' => '125000.50',
    'recipient_name' => 'Andi',
    'payment_method' => 'BANK_TRANSFER',
    'bank_name' => 'BCA',
    'bank_reference' => 'TEST-REF-002',
], 13);
$reimbAfterPay = $reimbRepo->findById($testReimbId2);
assertTest("Point 6: Full payment succeeds and marks status PAID", $payHistId > 0 && $reimbAfterPay['status'] === 'PAID');

// POINT 7: Expense edit & delete security
// Expense item linked to a non-DRAFT report cannot be edited or deleted
$expenseId = $expenseRepo->create([
    'employee_id' => 1,
    'category_id' => 1,
    'expense_date' => date('Y-m-d'),
    'description' => 'Test Protected Expense',
    'amount' => 50000.00,
    'created_by' => 1,
]);
// Link expense to report
$repEmpId = $reportRepo->addReportEmployee($testReportId, 1);
$reportRepo->addItem($testReportId, $expenseId, $repEmpId);
$expenseData = $expenseRepo->findById($expenseId);

// Report is APPROVED / PAID, not DRAFT
assertTest("Point 7: Expense in non-DRAFT report cannot be edited", $authService->canEditExpense($adminUser, $expenseData) === false);
assertTest("Point 7: Expense in non-DRAFT report cannot be deleted", $authService->canDeleteExpense($adminUser, $expenseData) === false);

// POINT 3: Transaction visibility by role
$finExpenses = $expenseRepo->getExpenses(['status_in' => ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID']]);
$hasDraftInFin = false;
foreach ($finExpenses['items'] as $it) {
    if ($it['status'] === 'DRAFT' || $it['status'] === 'SUBMITTED') {
        $hasDraftInFin = true;
    }
}
assertTest("Point 3: Finance status_in filter excludes DRAFT and SUBMITTED transactions", !$hasDraftInFin);

// POINT 8: Universal Pagination
$postingsData = $reportRepo->getPostings([], 1, 10);
assertTest("Point 8: getPostings supports pagination", isset($postingsData['items'], $postingsData['total'], $postingsData['total_pages']));

$reimbData = $reimbRepo->getReimbursements([], 1, 10);
assertTest("Point 8: getReimbursements supports pagination", isset($reimbData['items'], $reimbData['total'], $reimbData['total_pages']));

$repData = $reportRepo->getReports([], 1, 10);
assertTest("Point 8: getReports supports pagination", isset($repData['items'], $repData['total'], $repData['total_pages']));

// POINT 10: Sidebar routes exist and load cleanly
$routesFile = dirname(__DIR__, 2) . '/expenses/config/routes.php';
$router = new \App\Core\Router();
$routeLoader = require $routesFile;
$routeLoader($router);
$routes = $router->getRoutes();

$hasPostingHistoryRoute = false;
foreach ($routes['GET'] ?? [] as $r) {
    if ($r['path'] === '/finance/history') {
        $hasPostingHistoryRoute = true;
    }
}
assertTest("Point 10: Route /finance/history exists in Router", $hasPostingHistoryRoute);

$hasRetryRoute = false;
foreach ($routes['POST'] ?? [] as $r) {
    if ($r['path'] === '/reimbursements/{id}/retry') {
        $hasRetryRoute = true;
    }
}
assertTest("Point 10b: Route POST /reimbursements/{id}/retry exists in Router", $hasRetryRoute);

// PERBAIKAN 2 & 3: FAILED lifecycle, failure audit trail, and RETRY flow
$failedReportId = $reportRepo->create([
    'title' => 'Test Failure and Retry Flow',
    'created_by' => 1,
    'employee_ids' => [1],
]);
$pdo->prepare("UPDATE expense_reports SET status = 'POSTED' WHERE report_id = :id")->execute(['id' => $failedReportId]);
$failedReimbId = $reimbRepo->createConsolidated([
    'report_id' => $failedReportId,
    'amount' => '500000.00',
    'status' => 'PROCESSING',
    'recipient_name' => 'Budi PIC',
    'bank_name' => 'BCA',
    'account_number' => '1234567890',
]);

// Mark as FAILED with audit
$failReason = 'Nomor rekening tujuan tidak valid / salah cabang';
$workflowService->markFailed($failedReimbId, $failReason, $financeUser, 'TRF-FAIL-001');
$reimbAfterFail = $reimbRepo->findById($failedReimbId);
$failures = $reimbRepo->getFailures($failedReimbId);

assertTest("Perbaikan 2 & 3: Status becomes FAILED and note is recorded", $reimbAfterFail['status'] === 'FAILED' && str_contains($reimbAfterFail['notes'], $failReason));
assertTest("Perbaikan 3: Failure recorded in expense_reimbursement_failures table", count($failures) >= 1 && $failures[0]['failure_reason'] === $failReason && (int)$failures[0]['attempt_number'] === 1);

// Retry Reimbursement
$retryOk = $workflowService->retryReimbursement($failedReimbId, $financeUser);
$reimbAfterRetry = $reimbRepo->findById($failedReimbId);
assertTest("Perbaikan 2: retryReimbursement returns status to PROCESSING", $retryOk && $reimbAfterRetry['status'] === 'PROCESSING');

// PERBAIKAN 4: Employee bank privacy removed from getReportEmployees
$reportEmps = $reportRepo->getReportEmployees($failedReportId);
$hasBankColumn = false;
if (!empty($reportEmps)) {
    if (isset($reportEmps[0]['bank_account']) || isset($reportEmps[0]['bank_name'])) {
        $hasBankColumn = true;
    }
}
assertTest("Perbaikan 4: getReportEmployees hides bank accounts from participant list", !$hasBankColumn);

// PERBAIKAN 7: Event-based approval history
$historyData = $reportRepo->getApprovalEventsHistory(['search' => 'Test Failure and Retry Flow']);
assertTest("Perbaikan 7: getApprovalEventsHistory captures FAILED & RETRY events", isset($historyData['data']) && count($historyData['data']) >= 2);

// PERBAIKAN 8: Strict Proof Validation
$invalidExtProof = [
    'name' => 'script.php',
    'type' => 'text/php',
    'tmp_name' => __FILE__,
    'error' => UPLOAD_ERR_OK,
    'size' => 1024,
];
$proofBlocked = false;
try {
    $workflowService->recordReimbursementPayment($failedReimbId, [
        'amount' => '500000.00',
        'recipient_name' => 'Budi PIC',
        'payment_method' => 'BANK_TRANSFER',
        'bank_reference' => 'TRF-SUCCESS-001',
    ], $financeUser, $invalidExtProof);
} catch (RuntimeException $e) {
    $proofBlocked = str_contains($e->getMessage(), 'Format berkas bukti pembayaran tidak valid');
}
assertTest("Perbaikan 8: Uploading non-allowed extension proof is rejected", $proofBlocked);

// Clean up test data
$pdo->prepare("DELETE FROM expense_reimbursement_failures WHERE reimbursement_id = :id")->execute(['id' => $failedReimbId]);
$pdo->prepare("DELETE FROM reimbursements WHERE reimbursement_id = :id")->execute(['id' => $failedReimbId]);
$pdo->prepare("DELETE FROM expense_approvals WHERE report_id = :id")->execute(['id' => $failedReportId]);
$pdo->prepare("DELETE FROM expense_report_employees WHERE report_id = :id")->execute(['id' => $failedReportId]);
$pdo->prepare("DELETE FROM expense_reports WHERE report_id = :id")->execute(['id' => $failedReportId]);

// Clean up test data
$pdo->prepare("DELETE FROM expense_reimbursement_payments WHERE reimbursement_id IN (:r1, :r2)")->execute(['r1' => $reimbId, 'r2' => $testReimbId2]);
$pdo->prepare("DELETE FROM reimbursements WHERE report_id = :id")->execute(['id' => $testReportId]);
$pdo->prepare("DELETE FROM expense_report_items WHERE report_id = :id")->execute(['id' => $testReportId]);
$pdo->prepare("DELETE FROM expense_report_employees WHERE report_id = :id")->execute(['id' => $testReportId]);
$pdo->prepare("DELETE FROM expenses WHERE expense_id = :id")->execute(['id' => $expenseId]);
$pdo->prepare("DELETE FROM expense_reports WHERE report_id = :id")->execute(['id' => $testReportId]);

echo "\n=== SUMMARY: {$passCount} PASSED, {$failCount} FAILED ===\n";
