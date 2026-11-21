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
        $role = $user['role_name'] ?? '';
        if ($role !== 'FINANCE' && $role !== 'ADMIN' && !Auth::hasPermission('expense.reimburse')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        // 1. Posted reports waiting to be queued if any
        $postedReports = $this->reportRepo->getReports(['status' => 'POSTED'], 1, 50)['items'];

        // 2. Active reimbursements queue (PENDING, PROCESSING)
        $statusParam = !empty($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : '';
        if ($statusParam === 'PROCESSING') {
            $activeReimbursements = $this->reimbursementRepo->getReimbursements(['status' => 'PROCESSING'], 1, 50)['items'];
        } elseif ($statusParam === 'PENDING') {
            $activeReimbursements = $this->reimbursementRepo->getReimbursements(['status' => 'PENDING'], 1, 50)['items'];
        } else {
            $pending = $this->reimbursementRepo->getReimbursements(['status' => 'PENDING'], 1, 50)['items'];
            $processing = $this->reimbursementRepo->getReimbursements(['status' => 'PROCESSING'], 1, 50)['items'];
            $activeReimbursements = array_merge($pending, $processing);
        }

        $suggestedPayNumber = $this->reimbursementRepo->generatePaymentNumber();

        return View::render('reimbursements/index', [
            'title' => 'Antrean & Pembayaran Reimbursement (Finance)',
            'postedReports' => $postedReports,
            'activeReimbursements' => $activeReimbursements,
            'suggestedPayNumber' => $suggestedPayNumber,
            'currentStatus' => $statusParam,
            'user' => $user,
        ]);
    }

    public function createQueue(string|int $id): void
    {
        $reportId = (int) $id;
        $user = Auth::user();
        $role = $user['role_name'] ?? '';

        if ($role !== 'FINANCE' && $role !== 'ADMIN' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        try {
            $this->workflowService->queueReimbursement($reportId, $user);
            Flash::success('Laporan berhasil dimasukkan ke antrean pembayaran reimbursement satu kali penuh.');
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

    public function recordPayment(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();
        $role = $user['role_name'] ?? '';

        if ($role !== 'FINANCE' && $role !== 'ADMIN' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        $reimb = $this->reimbursementRepo->findById($reimbId);
        if (!$reimb) {
            Flash::error('Data reimbursement tidak ditemukan.');
            Url::redirect('/reimbursements');
        }

        // Amount must be the approved total amount of the report
        $approvedAmount = (float) ($reimb['approved_amount'] ?? $reimb['amount'] ?? 0);
        $payAmount = !empty($_POST['amount']) ? (float) $_POST['amount'] : $approvedAmount;

        $paymentData = [
            'amount' => $payAmount,
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'payment_number' => !empty($_POST['payment_number']) ? trim($_POST['payment_number']) : null,
            'payment_method' => $_POST['payment_method'] ?? 'BANK_TRANSFER',
            'bank_reference' => trim($_POST['bank_reference'] ?? ''),
            'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        try {
            $proofFile = isset($_FILES['proof']) ? $_FILES['proof'] : null;
            $this->workflowService->recordReimbursementPayment($reimbId, $paymentData, $user, $proofFile);
            Flash::success(sprintf('Pembayaran reimbursement satu laporan penuh sebesar Rp %s berhasil diselesaikan (LUNAS)!', number_format($approvedAmount, 0, ',', '.')));
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function markPaid(string|int $id): void
    {
        $this->recordPayment($id);
    }

    public function markFailed(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();
        $reason = trim($_POST['reason'] ?? 'Pembayaran gagal');

        try {
            $this->workflowService->markFailed($reimbId, $reason, $user);
            Flash::warning('Status reimbursement ditandai GAGAL.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function history(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? '';
        if ($role !== 'FINANCE' && $role !== 'ADMIN' && !Auth::hasPermission('expense.reimburse')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = $this->reimbursementRepo->getReimbursements(['status' => 'PAID'], $page, 25);

        return View::render('reimbursements/history', [
            'title' => 'Riwayat Pembayaran Reimbursement (Lunas)',
            'items' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'user' => $user,
        ]);
    }
}
