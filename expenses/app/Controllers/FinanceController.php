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
        $role = $user['role_name'] ?? '';
        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.post')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));
        $filters = [
            'status' => 'APPROVED',
            'search' => $_GET['search'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
        ];

        $data = $this->reportRepo->getReports($filters, $page, $perPage);
        $suggestedRef = $this->reportRepo->generateJournalReference();

        return View::render('finance/posting', [
            'title' => 'Pembukuan Laporan (Posting Finance)',
            'reports' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'perPage' => $perPage,
            'filters' => $filters,
            'suggestedRef' => $suggestedRef,
            'user' => $user,
        ]);
    }

    public function history(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? '';
        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.post')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));
        $filters = [
            'search' => $_GET['search'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
        ];

        $data = $this->reportRepo->getPostings($filters, $page, $perPage);

        return View::render('finance/history', [
            'title' => 'Riwayat Pembukuan Laporan (Posting)',
            'postings' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'perPage' => $perPage,
            'filters' => $filters,
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

        $journalRef = trim($_POST['journal_reference'] ?? '');
        $postingDate = !empty($_POST['posting_date']) ? $_POST['posting_date'] : date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');

        try {
            $this->workflowService->postReport($reportId, $user, $journalRef ?: null, $postingDate, $notes ?: null);
            Flash::success('Laporan ' . $report['report_number'] . ' berhasil dibukukan (POSTED) dan kewajiban reimbursement karyawan telah dibuat.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/finance/posting');
    }
}
