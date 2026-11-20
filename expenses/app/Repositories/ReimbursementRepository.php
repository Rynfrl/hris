<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;
use PDO;

class ReimbursementRepository
{
    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $pdo = Database::pdo();
        // Check expense_reimbursements first
        $sql = '
            SELECT er.*,
                   er.approved_amount as amount,
                   (er.approved_amount - er.paid_amount) as remaining_amount,
                   r.report_number, r.title as report_title, r.total_amount as report_total,
                   COALESCE(ere.employee_name_snapshot, emp.full_name) as employee_name,
                   COALESCE(ere.employee_number_snapshot, emp.employee_number) as employee_number,
                   COALESCE(ere.department_name_snapshot, d.department_name) as department_name,
                   emp.bank_name, emp.bank_account
            FROM expense_reimbursements er
            JOIN expense_reports r ON r.report_id = er.report_id
            JOIN employees emp ON emp.employee_id = er.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            LEFT JOIN expense_report_employees ere ON ere.report_employee_id = er.report_employee_id
            WHERE er.reimbursement_id = :id
            LIMIT 1
        ';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Fallback to legacy reimbursements table if not found
        $sqlLegacy = '
            SELECT rem.*,
                   rem.amount as approved_amount,
                   CASE WHEN rem.status = "PAID" THEN rem.amount ELSE 0.00 END as paid_amount,
                   CASE WHEN rem.status = "PAID" THEN 0.00 ELSE rem.amount END as remaining_amount,
                   r.report_number, r.title as report_title, r.total_amount as report_total,
                   emp.full_name as employee_name, emp.employee_number, emp.bank_name, emp.bank_account,
                   u.username as processor_username
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            JOIN employees emp ON emp.employee_id = rem.employee_id
            LEFT JOIN users u ON u.user_id = rem.processed_by
            WHERE rem.reimbursement_id = :id
            LIMIT 1
        ';
        if ($forUpdate) {
            $sqlLegacy .= ' FOR UPDATE';
        }
        $stmtLegacy = $pdo->prepare($sqlLegacy);
        $stmtLegacy->execute(['id' => $id]);
        $legacyRow = $stmtLegacy->fetch();
        return $legacyRow ?: null;
    }

    public function findByReportId(int $reportId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT er.*, er.approved_amount as amount
            FROM expense_reimbursements er
            WHERE er.report_id = :report_id
            LIMIT 1
        ');
        $stmt->execute(['report_id' => $reportId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Fallback
        $stmtLegacy = $pdo->prepare('SELECT * FROM reimbursements WHERE report_id = :report_id LIMIT 1');
        $stmtLegacy->execute(['report_id' => $reportId]);
        $legacy = $stmtLegacy->fetch();
        return $legacy ?: null;
    }

    public function getByReportId(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT er.*,
                   (er.approved_amount - er.paid_amount) as remaining_amount,
                   COALESCE(ere.employee_name_snapshot, emp.full_name) as employee_name,
                   COALESCE(ere.employee_number_snapshot, emp.employee_number) as employee_number,
                   COALESCE(ere.department_name_snapshot, d.department_name) as department_name,
                   emp.bank_name, emp.bank_account
            FROM expense_reimbursements er
            LEFT JOIN expense_report_employees ere ON ere.report_employee_id = er.report_employee_id
            LEFT JOIN employees emp ON emp.employee_id = er.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            WHERE er.report_id = :report_id
            ORDER BY er.reimbursement_id ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        $results = $stmt->fetchAll();

        if (empty($results)) {
            // Check legacy
            $stmtLegacy = $pdo->prepare('
                SELECT rem.*,
                       rem.amount as approved_amount,
                       CASE WHEN rem.status = "PAID" THEN rem.amount ELSE 0.00 END as paid_amount,
                       CASE WHEN rem.status = "PAID" THEN 0.00 ELSE rem.amount END as remaining_amount,
                       emp.full_name as employee_name, emp.employee_number, emp.bank_name, emp.bank_account
                FROM reimbursements rem
                JOIN employees emp ON emp.employee_id = rem.employee_id
                WHERE rem.report_id = :report_id
            ');
            $stmtLegacy->execute(['report_id' => $reportId]);
            $results = $stmtLegacy->fetchAll();
        }

        return $results;
    }

    public function getReimbursements(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'er.status = :status';
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['report_id'])) {
            $where[] = 'er.report_id = :report_id';
            $params['report_id'] = (int) $filters['report_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(r.report_number LIKE :q OR emp.full_name LIKE :q OR ere.employee_name_snapshot LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM expense_reimbursements er
            JOIN expense_reports r ON r.report_id = er.report_id
            JOIN employees emp ON emp.employee_id = er.employee_id
            LEFT JOIN expense_report_employees ere ON ere.report_employee_id = er.report_employee_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT er.*,
                   er.approved_amount as amount,
                   (er.approved_amount - er.paid_amount) as remaining_amount,
                   r.report_number, r.title as report_title,
                   COALESCE(ere.employee_name_snapshot, emp.full_name) as employee_name,
                   COALESCE(ere.employee_number_snapshot, emp.employee_number) as employee_number,
                   COALESCE(ere.department_name_snapshot, d.department_name) as department_name,
                   emp.bank_name, emp.bank_account,
                   (SELECT COUNT(*) FROM expense_reimbursement_payments WHERE reimbursement_id = er.reimbursement_id) as payments_count
            FROM expense_reimbursements er
            JOIN expense_reports r ON r.report_id = er.report_id
            JOIN employees emp ON emp.employee_id = er.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            LEFT JOIN expense_report_employees ere ON ere.report_employee_id = er.report_employee_id
            WHERE {$whereClause}
            ORDER BY er.reimbursement_id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $reportId = (int) $data['report_id'];
        $employeeId = (int) $data['employee_id'];
        $amount = (float) $data['amount'];
        $status = $data['status'] ?? 'PENDING';
        $notes = $data['notes'] ?? null;

        // Resolve report_employee_id
        $reportEmployeeId = !empty($data['report_employee_id']) ? (int) $data['report_employee_id'] : 0;
        if ($reportEmployeeId <= 0) {
            $chk = $pdo->prepare('SELECT report_employee_id FROM expense_report_employees WHERE report_id = :r_id AND employee_id = :e_id');
            $chk->execute(['r_id' => $reportId, 'e_id' => $employeeId]);
            $repEmpId = $chk->fetchColumn();
            if ($repEmpId) {
                $reportEmployeeId = (int) $repEmpId;
            } else {
                $repRepo = new ReportRepository();
                $reportEmployeeId = $repRepo->addReportEmployee($reportId, $employeeId);
            }
        }

        $stmt = $pdo->prepare('
            INSERT INTO expense_reimbursements (
                report_id, report_employee_id, employee_id, approved_amount, paid_amount, status, notes
            ) VALUES (
                :report_id, :report_employee_id, :employee_id, :approved_amount, :paid_amount, :status, :notes
            )
            ON DUPLICATE KEY UPDATE
                approved_amount = VALUES(approved_amount),
                status = VALUES(status),
                notes = VALUES(notes)
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'report_employee_id' => $reportEmployeeId,
            'employee_id' => $employeeId,
            'approved_amount' => $amount,
            'paid_amount' => $status === 'PAID' ? $amount : 0.00,
            'status' => $status,
            'notes' => $notes,
        ]);
        $reimbId = (int) $pdo->lastInsertId();
        if ($reimbId === 0) {
            $findStmt = $pdo->prepare('SELECT reimbursement_id FROM expense_reimbursements WHERE report_id = :r AND employee_id = :e');
            $findStmt->execute(['r' => $reportId, 'e' => $employeeId]);
            $reimbId = (int) $findStmt->fetchColumn();
        }

        // Keep legacy table synced if needed
        try {
            $legacyStmt = $pdo->prepare('
                INSERT INTO reimbursements (report_id, employee_id, amount, payment_method, status, notes)
                VALUES (:r, :e, :a, "BANK_TRANSFER", :s, :n)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = VALUES(status)
            ');
            $legacyStmt->execute([
                'r' => $reportId,
                'e' => $employeeId,
                'a' => $amount,
                's' => $status,
                'n' => $notes,
            ]);
        } catch (\Throwable $e) {
            // Ignore legacy unique constraint conflict if report has multiple employees
        }

        return $reimbId;
    }

    public function recordPayment(int $reimbursementId, array $data, int $paidBy): int
    {
        $pdo = Database::pdo();
        $reimb = $this->findById($reimbursementId, true);
        if (!$reimb) {
            throw new RuntimeException('Data reimbursement tidak ditemukan.');
        }

        $approvedAmount = (float) $reimb['approved_amount'];
        $currentPaid = (float) $reimb['paid_amount'];
        $remaining = round($approvedAmount - $currentPaid, 2);

        $payAmount = round((float) ($data['amount'] ?? 0), 2);
        if ($payAmount <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
        }

        if ($payAmount > $remaining) {
            throw new InvalidArgumentException(sprintf('Nominal pembayaran (Rp %s) melebihi sisa kewajiban reimbursement (Rp %s).', number_format($payAmount, 0, ',', '.'), number_format($remaining, 0, ',', '.')));
        }

        $paymentNumber = $data['payment_number'] ?? $this->generatePaymentNumber();
        $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : date('Y-m-d');
        $paymentMethod = $data['payment_method'] ?? 'BANK_TRANSFER';
        $bankName = !empty($data['bank_name']) ? trim($data['bank_name']) : null;
        $bankRef = !empty($data['bank_reference']) ? trim($data['bank_reference']) : null;
        $proofPath = !empty($data['proof_path']) ? $data['proof_path'] : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;

        $stmtPay = $pdo->prepare('
            INSERT INTO expense_reimbursement_payments (
                reimbursement_id, payment_number, payment_date, amount, payment_method, bank_name, bank_reference, proof_path, paid_by, notes
            ) VALUES (
                :reimbursement_id, :payment_number, :payment_date, :amount, :payment_method, :bank_name, :bank_reference, :proof_path, :paid_by, :notes
            )
        ');
        $stmtPay->execute([
            'reimbursement_id' => $reimbursementId,
            'payment_number' => $paymentNumber,
            'payment_date' => $paymentDate,
            'amount' => $payAmount,
            'payment_method' => $paymentMethod,
            'bank_name' => $bankName,
            'bank_reference' => $bankRef,
            'proof_path' => $proofPath,
            'paid_by' => $paidBy,
            'notes' => $notes,
        ]);
        $paymentId = (int) $pdo->lastInsertId();

        // Update reimbursement paid amount and status
        $newPaid = round($currentPaid + $payAmount, 2);
        $newStatus = ($newPaid >= $approvedAmount) ? 'PAID' : 'PARTIALLY_PAID';

        $stmtUp = $pdo->prepare('
            UPDATE expense_reimbursements
            SET paid_amount = :paid_amount,
                status = :status,
                updated_at = NOW()
            WHERE reimbursement_id = :id
        ');
        $stmtUp->execute([
            'paid_amount' => $newPaid,
            'status' => $newStatus,
            'id' => $reimbursementId,
        ]);

        // Sync legacy table if present
        try {
            $stmtLeg = $pdo->prepare('
                UPDATE reimbursements
                SET status = :status,
                    payment_reference = :ref,
                    payment_date = :pdate,
                    processed_by = :proc,
                    processed_at = NOW()
                WHERE reimbursement_id = :id
            ');
            $stmtLeg->execute([
                'status' => $newStatus === 'PARTIALLY_PAID' ? 'PROCESSING' : $newStatus,
                'ref' => $bankRef,
                'pdate' => $paymentDate,
                'proc' => $paidBy,
                'id' => $reimbursementId,
            ]);
        } catch (\Throwable $e) {
            // Ignore
        }

        return $paymentId;
    }

    public function getPayments(int $reimbursementId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT p.*, u.username as paid_by_username, emp.full_name as paid_by_name
            FROM expense_reimbursement_payments p
            JOIN users u ON u.user_id = p.paid_by
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE p.reimbursement_id = :id
            ORDER BY p.payment_date DESC, p.payment_id DESC
        ');
        $stmt->execute(['id' => $reimbursementId]);
        return $stmt->fetchAll();
    }

    public function isReportFullyPaid(int $reportId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM expense_reimbursements
            WHERE report_id = :report_id AND status != "PAID"
        ');
        $stmt->execute(['report_id' => $reportId]);
        $unpaidCount = (int) $stmt->fetchColumn();
        return $unpaidCount === 0;
    }

    public function updateStatus(int $id, string $status, ?int $processedBy = null, ?array $paymentDetails = null): bool
    {
        $pdo = Database::pdo();
        $statusUpper = strtoupper($status);

        $reimb = $this->findById($id);
        if ($reimb && $statusUpper === 'PAID') {
            $amount = (float) ($reimb['approved_amount'] ?? $reimb['amount'] ?? 0);
            $payData = [
                'amount' => $amount,
                'payment_method' => $paymentDetails['payment_method'] ?? 'BANK_TRANSFER',
                'bank_reference' => $paymentDetails['payment_reference'] ?? null,
                'payment_date' => $paymentDetails['payment_date'] ?? date('Y-m-d'),
                'notes' => $paymentDetails['notes'] ?? null,
            ];
            $this->recordPayment($id, $payData, $processedBy ?? 1);
            return true;
        }

        $stmt = $pdo->prepare('
            UPDATE expense_reimbursements
            SET status = :status,
                updated_at = NOW()
            WHERE reimbursement_id = :id
        ');
        $ok = $stmt->execute(['status' => $statusUpper, 'id' => $id]);

        // Also update legacy if exists
        try {
            $stmtLegacy = $pdo->prepare('
                UPDATE reimbursements
                SET status = :status,
                    processed_by = :processed_by,
                    processed_at = NOW()
                WHERE reimbursement_id = :id
            ');
            $stmtLegacy->execute(['status' => $statusUpper, 'processed_by' => $processedBy, 'id' => $id]);
        } catch (\Throwable $e) {
            // Ignore
        }

        return $ok;
    }

    public function generatePaymentNumber(): string
    {
        $yearMonth = date('Ym');
        $prefix = "PAY-{$yearMonth}-";
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT payment_number 
            FROM expense_reimbursement_payments 
            WHERE payment_number LIKE :prefix 
            ORDER BY payment_id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();
        $seq = 1;
        if ($last && preg_match('/PAY-\d{6}-(\d+)/', (string) $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return sprintf('PAY-%s-%04d', $yearMonth, $seq);
    }
}
