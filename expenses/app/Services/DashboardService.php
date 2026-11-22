<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\EmployeeRepository;
use PDO;

class DashboardService
{
    private EmployeeRepository $employeeRepo;

    public function __construct(?EmployeeRepository $employeeRepo = null)
    {
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
    }

    public function getMetrics(array $user): array
    {
        $role = $user['role_name'] ?? 'ADMIN';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        return match ($role) {
            'MANAGER' => $this->getManagerMetrics($user, $empId),
            'FINANCE' => $this->getFinanceMetrics($user, $empId),
            default => $this->getAdminMetrics($user, $empId),
        };
    }

    private function getAdminMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();

        // 1.1 Dashboard Admin Expense - Exact metrics required by specification
        $totalReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports')->fetchColumn();
        $draftReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "DRAFT"')->fetchColumn();
        $submittedReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "SUBMITTED"')->fetchColumn();
        $approvedReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "APPROVED"')->fetchColumn();
        $refusedReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "REFUSED"')->fetchColumn();
        $postedReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "POSTED"')->fetchColumn();
        $pendingReimbReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "REIMBURSEMENT_PENDING"')->fetchColumn();
        $paidReports = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "PAID"')->fetchColumn();
        $totalNominal = (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0.00) FROM expense_reports WHERE status != "CANCELLED"')->fetchColumn();

        // Extra admin counts
        $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn();
        $categoriesCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_categories WHERE is_active = 1')->fetchColumn();
        $expensesCount = (int) $pdo->query('SELECT COUNT(*) FROM expenses')->fetchColumn();

