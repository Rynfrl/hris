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
        $sql = '
            SELECT rem.*,
                   rem.amount as approved_amount,
                   CASE WHEN rem.status = "PAID" THEN rem.amount ELSE 0.00 END as paid_amount,
                   CASE WHEN rem.status = "PAID" THEN 0.00 ELSE rem.amount END as remaining_amount,
                   r.report_number, r.title as report_title, r.total_amount as report_total, r.status as report_status,
                   COALESCE(
                       rem.recipient_name,
                       (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       emp.full_name,
                       "Kas Operasional"
                   ) as employee_name,
                   COALESCE(
                       (SELECT GROUP_CONCAT(CONCAT(ere.employee_name_snapshot, " (", ere.employee_number_snapshot, ")") SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       emp.full_name,
                       "-"
                   ) as employees_summary,
                   COALESCE(
                       (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       1
                   ) as employee_count,
                   rem.bank_name,
                   rem.account_number,
                   rem.account_holder,
                   u.username as processor_username
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            LEFT JOIN employees emp ON emp.employee_id = rem.employee_id
            LEFT JOIN users u ON u.user_id = rem.processed_by
            WHERE rem.reimbursement_id = :id
            LIMIT 1
        ';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByReportId(int $reportId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM reimbursements WHERE report_id = :report_id LIMIT 1');
        $stmt->execute(['report_id' => $reportId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByReportId(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT rem.*,
                   rem.amount as approved_amount,
                   CASE WHEN rem.status = "PAID" THEN rem.amount ELSE 0.00 END as paid_amount,
                   CASE WHEN rem.status = "PAID" THEN 0.00 ELSE rem.amount END as remaining_amount,
                   r.report_number, r.title as report_title, r.total_amount as report_total,
                   COALESCE(
                       rem.recipient_name,
                       (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       emp.full_name,
                       "Kas Operasional"
                   ) as employee_name,
                   COALESCE(
                       (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       1
                   ) as employee_count,
                   rem.bank_name,
                   rem.account_number
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            LEFT JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE rem.report_id = :report_id
            ORDER BY rem.reimbursement_id ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function getReimbursements(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status_in']) && is_array($filters['status_in'])) {
            $inPlaceholders = [];
            foreach ($filters['status_in'] as $idx => $st) {
                $ph = ':st_in_' . $idx;
                $inPlaceholders[] = $ph;
                $params['st_in_' . $idx] = strtoupper((string) $st);
            }
            if (!empty($inPlaceholders)) {
                $where[] = 'rem.status IN (' . implode(',', $inPlaceholders) . ')';
            }
        } elseif (!empty($filters['status'])) {
            $where[] = 'rem.status = :status';
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['report_id'])) {
            $where[] = 'rem.report_id = :report_id';
            $params['report_id'] = (int) $filters['report_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(r.report_number LIKE :q1 OR r.title LIKE :q2 OR rem.recipient_name LIKE :q3 OR emp.full_name LIKE :q4)';
            $qVal = '%' . trim((string) $filters['search']) . '%';
            $params['q1'] = $qVal;
            $params['q2'] = $qVal;
            $params['q3'] = $qVal;
            $params['q4'] = $qVal;
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "
            SELECT COUNT(*)
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            LEFT JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE {$whereClause}
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT rem.*,
                   rem.amount as approved_amount,
                   CASE WHEN rem.status = 'PAID' THEN rem.amount ELSE 0.00 END as paid_amount,
                   CASE WHEN rem.status = 'PAID' THEN 0.00 ELSE rem.amount END as remaining_amount,
                   r.report_number, r.title as report_title, r.total_amount as report_total,
                   COALESCE(
                       rem.recipient_name,
                       (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ', ') FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       emp.full_name,
                       'Kas Operasional'
                   ) as employee_name,
                   COALESCE(
                       (SELECT GROUP_CONCAT(CONCAT(ere.employee_name_snapshot, ' (', ere.employee_number_snapshot, ')') SEPARATOR ', ') FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       emp.full_name,
                       '-'
                   ) as employees_summary,
                   COALESCE(
                       (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = rem.report_id),
                       1
                   ) as employee_count,
                   COALESCE(rem.bank_name, (SELECT p.bank_name FROM expense_reimbursement_payments p WHERE p.reimbursement_id = rem.reimbursement_id ORDER BY p.payment_id DESC LIMIT 1), 'Bank Transfer') as bank_name,
                   COALESCE(rem.account_number, '-') as bank_account,
                   COALESCE(rem.account_holder, rem.recipient_name, 'Kas Operasional') as account_holder,
                   COALESCE(rem.payment_date, (SELECT p.payment_date FROM expense_reimbursement_payments p WHERE p.reimbursement_id = rem.reimbursement_id ORDER BY p.payment_id DESC LIMIT 1), DATE(rem.processed_at), DATE(rem.updated_at)) as payment_date,
                   COALESCE(rem.payment_reference, (SELECT p.bank_reference FROM expense_reimbursement_payments p WHERE p.reimbursement_id = rem.reimbursement_id ORDER BY p.payment_id DESC LIMIT 1), (SELECT p.payment_number FROM expense_reimbursement_payments p WHERE p.reimbursement_id = rem.reimbursement_id ORDER BY p.payment_id DESC LIMIT 1), '-') as payment_reference,
                   COALESCE(rem.payment_method, (SELECT p.payment_method FROM expense_reimbursement_payments p WHERE p.reimbursement_id = rem.reimbursement_id ORDER BY p.payment_id DESC LIMIT 1), 'BANK_TRANSFER') as payment_method,
                   (SELECT COUNT(*) FROM expense_reimbursement_payments WHERE reimbursement_id = rem.reimbursement_id) as payments_count,
                   (SELECT failure_reason FROM expense_reimbursement_failures WHERE reimbursement_id = rem.reimbursement_id ORDER BY failure_id DESC LIMIT 1) as latest_failure_reason,
                   (SELECT f.created_at FROM expense_reimbursement_failures f WHERE f.reimbursement_id = rem.reimbursement_id ORDER BY f.failure_id DESC LIMIT 1) as latest_failure_date,
                   (SELECT u.username FROM expense_reimbursement_failures f JOIN users u ON u.user_id = f.failed_by WHERE f.reimbursement_id = rem.reimbursement_id ORDER BY f.failure_id DESC LIMIT 1) as failed_by_username
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            LEFT JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE {$whereClause}
            ORDER BY COALESCE(rem.payment_date, DATE(rem.processed_at), DATE(rem.created_at)) DESC, rem.reimbursement_id DESC
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
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function formatDecimal(mixed $val): string
    {
        $str = trim((string) $val);
        $str = preg_replace('/[^\d\.\-]/', '', $str);
        if ($str === '' || !is_numeric($str)) {
            return '0.00';
        }
        if (function_exists('bcadd')) {
            return bcadd($str, '0', 2);
        }
        $parts = explode('.', $str);
        $intPart = $parts[0] !== '' ? $parts[0] : '0';
        $decPart = isset($parts[1]) ? substr(str_pad($parts[1], 2, '0'), 0, 2) : '00';
        return $intPart . '.' . $decPart;
    }

    public function createConsolidated(array $data): int
    {
        $pdo = Database::pdo();
        $reportId = (int) $data['report_id'];
        $amount = self::formatDecimal($data['amount']);
        $status = $data['status'] ?? 'PENDING';
        $notes = $data['notes'] ?? ('Reimbursement gabungan laporan #' . $reportId);
        $employeeId = !empty($data['employee_id']) ? (int) $data['employee_id'] : null;

        $stmtFind = $pdo->prepare('SELECT reimbursement_id, status FROM reimbursements WHERE report_id = :r FOR UPDATE');
        $stmtFind->execute(['r' => $reportId]);
        $existing = $stmtFind->fetch();

        if ($existing) {
            $existingId = (int) $existing['reimbursement_id'];
            $existingStatus = strtoupper((string) $existing['status']);
            // Idempotency: Never overwrite or reset status if already PROCESSING, FAILED, or PAID
            if (in_array($existingStatus, ['PROCESSING', 'FAILED', 'PAID'], true)) {
                return $existingId;
            }
            // If PENDING, update amount and notes safely
            $stmtUp = $pdo->prepare('UPDATE reimbursements SET amount = :amount, notes = :notes, updated_at = NOW() WHERE reimbursement_id = :id');
            $stmtUp->execute([
                'amount' => $amount,
                'notes' => $notes,
                'id' => $existingId,
            ]);
            return $existingId;
        }

        // Insert new record
        $stmt = $pdo->prepare('
            INSERT INTO reimbursements (
                report_id, employee_id, amount, status, notes
            ) VALUES (
                :report_id, :employee_id, :amount, :status, :notes
            )
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'employee_id' => $employeeId,
            'amount' => $amount,
            'status' => $status,
            'notes' => $notes,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function create(array $data): int
    {
        return $this->createConsolidated($data);
    }

    public function recordPayment(int $reimbursementId, array $data, int $paidBy): int
    {
        return Database::transaction(function (PDO $pdo) use ($reimbursementId, $data, $paidBy) {
            // Row lock on reimbursements
            $stmtFind = $pdo->prepare('
                SELECT r.*, rep.report_number, rep.total_amount as report_total, rep.status as report_status,
                       COALESCE(emp.full_name, r.recipient_name, "Kas Operasional") as fallback_recipient
                FROM reimbursements r
                JOIN expense_reports rep ON rep.report_id = r.report_id
                LEFT JOIN employees emp ON emp.employee_id = r.employee_id
                WHERE r.reimbursement_id = :id
                FOR UPDATE
            ');
            $stmtFind->execute(['id' => $reimbursementId]);
            $reimb = $stmtFind->fetch();

            if (!$reimb) {
                throw new RuntimeException('Data reimbursement tidak ditemukan.');
            }

            if ($reimb['status'] === 'PAID') {
                throw new RuntimeException('Laporan reimbursement ini sudah dibayar penuh (PAID) dan tidak dapat dibayar ulang.');
            }

            // Prioritas 4: Direct payment on FAILED status is strictly forbidden
            if ($reimb['status'] === 'FAILED') {
                throw new RuntimeException('Reimbursement dengan status FAILED tidak dapat langsung dicairkan. Lakukan retry pembayaran terlebih dahulu.');
            }

            if (!in_array($reimb['status'], ['PENDING', 'PROCESSING'], true)) {
                throw new RuntimeException('Status reimbursement tidak valid untuk pembayaran: ' . $reimb['status']);
            }

            $approvedAmountStr = self::formatDecimal($reimb['amount']);
            $payAmountStr = self::formatDecimal($data['amount'] ?? $reimb['amount']);

            if (function_exists('bccomp')) {
                if (bccomp($payAmountStr, '0.00', 2) <= 0) {
                    throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
                }
                // Business Rule: One Report = One Reimbursement = One FULL Payment. No partial payments allowed.
                if (bccomp($payAmountStr, $approvedAmountStr, 2) !== 0) {
                    throw new InvalidArgumentException(sprintf(
                        'Pembayaran harus satu kali penuh sebesar total laporan (Rp %s). Pembayaran parsial tidak didukung.',
                        number_format((float) $approvedAmountStr, 0, ',', '.')
                    ));
                }
            } else {
                if ($payAmountStr === '0.00' || str_starts_with($payAmountStr, '-')) {
                    throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
                }
                if ($payAmountStr !== $approvedAmountStr) {
                    throw new InvalidArgumentException(sprintf(
                        'Pembayaran harus satu kali penuh sebesar total laporan (Rp %s). Pembayaran parsial tidak didukung.',
                        number_format((float) $approvedAmountStr, 0, ',', '.')
                    ));
                }
            }
            $payAmount = $payAmountStr;

            $recipientName = trim((string) ($data['recipient_name'] ?? ''));
            if ($recipientName === '') {
                $recipientName = $reimb['fallback_recipient'] ?? 'Kas Operasional';
            }

            $paymentNumber = !empty($data['payment_number']) ? trim($data['payment_number']) : $this->generatePaymentNumber();
            $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : date('Y-m-d');
            $paymentMethod = $data['payment_method'] ?? 'BANK_TRANSFER';
            $bankName = !empty($data['bank_name']) ? trim($data['bank_name']) : ($reimb['bank_name'] ?? 'Bank Transfer');
            $bankRef = !empty($data['bank_reference']) ? trim($data['bank_reference']) : (!empty($data['payment_reference']) ? trim($data['payment_reference']) : $paymentNumber);
            $accountNumber = !empty($data['account_number']) ? trim($data['account_number']) : ($reimb['account_number'] ?? '-');
            $accountHolder = !empty($data['account_holder']) ? trim($data['account_holder']) : ($reimb['account_holder'] ?? $recipientName);
            $proofPath = !empty($data['proof_path']) ? $data['proof_path'] : null;
            $notes = !empty($data['notes']) ? trim($data['notes']) : null;

            // 1. Insert into expense_reimbursement_payments history table (enforce integrity, no swallowed errors)
            $stmtPay = $pdo->prepare('
                INSERT INTO expense_reimbursement_payments (
                    reimbursement_id, payment_number, payment_date, amount, payment_method, bank_name, bank_reference, proof_path, paid_by, notes
                ) VALUES (
                    :reimbursement_id, :payment_number, :payment_date, :amount, :payment_method, :bank_name, :bank_reference, :proof_path, :paid_by, :notes
                )
            ');
            $executed = $stmtPay->execute([
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

            if (!$executed) {
                throw new RuntimeException('Gagal menyimpan riwayat pembayaran reimbursement ke database.');
            }
            $paymentHistoryId = (int) $pdo->lastInsertId();

            // 2. Update reimbursements table with recipient details and PAID status
            $stmtUp = $pdo->prepare('
                UPDATE reimbursements
                SET amount = :amount,
                    recipient_name = :recipient_name,
                    bank_name = :bank_name,
                    account_number = :account_number,
                    account_holder = :account_holder,
                    payment_method = :payment_method,
                    payment_reference = :payment_reference,
                    payment_date = :payment_date,
                    proof_path = :proof_path,
                    notes = :notes,
                    status = "PAID",
                    processed_by = :processed_by,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE reimbursement_id = :id
            ');
            $stmtUp->execute([
                'amount' => $payAmount,
                'recipient_name' => $recipientName,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_holder' => $accountHolder,
                'payment_method' => $paymentMethod,
                'payment_reference' => $bankRef,
                'payment_date' => $paymentDate,
                'proof_path' => $proofPath,
                'notes' => $notes,
                'processed_by' => $paidBy,
                'id' => $reimbursementId,
            ]);

            return $paymentHistoryId ?: $reimbursementId;
        });
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
        $stmt = $pdo->prepare('SELECT status FROM reimbursements WHERE report_id = :report_id LIMIT 1');
        $stmt->execute(['report_id' => $reportId]);
        $status = $stmt->fetchColumn();
        return $status === 'PAID';
    }

    public function updateStatus(int $id, string $status, ?int $processedBy = null, ?array $paymentDetails = null): bool
    {
        $statusUpper = strtoupper($status);

        if ($statusUpper === 'PAID') {
            $reimb = $this->findById($id);
            if ($reimb) {
                $amount = self::formatDecimal($reimb['approved_amount'] ?? $reimb['amount'] ?? '0.00');
                $payData = [
                    'amount' => $amount,
                    'payment_method' => $paymentDetails['payment_method'] ?? 'BANK_TRANSFER',
                    'bank_reference' => $paymentDetails['payment_reference'] ?? null,
                    'payment_date' => $paymentDetails['payment_date'] ?? date('Y-m-d'),
                    'notes' => $paymentDetails['notes'] ?? null,
                    'proof_path' => $paymentDetails['proof_path'] ?? null,
                ];
                $this->recordPayment($id, $payData, $processedBy ?? 1);
                return true;
            }
        }

        // Prioritas 5: Atomic status update and failure recording with row locking
        return Database::transaction(function (PDO $pdo) use ($id, $statusUpper, $processedBy, $paymentDetails) {
            $stmtLock = $pdo->prepare('SELECT * FROM reimbursements WHERE reimbursement_id = :id FOR UPDATE');
            $stmtLock->execute(['id' => $id]);
            $reimb = $stmtLock->fetch();

            if (!$reimb) {
                throw new RuntimeException('Data reimbursement tidak ditemukan.');
            }

            if ($statusUpper === 'FAILED') {
                $reason = trim((string) ($paymentDetails['notes'] ?? $paymentDetails['error_reason'] ?? ''));
                if ($reason === '' || mb_strlen($reason) < 5 || strcasecmp($reason, 'Pembayaran gagal') === 0) {
                    throw new InvalidArgumentException('Alasan kegagalan pembayaran wajib diisi secara jelas (minimal 5 karakter).');
                }
                $failedBy = $processedBy ?? 1;
                $prevStatus = $reimb['status'] ?? 'PROCESSING';
                $reportId = (int) $reimb['report_id'];

                // 1. Calculate attempt number safely with row lock
                $stmtAtt = $pdo->prepare('SELECT COALESCE(MAX(attempt_number), 0) + 1 FROM expense_reimbursement_failures WHERE reimbursement_id = :id FOR UPDATE');
                $stmtAtt->execute(['id' => $id]);
                $attemptNo = (int) $stmtAtt->fetchColumn();

                // 2. Insert into expense_reimbursement_failures history
                $stmtFail = $pdo->prepare('
                    INSERT INTO expense_reimbursement_failures (
                        reimbursement_id, report_id, previous_status, new_status, failure_reason, failed_by, payment_reference, attempt_number
                    ) VALUES (
                        :reimbursement_id, :report_id, :prev_status, "FAILED", :reason, :failed_by, :pay_ref, :attempt_no
                    )
                ');
                $stmtFail->execute([
                    'reimbursement_id' => $id,
                    'report_id' => $reportId,
                    'prev_status' => $prevStatus,
                    'reason' => $reason,
                    'failed_by' => $failedBy,
                    'pay_ref' => $paymentDetails['payment_reference'] ?? null,
                    'attempt_no' => $attemptNo,
                ]);

                // 3. Update reimbursements table with FAILED status and failure notes
                $stmt = $pdo->prepare('
                    UPDATE reimbursements
                    SET status = "FAILED",
                        notes = :reason,
                        processed_by = :processed_by,
                        processed_at = NOW(),
                        updated_at = NOW()
                    WHERE reimbursement_id = :id
                ');
                return $stmt->execute([
                    'reason' => $reason,
                    'processed_by' => $failedBy,
                    'id' => $id,
                ]);
            }

            $stmt = $pdo->prepare('
                UPDATE reimbursements
                SET status = :status,
                    processed_by = :processed_by,
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE reimbursement_id = :id
            ');
            return $stmt->execute([
                'status' => $statusUpper,
                'processed_by' => $processedBy,
                'id' => $id,
            ]);
        });
    }

    public function getFailures(int $reimbursementId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT f.*, u.username as failed_by_username, emp.full_name as failed_by_name
            FROM expense_reimbursement_failures f
            JOIN users u ON u.user_id = f.failed_by
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE f.reimbursement_id = :id
            ORDER BY f.attempt_number DESC, f.failure_id DESC
        ');
        $stmt->execute(['id' => $reimbursementId]);
        return $stmt->fetchAll();
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

        // Ensure unique against database constraint
        $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM expense_reimbursement_payments WHERE payment_number = :pnum");
        do {
            $candidate = sprintf('PAY-%s-%04d', $yearMonth, $seq);
            $chkStmt->execute(['pnum' => $candidate]);
            $exists = (int) $chkStmt->fetchColumn() > 0;
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $candidate;
    }
}
