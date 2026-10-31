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
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        if ($empId === null || ($user['role_name'] !== 'MANAGER' && !Auth::hasPermission('expense.approve'))) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $directReports = $this->employeeRepo->getDirectReports($empId);
        $subordinateIds = array_column($directReports, 'employee_id');

        $reports = [];
        if (!empty($subordinateIds)) {
            $data = $this->reportRepo->getReports([
                'status' => 'SUBMITTED',
                'employee_ids' => $subordinateIds,
            ], 1, 100);
            $reports = $data['items'];
        }

        return View::render('approvals/index', [
            'title' => 'Persetujuan Tim',
            'reports' => $reports,
            'directReportsCount' => count($directReports),
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

        try {
            $comment = !empty($_POST['comment']) ? trim($_POST['comment']) : null;
            $this->workflowService->approveReport($reportId, $comment, $user);
            Flash::success('Laporan ' . $report['report_number'] . ' berhasil disetujui.');
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

        try {
            $reason = trim($_POST['reason'] ?? '');
            $this->workflowService->refuseReport($reportId, $reason, $user);
            Flash::success('Laporan ' . $report['report_number'] . ' berhasil ditolak dengan alasan tercatat.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reports/' . $reportId);
    }
}
