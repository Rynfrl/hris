<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReimbursementRepository
{
    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $pdo = Database::pdo();
        $sql = '
            SELECT rem.*,
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
        $stmt = $pdo->prepare('
            SELECT * FROM reimbursements WHERE report_id = :report_id LIMIT 1
        ');
        $stmt->execute(['report_id' => $reportId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getReimbursements(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'rem.status = :status';
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['search'])) {
            $where[] = '(r.report_number LIKE :q OR emp.full_name LIKE :q OR rem.payment_reference LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT rem.*,
                   r.report_number, r.title as report_title,
                   emp.full_name as employee_name, emp.employee_number, emp.bank_name, emp.bank_account
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE {$whereClause}
            ORDER BY rem.reimbursement_id DESC
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
        $stmt = $pdo->prepare('
            INSERT INTO reimbursements (
                report_id, employee_id, amount, payment_method, status, notes
            ) VALUES (
                :report_id, :employee_id, :amount, :payment_method, :status, :notes
            )
        ');
        $stmt->execute([
            'report_id' => (int) $data['report_id'],
            'employee_id' => (int) $data['employee_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'BANK_TRANSFER',
            'status' => $data['status'] ?? 'PENDING',
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?int $processedBy = null, ?array $paymentDetails = null): bool
    {
        $pdo = Database::pdo();
        $fields = [
            'status = :status',
            'processed_by = :processed_by',
            'processed_at = NOW()',
        ];
        $params = [
            'id' => $id,
            'status' => strtoupper($status),
            'processed_by' => $processedBy,
        ];

        if ($paymentDetails) {
            if (!empty($paymentDetails['payment_method'])) {
                $fields[] = 'payment_method = :payment_method';
                $params['payment_method'] = $paymentDetails['payment_method'];
            }
            if (!empty($paymentDetails['payment_reference'])) {
                $fields[] = 'payment_reference = :payment_reference';
                $params['payment_reference'] = trim($paymentDetails['payment_reference']);
            }
            if (!empty($paymentDetails['payment_date'])) {
                $fields[] = 'payment_date = :payment_date';
                $params['payment_date'] = $paymentDetails['payment_date'];
            }
            if (!empty($paymentDetails['notes'])) {
                $fields[] = 'notes = :notes';
                $params['notes'] = trim($paymentDetails['notes']);
            }
        }

        $sql = 'UPDATE reimbursements SET ' . implode(', ', $fields) . ' WHERE reimbursement_id = :id';
        return $pdo->prepare($sql)->execute($params);
    }
}
