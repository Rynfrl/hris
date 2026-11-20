<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Csrf;
use App\Repositories\UserRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\ReportRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\ReimbursementRepository;
use App\Services\AuthorizationService;
use App\Services\ExpenseService;
use App\Services\ReportWorkflowService;
use App\Services\DashboardService;
use App\Services\CsvExportService;

Env::load(dirname(__DIR__) . '/.env');

class AcceptanceTestSuite
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    private function assert(string $code, string $description, bool $condition, ?string $failDetail = null): void
    {
        if ($condition) {
            $this->passed++;
            $this->results[] = "[PASS] {$code}: {$description}";
            echo "[PASS] {$code}: {$description}\n";
        } else {
            $this->failed++;
            $detail = $failDetail ? " - {$failDetail}" : '';
            $this->results[] = "[FAIL] {$code}: {$description}{$detail}";
            echo "[FAIL] {$code}: {$description}{$detail}\n";
        }
    }

    public function runAll(): void
    {
        echo "========================================================\n";
        echo "EXPENSES COMPLETE ACCEPTANCE & REGRESSION TEST SUITE\n";
        echo "========================================================\n\n";

        $this->testDatabaseIntegration();
        $this->testAuthenticationAndSecurity();
        $this->testExpenseManagement();
        $this->testReportWorkflow();
        $this->testMultiEmployeeExpenseWorkflow();
        $this->testFinanceAndReimbursement();
        $this->testRbacAndAuthorization();
        $this->testCsvAndDashboardIntegrity();

        $this->tearDown();

        echo "\n========================================================\n";
        echo "TEST RUN SUMMARY: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testDatabaseIntegration(): void
    {
        echo "--- 1. Database & Schema Contract Tests ---\n";

        // AC-DB-01: Connected to hris or erp_database
        $currentDb = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        $this->assert('AC-DB-01', 'Aplikasi terhubung ke database hris atau erp_database', in_array($currentDb, ['hris', 'erp_database'], true));

        // Check columns exist in baseline
        $chkCols = $this->pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '{$currentDb}' AND TABLE_NAME = 'expenses'
              AND COLUMN_NAME IN ('expense_id', 'expense_number', 'employee_id', 'amount', 'status', 'receipt_stored_name', 'created_by')
        ")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert('AC-DB-01b', 'Kolom resmi expenses ada pada baseline', count($chkCols) === 7);

        // AC-DB-02: No duplicate tables
        $tables = $this->pdo->query("SHOW TABLES LIKE 'gt_%'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert('AC-DB-02', 'Tidak ada tabel duplicate gt_expenses atau gt_users', empty($tables));

        // AC-DB-03: Seed GT Expense roles and permissions
        $roleEmp = $this->pdo->query("SELECT COUNT(*) FROM roles WHERE role_name = 'EMPLOYEE'")->fetchColumn();
        $permPost = $this->pdo->query("SELECT COUNT(*) FROM permissions WHERE permission_key = 'expense.post'")->fetchColumn();
        $permReimb = $this->pdo->query("SELECT COUNT(*) FROM permissions WHERE permission_key = 'expense.reimburse'")->fetchColumn();
        $this->assert('AC-DB-03', 'Role EMPLOYEE dan permission GT Expense tersedia di database', (int) $roleEmp > 0 && (int) $permPost > 0 && (int) $permReimb > 0);

        // Migration 001 verification
        $migChk = $this->pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE migration_name = '001_gt_expense_integrity.sql'")->fetchColumn();
        $commentsTable = $this->pdo->query("SHOW TABLES LIKE 'expense_comments'")->fetchColumn();
        $this->assert('AC-MIG-01', 'Migration 001_gt_expense_integrity.sql tercatat dan expense_comments tersedia', (int) $migChk > 0 && !empty($commentsTable));

        // Migration 002 verification
        $mig002Chk = $this->pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE migration_name = '002_add_bank_name_to_expenses.sql'")->fetchColumn();
        $bankCol = $this->pdo->query("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = '{$currentDb}' AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'bank_name'
        ")->fetchColumn();
        $this->assert('AC-MIG-02', 'Migration 002_add_bank_name_to_expenses.sql tercatat dan kolom bank_name tersedia', (int) $mig002Chk > 0 && (int) $bankCol > 0);

        // Migration 003 verification (Multi-Employee Expense Management)
        $mig003Chk = $this->pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE migration_name = '003_multi_employee_expense_management.sql'")->fetchColumn();
        $repEmpTable = $this->pdo->query("SHOW TABLES LIKE 'expense_report_employees'")->fetchColumn();
        $postTable = $this->pdo->query("SHOW TABLES LIKE 'expense_postings'")->fetchColumn();
        $reimbTable = $this->pdo->query("SHOW TABLES LIKE 'expense_reimbursements'")->fetchColumn();
        $payTable = $this->pdo->query("SHOW TABLES LIKE 'expense_reimbursement_payments'")->fetchColumn();
        $this->assert('AC-MIG-03', 'Migration 003_multi_employee_expense_management.sql tercatat dan tabel-tabel multi-karyawan tersedia', (int) $mig003Chk > 0 && !empty($repEmpTable) && !empty($postTable) && !empty($reimbTable) && !empty($payTable));
    }

    private function testAuthenticationAndSecurity(): void
    {
        echo "\n--- 2. Authentication & Security Tests ---\n";

        $userRepo = new UserRepository();

        // AC-AUTH-01: Valid user credentials
        $user = $userRepo->findByUsernameOrEmail('budi@example.test');
        $this->assert('AC-AUTH-01', 'User budi@example.test ditemukan dan aktif', $user !== null && (int) $user['is_active'] === 1);
        $validPass = password_verify('Password123!', $user['password_hash']);
        $this->assert('AC-AUTH-01b', 'Password hash terverifikasi dengan password_verify()', $validPass);

        // AC-AUTH-02: Invalid password check
        $invalidPass = password_verify('WrongPassword', $user['password_hash']);
        $this->assert('AC-AUTH-02', 'Password salah ditolak oleh password_verify()', !$invalidPass);

        // AC-AUTH-02b: Nonexistent user
        $fakeUser = $userRepo->findByUsernameOrEmail('nonexistent@example.test');
        $this->assert('AC-AUTH-02b', 'User tidak dikenal mengembalikan null', $fakeUser === null);

        // AC-AUTH-03: CSRF token generation and validation
        Session::start();
        $token = Csrf::token();
        $this->assert('AC-AUTH-03', 'CSRF token acak terbentuk', !empty($token) && strlen($token) === 64);
        $validCsrf = Csrf::validate($token);
        $invalidCsrf = Csrf::validate('invalid_token');
        $this->assert('AC-AUTH-03b', 'CSRF validasi hash_equals bekerja', $validCsrf && !$invalidCsrf);

        // AC-AUTH-04: XSS output escaping test
        $rawMalicious = '<script>alert("XSS")</script>&"\'';
        $escaped = \App\Core\View::e($rawMalicious);
        $this->assert('AC-SEC-XSS', 'Output escaping htmlspecialchars mencegah XSS', !str_contains($escaped, '<script>') && str_contains($escaped, '&lt;script&gt;'));
    }

    private function testExpenseManagement(): void
    {
        echo "\n--- 3. Expense Management Tests ---\n";

        $expenseRepo = new ExpenseRepository();
        $expenseService = new ExpenseService();
        $userRepo = new UserRepository();

        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');

        // AC-EXP-01: Create expense draft with bank_name
        $newExpenseId = $expenseService->createExpense([
            'category_id' => 1,
            'expense_date' => date('Y-m-d'),
            'description' => 'Test Unit Pengeluaran Budi',
            'amount' => 50000.00,
            'payment_method' => 'BANK_TRANSFER',
            'bank_name' => 'BCA',
            'merchant' => 'Toko Alat Tulis',
        ], $budiUser);

        $saved = $expenseRepo->findById($newExpenseId);
        $this->assert('AC-EXP-01', 'Employee aktif berhasil membuat expense draft', $saved !== null && $saved['status'] === 'DRAFT' && (float) $saved['amount'] === 50000.00);
        $this->assert('AC-EXP-01c', 'Kolom bank_name tersimpan dengan benar pada expense draft', $saved !== null && $saved['bank_name'] === 'BCA');

        // Update bank_name
        $expenseService->updateExpense($newExpenseId, [
            'category_id' => 1,
            'expense_date' => date('Y-m-d'),
            'description' => 'Test Unit Pengeluaran Budi Diperbarui',
            'amount' => 60000.00,
            'payment_method' => 'BANK_TRANSFER',
            'bank_name' => 'MANDIRI',
            'merchant' => 'Toko Alat Tulis',
        ], $budiUser);
        $updatedExpense = $expenseRepo->findById($newExpenseId);
        $this->assert('AC-EXP-01d', 'Kolom bank_name berhasil diperbarui pada expense draft', $updatedExpense !== null && $updatedExpense['bank_name'] === 'MANDIRI');

        // AC-EXP-02: Zero / negative amount rejection
        $threwNegative = false;
        try {
            $expenseService->createExpense([
                'category_id' => 1,
                'expense_date' => date('Y-m-d'),
                'description' => 'Nominal negatif',
                'amount' => -1000.00,
            ], $budiUser);
        } catch (InvalidArgumentException $e) {
            $threwNegative = true;
        }
        $this->assert('AC-EXP-02', 'Nominal negatif ditolak server', $threwNegative);

        // AC-EXP-02b: Future date rejection
        $threwFutureDate = false;
        try {
            $expenseService->createExpense([
                'category_id' => 1,
                'expense_date' => date('Y-m-d', strtotime('+10 days')),
                'description' => 'Tanggal masa depan',
                'amount' => 25000.00,
            ], $budiUser);
        } catch (InvalidArgumentException $e) {
            $threwFutureDate = true;
        }
        $this->assert('AC-EXP-02b', 'Tanggal masa depan ditolak server', $threwFutureDate);

        // AC-EXP-03: ID manipulation check (Sari cannot edit Budi's expense)
        $sariUser = $userRepo->findByUsernameOrEmail('sari@example.test');
        $authService = new AuthorizationService();
        $canSariEdit = $authService->canEditExpense($sariUser, $saved);
        $this->assert('AC-EXP-03', 'Karyawan lain tidak berhak mengedit expense orang lain', !$canSariEdit);

        // AC-EXP-04: Non-draft expense cannot be edited
        $paidExpense = $expenseRepo->findByExpenseNumber('EXP-DEMO-007') ?: $saved;
        $paidExpense['status'] = 'PAID';
        $canEditPaid = $authService->canEditExpense($budiUser, $paidExpense);
        $this->assert('AC-EXP-04', 'Expense yang bukan DRAFT tidak dapat diedit', !$canEditPaid);

        // AC-EXP-05: Receipt metadata and single attachment
        $dummyReceipt = [
            'receipt_original_name' => 'struk.jpg',
            'receipt_stored_name' => 'abc123_test.jpg',
            'receipt_path' => 'receipts/abc123_test.jpg',
            'receipt_mime' => 'image/jpeg',
            'receipt_size' => 102400,
        ];
        $expenseRepo->updateReceipt($newExpenseId, $dummyReceipt);
        $savedWithReceipt = $expenseRepo->findById($newExpenseId);
        $this->assert('AC-EXP-07', 'Satu receipt tersimpan sebagai metadata di kolom receipt_*', $savedWithReceipt['receipt_stored_name'] === 'abc123_test.jpg');

        // Cleanup test expense
        $expenseRepo->delete($newExpenseId);
    }

    private function testReportWorkflow(): void
    {
        echo "\n--- 4. Report Workflow & Approval Tests ---\n";

        $workflowService = new ReportWorkflowService();
        $expenseRepo = new ExpenseRepository();
        $reportRepo = new ReportRepository();
        $userRepo = new UserRepository();
        $authService = new AuthorizationService();

        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');
        $rinaUser = $userRepo->findByUsernameOrEmail('manager@example.test');

        // AC-REP-01: Create report draft
        $reportId = $workflowService->createReport([
            'title' => 'Laporan Uji Otomatis Budi',
            'start_date' => date('Y-m-d', strtotime('-3 days')),
            'end_date' => date('Y-m-d'),
            'description' => 'Uji alur submit - approve - refuse',
            'employee_id' => (int) $budiUser['employee_id'],
        ], $budiUser);

        $report = $reportRepo->findById($reportId);
        $this->assert('AC-REP-01', 'Report draft dibuat dengan employee_id pengguna aktif', (int) $report['employee_id'] === (int) $budiUser['employee_id'] && $report['status'] === 'DRAFT');

        // AC-REP-03: Empty report cannot be submitted
        $threwEmptySubmit = false;
        try {
            $workflowService->submitReport($reportId, $budiUser);
        } catch (RuntimeException $e) {
            $threwEmptySubmit = true;
        }
        $this->assert('AC-REP-03', 'Report kosong tanpa item ditolak saat submit', $threwEmptySubmit);

        // Create an expense and attach
        $expId = (new ExpenseService())->createExpense([
            'category_id' => 1,
            'expense_date' => date('Y-m-d'),
            'description' => 'Item Laporan Uji',
            'amount' => 120000.00,
        ], $budiUser);

        $workflowService->addItem($reportId, $expId, $budiUser);
        $reportAfterItem = $reportRepo->findById($reportId);
        $this->assert('AC-REP-01b', 'Total laporan otomatis tersinkronisasi saat item ditambahkan', (float) $reportAfterItem['total_amount'] === 120000.00);

        // AC-REP-02: Expense cannot be added to another report (Unique constraint)
        $reportId2 = $workflowService->createReport(['title' => 'Laporan Kedua', 'employee_id' => (int) $budiUser['employee_id']], $budiUser);
        $threwDuplicateItem = false;
        try {
            $workflowService->addItem($reportId2, $expId, $budiUser);
        } catch (RuntimeException $e) {
            $threwDuplicateItem = true;
        }
        $this->assert('AC-REP-02', 'Expense tidak dapat ditambahkan ke dua report berbeda', $threwDuplicateItem);

        // AC-REP-04: Submit report
        $workflowService->submitReport($reportId, $budiUser);
        $submittedReport = $reportRepo->findById($reportId);
        $submittedExp = $expenseRepo->findById($expId);
        $this->assert('AC-REP-04', 'Submit mengubah status report dan expense ke SUBMITTED secara atomik', $submittedReport['status'] === 'SUBMITTED' && $submittedExp['status'] === 'SUBMITTED');

        // AC-REP-05: Non-direct manager cannot approve
        // Sari is not Budi's manager
        $sariUser = $userRepo->findByUsernameOrEmail('sari@example.test');
        $canSariDecide = $authService->canDecideReport($sariUser, $submittedReport);
        $this->assert('AC-REP-05', 'Bukan direct manager tidak dapat memutuskan laporan', !$canSariDecide);

        // Direct manager Rina can decide
        $canRinaDecide = $authService->canDecideReport($rinaUser, $submittedReport);
        $this->assert('AC-REP-05b', 'Direct manager Rina berwenang memutuskan laporan bawahan', $canRinaDecide);

        // AC-REP-07: Refuse report requires reason
        $threwEmptyRefusal = false;
        try {
            $workflowService->refuseReport($reportId, '   ', $rinaUser);
        } catch (InvalidArgumentException $e) {
            $threwEmptyRefusal = true;
        }
        $this->assert('AC-REP-07', 'Refuse tanpa alasan ditolak server', $threwEmptyRefusal);

        // Refuse report with valid reason
        $workflowService->refuseReport($reportId, 'Kuitansi tidak terbaca jelas. Mohon unggah ulang.', $rinaUser);
        $refusedReport = $reportRepo->findById($reportId);
        $refusedExp = $expenseRepo->findById($expId);
        $this->assert('AC-REP-07b', 'Refusal mengubah status ke REFUSED dan menyimpan rejection_reason', $refusedReport['status'] === 'REFUSED' && $refusedExp['status'] === 'REFUSED' && !empty($refusedReport['rejection_reason']));

        // AC-REP-08: Revise report back to DRAFT
        $workflowService->reviseReport($reportId, $budiUser);
        $revisedReport = $reportRepo->findById($reportId);
        $revisedExp = $expenseRepo->findById($expId);
        $history = $reportRepo->getApprovalHistory($reportId);
        $this->assert('AC-REP-08', 'Revisi mengembalikan status report dan expense ke DRAFT dengan riwayat tetap ada', $revisedReport['status'] === 'DRAFT' && $revisedExp['status'] === 'DRAFT' && count($history) >= 2);

        // Re-submit and approve
        $workflowService->submitReport($reportId, $budiUser);
        $workflowService->approveReport($reportId, 'Sekarang sudah lengkap. Disetujui.', $rinaUser);
        $approvedReport = $reportRepo->findById($reportId);
        $approvedExp = $expenseRepo->findById($expId);
        $this->assert('AC-REP-06', 'Approval mengubah status report dan expense ke APPROVED', $approvedReport['status'] === 'APPROVED' && $approvedExp['status'] === 'APPROVED');
    }

    private function testMultiEmployeeExpenseWorkflow(): void
    {
        echo "\n--- 4b. Multi-Employee Expense Management Workflow Tests ---\n";

        $workflowService = new ReportWorkflowService();
        $expenseRepo = new ExpenseRepository();
        $reportRepo = new ReportRepository();
        $reimbRepo = new ReimbursementRepository();
        $userRepo = new UserRepository();
        $expenseService = new ExpenseService();

        $adminUser = $userRepo->findByUsernameOrEmail('admin@example.test');
        $managerUser = $userRepo->findByUsernameOrEmail('manager@example.test');
        $financeUser = $userRepo->findByUsernameOrEmail('finance@example.test');
        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');
        $sariUser = $userRepo->findByUsernameOrEmail('sari@example.test');

        // AC-MULTI-01: Admin creates consolidated report with multiple employees
        $multiReportId = $workflowService->createReport([
            'title' => 'Laporan Pengeluaran Gabungan Proyek Q4',
            'start_date' => date('Y-m-d', strtotime('-5 days')),
            'end_date' => date('Y-m-d'),
            'description' => '1 Report -> Banyak Karyawan -> Banyak Detail Pengeluaran',
            'employee_ids' => [(int) $budiUser['employee_id'], (int) $sariUser['employee_id']],
        ], $adminUser);

        $reportEmployees = $reportRepo->getReportEmployees($multiReportId);
        $this->assert('AC-MULTI-01', 'Admin Expense berhasil membuat laporan gabungan berisi banyak karyawan', count($reportEmployees) === 2);

        // AC-MULTI-02: Snapshot identity integrity
        $budiEmp = array_values(array_filter($reportEmployees, fn($r) => (int)$r['employee_id'] === (int)$budiUser['employee_id']))[0] ?? null;
        $sariEmp = array_values(array_filter($reportEmployees, fn($r) => (int)$r['employee_id'] === (int)$sariUser['employee_id']))[0] ?? null;
        $this->assert('AC-MULTI-02', 'Snapshot identitas karyawan tersimpan saat pengajuan dibuat', !empty($budiEmp['employee_name_snapshot']) && !empty($budiEmp['employee_number_snapshot']) && !empty($sariEmp['employee_name_snapshot']));

        // AC-MULTI-03: Duplicate employee constraint
        $threwDupEmp = false;
        try {
            $reportRepo->addReportEmployee($multiReportId, (int) $budiUser['employee_id']);
            $reCheck = $reportRepo->getReportEmployees($multiReportId);
            if (count($reCheck) === 2) {
                $threwDupEmp = true;
            }
        } catch (\Throwable $e) {
            $threwDupEmp = true;
        }
        $this->assert('AC-MULTI-03', 'Satu karyawan tidak boleh ditambahkan dua kali pada laporan yang sama', $threwDupEmp);

        // AC-MULTI-04: Multi-transaction itemization per employee
        $budiRepEmpId = (int) $budiEmp['report_employee_id'];
        $sariRepEmpId = (int) $sariEmp['report_employee_id'];

        $budiExp1 = $expenseService->createExpense([
            'category_id' => 1,
            'expense_date' => date('Y-m-d'),
            'description' => 'Tiket Kereta Dinas Budi',
            'amount' => 200000.00,
            'payment_method' => 'BANK_TRANSFER',
            'bank_name' => 'BCA',
        ], $budiUser);

        $budiExp2 = $expenseService->createExpense([
            'category_id' => 2,
            'expense_date' => date('Y-m-d'),
            'description' => 'Makan Siang Tim Budi',
            'amount' => 150000.00,
            'payment_method' => 'CASH',
        ], $budiUser);

        $sariExp1 = $expenseService->createExpense([
            'category_id' => 3,
            'expense_date' => date('Y-m-d'),
            'description' => 'Taksi Kunjungan Klien Sari',
            'amount' => 100000.00,
            'payment_method' => 'BANK_TRANSFER',
            'bank_name' => 'Mandiri',
        ], $sariUser);

        $workflowService->addItem($multiReportId, $budiExp1, $adminUser, $budiRepEmpId);
        $workflowService->addItem($multiReportId, $budiExp2, $adminUser, $budiRepEmpId);
        $workflowService->addItem($multiReportId, $sariExp1, $adminUser, $sariRepEmpId);

        $updatedMultiReport = $reportRepo->findById($multiReportId);
        $this->assert('AC-MULTI-04', 'Total laporan otomatis tersinkronisasi dari gabungan seluruh transaksi karyawan (Rp 450.000)', (float) $updatedMultiReport['total_amount'] === 450000.00);

        // AC-MULTI-05: Submit by Admin and Approve by Manager
        $workflowService->submitReport($multiReportId, $adminUser);
        $submittedReport = $reportRepo->findById($multiReportId);
        $this->assert('AC-MULTI-05a', 'Laporan gabungan berhasil diajukan dengan status SUBMITTED', $submittedReport['status'] === 'SUBMITTED');

        $workflowService->approveReport($multiReportId, 'Laporan gabungan disetujui lengkap.', $managerUser);
        $approvedReport = $reportRepo->findById($multiReportId);
        $this->assert('AC-MULTI-05b', 'Manager menyetujui satu laporan gabungan ke status APPROVED', $approvedReport['status'] === 'APPROVED');

        // AC-MULTI-06: Finance posting with journal reference
        $workflowService->postReport($multiReportId, $financeUser, 'JRN-TEST-202611-001', date('Y-m-d'), 'Pembukuan biaya gabungan proyek Q4');
        $postedReport = $reportRepo->findById($multiReportId);
        $postingRecord = $reportRepo->getPosting($multiReportId);
        $this->assert('AC-MULTI-06', 'Finance berhasil membukukan laporan gabungan ke expense_postings', $postedReport['status'] === 'POSTED' && $postingRecord !== null && $postingRecord['journal_reference'] === 'JRN-TEST-202611-001');

        // AC-MULTI-07: Per-employee reimbursement generation
        $reimbList = $reimbRepo->getByReportId($multiReportId);
        $this->assert('AC-MULTI-07', 'Kewajiban reimbursement individual per karyawan berhasil dibuat (Budi: Rp 350.000, Sari: Rp 100.000)', count($reimbList) === 2);

        $budiReimb = array_values(array_filter($reimbList, fn($r) => (int)$r['employee_id'] === (int)$budiUser['employee_id']))[0] ?? null;
        $sariReimb = array_values(array_filter($reimbList, fn($r) => (int)$r['employee_id'] === (int)$sariUser['employee_id']))[0] ?? null;
        $this->assert('AC-MULTI-07b', 'Nominal reimbursement dihitung akurat dari subtotal transaksi masing-masing karyawan', (float)$budiReimb['approved_amount'] === 350000.00 && (float)$sariReimb['approved_amount'] === 100000.00);

        // AC-MULTI-08: Partial payment tracking
        $budiReimbId = (int) $budiReimb['reimbursement_id'];
        $workflowService->recordReimbursementPayment($budiReimbId, [
            'amount' => 200000.00,
            'payment_method' => 'BANK_TRANSFER',
            'bank_reference' => 'TRF-PARTIAL-001',
            'payment_date' => date('Y-m-d'),
            'notes' => 'Pembayaran termin 1',
        ], $financeUser);

        $budiReimbAfterPartial = $reimbRepo->findById($budiReimbId);
        $this->assert('AC-MULTI-08', 'Mendukung pembayaran bertahap (status PARTIALLY_PAID, sisa Rp 150.000)', $budiReimbAfterPartial['status'] === 'PARTIALLY_PAID' && (float)$budiReimbAfterPartial['paid_amount'] === 200000.00 && (float)$budiReimbAfterPartial['remaining_amount'] === 150000.00);

        // AC-MULTI-09: Overpayment prevention
        $threwOverpay = false;
        try {
            $workflowService->recordReimbursementPayment($budiReimbId, [
                'amount' => 500000.00, // Exceeds 150,000 remaining
                'payment_reference' => 'TRF-OVERPAY',
            ], $financeUser);
        } catch (\InvalidArgumentException $e) {
            $threwOverpay = true;
        }
        $this->assert('AC-MULTI-09', 'Pembayaran melebihi sisa kewajiban ditolak sistem', $threwOverpay);

        // AC-MULTI-10: Settle remaining payments and auto transition report to PAID
        $workflowService->recordReimbursementPayment($budiReimbId, [
            'amount' => 150000.00,
            'payment_reference' => 'TRF-FINAL-BUDI',
        ], $financeUser);

        $sariReimbId = (int) $sariReimb['reimbursement_id'];
        $workflowService->recordReimbursementPayment($sariReimbId, [
            'amount' => 100000.00,
            'payment_reference' => 'TRF-FINAL-SARI',
        ], $financeUser);

        $finalMultiReport = $reportRepo->findById($multiReportId);
        $finalBudiReimb = $reimbRepo->findById($budiReimbId);
        $finalSariReimb = $reimbRepo->findById($sariReimbId);
        $this->assert('AC-MULTI-10', 'Setelah seluruh reimbursement karyawan lunas, status laporan otomatis menjadi PAID', $finalBudiReimb['status'] === 'PAID' && $finalSariReimb['status'] === 'PAID' && $finalMultiReport['status'] === 'PAID');
    }

    private function testFinanceAndReimbursement(): void
    {
        echo "\n--- 5. Finance Posting & Reimbursement Tests ---\n";

        $workflowService = new ReportWorkflowService();
        $reportRepo = new ReportRepository();
        $reimbRepo = new ReimbursementRepository();
        $userRepo = new UserRepository();
        $authService = new AuthorizationService();

        $financeUser = $userRepo->findByUsernameOrEmail('finance@example.test');
        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');

        // Locate approved report (ensure APPROVED status for test repeatability)
        $report = $reportRepo->findById(3);
        if (!$report || $report['status'] !== 'APPROVED') {
            $approvedList = $reportRepo->getReports(['status' => 'APPROVED'], 1, 1)['items'];
            if (!empty($approvedList)) {
                $report = $approvedList[0];
            } elseif ($report) {
                $this->pdo->prepare("DELETE FROM expense_postings WHERE report_id = ?")->execute([$report['report_id']]);
                $this->pdo->prepare("DELETE FROM expense_reimbursements WHERE report_id = ?")->execute([$report['report_id']]);
                $this->pdo->prepare("DELETE FROM reimbursements WHERE report_id = ?")->execute([$report['report_id']]);
                $this->pdo->prepare("UPDATE expense_reports SET status = 'APPROVED' WHERE report_id = ?")->execute([$report['report_id']]);
                $this->pdo->prepare("UPDATE expenses SET status = 'APPROVED' WHERE expense_id IN (SELECT expense_id FROM expense_report_items WHERE report_id = ?)")->execute([$report['report_id']]);
                $report = $reportRepo->findById((int) $report['report_id']);
            }
        }

        $reportId = (int) $report['report_id'];

        // AC-PAY-01: Finance with expense.post can post approved report
        $canPost = $authService->canPostReport($financeUser, $report);
        $this->assert('AC-PAY-01', 'Finance berwenang membukukan (POSTED) laporan APPROVED', $canPost);

        $workflowService->postReport($reportId, $financeUser);
        $postedReport = $reportRepo->findById($reportId);
        $this->assert('AC-PAY-01b', 'Status berubah ke POSTED setelah posting', $postedReport['status'] === 'POSTED');

        // AC-PAY-02: Queue reimbursement
        $reimbId = $workflowService->queueReimbursement($reportId, $financeUser);
        $reimbRecord = $reimbRepo->findById($reimbId);
        $queuedReport = $reportRepo->findById($reportId);
        $this->assert('AC-PAY-02', 'Laporan masuk antrean reimbursement dengan status REIMBURSEMENT_PENDING', $queuedReport['status'] === 'REIMBURSEMENT_PENDING' && in_array($reimbRecord['status'], ['PENDING', 'PROCESSING', 'PAID'], true));

        // AC-PAY-05: Mark paid atomically
        $paymentDetails = [
            'payment_method' => 'BANK_TRANSFER',
            'payment_reference' => 'TRF-TEST-20261011-999',
            'payment_date' => date('Y-m-d'),
            'notes' => 'Pembayaran selesai melalui internet banking',
        ];
        $workflowService->markPaid($reimbId, $paymentDetails, $financeUser);

        $finalReimb = $reimbRepo->findById($reimbId);
        $finalReport = $reportRepo->findById($reportId);
        $this->assert('AC-PAY-05', 'Mark paid menyinkronkan reimbursement dan report ke status PAID secara atomik', $finalReimb['status'] === 'PAID' && $finalReport['status'] === 'PAID');
    }

    private function testRbacAndAuthorization(): void
    {
        echo "\n--- 6. RBAC & Record Scope Tests ---\n";

        $userRepo = new UserRepository();
        $authService = new AuthorizationService();

        $adminUser = $userRepo->findByUsernameOrEmail('admin@example.test');
        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');
        $financeUser = $userRepo->findByUsernameOrEmail('finance@example.test');

        // AC-RBAC-03: Finance cannot approve manager reports
        $fakeSubmittedReport = [
            'report_id' => 999,
            'employee_id' => (int) $budiUser['employee_id'],
            'status' => 'SUBMITTED',
        ];
        $canFinanceDecide = $authService->canDecideReport($financeUser, $fakeSubmittedReport);
        $this->assert('AC-RBAC-03', 'Finance tidak berhak melakukan approval manajer', !$canFinanceDecide);

        // AC-RBAC-04: Employee cannot manage settings
        $isBudiAdmin = $budiUser['role_name'] === 'ADMIN';
        $isAdminAdmin = $adminUser['role_name'] === 'ADMIN';
        $this->assert('AC-RBAC-04', 'Employee ditolak dari hak kelola master data / admin', !$isBudiAdmin && $isAdminAdmin);

        // AC-RBAC-05: Manager cannot create expenses or create reports
        $rinaUser = $userRepo->findByUsernameOrEmail('manager@example.test');
        $canManagerCreateExpense = $authService->canCreateExpense($rinaUser);
        $canManagerCreateReport = $authService->canCreateReport($rinaUser);
        $this->assert('AC-RBAC-05', 'Role MANAGER tidak memiliki izin membuat pengeluaran dan laporan', !$canManagerCreateExpense && !$canManagerCreateReport);

        // Service level check
        $threwManagerExp = false;
        try {
            $expenseService = new ExpenseService();
            $expenseService->createExpense([
                'category_id' => 1,
                'expense_date' => date('Y-m-d'),
                'description' => 'Coba buat expense oleh manager',
                'amount' => 50000.00,
            ], $rinaUser);
        } catch (RuntimeException $e) {
            $threwManagerExp = true;
        }
        $this->assert('AC-RBAC-05b', 'Service menolak pembuatan expense oleh peran MANAGER', $threwManagerExp);

        $threwManagerRep = false;
        try {
            $reportWorkflowService = new ReportWorkflowService();
            $reportWorkflowService->createReport([
                'title' => 'Coba buat report oleh manager',
            ], $rinaUser);
        } catch (RuntimeException $e) {
            $threwManagerRep = true;
        }
        $this->assert('AC-RBAC-05c', 'Service menolak pembuatan report oleh peran MANAGER', $threwManagerRep);
    }

    private function testCsvAndDashboardIntegrity(): void
    {
        echo "\n--- 7. CSV Export & Dashboard Metrics Integrity Tests ---\n";

        // AC-CSV-02: Formula injection prevention
        $csvService = new CsvExportService();
        $unsafeFormula1 = '=SUM(A1:A10)';
        $unsafeFormula2 = '+cmd|"/C calc"!A0';
        $safeCell1 = $csvService->sanitizeCell($unsafeFormula1);
        $safeCell2 = $csvService->sanitizeCell($unsafeFormula2);
        $this->assert('AC-CSV-02', 'Formula injection dimitigasi dengan prepending single quote', str_starts_with($safeCell1, "'=") && str_starts_with($safeCell2, "'+"));

        // AC-DASH-01: Dashboard metrics match actual SQL queries
        $dashboardService = new DashboardService();
        $userRepo = new UserRepository();
        $budiUser = $userRepo->findByUsernameOrEmail('budi@example.test');
        $metrics = $dashboardService->getMetrics($budiUser);

        $actualDraftCount = (int) $this->pdo->query("SELECT COUNT(*) FROM expenses WHERE employee_id = {$budiUser['employee_id']} AND status = 'DRAFT'")->fetchColumn();
        $this->assert('AC-DASH-01', 'Angka dashboard dinamis cocok dengan query SQL nyata', (int) $metrics['draft_expenses_count'] === $actualDraftCount);

        // --- 8. Chart.js & Visual Analytics Integration Tests ---
        echo "\n--- 8. Chart.js Integration & Visual Analytics Tests ---\n";
        $chartAssetPath = __DIR__ . '/../public/assets/vendor/chartjs/chart.umd.min.js';
        $this->assert('AC-CHART-01', 'Chart.js UMD asset tersedia secara lokal (>100KB) untuk offline-first', file_exists($chartAssetPath) && filesize($chartAssetPath) > 100000);

        $layoutContent = file_get_contents(__DIR__ . '/../app/Views/layouts/main.php');
        $this->assert('AC-CHART-02', 'Layout utama menyertakan script chart.umd.min.js sebelum app.js', str_contains($layoutContent, '/assets/vendor/chartjs/chart.umd.min.js') && strpos($layoutContent, 'chart.umd.min.js') < strpos($layoutContent, 'app.js'));

        $this->assert('AC-CHART-03', 'Dashboard metrics menyediakan monthly_trend 6 bulan dan category_breakdown', isset($metrics['monthly_trend']) && count($metrics['monthly_trend']) === 6 && isset($metrics['category_breakdown']) && is_array($metrics['category_breakdown']));

        $dashView = file_get_contents(__DIR__ . '/../app/Views/dashboard/index.php');
        $this->assert('AC-CHART-04', 'View dashboard memiliki canvas dashboardTrendChart dan dashboardCategoryChart serta script inisialisasi', str_contains($dashView, 'dashboardTrendChart') && str_contains($dashView, 'dashboardCategoryChart') && str_contains($dashView, 'GTCharts.createTrendChart'));

        $analyticsView = file_get_contents(__DIR__ . '/../app/Views/analytics/index.php');
        $this->assert('AC-CHART-05', 'View analitik memiliki canvas analyticsMonthlyChart dan analyticsCategoryChart serta script inisialisasi', str_contains($analyticsView, 'analyticsMonthlyChart') && str_contains($analyticsView, 'analyticsCategoryChart') && str_contains($analyticsView, 'GTCharts.createBarChart'));
    }

    public function tearDown(): void
    {
        try {
            $testReports = $this->pdo->query("SELECT report_id FROM expense_reports WHERE title IN ('Laporan Uji Otomatis Budi', 'Laporan Kedua', 'Laporan Pengeluaran Gabungan Proyek Q4')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($testReports as $rId) {
                $this->pdo->prepare("DELETE FROM expense_postings WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_reimbursements WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM reimbursements WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_approvals WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_comments WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_report_items WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_report_employees WHERE report_id = ?")->execute([$rId]);
                $this->pdo->prepare("DELETE FROM expense_reports WHERE report_id = ?")->execute([$rId]);
            }
            $testExpenses = $this->pdo->query("SELECT expense_id FROM expenses WHERE description IN ('Item Laporan Uji', 'Test Unit Pengeluaran Budi', 'Test Unit Pengeluaran Budi Diperbarui', 'Tiket Kereta Dinas Budi', 'Makan Siang Tim Budi', 'Taksi Kunjungan Klien Sari')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($testExpenses as $eId) {
                $this->pdo->prepare("DELETE FROM expenses WHERE expense_id = ?")->execute([$eId]);
            }
            $this->pdo->exec("DELETE FROM audit_logs WHERE old_value LIKE '%Test Unit Pengeluaran Budi%' OR new_value LIKE '%Test Unit Pengeluaran Budi%'");
            $this->pdo->exec("DELETE FROM activity_logs WHERE summary LIKE '%Test Unit Pengeluaran Budi%' OR summary LIKE '%Laporan Uji Otomatis Budi%' OR summary LIKE '%Laporan Kedua%' OR summary LIKE '%Item Laporan Uji%' OR summary LIKE '%Laporan Pengeluaran Gabungan Proyek Q4%'");
        } catch (\Throwable $e) {
            // Silently ignore cleanup errors if any
        }
    }
}

$suite = new AcceptanceTestSuite();
$suite->runAll();
