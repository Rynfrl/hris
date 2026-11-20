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

        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidArgumentException('Tanggal awal laporan tidak boleh melewati tanggal akhir.');
        }

        // Support employee_ids array (multi-employee) or single employee_id
        $employeeIds = [];
        if (!empty($data['employee_ids']) && is_array($data['employee_ids'])) {
            foreach ($data['employee_ids'] as $eid) {
                if ((int) $eid > 0) {
                    $employeeIds[] = (int) $eid;
                }
            }
        } elseif (!empty($data['employee_id'])) {
            $employeeIds[] = (int) $data['employee_id'];
        } elseif ($empId) {
            $employeeIds[] = $empId;
        }

        $primaryEmployeeId = !empty($employeeIds) ? $employeeIds[0] : $empId;

        return Database::transaction(function () use ($data, $primaryEmployeeId, $employeeIds, $title, $startDate, $endDate, $userId) {
            $reportId = $this->reportRepo->create([
                'employee_id' => $primaryEmployeeId,
                'created_by' => $userId,
                'title' => $title,
                'description' => !empty($data['description']) ? trim($data['description']) : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'employee_ids' => $employeeIds,
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

        return Database::transaction(function () use ($reportId, $data, $title, $startDate, $endDate, $user) {
            $ok = $this->reportRepo->update($reportId, [
                'title' => $title,
                'description' => !empty($data['description']) ? trim($data['description']) : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Sync employees if employee_ids passed
            if (isset($data['employee_ids']) && is_array($data['employee_ids'])) {
                $currentEmployees = $this->reportRepo->getReportEmployees($reportId);
                $currentEmpIds = array_column($currentEmployees, 'employee_id');

                $newEmpIds = array_map('intval', $data['employee_ids']);
                $newEmpIds = array_filter($newEmpIds, fn ($id) => $id > 0);

                // Add missing
                foreach ($newEmpIds as $newId) {
                    if (!in_array($newId, $currentEmpIds, true)) {
                        $this->reportRepo->addReportEmployee($reportId, $newId);
                    }
                }

                // Remove unselected if no items attached
                foreach ($currentEmployees as $cur) {
                    if (!in_array((int) $cur['employee_id'], $newEmpIds, true)) {
                        if ((int) $cur['item_count'] === 0) {
                            $this->reportRepo->removeReportEmployee($reportId, (int) $cur['report_employee_id']);
                        }
                    }
                }
            }

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_UPDATED',
                'expense_reports',
                $reportId,
                'Memperbarui data laporan: ' . $title
            );

            return $ok;
        });
    }

    public function addEmployeeToReport(int $reportId, int $employeeId, array $user): int
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Karyawan hanya dapat ditambahkan pada laporan berstatus DRAFT.');
        }

        $emp = $this->employeeRepo->findById($employeeId);
        if (!$emp || ($emp['status'] ?? '') !== 'ACTIVE') {
            throw new InvalidArgumentException('Karyawan tidak ditemukan atau tidak aktif.');
        }

        return Database::transaction(function () use ($reportId, $employeeId, $user, $report, $emp) {
            $repEmpId = $this->reportRepo->addReportEmployee($reportId, $employeeId);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_EMPLOYEE_ADDED',
                'expense_reports',
                $reportId,
                sprintf('Menambahkan karyawan %s (%s) ke laporan %s', $emp['full_name'], $emp['employee_number'], $report['report_number'])
            );

            return $repEmpId;
        });
    }

    public function removeEmployeeFromReport(int $reportId, int $reportEmployeeId, array $user): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Karyawan hanya dapat dihapus dari laporan berstatus DRAFT.');
        }

        return Database::transaction(function () use ($reportId, $reportEmployeeId, $user, $report) {
            $ok = $this->reportRepo->removeReportEmployee($reportId, $reportEmployeeId);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_EMPLOYEE_REMOVED',
                'expense_reports',
                $reportId,
                sprintf('Menghapus karyawan dari laporan %s', $report['report_number'])
            );

            return $ok;
        });
    }

    public function addItem(int $reportId, int $expenseId, array $user, ?int $reportEmployeeId = null): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Item hanya dapat ditambahkan pada laporan berstatus DRAFT.');
        }

        $expense = $this->expenseRepo->findById($expenseId);
        if (!$expense || $expense['status'] !== 'DRAFT') {
            throw new RuntimeException('Pengeluaran harus berstatus DRAFT.');
        }

        // Verify expense is not already linked to another report
        $pdo = Database::pdo();
        $chk = $pdo->prepare('SELECT COUNT(*) FROM expense_report_items WHERE expense_id = :id');
        $chk->execute(['id' => $expenseId]);
        if ((int) $chk->fetchColumn() > 0) {
            throw new RuntimeException('Pengeluaran ini sudah terdaftar pada laporan lain.');
        }

        $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
        $reportEmployeeIds = array_column($reportEmployees, 'employee_id');

        // Check if report has employees
        $expEmployeeId = (int) ($expense['employee_id'] ?? 0);
        if ($expEmployeeId > 0 && !empty($reportEmployeeIds) && !in_array($expEmployeeId, $reportEmployeeIds, true)) {
            // If single-employee legacy report and owner matches, add employee
            if (count($reportEmployeeIds) === 0) {
                $reportEmployeeId = $this->reportRepo->addReportEmployee($reportId, $expEmployeeId);
            } else {
                throw new RuntimeException('Pengeluaran harus milik karyawan yang terdaftar dalam laporan ini.');
            }
        }

        // Resolve reportEmployeeId if null
        if ($reportEmployeeId === null && $expEmployeeId > 0) {
            foreach ($reportEmployees as $re) {
                if ((int) $re['employee_id'] === $expEmployeeId) {
                    $reportEmployeeId = (int) $re['report_employee_id'];
                    break;
                }
            }
            if ($reportEmployeeId === null) {
                $reportEmployeeId = $this->reportRepo->addReportEmployee($reportId, $expEmployeeId);
            }
        }

        return Database::transaction(function () use ($reportId, $expenseId, $reportEmployeeId, $user, $report, $expense) {
            $ok = $this->reportRepo->addItem($reportId, $expenseId, $reportEmployeeId);

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

            $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
            if (empty($reportEmployees)) {
                throw new RuntimeException('Laporan tidak dapat diajukan karena belum memiliki karyawan terdaftar.');
            }
            $registeredEmployeeIds = array_column($reportEmployees, 'employee_id');

            // Verify all items belong to registered report employees and are DRAFT
            foreach ($items as $item) {
                if (!empty($item['employee_id']) && !in_array((int) $item['employee_id'], $registeredEmployeeIds, true)) {
                    throw new RuntimeException('Terdapat item pengeluaran yang bukan milik karyawan bersangkutan dalam laporan.');
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
                sprintf('Mengajukan laporan %s sebesar Rp %s (%d karyawan, %d item)', $report['report_number'], number_format($totalAmount, 0, ',', '.'), count($reportEmployees), count($items))
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

            // Record refusal in approvals history and comments
            $this->reportRepo->addApproval($reportId, $userId, 'REFUSED', $cleanReason);
            $this->reportRepo->addComment($reportId, $userId, 'Ditolak: ' . $cleanReason);

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

    public function postReport(int $reportId, array $user, ?string $journalRef = null, ?string $postingDate = null, ?string $notes = null): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $journalRef, $postingDate, $notes, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'APPROVED') {
                throw new RuntimeException('Laporan hanya dapat dibukukan (POSTED) jika berstatus APPROVED.');
            }

            // Check if already posted
            $existingPosting = $this->reportRepo->getPosting($reportId);
            if ($existingPosting) {
                throw new RuntimeException('Laporan ini sudah dibukukan sebelumnya.');
            }

            $userId = (int) ($user['user_id'] ?? 0);
            $finalJournalRef = !empty($journalRef) ? trim($journalRef) : $this->reportRepo->generateJournalReference();
            $finalPostingDate = !empty($postingDate) ? $postingDate : date('Y-m-d');

            // 1. Create posting record
            $this->reportRepo->createPosting($reportId, $finalJournalRef, $finalPostingDate, $userId, $notes);

            // 2. Update report to POSTED
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "POSTED",
                    posted_at = NOW()
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // 3. Update linked expenses to POSTED
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "POSTED"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            // 4. Automatically generate expense_reimbursements per employee in the report
            $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
            foreach ($reportEmployees as $repEmp) {
                $subtotal = (float) $repEmp['subtotal_amount'];
                if ($subtotal > 0) {
                    $this->reimbursementRepo->create([
                        'report_id' => $reportId,
                        'report_employee_id' => (int) $repEmp['report_employee_id'],
                        'employee_id' => (int) $repEmp['employee_id'],
                        'amount' => $subtotal,
                        'status' => 'PENDING',
                        'notes' => 'Antrean reimbursement karyawan ' . $repEmp['employee_name_snapshot'] . ' untuk laporan ' . $report['report_number'],
                    ]);
                }
            }

            $this->reportRepo->addApproval($reportId, $userId, 'POSTED', sprintf('Laporan telah dibukukan oleh tim Keuangan (Ref: %s).', $finalJournalRef));

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_POSTED',
                'expense_reports',
                $reportId,
                sprintf('Membukukan laporan %s dengan referensi jurnal %s', $report['report_number'], $finalJournalRef)
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

            // Check if reimbursement records already exist
            $existingList = $this->reimbursementRepo->getByReportId($reportId);
            if (!empty($existingList)) {
                // Return first reimbursement ID
                $firstId = (int) $existingList[0]['reimbursement_id'];
                // Update report to REIMBURSEMENT_PENDING if not already
                $pdo->prepare('UPDATE expense_reports SET status = "REIMBURSEMENT_PENDING" WHERE report_id = ?')->execute([$reportId]);
                return $firstId;
            }

            $userId = (int) ($user['user_id'] ?? 0);
            $reportEmployees = $this->reportRepo->getReportEmployees($reportId);
            $firstReimbId = 0;

            if (empty($reportEmployees) && !empty($report['employee_id'])) {
                // Fallback for single employee
                $firstReimbId = $this->reimbursementRepo->create([
                    'report_id' => $reportId,
                    'employee_id' => (int) $report['employee_id'],
                    'amount' => (float) $report['total_amount'],
                    'status' => 'PENDING',
                    'notes' => 'Antrean reimbursement laporan ' . $report['report_number'],
                ]);
            } else {
                foreach ($reportEmployees as $re) {
                    $rId = $this->reimbursementRepo->create([
                        'report_id' => $reportId,
                        'report_employee_id' => (int) $re['report_employee_id'],
                        'employee_id' => (int) $re['employee_id'],
                        'amount' => (float) $re['subtotal_amount'],
                        'status' => 'PENDING',
                        'notes' => 'Antrean reimbursement laporan ' . $report['report_number'],
                    ]);
                    if ($firstReimbId === 0) {
                        $firstReimbId = $rId;
                    }
                }
            }

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
                $firstReimbId,
                sprintf('Membuat antrean reimbursement untuk laporan %s', $report['report_number'])
            );

            return $firstReimbId;
        });
    }

    public function recordReimbursementPayment(int $reimbursementId, array $paymentData, array $user, ?array $proofFile = null): int
    {
        return Database::transaction(function (PDO $pdo) use ($reimbursementId, $paymentData, $user, $proofFile) {
            $reimbursement = $this->reimbursementRepo->findById($reimbursementId, true);
            if (!$reimbursement || !in_array($reimbursement['status'], ['PENDING', 'PROCESSING', 'PARTIALLY_PAID'], true)) {
                throw new RuntimeException('Reimbursement hanya dapat dibayarkan jika berstatus PENDING, PROCESSING, atau PARTIALLY_PAID.');
            }

            $reportId = (int) $reimbursement['report_id'];
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report) {
                throw new RuntimeException('Laporan terkait tidak ditemukan.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Handle proof file if provided
            $proofPath = null;
            if ($proofFile && !empty($proofFile['tmp_name']) && $proofFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . '/storage/receipts';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($proofFile['name'], PATHINFO_EXTENSION);
                $storedName = 'proof_' . bin2hex(random_bytes(16)) . '.' . strtolower($ext);
                $dest = $uploadDir . '/' . $storedName;
                if (move_uploaded_file($proofFile['tmp_name'], $dest)) {
                    $proofPath = 'receipts/' . $storedName;
                }
            }

            $paymentData['proof_path'] = $proofPath ?? ($paymentData['proof_path'] ?? null);

            // Record payment in reimbursement repository
            $paymentId = $this->reimbursementRepo->recordPayment($reimbursementId, $paymentData, $userId);

            // Check if all reimbursements for the report are fully PAID
            if ($this->reimbursementRepo->isReportFullyPaid($reportId)) {
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

                $payRef = $paymentData['bank_reference'] ?? $paymentData['payment_number'] ?? '-';
                $this->reportRepo->addApproval(
                    $reportId,
                    $userId,
                    'PAID',
                    sprintf('Seluruh reimbursement telah diselesaikan dan dibayarkan (LUNAS). Ref terakhir: %s', $payRef)
                );
            }

            $this->auditRepo->logActivity(
                $userId,
                'REIMBURSEMENT_PAYMENT_RECORDED',
                'expense_reimbursements',
                $reimbursementId,
                sprintf('Mencatat pembayaran reimbursement #%d senilai Rp %s untuk laporan %s', $reimbursementId, number_format((float) $paymentData['amount'], 0, ',', '.'), $report['report_number'])
            );

            return $paymentId;
        });
    }

    public function markPaid(int $reimbursementId, array $paymentDetails, array $user): bool
    {
        $reimb = $this->reimbursementRepo->findById($reimbursementId);
        if (!$reimb) {
            throw new RuntimeException('Data reimbursement tidak ditemukan.');
        }

        $remaining = (float) ($reimb['remaining_amount'] ?? $reimb['approved_amount'] ?? $reimb['amount'] ?? 0);
        $payAmount = !empty($paymentDetails['amount']) ? (float) $paymentDetails['amount'] : $remaining;

        $paymentData = [
            'amount' => $payAmount,
            'payment_method' => $paymentDetails['payment_method'] ?? 'BANK_TRANSFER',
            'bank_name' => $paymentDetails['bank_name'] ?? ($reimb['bank_name'] ?? null),
            'bank_reference' => $paymentDetails['payment_reference'] ?? ($paymentDetails['bank_reference'] ?? null),
            'payment_date' => $paymentDetails['payment_date'] ?? date('Y-m-d'),
            'notes' => $paymentDetails['notes'] ?? null,
        ];

        $this->recordReimbursementPayment($reimbursementId, $paymentData, $user);
        return true;
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
        if (!$reimbursement || !in_array($reimbursement['status'], ['PENDING', 'PROCESSING', 'PARTIALLY_PAID'], true)) {
            throw new RuntimeException('Reimbursement gagal hanya dapat dicatat jika belum berstatus PAID/CANCELLED.');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return $this->reimbursementRepo->updateStatus($reimbursementId, 'FAILED', $userId, [
            'notes' => trim($reason),
        ]);
    }
}
