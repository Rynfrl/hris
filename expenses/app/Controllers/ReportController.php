<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ReimbursementRepository;
use App\Services\ReportWorkflowService;
use App\Services\AuthorizationService;
use App\Services\ExpenseService;
use Throwable;

class ReportController
{
    private ReportRepository $reportRepo;
    private ExpenseRepository $expenseRepo;
    private EmployeeRepository $employeeRepo;
    private CategoryRepository $categoryRepo;
    private ReimbursementRepository $reimbursementRepo;
    private ReportWorkflowService $workflowService;
    private AuthorizationService $authService;
    private ExpenseService $expenseService;

    public function __construct(
        ?ReportRepository $reportRepo = null,
        ?ExpenseRepository $expenseRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?CategoryRepository $categoryRepo = null,
        ?ReimbursementRepository $reimbursementRepo = null,
        ?ReportWorkflowService $workflowService = null,
        ?AuthorizationService $authService = null,
        ?ExpenseService $expenseService = null
    ) {
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->expenseRepo = $expenseRepo ?? new ExpenseRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->reimbursementRepo = $reimbursementRepo ?? new ReimbursementRepository();
        $this->workflowService = $workflowService ?? new ReportWorkflowService();
        $this->authService = $authService ?? new AuthorizationService();
        $this->expenseService = $expenseService ?? new ExpenseService();
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        // Employee has no access to operational expense menus
        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $role = $user['role_name'] ?? '';
        $isAdmin = in_array($role, ['ADMIN', 'ADMIN_EXPENSE'], true) || Auth::hasPermission('expenses.manage');
        $isManager = ($role === 'MANAGER') || Auth::hasPermission('expense.approve');
        $isFinance = ($role === 'FINANCE') || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse');

        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'search' => $_GET['search'] ?? null,
            'sort' => $_GET['sort'] ?? 'created_at',
            'order' => $_GET['order'] ?? 'DESC',
        ];

