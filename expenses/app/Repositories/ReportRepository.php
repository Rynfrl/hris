<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReportRepository
{
    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $pdo = Database::pdo();
        $sql = '
            SELECT r.*,
                   emp.full_name as employee_name, emp.employee_number, emp.manager_id,
                   d.department_name, d.department_code,
                   reimb.reimbursement_id, reimb.status as reimbursement_status, reimb.payment_method, reimb.payment_date, reimb.payment_reference
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            LEFT JOIN reimbursements reimb ON reimb.report_id = r.report_id
            WHERE r.report_id = :id
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

    public function getReportItems(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.*, c.category_name, c.category_code, eri.report_item_id
            FROM expense_report_items eri
            JOIN expenses e ON e.expense_id = eri.expense_id
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE eri.report_id = :report_id
            ORDER BY e.expense_date ASC, e.expense_id ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function getReports(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'r.employee_id = :employee_id';
            $params['employee_id'] = (int) $filters['employee_id'];
        } elseif (!empty($filters['employee_ids']) && is_array($filters['employee_ids'])) {
            $inPlaceholders = [];
            foreach ($filters['employee_ids'] as $idx => $empId) {
                $key = 'emp_' . $idx;
                $inPlaceholders[] = ':' . $key;
                $params[$key] = (int) $empId;
            }
            if (!empty($inPlaceholders)) {
                $where[] = 'r.employee_id IN (' . implode(',', $inPlaceholders) . ')';
            } else {
                $where[] = '1=0';
            }
        }

        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'r.created_at >= :start_date';
            $params['start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'r.created_at <= :end_date';
            $params['end_date'] = $filters['end_date'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $where[] = '(r.report_number LIKE :q OR r.title LIKE :q OR emp.full_name LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $allowedSort = [
            'created_at' => 'r.created_at',
            'report_number' => 'r.report_number',
            'total_amount' => 'r.total_amount',
            'submitted_at' => 'r.submitted_at',
        ];
        $sortKey = $filters['sort'] ?? 'created_at';
        $orderCol = $allowedSort[$sortKey] ?? 'r.created_at';
        $orderDir = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Count total
        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Items
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT r.*,
                   emp.full_name as employee_name, emp.employee_number,
                   d.department_name,
                   (SELECT COUNT(*) FROM expense_report_items WHERE report_id = r.report_id) as item_count
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            WHERE {$whereClause}
            ORDER BY {$orderCol} {$orderDir}, r.report_id DESC
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
            INSERT INTO expense_reports (
                report_number, employee_id, title, description, start_date, end_date, total_amount, status
            ) VALUES (
                :report_number, :employee_id, :title, :description, :start_date, :end_date, 0.00, "DRAFT"
            )
        ');
        $stmt->execute([
            'report_number' => $data['report_number'] ?? $this->generateReportNumber(),
            'employee_id' => (int) $data['employee_id'],
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE expense_reports
            SET title = :title,
                description = :description,
                start_date = :start_date,
                end_date = :end_date
            WHERE report_id = :id AND status = "DRAFT"
        ');
        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
        ]);
    }

    public function addItem(int $reportId, int $expenseId): bool
    {
        $pdo = Database::pdo();
        // Insert item relation
        $stmt = $pdo->prepare('
            INSERT INTO expense_report_items (report_id, expense_id)
            VALUES (:report_id, :expense_id)
        ');
        $ok = $stmt->execute([
            'report_id' => $reportId,
            'expense_id' => $expenseId,
        ]);

        if ($ok) {
            $this->recalculateTotal($reportId);
        }
        return $ok;
    }

    public function removeItem(int $reportId, int $expenseId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            DELETE FROM expense_report_items
            WHERE report_id = :report_id AND expense_id = :expense_id
        ');
        $ok = $stmt->execute([
            'report_id' => $reportId,
            'expense_id' => $expenseId,
        ]);

        if ($ok) {
            $this->recalculateTotal($reportId);
        }
        return $ok;
    }

    public function recalculateTotal(int $reportId): float
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(e.amount), 0.00)
            FROM expense_report_items eri
            JOIN expenses e ON e.expense_id = eri.expense_id
            WHERE eri.report_id = :report_id
        ');
        $stmt->execute(['report_id' => $reportId]);
        $total = (float) $stmt->fetchColumn();

        $updateStmt = $pdo->prepare('
            UPDATE expense_reports
            SET total_amount = :total
            WHERE report_id = :report_id
        ');
        $updateStmt->execute([
            'total' => $total,
            'report_id' => $reportId,
        ]);

        return $total;
    }

    public function getApprovalHistory(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT a.*, u.username, emp.full_name as approver_name
            FROM expense_approvals a
            JOIN users u ON u.user_id = a.approver_id
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE a.report_id = :report_id
            ORDER BY a.approval_id ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function getComments(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT c.*, u.username, emp.full_name as author_name
            FROM expense_comments c
            JOIN users u ON u.user_id = c.author_id
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE c.report_id = :report_id
            ORDER BY c.created_at ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function addApproval(int $reportId, int $approverId, string $action, ?string $comment): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO expense_approvals (report_id, approver_id, action, comment)
            VALUES (:report_id, :approver_id, :action, :comment)
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'approver_id' => $approverId,
            'action' => $action,
            'comment' => $comment,
        ]);
    }

    public function addComment(int $reportId, int $authorId, string $body, bool $isSystem = false): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO expense_comments (report_id, author_id, comment_body, is_system)
            VALUES (:report_id, :author_id, :comment_body, :is_system)
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'author_id' => $authorId,
            'comment_body' => trim($body),
            'is_system' => $isSystem ? 1 : 0,
        ]);
    }

    public function generateReportNumber(): string
    {
        $pdo = Database::pdo();
        $year = date('Y');
        $prefix = "GTE-{$year}-";

        $stmt = $pdo->prepare("
            SELECT report_number 
            FROM expense_reports 
            WHERE report_number LIKE :prefix 
            ORDER BY report_id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastNumber = $stmt->fetchColumn();

        $nextSeq = 1;
        if ($lastNumber && preg_match('/GTE-\d{4}-(\d+)/', (string) $lastNumber, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return sprintf('GTE-%s-%06d', $year, $nextSeq);
    }
}
