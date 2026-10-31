<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ReportRepository;
use App\Services\ReportWorkflowService;
use App\Services\AuthorizationService;
use Throwable;

class FinanceController
{
    private ReportRepository $reportRepo;
    private ReportWorkflowService $workflowService;
    private AuthorizationService $authService;

    public function __construct(
        ?ReportRepository $reportRepo = null,
        ?ReportWorkflowService $workflowService = null,
        ?AuthorizationService $authService = null
    ) {
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->workflowService = $workflowService ?? new ReportWorkflowService();
        $this->authService = $authService ?? new AuthorizationService();
    }

    public function postingQueue(): string
    {
        $user = Auth::user();
        if ($user['role_name'] !== 'FINANCE' && !Auth::hasPermission('expense.post')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $data = $this->reportRepo->getReports(['status' => 'APPROVED'], 1, 100);

        return View::render('finance/posting', [
            'title' => 'Pembukuan Laporan (Posting Finance)',
            'reports' => $data['items'],
            'user' => $user,
        ]);
    }

    public function postReport(string|int $id): void
    {
        $reportId = (int) $id;
        $report = $this->reportRepo->findById($reportId);
        $user = Auth::user();

        if (!$report || !$this->authService->canPostReport($user, $report)) {
            Flash::error('Anda tidak memiliki izin untuk membukukan laporan ini.');
            Url::redirect('/reports/' . $reportId);
        }

        try {
            $this->workflowService->postReport($reportId, $user);
            Flash::success('Laporan ' . $report['report_number'] . ' berhasil dibukukan (POSTED).');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/finance/posting');
    }
}
