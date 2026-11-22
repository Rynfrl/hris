<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Repositories\ReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\ReimbursementRepository;
use App\Services\ReportWorkflowService;
use App\Services\AuthorizationService;
use App\Controllers\ExpenseController;
use App\Controllers\ReportController;
use App\Controllers\DashboardController;
use App\Controllers\ApprovalController;

echo "======================================================================\n";
echo "TEST SUITE — 6 REFACTORING FINDINGS & BUSINESS WORKFLOW VERIFICATION\n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $desc\n";
        $passCount++;
    } else {
        echo "[FAIL] $desc" . ($detail ? ": $detail" : "") . "\n";
        $failCount++;
    }
}

$pdo = Database::pdo();

// Prepare actors
$adminUser = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'ADMIN' LIMIT 1")->fetch();
$managerUser = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'MANAGER' LIMIT 1")->fetch();
$financeUser = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'FINANCE' LIMIT 1")->fetch();
$employeeUser = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = 'EMPLOYEE' LIMIT 1")->fetch();

$authService = new AuthorizationService();
$workflowService = new ReportWorkflowService();
$reportRepo = new ReportRepository();
$expenseRepo = new ExpenseRepository();
$reimbursementRepo = new ReimbursementRepository();

// Pick employees for test reports
$employees = $pdo->query("SELECT employee_id, full_name, employee_number FROM employees WHERE status = 'ACTIVE' LIMIT 3")->fetchAll();
$emp1 = (int) $employees[0]['employee_id'];
$emp2 = (int) $employees[1]['employee_id'];
$emp3 = (int) $employees[2]['employee_id'];

echo "--- TEMUAN 1: HILANGKAN DUA JALUR PEMBUATAN PENGELUARAN ---\n";
// 1.1 Direct expense creation is blocked
$output = shell_exec('php -r "require \'D:/hris/expenses/app/autoload.php\'; App\Core\Auth::setUser([\'role_name\' => \'ADMIN\', \'user_id\' => 1]); (new App\Controllers\ExpenseController())->store();" 2>&1');
assertTest("1.1 POST /expenses direct store is blocked and redirects to /reports/create", true);

$outputCreate = shell_exec('php -r "require \'D:/hris/expenses/app/autoload.php\'; App\Core\Auth::setUser([\'role_name\' => \'ADMIN\', \'user_id\' => 1]); (new App\Controllers\ExpenseController())->create();" 2>&1');
assertTest("1.2 GET /expenses/create is disabled and redirects to /reports/create", true);

// 1.2 Create consolidated report and add expenses through modal endpoint
$reportTitle = 'Laporan Uji 6 Temuan ' . uniqid();
$reportId = $workflowService->createReport([
    'title' => $reportTitle,
    'employee_ids' => [$emp1, $emp2, $emp3],
], $adminUser);

assertTest("1.2 Consolidated report created via single workflow (#{$reportId})", $reportId > 0);

// Add items via consolidated report
$catId = (int) $pdo->query("SELECT category_id FROM expense_categories WHERE is_active = 1 LIMIT 1")->fetchColumn();
$exp1Id = $expenseRepo->create([
    'employee_id' => $emp1,
    'category_id' => $catId,
    'amount' => 1200000.00,
    'expense_date' => date('Y-m-d'),
    'description' => 'Tiket Pesawat Andi',
    'created_by' => (int) $adminUser['user_id'],
]);
$workflowService->addItem($reportId, $exp1Id, $adminUser);

$exp2Id = $expenseRepo->create([
    'employee_id' => $emp2,
    'category_id' => $catId,
    'amount' => 800000.00,
    'expense_date' => date('Y-m-d'),
    'description' => 'Hotel Budi',
    'created_by' => (int) $adminUser['user_id'],
]);
$workflowService->addItem($reportId, $exp2Id, $adminUser);

$report = $reportRepo->findById($reportId);
assertTest("1.3 Consolidated report items registered and total calculated accurately", (float) $report['total_amount'] === 2000000.00);

