<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\Database;
use App\Core\Auth;
use App\Services\AuthorizationService;
use App\Services\ReportWorkflowService;
use App\Repositories\ReportRepository;
use App\Repositories\ReimbursementRepository;
use App\Repositories\ExpenseRepository;

$pdo = Database::pdo();
$authService = new AuthorizationService();
$workflowService = new ReportWorkflowService();
$reportRepo = new ReportRepository();
$reimbRepo = new ReimbursementRepository();
$expenseRepo = new ExpenseRepository();

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$desc} " . ($detail ? "({$detail})" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE: 9 CORE PRIORITIES VERIFICATION\n";
echo "====================================================================\n\n";

// Fetch test users
$uAdmin = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.username = 'admin_gte' OR u.user_id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$uManager1 = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = 2 OR u.username = 'rina_manager' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$uFinance = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = 5 OR u.username = 'dimas_finance' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$uAdminHR = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.role_id = 2 OR r.role_name = 'Admin_HR' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$uManager2 = [
    'user_id' => 99999,
    'username' => 'budi_mgr2',
    'role_name' => 'MANAGER',
    'permissions' => ['expense.approve'],
];

// -------------------------------------------------------------
// PRIORITAS 2: Admin_HR Decoupling
// -------------------------------------------------------------
echo "--- Testing Prioritas 2: Admin_HR Decoupling ---\n";
$adminHrNoPerm = $uAdminHR;
$adminHrNoPerm['permissions'] = [];
assertTest("Admin_HR without expenses.manage cannot manage expenses", !$authService->isAdminExpense($adminHrNoPerm));
assertTest("Admin_HR without expenses.manage cannot create report", !$authService->canCreateReport($adminHrNoPerm));

$adminHrWithPerm = $uAdminHR;
$adminHrWithPerm['permissions'] = ['expenses.manage'];
assertTest("Admin_HR WITH expenses.manage CAN manage expenses", $authService->isAdminExpense($adminHrWithPerm));
assertTest("Admin_HR WITH expenses.manage CAN create report", $authService->canCreateReport($adminHrWithPerm));

$adminExpenseUser = $uAdmin;
assertTest("ADMIN / ADMIN_EXPENSE unconditionally CAN manage expenses", $authService->isAdminExpense($adminExpenseUser));

// -------------------------------------------------------------
// PRIORITAS 1: Manager Record-Level Authorization & Anti Self-Approval
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 1: Manager Record-Level Authorization & Anti Self-Approval ---\n";

// 1. Create a test report assigned to Manager 1
$testReportId = $reportRepo->create([
    'title' => 'Laporan Uji Record Level ' . uniqid(),
    'created_by' => (int)$uAdmin['user_id'],
    'approver_id' => (int)$uManager1['user_id'],
    'employee_ids' => [1, 2],
]);
$testReport = $reportRepo->findById($testReportId);

assertTest("Report created with approver_id assigned to Manager 1", (int)$testReport['approver_id'] === (int)$uManager1['user_id']);
assertTest("Manager 1 is report approver for assigned report", $authService->isReportApprover($uManager1, $testReport));
assertTest("Manager 2 is NOT report approver for report assigned to Manager 1", !$authService->isReportApprover($uManager2, $testReport));
assertTest("Manager 1 can view assigned report", $authService->canViewReport($uManager1, $testReport));
assertTest("Manager 2 CANNOT view report assigned to Manager 1", !$authService->canViewReport($uManager2, $testReport));

// Anti Self-Approval test: Manager 1 creates a report -> Manager 1 cannot approve it
$selfReport = [
    'report_id' => 99998,
    'status' => 'SUBMITTED',
    'created_by' => (int)$uManager1['user_id'],
    'approver_id' => (int)$uManager1['user_id'],
];
assertTest("Anti self-approval: Creator cannot be approver even if manager", !$authService->isReportApprover($uManager1, $selfReport));
assertTest("canDecideReport strictly rejects self-approval", !$authService->canDecideReport($uManager1, $selfReport));

// -------------------------------------------------------------
// PRIORITAS 6: Decimal Financial Precision (No Float Casts)
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 6: Decimal Financial Precision (No Float Casts) ---\n";
assertTest("formatDecimal converts integer to 2 decimal places", ReimbursementRepository::formatDecimal('1500000') === '1500000.00');
assertTest("formatDecimal handles existing decimals correctly", ReimbursementRepository::formatDecimal('1500000.75') === '1500000.75');
assertTest("formatDecimal sanitizes currency characters safely", ReimbursementRepository::formatDecimal('Rp 2.500.000,50') !== '');

