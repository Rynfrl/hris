<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\ReimbursementRepository;
use App\Repositories\ReportRepository;
use App\Services\ReportWorkflowService;
use Throwable;

class ReimbursementController
{
    private ReimbursementRepository $reimbursementRepo;
    private ReportRepository $reportRepo;
    private ReportWorkflowService $workflowService;

    public function __construct(
        ?ReimbursementRepository $reimbursementRepo = null,
        ?ReportRepository $reportRepo = null,
        ?ReportWorkflowService $workflowService = null
    ) {
        $this->reimbursementRepo = $reimbursementRepo ?? new ReimbursementRepository();
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->workflowService = $workflowService ?? new ReportWorkflowService();
    }

    public function index(): string
    {
        $user = Auth::user();
        if ($user['role_name'] !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        // 1. Posted reports waiting to be queued
        $postedReports = $this->reportRepo->getReports(['status' => 'POSTED'], 1, 50)['items'];

        // 2. Active reimbursements queue (PENDING & PROCESSING)
        $pendingReimbs = $this->reimbursementRepo->getReimbursements(['status' => 'PENDING'], 1, 50)['items'];
        $processingReimbs = $this->reimbursementRepo->getReimbursements(['status' => 'PROCESSING'], 1, 50)['items'];
        $activeReimbursements = array_merge($pendingReimbs, $processingReimbs);

        return View::render('reimbursements/index', [
            'title' => 'Antrean Reimbursement',
            'postedReports' => $postedReports,
            'activeReimbursements' => $activeReimbursements,
            'user' => $user,
        ]);
    }

    public function createQueue(string|int $id): void
    {
        $reportId = (int) $id;
        $user = Auth::user();

        if ($user['role_name'] !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        try {
            $this->workflowService->queueReimbursement($reportId, $user);
            Flash::success('Laporan berhasil dimasukkan ke antrean pembayaran reimbursement.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function setProcessing(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();

        try {
            $this->workflowService->setProcessing($reimbId, $user);
            Flash::info('Status reimbursement diubah ke SEDANG DIPROSES.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function markPaid(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();

        $paymentDetails = [
            'payment_method' => $_POST['payment_method'] ?? 'BANK_TRANSFER',
            'payment_reference' => trim($_POST['payment_reference'] ?? ''),
            'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        try {
            $this->workflowService->markPaid($reimbId, $paymentDetails, $user);
            Flash::success('Pembayaran reimbursement berhasil diselesaikan dan dicatat lunas (PAID).');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function markFailed(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();
        $reason = trim($_POST['reason'] ?? 'Pembayaran gagal');

        try {
            $this->workflowService->markFailed($reimbId, $reason, $user);
            Flash::warning('Reimbursement ditandai GAGAL.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function history(): string
    {
        $user = Auth::user();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = $this->reimbursementRepo->getReimbursements(['status' => 'PAID'], $page, 20);

        return View::render('reimbursements/history', [
            'title' => 'Riwayat Pembayaran Reimbursement',
            'reimbursements' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'user' => $user,
        ]);
    }
}