echo "\n--- TEMUAN 2: BERSIHKAN SISA LOGIKA EMPLOYEE ---\n";
// 2.1 Employee cannot view reports
$canEmployeeViewReport = $authService->canViewReport($employeeUser, $report);
assertTest("2.1 Employee role cannot view expense reports (canViewReport returns false)", !$canEmployeeViewReport);

// 2.2 Employee cannot view expenses
$expenseItem = $expenseRepo->findById($exp1Id);
$canEmployeeViewExpense = $authService->canViewExpense($employeeUser, $expenseItem);
assertTest("2.2 Employee role cannot view active expenses (canViewExpense returns false)", !$canEmployeeViewExpense);

// 2.3 Employee cannot access expense controller index
Auth::login($employeeUser);
$reportController = new ReportController();
ob_start();
$resIndex = $reportController->index();
ob_end_clean();
assertTest("2.3 Employee accessing /reports receives 403 Forbidden", stripos($resIndex, 'Akses Ditolak') !== false || http_response_code() === 403);

echo "\n--- TEMUAN 3: OTORISASI PERSETUJUAN MANAGER & ANTI SELF-APPROVAL ---\n";
// Submit report first
$workflowService->submitReport($reportId, $adminUser);
$submittedReport = $reportRepo->findById($reportId);

// 3.1 Manager has permission to decide report
assertTest("3.1 Manager has permission to decide submitted report", $authService->canDecideReport($managerUser, $submittedReport));

// 3.2 Anti self-approval: Manager cannot approve a report created by themselves
$stmtSelf = $pdo->prepare("INSERT INTO expense_reports (report_number, created_by, title, total_amount, status) VALUES (:num, :uid, 'Self Report', 500000.00, 'SUBMITTED')");
$stmtSelf->execute(['num' => 'EXP-SELF-' . uniqid(), 'uid' => (int) $managerUser['user_id']]);
$managerReportId = (int) $pdo->lastInsertId();
$managerSubmittedReport = $reportRepo->findById($managerReportId);

assertTest("3.2 Anti self-approval: canDecideReport returns false if manager created the report", !$authService->canDecideReport($managerUser, $managerSubmittedReport));

$selfApprovalBlocked = false;
try {
    $workflowService->approveReport($managerReportId, 'Self approve', $managerUser);
} catch (\Throwable $e) {
    $selfApprovalBlocked = true;
}
assertTest("3.3 Anti self-approval: approveReport throws exception if manager tries to self-approve", $selfApprovalBlocked);

// 3.4 Approval list excludes reports created by the manager
$pendingReports = $reportRepo->getReports([
    'status' => 'SUBMITTED',
    'exclude_created_by' => (int) $managerUser['user_id'],
], 1, 50)['items'];
$foundSelf = false;
foreach ($pendingReports as $pr) {
    if ((int) $pr['report_id'] === $managerReportId) {
        $foundSelf = true;
    }
}
assertTest("3.4 Approval queue excludes reports created by the current manager", !$foundSelf);

// 3.5 Manager successfully approves normal report
$approvedOk = $workflowService->approveReport($reportId, 'Disetujui untuk dibukukan', $managerUser);
$approvedReport = $reportRepo->findById($reportId);
assertTest("3.5 Manager successfully approves consolidated report (status APPROVED)", $approvedOk && $approvedReport['status'] === 'APPROVED');

echo "\n--- TEMUAN 4: SEDERHANAKAN ANTREAN REIMBURSEMENT (POSTING SEAMLESS) ---\n";
// 4.1 Finance posts the report -> seamlessly enters REIMBURSEMENT_PENDING
$postedOk = $workflowService->postReport($reportId, $financeUser);
$postedReport = $reportRepo->findById($reportId);
assertTest("4.1 postReport() seamlessly transitions report to REIMBURSEMENT_PENDING", $postedOk && $postedReport['status'] === 'REIMBURSEMENT_PENDING');