        // Recent reports
        $stmtReports = $pdo->query('
            SELECT r.report_id, r.report_number, r.title, r.total_amount, r.status, r.created_at,
                   COALESCE(u.username, "Admin") as creator_name,
                   (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employee_count,
                   (SELECT COUNT(*) FROM expense_report_items eri WHERE eri.report_id = r.report_id) as item_count
            FROM expense_reports r
            LEFT JOIN users u ON u.user_id = r.created_by
            ORDER BY r.report_id DESC
            LIMIT 5
        ');
        $recentReports = $stmtReports->fetchAll();

        // Recent audit activity
        $recentActivity = $pdo->query('
            SELECT a.*, u.username
            FROM activity_logs a
            LEFT JOIN users u ON u.user_id = a.user_id
            ORDER BY a.activity_id DESC
            LIMIT 6
        ')->fetchAll();

        return [
            'role' => 'ADMIN',
            'total_reports' => $totalReports,
            'draft_reports_count' => $draftReports,
            'submitted_reports_count' => $submittedReports,
            'approved_reports_count' => $approvedReports,
            'refused_reports_count' => $refusedReports,
            'posted_reports_count' => $postedReports,
            'pending_reimb_reports_count' => $pendingReimbReports,
            'paid_reports_count' => $paidReports,
            'total_expenses_nominal' => $totalNominal,
            'users_count' => $usersCount,
            'categories_count' => $categoriesCount,
            'expenses_count' => $expensesCount,
            'recent_reports' => $recentReports,
            'recent_activity' => $recentActivity,
            'monthly_trend' => $this->getCompanyMonthlyTrend(),
            'category_breakdown' => $this->getCompanyCategoryBreakdown(),
        ];
    }

    private function getManagerMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();
        $userId = (int) ($user['user_id'] ?? 0);

        // 2.1 Dashboard Manager Expense - Exact metrics required by specification
        $totalIncoming = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status != "DRAFT"')->fetchColumn();
        
        // Anti self-approval: Manager cannot approve their own reports
        $stmtPending = $pdo->prepare('SELECT COUNT(*) FROM expense_reports WHERE status = "SUBMITTED" AND (created_by IS NULL OR created_by != :uid)');
        $stmtPending->execute(['uid' => $userId]);
        $pendingApproval = (int) $stmtPending->fetchColumn();

        $approvedCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status IN ("APPROVED", "POSTED", "REIMBURSEMENT_PENDING", "PAID")')->fetchColumn();
        $refusedCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "REFUSED"')->fetchColumn();
        
        $stmtPendingTotal = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0.00) FROM expense_reports WHERE status = "SUBMITTED" AND (created_by IS NULL OR created_by != :uid)');
        $stmtPendingTotal->execute(['uid' => $userId]);
        $pendingApprovalTotal = (float) $stmtPendingTotal->fetchColumn();

        $approvedTotal = (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0.00) FROM expense_reports WHERE status IN ("APPROVED", "POSTED", "REIMBURSEMENT_PENDING", "PAID")')->fetchColumn();

        // Reports awaiting manager approval (excluding self-created)
        $stmtList = $pdo->prepare('
            SELECT r.report_id, r.report_number, r.title, r.total_amount, r.submitted_at, r.status,
                   COALESCE(u.username, "Admin") as creator_name,
                   (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employee_count,
                   (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employees_summary
            FROM expense_reports r
            LEFT JOIN users u ON u.user_id = r.created_by
            WHERE r.status = "SUBMITTED" AND (r.created_by IS NULL OR r.created_by != :uid)
            ORDER BY r.submitted_at ASC
            LIMIT 5
        ');
        $stmtList->execute(['uid' => $userId]);
        $pendingReports = $stmtList->fetchAll();

        return [
            'role' => 'MANAGER',
            'total_incoming_reports' => $totalIncoming,
            'pending_approval_count' => $pendingApproval,
            'approved_count' => $approvedCount,
            'refused_count' => $refusedCount,
            'pending_approval_total' => $pendingApprovalTotal,
            'approved_total' => $approvedTotal,
            'pending_approval_reports' => $pendingReports,
            'monthly_trend' => $this->getCompanyMonthlyTrend(),
            'category_breakdown' => $this->getCompanyCategoryBreakdown(),
        ];
    }

    private function getFinanceMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();

        // 3.1 Dashboard Finance - Exact metrics required by specification
        $approvedCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status = "APPROVED"')->fetchColumn();
        $awaitingPostCount = $approvedCount; // Reports approved waiting for journal posting
        $postedCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_reports WHERE status IN ("POSTED", "REIMBURSEMENT_PENDING", "PAID")')->fetchColumn();
        $reimbPending = (int) $pdo->query('SELECT COUNT(*) FROM reimbursements WHERE status = "PENDING"')->fetchColumn();
        $reimbProcessing = (int) $pdo->query('SELECT COUNT(*) FROM reimbursements WHERE status = "PROCESSING"')->fetchColumn();
        $reimbPaid = (int) $pdo->query('SELECT COUNT(*) FROM reimbursements WHERE status = "PAID"')->fetchColumn();

        $totalMustPay = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0.00) FROM reimbursements WHERE status IN ("PENDING", "PROCESSING")')->fetchColumn();
        $totalPaid = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0.00) FROM reimbursements WHERE status = "PAID"')->fetchColumn();
        $totalUnpaid = (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0.00) FROM expense_reports WHERE status IN ("APPROVED", "POSTED", "REIMBURSEMENT_PENDING")')->fetchColumn();

        // Recent reports waiting for posting
        $stmtPost = $pdo->query('
            SELECT r.report_id, r.report_number, r.title, r.total_amount, r.approved_at,
                   (SELECT COUNT(*) FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employee_count,
                   (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employees_summary
            FROM expense_reports r
            WHERE r.status = "APPROVED"
            ORDER BY r.approved_at ASC
            LIMIT 5
        ');

        // Recent reimbursements queue
        $stmtRem = $pdo->query('
            SELECT rem.reimbursement_id, rem.amount, rem.status, r.report_number, r.title,
                   COALESCE(rem.recipient_name, (SELECT GROUP_CONCAT(ere.employee_name_snapshot SEPARATOR ", ") FROM expense_report_employees ere WHERE ere.report_id = rem.report_id)) as recipient_name,
                   rem.bank_name, rem.account_number
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            WHERE rem.status IN ("PENDING", "PROCESSING")
            ORDER BY rem.reimbursement_id ASC
            LIMIT 5
        ');

        return [
            'role' => 'FINANCE',
            'approved_reports_count' => $approvedCount,
            'awaiting_post_count' => $awaitingPostCount,
            'posted_reports_count' => $postedCount,
            'reimb_pending_count' => $reimbPending,
            'reimb_processing_count' => $reimbProcessing,
            'reimb_paid_count' => $reimbPaid,
            'total_must_pay' => $totalMustPay,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'posting_queue' => $stmtPost->fetchAll(),
            'reimbursement_queue' => $stmtRem->fetchAll(),
            'monthly_trend' => $this->getCompanyMonthlyTrend(),
            'category_breakdown' => $this->getCompanyCategoryBreakdown(),
        ];
    }

    private function getCompanyMonthlyTrend(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('
            SELECT DATE_FORMAT(r.created_at, "%Y-%m") as ym, COALESCE(SUM(r.total_amount), 0.00) as total
            FROM expense_reports r
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND r.status != "CANCELLED"
            GROUP BY ym
            ORDER BY ym ASC
        ');
        return $this->fillLastSixMonths($stmt->fetchAll());
    }

    private function getCompanyCategoryBreakdown(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('
            SELECT c.category_name, COALESCE(SUM(e.amount), 0.00) as total
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE e.status != "DRAFT"
            GROUP BY c.category_id, c.category_name
            ORDER BY total DESC
            LIMIT 5
        ');
        return $stmt->fetchAll();
    }

    private function fillLastSixMonths(array $dbRows): array
    {
        $mapped = [];
        foreach ($dbRows as $row) {
            $mapped[$row['ym']] = (float) $row['total'];
        }

        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} month"));
            $result[] = [
                'ym' => $ym,
                'total' => $mapped[$ym] ?? 0.00,
            ];
        }

        return $result;
    }
}
