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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'start_date' => trim((string) ($_GET['start_date'] ?? '')),
            'end_date' => trim((string) ($_GET['end_date'] ?? '')),
        ];

        if ($statusFilter === 'HISTORY') {
            // Event-based approval history from expense_approvals (Perbaikan 7)
            if (!empty($_GET['action'])) {
                $filters['action'] = trim((string) $_GET['action']);
            }
            $filters['approver_id'] = $userId;
            $data = $this->reportRepo->getApprovalEventsHistory($filters, $page, $perPage);
            $reports = $data['data'];
            $total = $data['total'];
            $totalPages = $data['total_pages'];
        } else {
            // Pending queue: SUBMITTED reports awaiting manager decision (Prioritas 1)
            $reportFilters = array_merge($filters, [
                'status' => 'SUBMITTED',
                'exclude_created_by' => $userId, // Anti self-approval
                'approver_id' => $userId,
            ]);
            $data = $this->reportRepo->getReports($reportFilters, $page, $perPage);
            $reports = $data['items'];
            $total = $data['total'];
            $totalPages = $data['total_pages'];
        }

        return View::render('approvals/index', [
            'title' => ($statusFilter === 'HISTORY') ? 'Riwayat Keputusan Persetujuan' : 'Antrean Persetujuan Laporan',
            'reports' => $reports,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'currentStatus' => $statusFilter,
            'filters' => $filters,
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