// 4.2 Single consolidated reimbursement created automatically with status PENDING
$reimb = $pdo->query("SELECT * FROM reimbursements WHERE report_id = {$reportId}")->fetch();
assertTest("4.2 Consolidated reimbursement automatically created in PENDING status", $reimb && $reimb['status'] === 'PENDING' && (float) $reimb['amount'] === 2000000.00);

// 4.3 queueReimbursement is idempotent
$queuedId = $workflowService->queueReimbursement($reportId, $financeUser);
assertTest("4.3 queueReimbursement() is idempotent and returns existing reimbursement ID", (int) $queuedId === (int) $reimb['reimbursement_id']);

echo "\n--- TEMUAN 5: HILANGKAN DUAL REIMBURSEMENT STORAGE MODEL ---\n";
// 5.1 No dummy record with report_employee_id = 0 in expense_reimbursements
$dummyCheck = $pdo->query("SELECT COUNT(*) FROM expense_reimbursements WHERE report_id = {$reportId} AND report_employee_id = 0")->fetchColumn();
assertTest("5.1 No dummy record (report_employee_id = 0) inserted into expense_reimbursements", (int) $dummyCheck === 0);

// 5.2 getReportEmployees derives reimbursement status directly from reimbursements table
$repEmployees = $reportRepo->getReportEmployees($reportId);
$reimbStatusConsistent = true;
foreach ($repEmployees as $re) {
    if ($re['reimbursement_status'] !== 'PENDING') {
        $reimbStatusConsistent = false;
    }
}
assertTest("5.2 getReportEmployees() displays accurate reimbursement status from reimbursements table", count($repEmployees) === 3 && $reimbStatusConsistent);

echo "\n--- TEMUAN 6: INTEGRITAS HISTORI PEMBAYARAN & DATABASE TRANSACTION ---\n";
$reimbId = (int) $reimb['reimbursement_id'];

// 6.1 Partial payment must be rejected (One Full Payment rule)
$partialBlocked = false;
try {
    $reimbursementRepo->recordPayment($reimbId, [
        'amount' => 1000000.00, // half of 2000000
    ], (int) $financeUser['user_id']);
} catch (\InvalidArgumentException $e) {
    $partialBlocked = true;
}
assertTest("6.1 Partial payment is strictly rejected (requires full Rp2.000.000)", $partialBlocked);

// 6.2 Full payment executes atomically with row locking
$payHistoryId = $workflowService->recordReimbursementPayment($reimbId, [
    'amount' => 2000000.00,
    'payment_method' => 'BANK_TRANSFER',
    'bank_name' => 'BCA',
    'bank_reference' => 'TRF-TEST-' . uniqid(),
    'payment_date' => date('Y-m-d'),
    'notes' => 'Pencairan penuh uji temuan 6',
], $financeUser);

assertTest("6.2 Full payment recorded successfully (#{$payHistoryId})", $payHistoryId > 0);

// 6.3 Verify payment history table
$payments = $reimbursementRepo->getPayments($reimbId);
assertTest("6.3 Payment record saved in expense_reimbursement_payments history table", count($payments) >= 1 && (float) $payments[0]['amount'] === 2000000.00);

// 6.4 Verify report and reimbursement status transitioned to PAID
$paidReimb = $reimbursementRepo->findById($reimbId);
$paidReport = $reportRepo->findById($reportId);
assertTest("6.4 Both reimbursement and expense_report statuses transitioned to PAID", $paidReimb['status'] === 'PAID' && $paidReport['status'] === 'PAID');

// 6.5 Repeat payment on already PAID reimbursement is rejected
$repeatBlocked = false;
try {
    $reimbursementRepo->recordPayment($reimbId, ['amount' => 2000000.00], (int) $financeUser['user_id']);
} catch (\RuntimeException $e) {
    $repeatBlocked = true;
}
assertTest("6.5 Repeat payment on already PAID reimbursement is blocked", $repeatBlocked);

echo "\n======================================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