// -------------------------------------------------------------
// PRIORITAS 7: Restrict Bank & Recipient Info (No Leaks)
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 7: Restrict Bank & Recipient Info ---\n";
$reimbData = $reimbRepo->createConsolidated([
    'report_id' => $testReportId,
    'amount' => '2500000.00',
    'status' => 'PENDING',
    'notes' => 'Test reimbursement bank privacy',
]);
$fetchedReimb = $reimbRepo->findById($reimbData);
assertTest("Reimbursement bank details does not pull emp.bank_account when null", empty($fetchedReimb['account_number']) || $fetchedReimb['account_number'] === '-');

// -------------------------------------------------------------
// PRIORITAS 8: Meaningful Failure Reason Enforcement
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 8: Meaningful Payment Failure Reason ---\n";
$reimbRepo->updateStatus($reimbData, 'PROCESSING', (int)$uFinance['user_id']);

$shortFailedCaught = false;
try {
    $workflowService->markFailed($reimbData, 'Ggl', $uFinance);
} catch (InvalidArgumentException $e) {
    $shortFailedCaught = true;
}
assertTest("markFailed rejects reason shorter than 5 chars", $shortFailedCaught);

$genericFailedCaught = false;
try {
    $workflowService->markFailed($reimbData, 'Pembayaran gagal', $uFinance);
} catch (InvalidArgumentException $e) {
    $genericFailedCaught = true;
}
assertTest("markFailed rejects generic default reason 'Pembayaran gagal'", $genericFailedCaught);

// -------------------------------------------------------------
// PRIORITAS 5: Atomic FAILED & Failure History Recording
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 5: Atomic FAILED & Failure History Recording ---\n";
$validReason = 'Rekening tujuan terblokir oleh bank penerima';
$okFail = $workflowService->markFailed($reimbData, $validReason, $uFinance, 'TRX-ERR-001');
assertTest("markFailed succeeds with explicit meaningful reason", $okFail);

$reimbAfterFail = $reimbRepo->findById($reimbData);
assertTest("Reimbursement status transitioned to FAILED", $reimbAfterFail['status'] === 'FAILED');

$failures = $reimbRepo->getFailures($reimbData);
assertTest("Failure history recorded in expense_reimbursement_failures", count($failures) >= 1);
assertTest("Failure reason matches permanently", $failures[0]['failure_reason'] === $validReason);
assertTest("Attempt number computed accurately", (int)$failures[0]['attempt_number'] >= 1);

// -------------------------------------------------------------
// PRIORITAS 4: FAILED -> RETRY -> PROCESSING -> PAID Lifecycle
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 4: FAILED -> RETRY -> PROCESSING -> PAID Lifecycle ---\n";

// Direct payment on FAILED must be rejected
$directPayOnFailedRejected = false;
try {
    $workflowService->recordReimbursementPayment($reimbData, [
        'amount' => '2500000.00',
        'proof_path' => 'receipts/test.png',
    ], $uFinance);
} catch (RuntimeException $e) {
    $directPayOnFailedRejected = true;
    assertTest("Direct payment on FAILED status is strictly rejected", true, $e->getMessage());
}
if (!$directPayOnFailedRejected) {
    assertTest("Direct payment on FAILED status is strictly rejected", false);
}

// Retry reimbursement
$okRetry = $workflowService->retryReimbursement($reimbData, $uFinance);
assertTest("retryReimbursement succeeds", $okRetry);

$reimbAfterRetry = $reimbRepo->findById($reimbData);
assertTest("Reimbursement status transitioned from FAILED to PROCESSING", $reimbAfterRetry['status'] === 'PROCESSING');

// -------------------------------------------------------------
// PRIORITAS 3: Backend Mandatory Payment Proof Validation
// -------------------------------------------------------------
echo "\n--- Testing Prioritas 3: Mandatory Backend Payment Proof Validation ---\n";

$noProofRejected = false;
try {
    $workflowService->recordReimbursementPayment($reimbData, [
        'amount' => '2500000.00',
    ], $uFinance, null);
} catch (RuntimeException $e) {
    $noProofRejected = true;
    assertTest("Payment rejected when proof file/path is missing", true, $e->getMessage());
}
if (!$noProofRejected) {
    assertTest("Payment rejected when proof file/path is missing", false);
}

// Payment WITH valid proof completes full lifecycle
$dummyProofPath = 'receipts/proof_test_' . uniqid() . '.png';
$paymentId = $workflowService->recordReimbursementPayment($reimbData, [
    'amount' => '2500000.00',
    'proof_path' => $dummyProofPath,
    'payment_method' => 'BANK_TRANSFER',
    'bank_name' => 'BCA',
    'recipient_name' => 'Kas Operasional',
], $uFinance, null);

assertTest("Payment recorded with proof succeeds", $paymentId > 0);

$reimbPaid = $reimbRepo->findById($reimbData);
assertTest("Reimbursement status transitioned to PAID", $reimbPaid['status'] === 'PAID');

$reportPaid = $reportRepo->findById($testReportId);
assertTest("Parent report status transitioned to PAID", $reportPaid['status'] === 'PAID');

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
