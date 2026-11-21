<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

class ReportRepository
{
    public function findById(int $id, bool $forUpdate = false): ?array
    {
        $pdo = Database::pdo();
        $sql = '
            SELECT r.*,
                   COALESCE(u.username, "admin") as creator_username,
                   u.name as creator_name,
                   emp.full_name as employee_name, emp.employee_number, emp.manager_id,
                   d.department_name, d.department_code,
                   reimb.reimbursement_id, reimb.status as reimbursement_status, reimb.payment_method, reimb.payment_date, reimb.payment_reference,
                   post.posting_id, post.journal_reference, post.posting_date, post.posted_at,
                   (SELECT COUNT(*) FROM expense_report_employees WHERE report_id = r.report_id) as employee_count,
                   (SELECT COUNT(*) FROM expense_report_items WHERE report_id = r.report_id) as item_count
            FROM expense_reports r
            LEFT JOIN users u ON u.user_id = r.created_by
            LEFT JOIN employees emp ON emp.employee_id = r.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            LEFT JOIN reimbursements reimb ON reimb.report_id = r.report_id
            LEFT JOIN expense_postings post ON post.report_id = r.report_id
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

    public function getReportEmployees(int $reportId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT ere.*,
                   emp.bank_name, emp.bank_account, emp.email, emp.position,
                   (
                       SELECT COUNT(*) 
                       FROM expense_report_items eri 
                       WHERE eri.report_employee_id = ere.report_employee_id
                   ) as item_count,
                   (
                       SELECT COALESCE(SUM(exp.amount), 0.00)
                       FROM expense_report_items eri
                       JOIN expenses exp ON exp.expense_id = eri.expense_id
                       WHERE eri.report_employee_id = ere.report_employee_id
                   ) as subtotal_amount,
                   er.reimbursement_id, er.approved_amount, er.paid_amount, er.status as reimbursement_status
            FROM expense_report_employees ere
            LEFT JOIN employees emp ON emp.employee_id = ere.employee_id
            LEFT JOIN expense_reimbursements er ON er.report_employee_id = ere.report_employee_id
            WHERE ere.report_id = :report_id
            ORDER BY ere.report_employee_id ASC
        ');
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetchAll();
    }

    public function getReportEmployee(int $reportId, int $reportEmployeeId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT ere.*, emp.full_name, emp.employee_number, emp.bank_name, emp.bank_account
            FROM expense_report_employees ere
            JOIN employees emp ON emp.employee_id = ere.employee_id
            WHERE ere.report_employee_id = :re_id AND ere.report_id = :r_id
            LIMIT 1
        ');
        $stmt->execute(['re_id' => $reportEmployeeId, 'r_id' => $reportId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addReportEmployee(int $reportId, int $employeeId): int
    {
        $pdo = Database::pdo();
        // Check if already in report
        $chk = $pdo->prepare('SELECT report_employee_id FROM expense_report_employees WHERE report_id = :r_id AND employee_id = :e_id');
        $chk->execute(['r_id' => $reportId, 'e_id' => $employeeId]);
        $existing = $chk->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }

        // Fetch employee snapshot
        $empStmt = $pdo->prepare('
            SELECT e.employee_number, e.full_name, e.status, d.department_name
            FROM employees e
            LEFT JOIN departments d ON d.department_id = e.department_id
            WHERE e.employee_id = :e_id
        ');
        $empStmt->execute(['e_id' => $employeeId]);
        $emp = $empStmt->fetch();
        if (!$emp) {
            throw new InvalidArgumentException('Data karyawan tidak ditemukan.');
        }

        if (($emp['status'] ?? 'ACTIVE') !== 'ACTIVE') {
            throw new InvalidArgumentException('Karyawan yang tidak aktif tidak boleh dipilih untuk pengajuan laporan.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO expense_report_employees (
                report_id, employee_id, employee_number_snapshot, employee_name_snapshot, department_name_snapshot
            ) VALUES (
                :report_id, :employee_id, :num, :name, :dept
            )
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'employee_id' => $employeeId,
            'num' => $emp['employee_number'] ?? ('EMP-' . $employeeId),
            'name' => $emp['full_name'] ?? 'Unknown Employee',
            'dept' => $emp['department_name'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function removeReportEmployee(int $reportId, int $reportEmployeeId): bool
    {
        $pdo = Database::pdo();
        // Resolve real report_employee_id if employee_id was provided
        $findStmt = $pdo->prepare('
            SELECT report_employee_id FROM expense_report_employees
            WHERE report_id = :r_id AND (report_employee_id = :re_id OR employee_id = :re_id)
            LIMIT 1
        ');
        $findStmt->execute(['r_id' => $reportId, 're_id' => $reportEmployeeId]);
        $row = $findStmt->fetch();
        if ($row) {
            $reportEmployeeId = (int) $row['report_employee_id'];
        }

        // Unlink or remove items linked to this report employee
        $stmtItems = $pdo->prepare('
            DELETE FROM expense_report_items 
            WHERE report_id = :report_id AND report_employee_id = :report_employee_id
        ');
        $stmtItems->execute([
            'report_id' => $reportId,
            'report_employee_id' => $reportEmployeeId,
        ]);

        $stmt = $pdo->prepare('
            DELETE FROM expense_report_employees
            WHERE report_id = :report_id AND report_employee_id = :report_employee_id
        ');
        $ok = $stmt->execute([
            'report_id' => $reportId,
            'report_employee_id' => $reportEmployeeId,
        ]);

        $this->recalculateTotal($reportId);
        return $ok;
    }

    public function getReportItems(int $reportId, ?int $reportEmployeeId = null): array
    {
        $pdo = Database::pdo();
        $sql = '
            SELECT e.*, c.category_name, c.category_code, eri.report_item_id, eri.report_employee_id,
                   ere.employee_name_snapshot, ere.employee_number_snapshot, ere.department_name_snapshot,
                   emp.full_name as current_employee_name
            FROM expense_report_items eri
            JOIN expenses e ON e.expense_id = eri.expense_id
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN expense_report_employees ere ON ere.report_employee_id = eri.report_employee_id
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            WHERE eri.report_id = :report_id
        ';
        $params = ['report_id' => $reportId];
        if ($reportEmployeeId !== null) {
            $sql .= ' AND eri.report_employee_id = :report_employee_id';
            $params['report_employee_id'] = $reportEmployeeId;
        }
        $sql .= ' ORDER BY e.expense_date ASC, e.expense_id ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getReports(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = '(r.employee_id = :employee_id OR EXISTS (SELECT 1 FROM expense_report_employees ere WHERE ere.report_id = r.report_id AND ere.employee_id = :employee_id_sub))';
            $params['employee_id'] = (int) $filters['employee_id'];
            $params['employee_id_sub'] = (int) $filters['employee_id'];
        } elseif (!empty($filters['employee_ids']) && is_array($filters['employee_ids'])) {
            $inPlaceholders1 = [];
            $inPlaceholders2 = [];
            foreach ($filters['employee_ids'] as $idx => $empId) {
                $key1 = 'emp1_' . $idx;
                $key2 = 'emp2_' . $idx;
                $inPlaceholders1[] = ':' . $key1;
                $inPlaceholders2[] = ':' . $key2;
                $params[$key1] = (int) $empId;
                $params[$key2] = (int) $empId;
            }
            if (!empty($inPlaceholders1)) {
                $inList1 = implode(',', $inPlaceholders1);
                $inList2 = implode(',', $inPlaceholders2);
                $where[] = '(r.employee_id IN (' . $inList1 . ') OR EXISTS (SELECT 1 FROM expense_report_employees ere WHERE ere.report_id = r.report_id AND ere.employee_id IN (' . $inList2 . ')))';
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
            $where[] = '(r.report_number LIKE :q1 OR r.title LIKE :q2 OR emp.full_name LIKE :q3 OR EXISTS (SELECT 1 FROM expense_report_employees ere WHERE ere.report_id = r.report_id AND ere.employee_name_snapshot LIKE :q4))';
            $qVal = '%' . trim((string) $filters['search']) . '%';
            $params['q1'] = $qVal;
            $params['q2'] = $qVal;
            $params['q3'] = $qVal;
            $params['q4'] = $qVal;
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
            SELECT COUNT(DISTINCT r.report_id)
            FROM expense_reports r
            LEFT JOIN employees emp ON emp.employee_id = r.employee_id
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
                   (SELECT COUNT(*) FROM expense_report_items WHERE report_id = r.report_id) as item_count,
                   (SELECT COUNT(*) FROM expense_report_employees WHERE report_id = r.report_id) as employee_count,
                   (
                       SELECT GROUP_CONCAT(DISTINCT ere.employee_name_snapshot SEPARATOR ', ')
                       FROM expense_report_employees ere
                       WHERE ere.report_id = r.report_id
                   ) as employees_summary
            FROM expense_reports r
            LEFT JOIN employees emp ON emp.employee_id = r.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            WHERE {$whereClause}
            GROUP BY r.report_id
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
                report_number, employee_id, created_by, title, description, start_date, end_date, total_amount, status
            ) VALUES (
                :report_number, :employee_id, :created_by, :title, :description, :start_date, :end_date, 0.00, "DRAFT"
            )
        ');
        $stmt->execute([
            'report_number' => $data['report_number'] ?? $this->generateReportNumber(),
            'employee_id' => !empty($data['employee_id']) ? (int) $data['employee_id'] : null,
            'created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
        ]);
        $reportId = (int) $pdo->lastInsertId();

        // Attach employees if provided
        if (!empty($data['employee_ids']) && is_array($data['employee_ids'])) {
            foreach ($data['employee_ids'] as $empId) {
                if ((int) $empId > 0) {
                    $this->addReportEmployee($reportId, (int) $empId);
                }
            }
        } elseif (!empty($data['employee_id'])) {
            $this->addReportEmployee($reportId, (int) $data['employee_id']);
        }

        return $reportId;
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
        $ok = $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'start_date' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
        ]);

        return $ok;
    }

    public function addItem(int $reportId, int $expenseId, ?int $reportEmployeeId = null): bool
    {
        $pdo = Database::pdo();

        // If reportEmployeeId is not provided, resolve from expense employee_id and report
        if ($reportEmployeeId === null) {
            $expStmt = $pdo->prepare('SELECT employee_id FROM expenses WHERE expense_id = :id');
            $expStmt->execute(['id' => $expenseId]);
            $expEmployeeId = $expStmt->fetchColumn();

            if ($expEmployeeId) {
                $repEmpStmt = $pdo->prepare('SELECT report_employee_id FROM expense_report_employees WHERE report_id = :r_id AND employee_id = :e_id');
                $repEmpStmt->execute(['r_id' => $reportId, 'e_id' => $expEmployeeId]);
                $foundRepEmpId = $repEmpStmt->fetchColumn();
                if ($foundRepEmpId) {
                    $reportEmployeeId = (int) $foundRepEmpId;
                } else {
                    $reportEmployeeId = $this->addReportEmployee($reportId, (int) $expEmployeeId);
                }
            }
        }

        // Insert item relation
        $stmt = $pdo->prepare('
            INSERT INTO expense_report_items (report_id, expense_id, report_employee_id)
            VALUES (:report_id, :expense_id, :report_employee_id)
        ');
        $ok = $stmt->execute([
            'report_id' => $reportId,
            'expense_id' => $expenseId,
            'report_employee_id' => $reportEmployeeId,
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

    public function getHistory(int $reportId): array
    {
        return $this->getApprovalHistory($reportId);
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

    public function createPosting(int $reportId, string $journalRef, string $postingDate, int $postedBy, ?string $notes = null): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO expense_postings (
                report_id, journal_reference, posting_date, posted_by, notes
            ) VALUES (
                :report_id, :journal_reference, :posting_date, :posted_by, :notes
            )
        ');
        $stmt->execute([
            'report_id' => $reportId,
            'journal_reference' => trim($journalRef),
            'posting_date' => $postingDate,
            'posted_by' => $postedBy,
            'notes' => !empty($notes) ? trim($notes) : null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function getPosting(int $reportId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT p.*, u.username as posted_by_username, emp.full_name as posted_by_name
            FROM expense_postings p
            JOIN users u ON u.user_id = p.posted_by
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE p.report_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $reportId]);
        $row = $stmt->fetch();
        return $row ?: null;
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

    public function generateJournalReference(): string
    {
        $yearMonth = date('Ym');
        $prefix = "JRN-EXP-{$yearMonth}-";
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT journal_reference 
            FROM expense_postings 
            WHERE journal_reference LIKE :prefix 
            ORDER BY posting_id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();
        $seq = 1;
        if ($last && preg_match('/JRN-EXP-\d{6}-(\d+)/', (string) $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return sprintf('JRN-EXP-%s-%04d', $yearMonth, $seq);
    }
}
