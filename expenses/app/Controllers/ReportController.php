<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\EmployeeRepository;
use App\Services\ReportWorkflowService;
use App\Services\AuthorizationService;
use Throwable;

class ReportController
{
    private ReportRepository $reportRepo;
    private ExpenseRepository $expenseRepo;
    private EmployeeRepository $employeeRepo;
    private ReportWorkflowService $workflowService;
    private AuthorizationService $authService;

    public function __construct(
        ?ReportRepository $reportRepo = null,
        ?ExpenseRepository $expenseRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?ReportWorkflowService $workflowService = null,
        ?AuthorizationService $authService = null
    ) {
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->expenseRepo = $expenseRepo ?? new ExpenseRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->workflowService = $workflowService ?? new ReportWorkflowService();
        $this->authService = $authService ?? new AuthorizationService();
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $filters = [
            'status' => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'search' => $_GET['search'] ?? null,
            'sort' => $_GET['sort'] ?? 'created_at',
            'order' => $_GET['order'] ?? 'DESC',
        ];

        // Scope filter
        if ($role === 'EMPLOYEE' || ($role !== 'ADMIN' && $role !== 'FINANCE' && $role !== 'MANAGER')) {
            $filters['employee_id'] = $empId ?? -1;
        } elseif ($role === 'MANAGER') {
            $directReports = $this->employeeRepo->getDirectReports($empId ?? 0);
            $empIds = array_column($directReports, 'employee_id');
            if ($empId) {
                $empIds[] = $empId;
            }
            $filters['employee_ids'] = $empIds;
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

        if (empty($user['employee_id'])) {
            Flash::error('Akun Anda belum terhubung dengan data karyawan.');
            Url::redirect('/dashboard');
        }

        return View::render('reports/create', [
            'title' => 'Buat Laporan Pengeluaran Baru',
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
            Flash::success('Laporan berhasil dibuat. Silakan tambahkan item pengeluaran ke dalam laporan.');
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

        $items = $this->reportRepo->getReportItems($reportId);
        $history = $this->reportRepo->getApprovalHistory($reportId);
        $comments = $this->reportRepo->getComments($reportId);

        // If report is DRAFT and user is owner, get unassigned draft expenses to add
        $unassignedExpenses = [];
        if ($report['status'] === 'DRAFT' && !empty($user['employee_id']) && (int) $user['employee_id'] === (int) $report['employee_id']) {
            $unassignedExpenses = $this->expenseRepo->getUnassignedDraftExpenses((int) $user['employee_id']);
        }

        return View::render('reports/show', [
            'title' => 'Laporan ' . $report['report_number'],
            'report' => $report,
            'items' => $items,
            'history' => $history,
            'comments' => $comments,
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

        return View::render('reports/edit', [
            'title' => 'Edit Laporan ' . $report['report_number'],
            'report' => $report,
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
        if ($expenseId <= 0) {
            Flash::error('Pilih pengeluaran yang ingin ditambahkan.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->addItem($reportId, $expenseId, $user);
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