        $reqStatus = !empty($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : null;

        if ($isFinance && !$isAdmin) {
            // Finance role scoping: strictly APPROVED, POSTED, REIMBURSEMENT_PENDING, PAID (Perbaikan 6)
            $financeAllowed = ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'];
            if ($reqStatus && in_array($reqStatus, $financeAllowed, true)) {
                $filters['status'] = $reqStatus;
            } else {
                $filters['status_in'] = $financeAllowed;
            }
        } elseif ($isManager && !$isAdmin) {
            // Manager role scoping: SUBMITTED, APPROVED, POSTED, REIMBURSEMENT_PENDING, PAID, REFUSED (Prioritas 1)
            $managerAllowed = ['SUBMITTED', 'APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID', 'REFUSED'];
            if ($reqStatus && in_array($reqStatus, $managerAllowed, true)) {
                $filters['status'] = $reqStatus;
            } else {
                $filters['status_in'] = $managerAllowed;
            }
            $filters['approver_id'] = (int) ($user['user_id'] ?? 0);
        } else {
            // Admin role: can filter by any status
            if ($reqStatus) {
                $filters['status'] = $reqStatus;
            }
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = $this->reportRepo->getReports($filters, $page, 20);

        return View::render('reports/index', [
            'title' => 'Laporan Pengeluaran',
            'reports' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'filters' => $filters,
            'user' => $user,
        ]);
    }

    public function create(): string
    {
        $user = Auth::user();
        if (!$this->authService->canCreateReport($user)) {
            Flash::error('Peran Manajer tidak memiliki hak akses untuk membuat laporan pengeluaran.');
            Url::redirect('/reports');
        }

        $activeEmployees = $this->employeeRepo->getAll(true);
        $approvers = \App\Core\Database::pdo()->query("
            SELECT u.user_id, u.username, r.role_name, emp.full_name
            FROM users u
            JOIN roles r ON (r.role_id = u.role_id OR r.id_role = u.role_id)
            LEFT JOIN employees emp ON (emp.employee_id = u.employee_id OR emp.id_employee = u.employee_id)
            WHERE (r.role_name IN ('MANAGER', 'MANAGER_EXPENSE') OR u.username = 'rina_manager'
                   OR EXISTS (SELECT 1 FROM role_permissions rp JOIN permissions p ON p.permission_id = rp.permission_id WHERE rp.role_id = u.role_id AND p.permission_key = 'expense.approve'))
              AND u.deleted_at IS NULL
            ORDER BY u.user_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return View::render('reports/create', [
            'title' => 'Buat Laporan Pengeluaran Baru',
            'activeEmployees' => $activeEmployees,
            'approvers' => $approvers,
            'user' => $user,
        ]);
    }

    public function store(): void
    {
        $user = Auth::user();
        if (!$this->authService->canCreateReport($user)) {
            Flash::error('Peran Manajer tidak memiliki hak akses untuk membuat laporan pengeluaran.');
            Url::redirect('/reports');
        }

        try {
            $reportId = $this->workflowService->createReport($_POST, $user);
            Flash::success('Laporan pengeluaran berhasil dibuat. Silakan tambahkan atau periksa rincian karyawan dan pengeluaran.');
            Url::redirect('/reports/' . $reportId);
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
            Url::redirect('/reports/create');
        }
    }

    public function show(string|int $id): string
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report) {
            http_response_code(404);
            return View::render('errors/404', ['title' => 'Laporan Tidak Ditemukan'], 'layouts/error');
        }

        if (!$this->authService->canViewReport($user, $report)) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
        $items = $this->reportRepo->getReportItems($reportId);
        $history = $this->reportRepo->getApprovalHistory($reportId);
        $comments = $this->reportRepo->getComments($reportId);
        $postingInfo = $this->reportRepo->getPosting($reportId);
        $reimbursements = $this->reimbursementRepo->getByReportId($reportId);

        $categories = $this->categoryRepo->getAll(true);
        $activeEmployees = $this->employeeRepo->getAll(true);

        // Filter unassigned draft expenses for any of the report's employees
        $registeredEmpIds = array_column($reportEmployees, 'employee_id');
        $unassignedExpenses = [];
        if ($report['status'] === 'DRAFT') {
            foreach ($registeredEmpIds as $eid) {
                $unassigned = $this->expenseRepo->getUnassignedDraftExpenses((int) $eid);
                foreach ($unassigned as $u) {
                    $unassignedExpenses[] = $u;
                }
            }
        }

        return View::render('reports/show', [
            'title' => 'Laporan ' . $report['report_number'],
            'report' => $report,
            'reportEmployees' => $reportEmployees,
            'items' => $items,
            'history' => $history,
            'comments' => $comments,
            'postingInfo' => $postingInfo,
            'reimbursements' => $reimbursements,
            'categories' => $categories,
            'activeEmployees' => $activeEmployees,
            'unassignedExpenses' => $unassignedExpenses,
            'user' => $user,
            'canEdit' => $this->authService->canEditReport($user, $report),
            'canSubmit' => $this->authService->canSubmitReport($user, $report),
            'canDecide' => $this->authService->canDecideReport($user, $report),
            'canRevise' => $this->authService->canReviseReport($user, $report),
            'canPost' => $this->authService->canPostReport($user, $report),
            'canReimburse' => $this->authService->canReimburseReport($user, $report),
        ]);
    }

