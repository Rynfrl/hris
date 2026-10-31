<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\ReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\ReimbursementRepository;
use App\Repositories\AuditRepository;
use InvalidArgumentException;
use RuntimeException;
use PDO;

class ReportWorkflowService
{
    private ReportRepository $reportRepo;
    private ExpenseRepository $expenseRepo;
    private EmployeeRepository $employeeRepo;
    private ReimbursementRepository $reimbursementRepo;
    private AuditRepository $auditRepo;

    public function __construct(
        ?ReportRepository $reportRepo = null,
        ?ExpenseRepository $expenseRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?ReimbursementRepository $reimbursementRepo = null,
        ?AuditRepository $auditRepo = null
    ) {
        $this->reportRepo = $reportRepo ?? new ReportRepository();
        $this->expenseRepo = $expenseRepo ?? new ExpenseRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->reimbursementRepo = $reimbursementRepo ?? new ReimbursementRepository();
        $this->auditRepo = $auditRepo ?? new AuditRepository();
    }

    public function createReport(array $data, array $user): int
    {
        $title = trim($data['title'] ?? '');
        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
            throw new InvalidArgumentException('Judul laporan wajib diisi antara 3 hingga 150 karakter.');
        }

        if (($user['role_name'] ?? '') === 'MANAGER') {
            throw new RuntimeException('Peran Manajer tidak diizinkan untuk membuat laporan pengeluaran.');
        }

        $empId = (int) ($user['employee_id'] ?? 0);
        if ($empId <= 0) {
            throw new InvalidArgumentException('Akun Anda tidak memiliki data karyawan yang valid untuk membuat laporan.');
        }

        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidArgumentException('Tanggal awal laporan tidak boleh melewati tanggal akhir.');
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return Database::transaction(function () use ($data, $empId, $title, $startDate, $endDate, $userId) {
            $reportId = $this->reportRepo->create([
                'employee_id' => $empId,
                'title' => $title,
                'description' => !empty($data['description']) ? trim($data['description']) : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_CREATED',
                'expense_reports',
                $reportId,
                'Membuat laporan biaya baru: ' . $title
            );

            return $reportId;
        });
    }

