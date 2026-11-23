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

        $userRole = $user['role_name'] ?? '';
        $userPerms = $user['permissions'] ?? [];
        $allowedRoles = ['ADMIN', 'ADMIN_EXPENSE'];
        $hasManagePerm = in_array('expenses.manage', $userPerms, true) || in_array('expense.manage', $userPerms, true);

        if (!in_array($userRole, $allowedRoles, true) && !$hasManagePerm) {
            throw new RuntimeException('Peran Anda tidak diizinkan untuk membuat laporan pengeluaran. Pembuatan laporan hanya diizinkan untuk Admin Expense.');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidArgumentException('Tanggal awal laporan tidak boleh melewati tanggal akhir.');
        }

        // Resolve approver_id: specific manager selection or default manager
        $approverId = !empty($data['approver_id']) ? (int) $data['approver_id'] : null;
        if (!$approverId) {
            $defaultApproverStmt = Database::pdo()->query("
                SELECT u.user_id 
                FROM users u 
                JOIN roles r ON (r.role_id = u.role_id OR r.id_role = u.role_id)
                WHERE (r.role_name IN ('MANAGER', 'MANAGER_EXPENSE') OR u.username = 'rina_manager'
                       OR EXISTS (SELECT 1 FROM role_permissions rp JOIN permissions p ON p.permission_id = rp.permission_id WHERE rp.role_id = u.role_id AND p.permission_key = 'expense.approve'))
                  AND u.deleted_at IS NULL
                ORDER BY u.user_id ASC
                LIMIT 1
            ");
            $approverId = (int) ($defaultApproverStmt->fetchColumn() ?: 2);
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
        }

        $primaryEmployeeId = !empty($employeeIds) ? $employeeIds[0] : null;

        return Database::transaction(function () use ($data, $primaryEmployeeId, $employeeIds, $title, $startDate, $endDate, $userId, $approverId) {
            $reportId = $this->reportRepo->create([
                'report_number' => !empty($data['report_number']) ? trim($data['report_number']) : null,
                'employee_id' => $primaryEmployeeId,
                'created_by' => $userId,
                'approver_id' => $approverId,
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
        $isActive = ($emp && (($emp['status'] ?? 'ACTIVE') === 'ACTIVE' || !empty($emp['is_active'])));
        if (!$emp || !$isActive) {
            throw new InvalidArgumentException('Karyawan tidak ditemukan atau sedang tidak aktif.');
        }

        $existingList = $this->reportRepo->getReportEmployees($reportId);
        foreach ($existingList as $item) {
            if ((int) ($item['employee_id'] ?? 0) === $employeeId) {
                throw new InvalidArgumentException('Karyawan ini sudah terdaftar dalam laporan yang sama.');
            }
        }

        $repEmpId = $this->reportRepo->addReportEmployee($reportId, $employeeId);

        $this->auditRepo->logActivity(
            (int) ($user['user_id'] ?? 0),
            'REPORT_EMPLOYEE_ADDED',
            'expense_report_employees',
            $repEmpId,
            sprintf('Menambahkan karyawan %s (%s) ke laporan %s', $emp['full_name'], $emp['employee_number'], $report['report_number'])
        );

        return $repEmpId;
    }

    public function removeEmployeeFromReport(int $reportId, int $reportEmployeeId, array $user): bool
    {
        $report = $this->reportRepo->findById($reportId);
        if (!$report || $report['status'] !== 'DRAFT') {
            throw new RuntimeException('Karyawan hanya dapat dihapus dari laporan berstatus DRAFT.');
        }

        $ok = $this->reportRepo->removeReportEmployee($reportId, $reportEmployeeId);

        $this->auditRepo->logActivity(
            (int) ($user['user_id'] ?? 0),
            'REPORT_EMPLOYEE_REMOVED',
            'expense_report_employees',
            $reportEmployeeId,
            sprintf('Menghapus data peserta karyawan #%d dari laporan %s', $reportEmployeeId, $report['report_number'])
        );

        return $ok;
    }

    public function getReportEmployees(int $reportId): array
    {
        return $this->reportRepo->getReportEmployees($reportId);
    }

    public function addItem(int $reportId, int $expenseId, array $user, ?int $reportEmployeeId = null): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $expenseId, $user, $reportEmployeeId) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'DRAFT') {
                throw new RuntimeException('Item hanya dapat ditambahkan pada laporan berstatus DRAFT.');
            }

            $expense = $this->expenseRepo->findById($expenseId, true);
            if (!$expense) {
                throw new RuntimeException('Data pengeluaran tidak ditemukan.');
            }
            if ($expense['status'] !== 'DRAFT') {
                throw new RuntimeException('Pengeluaran harus berstatus DRAFT untuk dimasukkan ke laporan.');
            }

            // Check if already in another report
            $chk = $pdo->prepare('SELECT report_id FROM expense_report_items WHERE expense_id = :e_id');
            $chk->execute(['e_id' => $expenseId]);
            $existingRep = $chk->fetchColumn();
            if ($existingRep && (int) $existingRep !== $reportId) {
                throw new RuntimeException('Pengeluaran ini sudah terdaftar pada laporan lain (# ' . $existingRep . ').');
            }

            // Resolve & strictly validate report_employee_id (Perbaikan 5)
            $resolvedRepEmpId = $reportEmployeeId;
            if ($resolvedRepEmpId) {
                $chkRepEmp = $pdo->prepare('SELECT employee_id FROM expense_report_employees WHERE report_employee_id = :rep_emp_id AND report_id = :r_id');
                $chkRepEmp->execute(['rep_emp_id' => $resolvedRepEmpId, 'r_id' => $reportId]);
                $repEmpOwnerId = $chkRepEmp->fetchColumn();
                if (!$repEmpOwnerId) {
                    throw new RuntimeException('Data peserta karyawan tidak valid atau tidak terdaftar dalam laporan ini.');
                }
                if ((int) $repEmpOwnerId !== (int) $expense['employee_id']) {
                    throw new RuntimeException('Karyawan pemilik pengeluaran tidak sesuai dengan peserta laporan yang dipilih.');
                }
            } elseif (!empty($expense['employee_id'])) {
                $chkEmp = $pdo->prepare('SELECT report_employee_id FROM expense_report_employees WHERE report_id = :r_id AND employee_id = :e_id');
                $chkEmp->execute(['r_id' => $reportId, 'e_id' => $expense['employee_id']]);
                $foundRepEmp = $chkEmp->fetchColumn();
                if ($foundRepEmp) {
                    $resolvedRepEmpId = (int) $foundRepEmp;
                } else {
                    $resolvedRepEmpId = $this->reportRepo->addReportEmployee($reportId, (int) $expense['employee_id']);
                }
            } else {
                throw new RuntimeException('Pengeluaran harus terhubung dengan data karyawan yang valid.');
            }

            $ok = $this->reportRepo->addItem($reportId, $expenseId, $resolvedRepEmpId);

            // Recalculate total amount
            $total = $this->reportRepo->recalculateTotal($reportId);
            $stmt = $pdo->prepare('UPDATE expense_reports SET total_amount = :total WHERE report_id = :id');
            $stmt->execute(['total' => $total, 'id' => $reportId]);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_ITEM_ADDED',
                'expense_report_items',
                $expenseId,
                sprintf('Menambahkan transaksi %s ke laporan %s', $expense['expense_number'], $report['report_number'])
            );

            return $ok;
        });
    }

    public function removeItem(int $reportId, int $expenseId, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $expenseId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report || $report['status'] !== 'DRAFT') {
                throw new RuntimeException('Item hanya dapat dihapus dari laporan berstatus DRAFT.');
            }

            $ok = $this->reportRepo->removeItem($reportId, $expenseId);

            // Recalculate total
            $total = $this->reportRepo->recalculateTotal($reportId);
            $stmt = $pdo->prepare('UPDATE expense_reports SET total_amount = :total WHERE report_id = :id');
            $stmt->execute(['total' => $total, 'id' => $reportId]);

            $this->auditRepo->logActivity(
                (int) ($user['user_id'] ?? 0),
                'REPORT_ITEM_REMOVED',
                'expense_report_items',
                $expenseId,
                sprintf('Menghapus transaksi #%d dari laporan %s', $expenseId, $report['report_number'])
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

            // Strict verification on each item and participant consistency (Perbaikan 5)
            $seenExpenseIds = [];
            foreach ($items as $item) {
                $expId = (int) $item['expense_id'];
                if (isset($seenExpenseIds[$expId])) {
                    throw new RuntimeException(sprintf('Terdeteksi transaksi duplikat %s dalam laporan.', $item['expense_number']));
                }
                $seenExpenseIds[$expId] = true;

                $itemEmpId = (int) ($item['employee_id'] ?? 0);
                if (!in_array($itemEmpId, $registeredEmployeeIds, true)) {
                    throw new RuntimeException('Terdapat item pengeluaran yang bukan milik karyawan terdaftar dalam laporan.');
                }

                // Verify that the linked report_employee_id matches the expense's employee_id
                $chkMatch = $pdo->prepare('
                    SELECT employee_id FROM expense_report_employees 
                    WHERE report_employee_id = :rep_emp_id AND report_id = :report_id
                ');
                $chkMatch->execute([
                    'rep_emp_id' => (int) $item['report_employee_id'],
                    'report_id' => $reportId,
                ]);
                $matchedEmpId = $chkMatch->fetchColumn();
                if (!$matchedEmpId || (int) $matchedEmpId !== $itemEmpId) {
                    throw new RuntimeException(sprintf(
                        'Integritas data tidak valid: Transaksi %s tidak terhubung dengan peserta karyawan yang tepat.',
                        $item['expense_number']
                    ));
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
                sprintf('Mengajukan laporan %s sebesar Rp %s (%d karyawan, %d item)', $report['report_number'], number_format((float) $totalAmount, 0, ',', '.'), count($reportEmployees), count($items))
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

            // Anti self-approval & manager role authorization check (Segregation of Duties)
            $authService = new AuthorizationService();
            if (!$authService->canDecideReport($user, $report)) {
                throw new RuntimeException('Anda tidak memiliki wewenang untuk menyetujui laporan ini (Segregation of Duties).');
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

            // Anti self-approval & manager role authorization check (Segregation of Duties)
            $authService = new AuthorizationService();
            if (!$authService->canDecideReport($user, $report)) {
                throw new RuntimeException('Anda tidak memiliki wewenang untuk menolak laporan ini (Segregation of Duties).');
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
                throw new RuntimeException('Hanya laporan berstatus REFUSED yang dapat direvisi kembali ke DRAFT.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Update report to DRAFT
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "DRAFT"
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // Update linked expenses to DRAFT
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "DRAFT"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            $this->reportRepo->addApproval($reportId, $userId, 'DRAFT', 'Laporan dikembalikan ke status Draft untuk perbaikan.');

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

            // 2. Update report to REIMBURSEMENT_PENDING (seamless queue entry upon posting)
            $stmt = $pdo->prepare('
                UPDATE expense_reports
                SET status = "REIMBURSEMENT_PENDING",
                    posted_at = NOW()
                WHERE report_id = :id
            ');
            $stmt->execute(['id' => $reportId]);

            // 3. Update linked expenses to REIMBURSEMENT_PENDING
            $stmtExp = $pdo->prepare('
                UPDATE expenses
                SET status = "REIMBURSEMENT_PENDING"
                WHERE expense_id IN (
                    SELECT expense_id FROM expense_report_items WHERE report_id = :report_id
                )
            ');
            $stmtExp->execute(['report_id' => $reportId]);

            // 4. Create EXACTLY ONE consolidated reimbursement record for the entire report (status PENDING)
            $reimbId = $this->reimbursementRepo->createConsolidated([
                'report_id' => $reportId,
                'amount' => (float) $report['total_amount'],
                'status' => 'PENDING',
                'notes' => 'Antrean reimbursement gabungan laporan ' . $report['report_number'],
            ]);

            $this->reportRepo->addApproval($reportId, $userId, 'POSTED', sprintf('Laporan telah dibukukan oleh tim Keuangan (Ref: %s) dan otomatis masuk antrean pembayaran reimbursement.', $finalJournalRef));

            $this->auditRepo->logActivity(
                $userId,
                'REPORT_POSTED',
                'expense_reports',
                $reportId,
                sprintf('Membukukan laporan %s dengan jurnal %s dan memasukkan ke antrean pembayaran reimbursement #%d', $report['report_number'], $finalJournalRef, $reimbId)
            );

            return true;
        });
    }

    public function queueReimbursement(int $reportId, array $user): int
    {
        return Database::transaction(function (PDO $pdo) use ($reportId, $user) {
            $report = $this->reportRepo->findById($reportId, true);
            if (!$report) {
                throw new RuntimeException('Laporan tidak ditemukan.');
            }

            // Idempotent: If already REIMBURSEMENT_PENDING or PAID, return existing reimbursement
            $stmtFind = $pdo->prepare('SELECT reimbursement_id FROM reimbursements WHERE report_id = :r');
            $stmtFind->execute(['r' => $reportId]);
            $existingReimbId = $stmtFind->fetchColumn();

            if ($report['status'] === 'REIMBURSEMENT_PENDING' && $existingReimbId) {
                return (int) $existingReimbId;
            }

            if ($report['status'] === 'APPROVED') {
                throw new RuntimeException('Laporan belum dibukukan. Pembukuan (posting) wajib dilakukan terlebih dahulu sebelum reimbursement.');
            }

            if (!in_array($report['status'], ['POSTED', 'REIMBURSEMENT_PENDING'], true)) {
                throw new RuntimeException('Laporan harus berstatus POSTED sebelum dimasukkan ke antrean reimbursement.');
            }

            $userId = (int) ($user['user_id'] ?? 0);

            // Create or get single consolidated reimbursement
            $reimbId = $this->reimbursementRepo->createConsolidated([
                'report_id' => $reportId,
                'amount' => (float) $report['total_amount'],
                'status' => 'PENDING',
                'notes' => 'Antrean reimbursement gabungan laporan ' . $report['report_number'],
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

            $this->reportRepo->addApproval($reportId, $userId, 'REIMBURSEMENT_PENDING', 'Dimasukkan ke antrean pembayaran reimbursement satu laporan penuh.');

            $this->auditRepo->logActivity(
                $userId,
                'REIMBURSEMENT_QUEUED',
                'reimbursements',
                $reimbId,
                sprintf('Membuat antrean reimbursement satu laporan untuk %s', $report['report_number'])
            );

            return $reimbId;
        });
    }

    public function recordReimbursementPayment(int $reimbursementId, array $paymentData, array $user, ?array $proofFile = null): int
    {
        $uploadedFilePath = null;
        try {
            return Database::transaction(function (PDO $pdo) use ($reimbursementId, $paymentData, $user, $proofFile, &$uploadedFilePath) {
                $reimbursement = $this->reimbursementRepo->findById($reimbursementId, true);
                if (!$reimbursement) {
                    throw new RuntimeException('Data reimbursement tidak ditemukan.');
                }

                // Prioritas 4: Direct payment on FAILED status is strictly forbidden
                if ($reimbursement['status'] === 'FAILED') {
                    throw new RuntimeException('Reimbursement yang gagal harus melalui proses retry terlebih dahulu sebelum dapat dicairkan kembali.');
                }

                if (!in_array($reimbursement['status'], ['PENDING', 'PROCESSING'], true)) {
                    throw new RuntimeException('Reimbursement hanya dapat dibayarkan jika berstatus PENDING atau PROCESSING.');
                }

                $reportId = (int) $reimbursement['report_id'];
                $report = $this->reportRepo->findById($reportId, true);
                if (!$report) {
                    throw new RuntimeException('Laporan terkait tidak ditemukan.');
                }

                $userId = (int) ($user['user_id'] ?? 0);

                // Prioritas 3: Mandatory payment proof validation
                $proofPath = !empty($paymentData['proof_path']) ? $paymentData['proof_path'] : null;
                if ($proofFile && !empty($proofFile['name'])) {
                    if ($proofFile['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Gagal mengunggah bukti pembayaran. Kode error: ' . $proofFile['error']);
                    }
                    if ($proofFile['size'] > 10 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran berkas bukti pembayaran maksimal 10MB.');
                    }

                    $ext = strtolower(pathinfo($proofFile['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (!in_array($ext, $allowedExts, true)) {
                        throw new RuntimeException('Format berkas bukti pembayaran tidak valid. Hanya file PDF, JPG, JPEG, dan PNG yang diperbolehkan.');
                    }

                    // Strict MIME validation
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $proofFile['tmp_name']) : null;
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];
                    if (!$mime || !in_array($mime, $allowedMimes, true)) {
                        throw new RuntimeException('Tipe konten berkas tidak valid. Pastikan file adalah gambar atau dokumen PDF yang valid.');
                    }

                    $uploadDir = dirname(__DIR__, 2) . '/storage/receipts';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $storedName = 'proof_' . bin2hex(random_bytes(16)) . '.' . $ext;
                    $dest = $uploadDir . '/' . $storedName;
                    if (!move_uploaded_file($proofFile['tmp_name'], $dest)) {
                        throw new RuntimeException('Gagal memindahkan berkas bukti transfer ke direktori penyimpanan.');
                    }
                    $uploadedFilePath = $dest;
                    $proofPath = 'receipts/' . $storedName;
                }

                if (empty($proofPath)) {
                    throw new RuntimeException('Bukti transfer / pembayaran penuh wajib diunggah.');
                }

                $paymentData['proof_path'] = $proofPath;

                // Record payment in reimbursement repository (enforces full payment)
                $paymentId = $this->reimbursementRepo->recordPayment($reimbursementId, $paymentData, $userId);

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
                    sprintf('Pembayaran reimbursement satu laporan penuh telah diselesaikan (LUNAS). Ref: %s', $payRef)
                );

                $this->auditRepo->logActivity(
                    $userId,
                    'REIMBURSEMENT_PAYMENT_RECORDED',
                    'reimbursements',
                    $reimbursementId,
                    sprintf('Mencatat pembayaran penuh reimbursement #%d senilai Rp %s untuk laporan %s', $reimbursementId, number_format((float) ($paymentData['amount'] ?? $report['total_amount']), 0, ',', '.'), $report['report_number'])
                );

                return $paymentId;
            });
        } catch (\Throwable $e) {
            // Cleanup newly uploaded file on failure to avoid orphan files (Perbaikan 8)
            if ($uploadedFilePath && file_exists($uploadedFilePath)) {
                @unlink($uploadedFilePath);
            }
            throw $e;
        }
    }

    public function markPaid(int $reimbursementId, array $paymentDetails, array $user): bool
    {
        $reimb = $this->reimbursementRepo->findById($reimbursementId);
        if (!$reimb) {
            throw new RuntimeException('Data reimbursement tidak ditemukan.');
        }

        $approvedAmount = (string) ($reimb['approved_amount'] ?? $reimb['amount'] ?? '0.00');
        $paymentData = [
            'amount' => $approvedAmount,
            'recipient_name' => $paymentDetails['recipient_name'] ?? ($reimb['employee_name'] ?? null),
            'bank_name' => $paymentDetails['bank_name'] ?? ($reimb['bank_name'] ?? null),
            'account_number' => $paymentDetails['account_number'] ?? ($reimb['account_number'] ?? null),
            'account_holder' => $paymentDetails['account_holder'] ?? ($reimb['account_holder'] ?? null),
            'payment_method' => $paymentDetails['payment_method'] ?? 'BANK_TRANSFER',
            'bank_reference' => $paymentDetails['payment_reference'] ?? ($paymentDetails['bank_reference'] ?? null),
            'payment_date' => $paymentDetails['payment_date'] ?? date('Y-m-d'),
            'notes' => $paymentDetails['notes'] ?? null,
            'proof_path' => $paymentDetails['proof_path'] ?? null,
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

    public function retryReimbursement(int $reimbursementId, array $user): bool
    {
        return Database::transaction(function (PDO $pdo) use ($reimbursementId, $user) {
            $stmt = $pdo->prepare('SELECT * FROM reimbursements WHERE reimbursement_id = :id FOR UPDATE');
            $stmt->execute(['id' => $reimbursementId]);
            $reimbursement = $stmt->fetch();

            if (!$reimbursement) {
                throw new RuntimeException('Data reimbursement tidak ditemukan.');
            }
            if ($reimbursement['status'] !== 'FAILED') {
                throw new RuntimeException('Hanya reimbursement dengan status FAILED (gagal) yang dapat diulang (retry).');
            }

            $userId = (int) ($user['user_id'] ?? 0);
            $stmtUp = $pdo->prepare('
                UPDATE reimbursements
                SET status = "PROCESSING",
                    notes = "Pembayaran diulang (retry) oleh Finance",
                    processed_by = :uid,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE reimbursement_id = :id
            ');
            $ok = $stmtUp->execute(['uid' => $userId, 'id' => $reimbursementId]);

            if ($ok) {
                $this->reportRepo->addApproval(
                    (int) $reimbursement['report_id'],
                    $userId,
                    'RETRY',
                    'Percobaan ulang proses pencairan dana reimbursement oleh Finance.'
                );
                $this->auditRepo->logActivity(
                    $userId,
                    'REIMBURSEMENT_RETRY',
                    'reimbursements',
                    $reimbursementId,
                    sprintf('Mengulang proses reimbursement #%d setelah gagal sebelumnya', $reimbursementId)
                );
            }

            return $ok;
        });
    }

    public function markFailed(int $reimbursementId, string $reason, array $user, ?string $paymentRef = null): bool
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '' || mb_strlen($cleanReason) < 5 || strcasecmp($cleanReason, 'Pembayaran gagal') === 0) {
            throw new InvalidArgumentException('Alasan kegagalan pembayaran wajib diisi secara jelas (minimal 5 karakter).');
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return Database::transaction(function (PDO $pdo) use ($reimbursementId, $cleanReason, $userId, $paymentRef) {
            $stmt = $pdo->prepare('SELECT * FROM reimbursements WHERE reimbursement_id = :id FOR UPDATE');
            $stmt->execute(['id' => $reimbursementId]);
            $reimbursement = $stmt->fetch();

            if (!$reimbursement || !in_array($reimbursement['status'], ['PENDING', 'PROCESSING'], true)) {
                throw new RuntimeException('Reimbursement gagal hanya dapat dicatat jika belum berstatus PAID/CANCELLED.');
            }

            $reportId = (int) $reimbursement['report_id'];

            $ok = $this->reimbursementRepo->updateStatus($reimbursementId, 'FAILED', $userId, [
                'notes' => $cleanReason,
                'payment_reference' => $paymentRef,
            ]);

            if ($ok) {
                $this->reportRepo->addApproval(
                    $reportId,
                    $userId,
                    'FAILED',
                    sprintf('Pembayaran reimbursement ditandai gagal: %s', $cleanReason)
                );
                $this->auditRepo->logActivity(
                    $userId,
                    'REIMBURSEMENT_FAILED',
                    'reimbursements',
                    $reimbursementId,
                    sprintf('Menandai kegagalan pembayaran reimbursement #%d dengan alasan: %s', $reimbursementId, $cleanReason)
                );
            }

            return $ok;
        });
    }
}