    public function edit(string|int $id): string
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Laporan tidak dapat diedit.');
            Url::redirect('/reports/' . $reportId);
        }

        $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
        $activeEmployees = $this->employeeRepo->getAll(true);

        return View::render('reports/edit', [
            'title' => 'Edit Laporan ' . $report['report_number'],
            'report' => $report,
            'reportEmployees' => $reportEmployees,
            'activeEmployees' => $activeEmployees,
            'user' => $user,
        ]);
    }

    public function update(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Laporan tidak dapat diedit.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->updateReport($reportId, $_POST, $user);
            Flash::success('Laporan berhasil diperbarui.');
            Url::redirect('/reports/' . $reportId);
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
            Url::redirect('/reports/' . $reportId . '/edit');
        }
    }

    public function addEmployee(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Laporan tidak dapat diubah.');
            Url::redirect('/reports/' . $reportId);
        }

        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            Flash::error('Silakan pilih karyawan terlebih dahulu.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->addEmployeeToReport($reportId, $employeeId, $user);
            Flash::success('Karyawan berhasil ditambahkan ke dalam laporan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function removeEmployee(string|int $id, string|int $employeeId = 0, string|int $reportEmployeeId = 0): void
    {
        $reportId = (int) $id;
        $repEmpId = (int) ($employeeId ?: $reportEmployeeId);
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Laporan tidak dapat diubah.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->removeEmployeeFromReport($reportId, $repEmpId, $user);
            Flash::success('Karyawan dan rincian transaksinya berhasil dihapus dari laporan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function createExpenseItem(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Laporan tidak dapat diubah.');
            Url::redirect('/reports/' . $reportId);
        }

        $reportEmployeeId = (int) ($_POST['report_employee_id'] ?? 0);
        if ($reportEmployeeId <= 0) {
            Flash::error('Pilih karyawan pemilik transaksi pengeluaran.');
            Url::redirect('/reports/' . $reportId);
        }

        // Fetch employee from report_employee_id
        $repEmp = $this->reportRepo->getReportEmployee($reportId, $reportEmployeeId);
        if (!$repEmp || empty($repEmp['employee_id'])) {
            Flash::error('Karyawan tidak terdaftar pada laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }
        $employeeId = (int) $repEmp['employee_id'];

        try {
            // 1. Create expense
            $expensePayload = [
                'employee_id' => $employeeId,
                'category_id' => (int) ($_POST['category_id'] ?? 0),
                'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                'description' => trim($_POST['description'] ?? ''),
                'amount' => (float) ($_POST['amount'] ?? 0),
                'payment_method' => $_POST['payment_method'] ?? 'BANK_TRANSFER',
                'bank_name' => trim($_POST['bank_name'] ?? ($repEmp['bank_name'] ?? '')),
                'merchant' => trim($_POST['merchant'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
            ];

            // Use pseudo user with correct employee_id so ExpenseService creates expense for this employee
            $actorUser = array_merge($user, ['employee_id' => $employeeId]);
            $expenseId = $this->expenseService->createExpense($expensePayload, $actorUser);

            // Handle receipt file upload if provided
            if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                $this->expenseService->uploadReceipt($expenseId, $_FILES['receipt'], $actorUser);
            }

            // 2. Link to report with report_employee_id
            $this->workflowService->addItem($reportId, $expenseId, $user, $reportEmployeeId);

            Flash::success('Transaksi pengeluaran karyawan berhasil dicatat dan ditambahkan ke laporan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function addItem(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Tidak dapat menambahkan item ke laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }

        $expenseId = (int) ($_POST['expense_id'] ?? 0);
        $reportEmployeeId = !empty($_POST['report_employee_id']) ? (int) $_POST['report_employee_id'] : null;

        if ($expenseId <= 0) {
            Flash::error('Pilih pengeluaran yang ingin ditambahkan.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->addItem($reportId, $expenseId, $user, $reportEmployeeId);
            Flash::success('Item pengeluaran berhasil ditambahkan ke laporan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function removeItem(string|int $id, string|int $expenseId): void
    {
        $reportId = (int) $id;
        $expId = (int) $expenseId;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canEditReport($user, $report)) {
            Flash::error('Tidak dapat melepaskan item dari laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->removeItem($reportId, $expId, $user);
            Flash::success('Item pengeluaran berhasil dilepas dari laporan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function submit(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canSubmitReport($user, $report)) {
            Flash::error('Laporan tidak dapat diajukan.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->submitReport($reportId, $user);
            Flash::success('Laporan berhasil diajukan untuk persetujuan manajer.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function revise(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canReviseReport($user, $report)) {
            Flash::error('Laporan tidak dapat direvisi.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->reviseReport($reportId, $user);
            Flash::success('Laporan telah dikembalikan ke status Draft untuk direvisi.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function addComment(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canViewReport($user, $report)) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reports/' . $reportId);
        }

        $body = trim($_POST['comment_body'] ?? '');
        if ($body === '') {
            Flash::error('Komentar tidak boleh kosong.');
            Url::redirect('/reports/' . $reportId);
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $this->reportRepo->addComment($reportId, $userId, $body);

        Flash::success('Komentar berhasil ditambahkan.');
        Url::redirect('/reports/' . $reportId);
    }
}
