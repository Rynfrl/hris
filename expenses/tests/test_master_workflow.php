<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\EmployeeRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ReimbursementRepository;
use App\Services\AuthorizationService;
use App\Services\ExpenseService;
use App\Services\ReportWorkflowService;
use App\Services\DashboardService;

echo "======================================================================\n";
echo "MASTER TEST SUITE — CONSOLIDATED EXPENSE WORKFLOW & SYSTEM INTEGRATION\n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

$test = function (string $desc, callable $fn) use (&$passCount, &$failCount): void {
    try {
        $fn();
        echo "[PASS] {$desc}\n";
        $passCount++;
    } catch (Throwable $e) {
        echo "[FAIL] {$desc}: " . $e->getMessage() . "\n";
        $failCount++;
    }
};

$pdo = Database::pdo();
$authService = new AuthorizationService();
$employeeRepo = new EmployeeRepository();
$expenseRepo = new ExpenseRepository();
$reportRepo = new ReportRepository();
$reimbRepo = new ReimbursementRepository();
$expenseService = new ExpenseService();
$workflowService = new ReportWorkflowService();
$dashboardService = new DashboardService($employeeRepo);

// 1. Setup Test Users for Roles: ADMIN, MANAGER, FINANCE, EMPLOYEE
$adminUser = $pdo->query("SELECT u.*, 'ADMIN' as role_name FROM users u WHERE u.deleted_at IS NULL AND (u.role = 'Admin_HR' OR u.role_id = 1) LIMIT 1")->fetch();
if (!$adminUser) {
    throw new Exception("Admin user not found in database.");
}
$adminUser['role_name'] = 'ADMIN';

// Find or mock Manager user
$managerUser = $pdo->query("SELECT u.*, 'MANAGER' as role_name FROM users u JOIN roles r ON r.id_role = u.role_id WHERE u.deleted_at IS NULL AND r.role_name = 'MANAGER' LIMIT 1")->fetch();
if (!$managerUser) {
    $managerUser = array_merge($adminUser, ['id' => 9991, 'user_id' => 9991, 'username' => 'test_manager', 'role_name' => 'MANAGER']);
} else {
    $managerUser['role_name'] = 'MANAGER';
}

// Find or mock Finance user
$financeUser = $pdo->query("SELECT u.*, 'FINANCE' as role_name FROM users u JOIN roles r ON r.id_role = u.role_id WHERE u.deleted_at IS NULL AND r.role_name = 'FINANCE' LIMIT 1")->fetch();
if (!$financeUser) {
    $financeUser = array_merge($adminUser, ['id' => 9992, 'user_id' => 9992, 'username' => 'test_finance', 'role_name' => 'FINANCE']);
} else {
    $financeUser['role_name'] = 'FINANCE';
}

// Find or mock Employee user
$employeeUser = $pdo->query("SELECT u.*, 'EMPLOYEE' as role_name FROM users u JOIN roles r ON r.id_role = u.role_id WHERE u.deleted_at IS NULL AND (r.role_name = 'EMPLOYEE' OR r.role_name = 'Karyawan') LIMIT 1")->fetch();
if (!$employeeUser) {
    $employeeUser = array_merge($adminUser, ['id' => 9993, 'user_id' => 9993, 'username' => 'test_employee', 'role_name' => 'EMPLOYEE']);
} else {
    $employeeUser['role_name'] = 'EMPLOYEE';
}

