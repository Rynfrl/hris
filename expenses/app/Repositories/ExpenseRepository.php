<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ExpenseRepository
{
    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.*,
                   c.category_name, c.category_code,
                   emp.full_name as employee_name, emp.employee_number,
                   d.department_name, d.department_code,
                   u.username as creator_username,
                   er.report_id, er.report_number, er.title as report_title, er.status as report_status
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            LEFT JOIN departments d ON d.department_id = e.department_id
            LEFT JOIN users u ON u.user_id = e.created_by
            LEFT JOIN expense_report_items eri ON eri.expense_id = e.expense_id
            LEFT JOIN expense_reports er ON er.report_id = eri.report_id
            WHERE e.expense_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByExpenseNumber(string $number): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.*,
                   c.category_name, c.category_code,
                   emp.full_name as employee_name, emp.employee_number,
                   er.report_id, er.report_number
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            LEFT JOIN expense_report_items eri ON eri.expense_id = e.expense_id
            LEFT JOIN expense_reports er ON er.report_id = eri.report_id
            WHERE e.expense_number = :num
            LIMIT 1
        ');
        $stmt->execute(['num' => $number]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getExpenses(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        // Scope filter: employee_id or created_by
        if (!empty($filters['employee_id'])) {
            $where[] = 'e.employee_id = :employee_id';
            $params['employee_id'] = (int) $filters['employee_id'];
        } elseif (!empty($filters['employee_ids']) && is_array($filters['employee_ids'])) {
            $inPlaceholders = [];
            foreach ($filters['employee_ids'] as $idx => $empId) {
                $key = 'emp_' . $idx;
                $inPlaceholders[] = ':' . $key;
                $params[$key] = (int) $empId;
            }
            if (!empty($inPlaceholders)) {
                $where[] = 'e.employee_id IN (' . implode(',', $inPlaceholders) . ')';
            } else {
                $where[] = '1=0';
            }
        }

        if (!empty($filters['status'])) {
            $where[] = 'e.status = :status';
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'e.category_id = :cat_id';
            $params['cat_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'e.expense_date >= :start_date';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'e.expense_date <= :end_date';
            $params['end_date'] = $filters['end_date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(e.expense_number LIKE :q OR e.description LIKE :q OR e.merchant LIKE :q OR emp.full_name LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Allowlist sort
        $allowedSort = [
            'expense_date' => 'e.expense_date',
            'amount' => 'e.amount',
            'created_at' => 'e.created_at',
            'expense_number' => 'e.expense_number',
        ];
        $sortKey = $filters['sort'] ?? 'expense_date';
        $orderCol = $allowedSort[$sortKey] ?? 'e.expense_date';
        $orderDir = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Count total
        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM expenses e
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Paginated items
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT e.*,
                   c.category_name, c.category_code,
                   emp.full_name as employee_name, emp.employee_number,
                   er.report_id, er.report_number
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            LEFT JOIN expense_report_items eri ON eri.expense_id = e.expense_id
            LEFT JOIN expense_reports er ON er.report_id = eri.report_id
            WHERE {$whereClause}
            ORDER BY {$orderCol} {$orderDir}, e.expense_id DESC
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

    public function getUnassignedDraftExpenses(int $employeeId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT e.*, c.category_name, c.category_code
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN expense_report_items eri ON eri.expense_id = e.expense_id
            WHERE e.employee_id = :employee_id
              AND e.status = 'DRAFT'
              AND eri.report_item_id IS NULL
            ORDER BY e.expense_date DESC, e.expense_id DESC
        ");
        $stmt->execute(['employee_id' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO expenses (
                expense_number, employee_id, category_id, expense_date, description,
                amount, currency, payment_method, bank_name, merchant, department_id,
                is_reimbursable, is_reinvoiceable, reinvoice_status, status,
                receipt_original_name, receipt_stored_name, receipt_path, receipt_mime, receipt_size,
                notes, created_by
            ) VALUES (
                :expense_number, :employee_id, :category_id, :expense_date, :description,
                :amount, :currency, :payment_method, :bank_name, :merchant, :department_id,
                :is_reimbursable, :is_reinvoiceable, :reinvoice_status, :status,
                :receipt_original_name, :receipt_stored_name, :receipt_path, :receipt_mime, :receipt_size,
                :notes, :created_by
            )
        ');

        $stmt->execute([
            'expense_number' => $data['expense_number'] ?? $this->generateExpenseNumber(),
            'employee_id' => (int) $data['employee_id'],
            'category_id' => (int) $data['category_id'],
            'expense_date' => $data['expense_date'],
            'description' => trim($data['description']),
            'amount' => $data['amount'],
            'currency' => 'IDR',
            'payment_method' => $data['payment_method'] ?? 'CASH',
            'bank_name' => !empty($data['bank_name']) ? trim((string) $data['bank_name']) : null,
            'merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'is_reimbursable' => 1,
            'is_reinvoiceable' => 0,
            'reinvoice_status' => 'NOT_INVOICED',
            'status' => 'DRAFT',
            'receipt_original_name' => $data['receipt_original_name'] ?? null,
            'receipt_stored_name' => $data['receipt_stored_name'] ?? null,
            'receipt_path' => $data['receipt_path'] ?? null,
            'receipt_mime' => $data['receipt_mime'] ?? null,
            'receipt_size' => $data['receipt_size'] ?? null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'created_by' => (int) $data['created_by'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $fields = [
            'category_id = :category_id',
            'expense_date = :expense_date',
            'description = :description',
            'amount = :amount',
            'payment_method = :payment_method',
            'bank_name = :bank_name',
            'merchant = :merchant',
            'notes = :notes',
            'updated_by = :updated_by',
        ];

        $params = [
            'id' => $id,
            'category_id' => (int) $data['category_id'],
            'expense_date' => $data['expense_date'],
            'description' => trim($data['description']),
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'CASH',
            'bank_name' => !empty($data['bank_name']) ? trim((string) $data['bank_name']) : null,
            'merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'updated_by' => (int) $data['updated_by'],
        ];

        $sql = 'UPDATE expenses SET ' . implode(', ', $fields) . ' WHERE expense_id = :id AND status = "DRAFT"';
        return $pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): bool
    {
        $pdo = Database::pdo();
        // Make sure it is not attached to any report
        $chk = $pdo->prepare('SELECT COUNT(*) FROM expense_report_items WHERE expense_id = :id');
        $chk->execute(['id' => $id]);
        if ((int) $chk->fetchColumn() > 0) {
            return false;
        }

        $stmt = $pdo->prepare('DELETE FROM expenses WHERE expense_id = :id AND status = "DRAFT"');
        return $stmt->execute(['id' => $id]);
    }

    public function updateReceipt(int $id, array $receiptData): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE expenses
            SET receipt_original_name = :orig,
                receipt_stored_name = :stored,
                receipt_path = :path,
                receipt_mime = :mime,
                receipt_size = :size
            WHERE expense_id = :id AND status = "DRAFT"
        ');
        return $stmt->execute([
            'id' => $id,
            'orig' => $receiptData['receipt_original_name'],
            'stored' => $receiptData['receipt_stored_name'],
            'path' => $receiptData['receipt_path'],
            'mime' => $receiptData['receipt_mime'],
            'size' => $receiptData['receipt_size'],
        ]);
    }

    public function removeReceipt(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE expenses
            SET receipt_original_name = NULL,
                receipt_stored_name = NULL,
                receipt_path = NULL,
                receipt_mime = NULL,
                receipt_size = NULL
            WHERE expense_id = :id AND status = "DRAFT"
        ');
        return $stmt->execute(['id' => $id]);
    }

    public function generateExpenseNumber(): string
    {
        $pdo = Database::pdo();
        $year = date('Y');
        $prefix = "EXP-{$year}-";

        // Lock or query last sequence for today's year
        $stmt = $pdo->prepare("
            SELECT expense_number 
            FROM expenses 
            WHERE expense_number LIKE :prefix 
            ORDER BY expense_id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastNumber = $stmt->fetchColumn();

        $nextSeq = 1;
        if ($lastNumber && preg_match('/EXP-\d{4}-(\d+)/', (string) $lastNumber, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return sprintf('EXP-%s-%06d', $year, $nextSeq);
    }
}
