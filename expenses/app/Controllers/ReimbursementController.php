<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Logger;
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
        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));
        $filters = [];

        $statusParam = !empty($_GET['status']) ? strtoupper(trim((string)$_GET['status'])) : '';
        if (in_array($statusParam, ['PENDING', 'PROCESSING', 'FAILED'], true)) {
            $filters['status'] = $statusParam;
        } else {
            $filters['status_in'] = ['PENDING', 'PROCESSING', 'FAILED'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string) $_GET['search']);
        }

        $data = $this->reimbursementRepo->getReimbursements($filters, $page, $perPage);
        $suggestedPayNumber = $this->reimbursementRepo->generatePaymentNumber();

        return View::render('reimbursements/index', [
            'title' => 'Antrean & Pembayaran Reimbursement (Finance)',
            'activeReimbursements' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'perPage' => $perPage,
            'filters' => $filters,
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

        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
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
        $role = $user['role_name'] ?? '';

        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

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

        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        $reimb = $this->reimbursementRepo->findById($reimbId);
        if (!$reimb) {
            Flash::error('Data reimbursement tidak ditemukan.');
            Url::redirect('/reimbursements');
        }

        // Amount must be the approved total amount of the report
        $approvedAmount = (string) ($reimb['approved_amount'] ?? $reimb['amount'] ?? '0.00');
        $payAmount = !empty($_POST['amount']) ? (string) $_POST['amount'] : $approvedAmount;

        $paymentData = [
            'amount' => $payAmount,
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'payment_number' => !empty($_POST['payment_number']) ? trim($_POST['payment_number']) : null,
            'payment_method' => $_POST['payment_method'] ?? 'BANK_TRANSFER',
            'bank_reference' => trim($_POST['bank_reference'] ?? $_POST['payment_reference'] ?? ''),
            'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        try {
            $proofFile = isset($_FILES['proof']) ? $_FILES['proof'] : null;
            $this->workflowService->recordReimbursementPayment($reimbId, $paymentData, $user, $proofFile);
            Flash::success(sprintf('Pembayaran reimbursement satu laporan penuh sebesar Rp %s berhasil diselesaikan (LUNAS)!', number_format((float) $approvedAmount, 0, ',', '.')));
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function retryPayment(string|int $id): void
    {
        $reimbId = (int) $id;
        $user = Auth::user();
        $role = $user['role_name'] ?? '';

        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        try {
            $this->workflowService->retryReimbursement($reimbId, $user);
            Flash::success('Reimbursement yang gagal berhasil dikembalikan ke antrean transfer (PROCESSING) untuk dicairkan ulang.');
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
        $role = $user['role_name'] ?? '';

        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            Flash::error('Akses ditolak.');
            Url::redirect('/reimbursements');
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '' || mb_strlen($reason) < 5 || strcasecmp($reason, 'Pembayaran gagal') === 0) {
            Flash::error('Alasan kegagalan pembayaran wajib diisi secara jelas (minimal 5 karakter).');
            Url::redirect('/reimbursements');
            return;
        }

        $paymentRef = trim((string) ($_POST['payment_reference'] ?? $_POST['bank_reference'] ?? ''));

        try {
            $this->workflowService->markFailed($reimbId, $reason, $user, $paymentRef ?: null);
            Flash::warning('Status reimbursement telah ditandai GAGAL.');
        } catch (Throwable $e) {
            Flash::error($e->getMessage());
        }

        Url::redirect('/reimbursements');
    }

    public function history(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? '';
        if ($role !== 'FINANCE' && !Auth::hasPermission('expense.reimburse')) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 20)));
        $filters = ['status' => 'PAID'];
        if (!empty($_GET['search'])) {
            $filters['search'] = trim((string) $_GET['search']);
        }

        $errorMessage = null;
        try {
            $data = $this->reimbursementRepo->getReimbursements($filters, $page, $perPage);
            // Handle if requested page exceeds total_pages and total_pages > 0
            if ($page > $data['total_pages'] && $data['total_pages'] > 0) {
                $page = (int) $data['total_pages'];
                $data = $this->reimbursementRepo->getReimbursements($filters, $page, $perPage);
            }
        } catch (Throwable $e) {
            Logger::error('Error loading reimbursement history: ' . $e->getMessage(), [
                'exception' => $e,
                'filters' => $filters,
            ]);
            $errorMessage = 'Terjadi kesalahan sistem saat memuat data riwayat pembayaran reimbursement.';
            $data = [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0,
            ];
        }

        return View::render('reimbursements/history', [
            'title' => 'Riwayat Pembayaran Reimbursement (Lunas)',
            'reimbursements' => $data['items'],
            'items' => $data['items'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'perPage' => $perPage,
            'filters' => $filters,
            'user' => $user,
            'errorMessage' => $errorMessage,
        ]);
    }
}