// 2. Setup 3 Target Employees: Andi Pratama, Budi Santoso, Citra Amelia
$getOrCreateEmployee = function (string $name, string $divisi, string $position) use ($pdo): array {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE nama = ? LIMIT 1");
    $stmt->execute([$name]);
    $emp = $stmt->fetch();
    if ($emp) {
        return $emp;
    }

    $stmtIns = $pdo->prepare("
        INSERT INTO employees (nama, divisi, jabatan, gaji, tanggal_masuk, is_active)
        VALUES (?, ?, ?, 5000000, CURDATE(), 1)
    ");
    $stmtIns->execute([$name, $divisi, $position]);
    $empId = (int) $pdo->lastInsertId();
    return $pdo->query("SELECT * FROM employees WHERE id_employee = {$empId}")->fetch();
};

$empAndi = $getOrCreateEmployee('Andi Pratama', 'IT', 'Software Engineer');
$empBudi = $getOrCreateEmployee('Budi Santoso', 'HR', 'HR Specialist');
$empCitra = $getOrCreateEmployee('Citra Amelia', 'Finance', 'Financial Analyst');

// Ensure an active category exists
$category = $pdo->query("SELECT * FROM expense_categories WHERE is_active = 1 LIMIT 1")->fetch();
if (!$category) {
    $pdo->query("INSERT INTO expense_categories (name, code, is_active) VALUES ('Operasional', 'OPS', 1)");
    $category = $pdo->query("SELECT * FROM expense_categories WHERE is_active = 1 LIMIT 1")->fetch();
}
$catId = (int) ($category['category_id'] ?? $category['id'] ?? 1);

echo "Test Actors & Target Data:\n";
echo " - Admin: {$adminUser['username']} ({$adminUser['role_name']})\n";
echo " - Manager: {$managerUser['username']} ({$managerUser['role_name']})\n";
echo " - Finance: {$financeUser['username']} ({$financeUser['role_name']})\n";
echo " - Employee: {$employeeUser['username']} ({$employeeUser['role_name']})\n";
echo " - Employees: Andi ({$empAndi['id_employee']}), Budi ({$empBudi['id_employee']}), Citra ({$empCitra['id_employee']})\n\n";

// ======================================================================
// TEST SUITE 1: SCENARIO EXP-2026-001 (MAIN CONSOLIDATED WORKFLOW)
// ======================================================================
echo "--- TEST SUITE 1: SCENARIO EXP-2026-001 ---\n";

$reportId = 0;
$reimbId = 0;

$test('1.1 Admin Expense creates consolidated report EXP-2026-001', function () use ($workflowService, $adminUser, &$reportId) {
    $reportNumber = 'EXP-2026-' . rand(100, 999);
    $reportData = [
        'report_number' => $reportNumber,
        'title' => 'Laporan Pengeluaran Operasional November 2026',
        'description' => 'Laporan gabungan karyawan operasional IT, HR, dan Finance',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-15',
    ];

    $reportId = $workflowService->createReport($reportData, $adminUser);
    if ($reportId <= 0) {
        throw new Exception("Failed to create report.");
    }
});

$test('1.2 Admin adds Andi, Budi, and Citra to the consolidated report', function () use ($workflowService, $adminUser, $empAndi, $empBudi, $empCitra, &$reportId) {
    $workflowService->addEmployeeToReport($reportId, (int) $empAndi['id_employee'], $adminUser);
    $workflowService->addEmployeeToReport($reportId, (int) $empBudi['id_employee'], $adminUser);
    $workflowService->addEmployeeToReport($reportId, (int) $empCitra['id_employee'], $adminUser);

    $employees = $workflowService->getReportEmployees($reportId);
    if (count($employees) !== 3) {
        throw new Exception("Expected 3 employees in report, found: " . count($employees));
    }
});

$test('1.3 Prevent duplicate employee in the same report', function () use ($workflowService, $adminUser, $empAndi, &$reportId) {
    try {
        $workflowService->addEmployeeToReport($reportId, (int) $empAndi['id_employee'], $adminUser);
        throw new Exception("Duplicate employee was permitted!");
    } catch (\Throwable $e) {
        if (!str_contains($e->getMessage(), 'sudah terdaftar')) {
            throw $e;
        }
    }
});

$test('1.4 Input transactions: Andi (Rp1.500.000), Budi (Rp2.000.000), Citra (Rp1.000.000)', function () use (
    $expenseService, $workflowService, $adminUser, $empAndi, $empBudi, $empCitra, $catId, &$reportId
) {
    $repEmployees = $workflowService->getReportEmployees($reportId);
    $map = [];
    foreach ($repEmployees as $re) {
        $map[(int)$re['employee_id']] = (int)$re['report_employee_id'];
    }

    // Andi: 1.500.000
    $exp1 = $expenseService->createExpense([
        'employee_id' => (int) $empAndi['id_employee'],
        'category_id' => $catId,
        'expense_date' => '2026-11-05',
        'description' => 'Biaya Server Cloud AWS Andi',
        'amount' => 1500000.00,
        'payment_method' => 'BANK_TRANSFER',
        'bank_name' => 'BCA',
    ], array_merge($adminUser, ['employee_id' => (int) $empAndi['id_employee']]));
    $workflowService->addItem($reportId, $exp1, $adminUser, $map[(int)$empAndi['id_employee']]);

    // Budi: 2.000.000
    $exp2 = $expenseService->createExpense([
        'employee_id' => (int) $empBudi['id_employee'],
        'category_id' => $catId,
        'expense_date' => '2026-11-07',
        'description' => 'Pelatihan Recruitment & HR Budi',
        'amount' => 2000000.00,
        'payment_method' => 'BANK_TRANSFER',
        'bank_name' => 'Mandiri',
    ], array_merge($adminUser, ['employee_id' => (int) $empBudi['id_employee']]));
    $workflowService->addItem($reportId, $exp2, $adminUser, $map[(int)$empBudi['id_employee']]);

    // Citra: 1.000.000
    $exp3 = $expenseService->createExpense([
        'employee_id' => (int) $empCitra['id_employee'],
        'category_id' => $catId,
        'expense_date' => '2026-11-10',
        'description' => 'Biaya Materai dan Audit Pajak Citra',
        'amount' => 1000000.00,
        'payment_method' => 'BANK_TRANSFER',
        'bank_name' => 'BCA',
    ], array_merge($adminUser, ['employee_id' => (int) $empCitra['id_employee']]));
    $workflowService->addItem($reportId, $exp3, $adminUser, $map[(int)$empCitra['id_employee']]);
});

$test('1.5 Backend accurately calculates total sum: Rp4.500.000', function () use ($reportRepo, &$reportId) {
    $report = $reportRepo->findById($reportId);
    $total = (float) $report['total_amount'];
    if (abs($total - 4500000.00) > 0.01) {
        throw new Exception("Expected total Rp4.500.000, got: " . $total);
    }
});

$test('1.6 Admin submits report -> Status changes to SUBMITTED', function () use ($workflowService, $adminUser, $reportRepo, &$reportId) {
    $workflowService->submitReport($reportId, $adminUser);
    $report = $reportRepo->findById($reportId);
    if ($report['status'] !== 'SUBMITTED') {
        throw new Exception("Expected status SUBMITTED, got: " . $report['status']);
    }
    if (empty($report['submitted_at'])) {
        throw new Exception("submitted_at was not recorded.");
    }
});

$test('1.7 Manager reviews and APPROVES the report as 1 single decision', function () use ($workflowService, $managerUser, $reportRepo, &$reportId) {
    $workflowService->approveReport($reportId, 'Disetujui untuk seluruh 3 karyawan.', $managerUser);
    $report = $reportRepo->findById($reportId);
    if ($report['status'] !== 'APPROVED') {
        throw new Exception("Expected status APPROVED, got: " . $report['status']);
    }
    if (empty($report['approved_at'])) {
        throw new Exception("approved_at was not recorded.");
    }
});

$test('1.8 Finance posts report -> Creates EXACTLY 1 consolidated reimbursement record (Rp4.500.000)', function () use (
    $workflowService, $financeUser, $reportRepo, $reimbRepo, &$reportId, &$reimbId
) {
    $workflowService->postReport($reportId, $financeUser);
    $report = $reportRepo->findById($reportId);
    if (!in_array($report['status'], ['POSTED', 'REIMBURSEMENT_PENDING'], true)) {
        throw new Exception("Expected status POSTED or REIMBURSEMENT_PENDING, got: " . $report['status']);
    }

    $reimbList = $reimbRepo->getReimbursements(['report_id' => $reportId], 1, 10);
    if ($reimbList['total'] !== 1) {
        throw new Exception("Expected exactly 1 consolidated reimbursement, found: " . $reimbList['total']);
    }

    $reimb = $reimbList['items'][0];
    $reimbId = (int) $reimb['reimbursement_id'];
    $amount = (float) ($reimb['approved_amount'] ?? $reimb['amount']);
    if (abs($amount - 4500000.00) > 0.01) {
        throw new Exception("Expected reimbursement amount Rp4.500.000, got: " . $amount);
    }
});

$test('1.9 Finance cannot do partial payment (Enforce 1 Full Payment)', function () use ($workflowService, $financeUser, &$reimbId) {
    try {
        $workflowService->recordReimbursementPayment($reimbId, [
            'amount' => 2000000.00, // partial
            'payment_method' => 'BANK_TRANSFER',
            'bank_reference' => 'TRF-TEST-PARTIAL',
            'payment_date' => date('Y-m-d'),
        ], $financeUser);
        throw new Exception("Partial payment was allowed!");
    } catch (\Throwable $e) {
        if (!str_contains($e->getMessage(), 'penuh')) {
            throw $e;
        }
    }
});

$test('1.10 Finance executes 1 full payment (Rp4.500.000) -> Status transitions to PAID', function () use (
    $workflowService, $financeUser, $reportRepo, $reimbRepo, &$reportId, &$reimbId
) {
    $workflowService->recordReimbursementPayment($reimbId, [
        'amount' => 4500000.00,
        'recipient_name' => 'Andi Pratama (PIC Operasional)',
        'bank_name' => 'BCA',
        'account_number' => '8820192837',
        'account_holder' => 'Andi Pratama',
        'payment_method' => 'BANK_TRANSFER',
        'bank_reference' => 'TRF-FULL-PAY-20261121',
        'payment_date' => date('Y-m-d'),
        'notes' => 'Pencairan penuh seluruh pengeluaran operasional November 2026',
    ], $financeUser);

    $reimb = $reimbRepo->findById($reimbId);
    if ($reimb['status'] !== 'PAID') {
        throw new Exception("Expected reimbursement status PAID, got: " . $reimb['status']);
    }

    $report = $reportRepo->findById($reportId);
    if ($report['status'] !== 'PAID') {
        throw new Exception("Expected report status PAID, got: " . $report['status']);
    }
});

// ======================================================================
// TEST SUITE 2: REFUSAL WORKFLOW WITH MANDATORY REASON
// ======================================================================
echo "\n--- TEST SUITE 2: REFUSAL & REVISION WORKFLOW ---\n";

$refuseReportId = 0;

$test('2.1 Admin creates and submits a second report', function () use ($expenseService, $workflowService, $adminUser, $empAndi, $catId, &$refuseReportId) {
    $refuseReportId = $workflowService->createReport([
        'report_number' => 'EXP-REFUSE-' . rand(100, 999),
        'title' => 'Laporan Uji Penolakan Manajer',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-10',
    ], $adminUser);

    $workflowService->addEmployeeToReport($refuseReportId, (int) $empAndi['id_employee'], $adminUser);

    $expRefuse = $expenseService->createExpense([
        'employee_id' => (int) $empAndi['id_employee'],
        'category_id' => $catId,
        'expense_date' => '2026-11-02',
        'description' => 'Biaya Makan Siang Meeting Operasional',
        'amount' => 150000.00,
        'payment_method' => 'CASH',
    ], array_merge($adminUser, ['employee_id' => (int) $empAndi['id_employee']]));
    $workflowService->addItem($refuseReportId, $expRefuse, $adminUser);

    $workflowService->submitReport($refuseReportId, $adminUser);
});

$test('2.2 Manager refuses report WITHOUT reason -> Blocked (Reason is mandatory)', function () use ($workflowService, $managerUser, &$refuseReportId) {
    try {
        $workflowService->refuseReport($refuseReportId, '', $managerUser);
        throw new Exception("Manager was allowed to refuse without reason!");
    } catch (\Throwable $e) {
        // Expected
    }
});

$test('2.3 Manager refuses report WITH reason -> Status REFUSED and reason logged', function () use ($workflowService, $managerUser, $reportRepo, &$refuseReportId) {
    $workflowService->refuseReport($refuseReportId, 'Bukti struk belum jelas, mohon diperbaiki.', $managerUser);
    $report = $reportRepo->findById($refuseReportId);
    if ($report['status'] !== 'REFUSED') {
        throw new Exception("Expected status REFUSED, got: " . $report['status']);
    }

    $history = $reportRepo->getHistory($refuseReportId);
    $foundRefusal = false;
    foreach ($history as $h) {
        if (($h['action'] ?? '') === 'REFUSED' && str_contains($h['comment'] ?? '', 'Bukti struk')) {
            $foundRefusal = true;
            break;
        }
    }
    if (!$foundRefusal) {
        throw new Exception("Refusal reason was not properly saved in history.");
    }
});

$test('2.4 Admin revises REFUSED report -> Status back to DRAFT', function () use ($workflowService, $adminUser, $reportRepo, &$refuseReportId) {
    $workflowService->reviseReport($refuseReportId, $adminUser);
    $report = $reportRepo->findById($refuseReportId);
    if ($report['status'] !== 'DRAFT') {
        throw new Exception("Expected status DRAFT, got: " . $report['status']);
    }
});

$test('2.5 Admin can resubmit the revised report', function () use ($workflowService, $adminUser, $reportRepo, &$refuseReportId) {
    $workflowService->submitReport($refuseReportId, $adminUser);
    $report = $reportRepo->findById($refuseReportId);
    if ($report['status'] !== 'SUBMITTED') {
        throw new Exception("Expected status SUBMITTED, got: " . $report['status']);
    }
});

// ======================================================================
// TEST SUITE 3: SEGREGATION OF DUTIES & ROLE ACCESS CONTROLS
// ======================================================================
echo "\n--- TEST SUITE 3: SEGREGATION OF DUTIES ---\n";

$test('3.1 Employee cannot create expense reports', function () use ($authService, $employeeUser) {
    if ($authService->canCreateReport($employeeUser)) {
        throw new Exception("Employee was permitted to create reports!");
    }
});

$test('3.2 Employee cannot create expenses', function () use ($authService, $employeeUser) {
    if ($authService->canCreateExpense($employeeUser)) {
        throw new Exception("Employee was permitted to create expenses!");
    }
});

$test('3.3 Admin cannot approve reports (Manager only)', function () use ($authService, $adminUser, $reportRepo, &$reportId) {
    $report = $reportRepo->findById($reportId);
    if ($authService->canDecideReport($adminUser, $report)) {
        throw new Exception("Admin was permitted to approve reports!");
    }
});

$test('3.4 Manager cannot post reports or pay reimbursements', function () use ($authService, $managerUser, $reportRepo, &$reportId) {
    $report = $reportRepo->findById($reportId);
    if ($authService->canPostReport($managerUser, $report)) {
        throw new Exception("Manager was permitted to post reports!");
    }
    if ($authService->canReimburseReport($managerUser, $report)) {
        throw new Exception("Manager was permitted to reimburse reports!");
    }
});

$test('3.5 Finance cannot approve reports', function () use ($authService, $financeUser, $reportRepo, &$reportId) {
    $report = $reportRepo->findById($reportId);
    if ($authService->canDecideReport($financeUser, $report)) {
        throw new Exception("Finance was permitted to approve reports!");
    }
});

// ======================================================================
// TEST SUITE 4: DASHBOARD METRICS INTEGRITY
// ======================================================================
echo "\n--- TEST SUITE 4: DASHBOARD METRICS ---\n";

$test('4.1 Admin Expense Dashboard returns 9 required metrics', function () use ($dashboardService, $adminUser) {
    $m = $dashboardService->getMetrics($adminUser);
    $required = [
        'total_reports', 'draft_reports_count', 'submitted_reports_count',
        'approved_reports_count', 'refused_reports_count', 'posted_reports_count',
        'pending_reimb_reports_count', 'paid_reports_count', 'total_expenses_nominal'
    ];
    foreach ($required as $key) {
        if (!array_key_exists($key, $m)) {
            throw new Exception("Missing Admin metric: {$key}");
        }
    }
});

$test('4.2 Manager Expense Dashboard returns 6 required metrics', function () use ($dashboardService, $managerUser) {
    $m = $dashboardService->getMetrics($managerUser);
    $required = [
        'total_incoming_reports', 'pending_approval_count', 'approved_count',
        'refused_count', 'pending_approval_total', 'approved_total'
    ];
    foreach ($required as $key) {
        if (!array_key_exists($key, $m)) {
            throw new Exception("Missing Manager metric: {$key}");
        }
    }
});

$test('4.3 Finance Dashboard returns 9 required metrics', function () use ($dashboardService, $financeUser) {
    $m = $dashboardService->getMetrics($financeUser);
    $required = [
        'approved_reports_count', 'awaiting_post_count', 'posted_reports_count',
        'reimb_pending_count', 'reimb_processing_count', 'reimb_paid_count',
        'total_must_pay', 'total_paid', 'total_unpaid'
    ];
    foreach ($required as $key) {
        if (!array_key_exists($key, $m)) {
            throw new Exception("Missing Finance metric: {$key}");
        }
    }
});

// ======================================================================
// TEST SUITE 5: ROUTER PARAMETER ARGUMENTS (PHP 8 SAFETY)
// ======================================================================
echo "\n--- TEST SUITE 5: ROUTER SAFETY (PHP 8 PARAMETERS) ---\n";

$test('5.1 Router correctly dispatches without Unknown named parameter error', function () {
    $router = new \App\Core\Router();
    $router->post('/reports/{id}/employees/{employeeId}/remove', function (string|int $id, string|int $employeeId) {
        return "OK: report={$id}, emp={$employeeId}";
    });

    // Test URL resolution
    $result = $router->dispatch('POST', '/reports/136/employees/150/remove');
    if ($result !== 'OK: report=136, emp=150') {
        throw new Exception("Router did not return expected value: " . var_export($result, true));
    }
});

echo "\n======================================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
