<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ReportRepository;
use App\Repositories\EmployeeRepository;
use App\Services\ReportWorkflowService;
use App\Services\AuthorizationService;
use Throwable;

class ApprovalController
{
    private ReportRepository $reportRepo;
    private EmployeeRepository $employeeRepo;
    private ReportWorkflowService $workflowService;
    private AuthorizationService $authService;

    public function __construct(
        ?ReportRepository $reportRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?ReportWorkflowService $workflowService = null,
        ?AuthorizationService $authService = null
    ) {
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->workflowService = $workflowService ?? new ReportWorkflowService();
        $this->authService = $authService ?? new AuthorizationService();
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? '';

        if ($role !== 'MANAGER' && !Auth::hasPermission('expense.approve')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $statusFilter = !empty($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : 'SUBMITTED';
        $userId = (int) ($user['user_id'] ?? 0);
        $filters = [];
        if ($statusFilter === 'HISTORY') {
            $filters['status_in'] = ['APPROVED', 'REFUSED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'];
        } elseif ($statusFilter !== 'ALL') {
            $filters['status'] = $statusFilter;
        }

        // Anti self-approval: Manager cannot approve reports created by themselves
        if ($statusFilter === 'SUBMITTED') {
            $filters['exclude_created_by'] = $userId;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));

        $data = $this->reportRepo->getReports($filters, $page, $perPage);
        $reports = $data['items'];

        return View::render('approvals/index', [
            'title' => 'Persetujuan Laporan Pengeluaran (Manager)',
            'reports' => $reports,
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'perPage' => $perPage,
            'currentStatus' => $statusFilter,
            'user' => $user,
        ]);
    }

    public function approve(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canDecideReport($user, $report)) {
            Flash::error('Anda tidak memiliki wewenang untuk menyetujui laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }

        $comment = trim($_POST['comment'] ?? '');

        try {
            $this->workflowService->approveReport($reportId, $comment ?: null, $user);
            Flash::success('Laporan ' . $report['report_number'] . ' berhasil DISETUJUI.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }

    public function refuse(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canDecideReport($user, $report)) {
            Flash::error('Anda tidak memiliki wewenang untuk menolak laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }

        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            Flash::error('Alasan penolakan wajib diisi untuk memberikan catatan revisi kepada Admin Expense.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->refuseReport($reportId, $reason, $user);
            Flash::warning('Laporan ' . $report['report_number'] . ' telah DITOLAK (REFUSED) dengan catatan yang tersimpan.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }
}