    public function updateReport(int $reportId, array $data, array $user): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Laporan hanya dapat diedit ketika berstatus DRAFT.');
        }

        $title = trim($data['title'] ?? '');
        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
            throw new InvalidArgumentException('Judul laporan wajib diisi antara 3 hingga 150 karakter.');
        }

        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidArgumentException('Tanggal awal laporan tidak boleh melewati tanggal akhir.');
        }

        return $this->reportRepo->update($reportId, [
            'title' => $title,
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function addItem(int $reportId, int $expenseId, array $user): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Item hanya dapat ditambahkan pada laporan berstatus DRAFT.');
        }

        $expense = $this->expenseRepo->findById($expenseId);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Pengeluaran harus berstatus DRAFT.');
        }

        if ((int) $expense['employee_id'] !== (int) $report['employee_id']) {
            throw new RuntimeException('Pengeluaran harus milik karyawan yang sama dengan pemilik laporan.');
        }

        // Verify expense is not already linked to another report
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT COUNT(*) FROM expense_report_items WHERE expense_id = :id');
        $chk->execute(['id' => $expenseId]);
        if ((int) $chk->fetchColumn() > 0) {
            throw new RuntimeException('Pengeluaran ini sudah terdaftar pada laporan lain.');
        }

        return Database::transaction(function () use ($reportId, $expenseId, $user, $report, $expense) {
            $ok = $this->reportRepo->addItem($reportId, $expenseId);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_ITEM_ADDED',
                'expense_reports',
                $reportId,
                sprintf('Menambahkan item %s ke laporan %s', $expense['expense_number'], $report['report_number'])
            );

            return $ok;
        });
    }

    public function removeItem(int $reportId, int $expenseId, array $user): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Item hanya dapat dilepas dari laporan berstatus DRAFT.');
        }

        return Database::transaction(function () use ($reportId, $expenseId, $user, $report) {
            $ok = $this->reportRepo->removeItem($reportId, $expenseId);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_ITEM_REMOVED',
                'expense_reports',
                $reportId,
                sprintf('Melepaskan item expense #%d dari laporan %s', $expenseId, $report['report_number'])
            );

            return $ok;
        });
    }

    public function submitReport(int $reportId, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'DRAFT') {
                throw new RuntimeException('Laporan hanya dapat diajukan jika saat ini berstatus DRAFT.');
            }

            $items = $this->reportRepo->getReportItems($reportId);
            if (empty($items)) {
                throw new RuntimeException('Laporan tidak dapat diajukan karena belum memiliki item pengeluaran.');
            }

            // Verify all items belong to same employee and are DRAFT
            foreach ($items as $item) {
                if ((int) $item['employee_id'] !== (int) $report['employee_id']) {
                    throw new RuntimeException('Terdapat item pengeluaran yang bukan milik karyawan bersangkutan.');
                }
                if ($item['status'] !== 'DRAFT') {
                    throw new RuntimeException(sprintf('Pengeluaran %s tidak berstatus DRAFT.', $item['expense_number']));
                }
            }

            // Sync total amount from actual sum
            $totalAmount = $this->reportRepo->recalculateTotal($reportId);
            if ($totalAmount <= 0) {
                throw new RuntimeException('Total nominal laporan harus lebih besar dari Rp 0.');
            }

            // Update report status
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "SUBMITTED",
                    submitted_at = NOW(),
                    total_amount = :total
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId, 'total' => $totalAmount]);

            // Update linked expenses status to SUBMITTED
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "SUBMITTED"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            $userId = (int) ($user['user_id'] ?? 0);

            // Record workflow action
            $this->reportRepo->addApproval($reportId, $userId, 'SUBMITTED', 'Laporan diajukan untuk persetujuan manajer.');

            // Activity & Audit log
            $this->auditRepo->logActivity(
                $userId,
                'REPORT_SUBMITTED',
                'expense_reports',
                $reportId,
                sprintf('Mengajukan laporan %s sebesar Rp %s', $report['report_number'], number_format($totalAmount, 0, ',', '.'))
            );

            return true;
        });
    }

    public function approveReport(int $reportId, ?string $comment, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $comment, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'SUBMITTED') {
                throw new RuntimeException('Laporan hanya dapat disetujui jika berstatus SUBMITTED.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Update report to APPROVED
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "APPROVED",
                    approved_at = NOW(),
                    refused_at = NULL,
                    rejection_reason = NULL
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Update linked expenses to APPROVED
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "APPROVED",
                    approved_by = :approved_by,
                    approved_at = NOW(),
                    rejected_by = NULL,
                    rejected_at = NULL,
                    rejection_reason = NULL
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['approved_by' => $userId, 'report_id' => $reportId]);

            // Record approval
            $this->reportRepo->addApproval($reportId, $userId, 'APPROVED', $comment ?: 'Laporan disetujui.');

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_APPROVED',
                'expense_reports',
                $reportId,
                sprintf('Menyetujui laporan %s', $report['report_number'])
            );

            return true;
        });
    }

    public function refuseReport(int $reportId, string $reason, array $user): bool
    {
        $cleanReason = trim($reason);
        if (mb_strlen($cleanReason) < 5 || mb_strlen($cleanReason) > 2000) {
            throw new InvalidArgumentException('Alasan penolakan wajib diisi antara 5 hingga 2.000 karakter.');
        }

        return Database::transaction(function (PDO $pdo) use ($reportId, $cleanReason, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'SUBMITTED') {
                throw new RuntimeException('Laporan hanya dapat ditolak jika berstatus SUBMITTED.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Update report to REFUSED
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "REFUSED",
                    refused_at = NOW(),
                    rejection_reason = :reason
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId, 'reason' => $cleanReason]);

            // Update linked expenses to REFUSED
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "REFUSED",
                    rejected_by = :rejected_by,
                    rejected_at = NOW(),
                    rejection_reason = :reason
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute([
                'rejected_by' => $userId,
                'reason' => $cleanReason,
                'report_id' => $reportId,
            ]);

            // Record refusal in approvals history
            $this->reportRepo->addApproval($reportId, $userId, 'REFUSED', $cleanReason);

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_REFUSED',
                'expense_reports',
                $reportId,
                sprintf('Menolak laporan %s dengan alasan: %s', $report['report_number'], $cleanReason)
            );

            return true;
        });
    }

    public function reviseReport(int $reportId, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'REFUSED') {
                throw new RuntimeException('Hanya laporan berstatus DITOLAK (REFUSED) yang dapat direvisi kembali ke DRAFT.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Revert report to DRAFT, clearing active rejection flags
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "DRAFT",
                    refused_at = NULL,
                    rejection_reason = NULL
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Revert linked expenses to DRAFT
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "DRAFT",
                    rejected_by = NULL,
                    rejected_at = NULL,
                    rejection_reason = NULL
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            // Keep historical decision in approvals and add revision record
            $this->reportRepo->addApproval($reportId, $userId, 'RETURNED_TO_DRAFT', 'Laporan dikembalikan ke status draft untuk revisi.');

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_REVISED',
                'expense_reports',
                $reportId,
                sprintf('Mengembalikan laporan %s ke draft untuk revisi', $report['report_number'])
            );

            return true;
        });
    }

    public function postReport(int $reportId, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'APPROVED') {
                throw new RuntimeException('Laporan hanya dapat dibukukan (POSTED) jika berstatus APPROVED.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Update report to POSTED
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "POSTED",
                    posted_at = NOW()
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Update linked expenses to POSTED
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "POSTED"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            $this->reportRepo->addApproval($reportId, $userId, 'POSTED', 'Laporan telah dibukukan oleh tim Keuangan.');

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_POSTED',
                'expense_reports',
                $reportId,
                sprintf('Membukukan laporan %s', $report['report_number'])
            );

            return true;
        });
    }

    public function queueReimbursement(int $reportId, array $user): int
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'POSTED') {
                throw new RuntimeException('Laporan harus berstatus POSTED sebelum dimasukkan ke antrean reimbursement.');
            }

            // Check if reimbursement record already exists
            $existing = $this->reimbursementRepo->findByReportId($reportId);
            if ($existing) {
                throw new RuntimeException('Laporan ini sudah memiliki data reimbursement.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Create reimbursement
            $reimbursementId = $this->reimbursementRepo->create([
                'report_id' => $reportId,
                'employee_id' => (int) $report['employee_id'],
                'amount' => (float) $report['total_amount'],
                'payment_method' => 'BANK_TRANSFER',
                'status' => 'PENDING',
                'notes' => 'Antrean reimbursement laporan ' . $report['report_number'],
            ]);

            // Update report to REIMBURSEMENT_PENDING
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "REIMBURSEMENT_PENDING"
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Update linked expenses to REIMBURSEMENT_PENDING
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "REIMBURSEMENT_PENDING"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            $this->reportRepo->addApproval($reportId, $userId, 'REIMBURSEMENT_PENDING', 'Dimasukkan ke antrean pembayaran reimbursement.');

            $this->auditRepo->logActivity(
                $userId,
                'REIMBURSEMENT_QUEUED',
                'reimbursements',
                $reimbursementId,
                sprintf('Membuat antrean reimbursement untuk laporan %s', $report['report_number'])
            );

            return $reimbursementId;
        });
    }

    public function markPaid(int $reimbursementId, array $paymentDetails, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reimbursementId, $paymentDetails, $user) {
            $reimbursement = $this->reimbursementRepo->findById($reimbursementId, true);
            if (!$reimbursement || !in_array($reimbursement['status'], ['PENDING', 'PROCESSING'], true)) {
                throw new RuntimeException('Reimbursement hanya dapat diselesaikan jika berstatus PENDING atau PROCESSING.');
            }

            $reportId = (int) $reimbursement['report_id'];
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report) {
                throw new RuntimeException('Laporan terkait tidak ditemukan.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Update reimbursement to PAID
            $this->reimbursementRepo->updateStatus($reimbursementId, 'PAID', $userId, $paymentDetails);

            // Update report to PAID
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "PAID",
                    paid_at = NOW()
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Update linked expenses to PAID
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "PAID",
                    paid_by = :paid_by,
                    paid_at = NOW()
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['paid_by' => $userId, 'report_id' => $reportId]);

            $payRef = $paymentDetails['payment_reference'] ?? '-';
            $this->reportRepo->addApproval(
                $reportId,
                $userId,
                'PAID',
                sprintf('Reimbursement telah dibayarkan. Ref: %s', $payRef)
            );

            $this->auditRepo->logActivity(
                $userId,
                'REIMBURSEMENT_PAID',
                'reimbursements',
                $reimbursementId,
                sprintf('Menyelesaikan pembayaran reimbursement laporan %s (Rp %s)', $report['report_number'], number_format((float) $reimbursement['amount'], 0, ',', '.'))
            );

            return true;
        });
    }

    public function setProcessing(int $reimbursementId, array $user): bool
    {
        $reimbursement = $this->reimbursementRepo->findById($reimbursementId);
        if (!$reimbursement || $reimbursement['status'] !== 'PENDING') {
            throw new RuntimeException('Hanya reimbursement berstatus PENDING yang dapat diubah ke PROCESSING.');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return $this->reimbursementRepo->updateStatus($reimbursementId, 'PROCESSING', $userId);
    }

    public function markFailed(int $reimbursementId, string $reason, array $user): bool
    {
        $reimbursement = $this->reimbursementRepo->findById($reimbursementId);
        if (!$reimbursement || !in_array($reimbursement['status'], ['PENDING', 'PROCESSING'], true)) {
            throw new RuntimeException('Reimbursement gagal hanya dapat dicatat jika belum berstatus PAID/CANCELLED.');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return $this->reimbursementRepo->updateStatus($reimbursementId, 'FAILED', $userId, [
            'notes' => trim($reason),
        ]);
    }
}
