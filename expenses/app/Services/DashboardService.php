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
        $role = $user['role_name'] ?? 'EMPLOYEE';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        return match ($role) {
            'MANAGER' => $this->getManagerMetrics($user, $empId),
            'FINANCE' => $this->getFinanceMetrics($user, $empId),
            'ADMIN' => $this->getAdminMetrics($user, $empId),
            default => $this->getEmployeeMetrics($user, $empId),
        };
    }

    private function getEmployeeMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();
        $metrics = [
            'role' => 'EMPLOYEE',
            'draft_expenses_count' => 0,
            'draft_expenses_total' => 0.00,
            'pending_reports_count' => 0,
            'pending_reports_total' => 0.00,
            'approved_reports_count' => 0,
            'paid_reports_total' => 0.00,
            'recent_reports' => [],
            'recent_expenses' => [],
            'monthly_trend' => $this->fillLastSixMonths([]),
            'category_breakdown' => [],
        ];

        if ($empId === null) {
            return $metrics;
        }

        // Draft expenses count & sum
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0.00) as total
            FROM expenses
            WHERE employee_id = :emp_id AND status = "DRAFT"
        ');
        $stmt->execute(['emp_id' => $empId]);
        $row = $stmt->fetch();
        $metrics['draft_expenses_count'] = (int) ($row['cnt'] ?? 0);
        $metrics['draft_expenses_total'] = (float) ($row['total'] ?? 0);

        // Submitted reports count & sum
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0.00) as total
            FROM expense_reports
            WHERE employee_id = :emp_id AND status = "SUBMITTED"
        ');
        $stmt->execute(['emp_id' => $empId]);
        $row = $stmt->fetch();
        $metrics['pending_reports_count'] = (int) ($row['cnt'] ?? 0);
        $metrics['pending_reports_total'] = (float) ($row['total'] ?? 0);

        // Approved reports
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as cnt
            FROM expense_reports
            WHERE employee_id = :emp_id AND status IN ("APPROVED", "POSTED", "REIMBURSEMENT_PENDING")
        ');
        $stmt->execute(['emp_id' => $empId]);
        $metrics['approved_reports_count'] = (int) $stmt->fetchColumn();

        // Paid reports total
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(total_amount), 0.00)
            FROM expense_reports
            WHERE employee_id = :emp_id AND status = "PAID"
        ');
        $stmt->execute(['emp_id' => $empId]);
        $metrics['paid_reports_total'] = (float) $stmt->fetchColumn();

        // Recent reports
        $stmt = $pdo->prepare('
            SELECT report_id, report_number, title, total_amount, status, created_at
            FROM expense_reports
            WHERE employee_id = :emp_id
            ORDER BY report_id DESC
            LIMIT 5
        ');
        $stmt->execute(['emp_id' => $empId]);
        $metrics['recent_reports'] = $stmt->fetchAll();

        // Recent expenses
        $stmt = $pdo->prepare('
            SELECT e.expense_id, e.expense_number, e.expense_date, e.description, e.amount, e.status, c.category_name
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE e.employee_id = :emp_id
            ORDER BY e.expense_id DESC
            LIMIT 5
        ');
        $stmt->execute(['emp_id' => $empId]);
        $metrics['recent_expenses'] = $stmt->fetchAll();

        $metrics['monthly_trend'] = $this->getEmployeeMonthlyTrend($empId);
        $metrics['category_breakdown'] = $this->getEmployeeCategoryBreakdown($empId);

        return $metrics;
    }

    private function getManagerMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();
        $metrics = [
            'role' => 'MANAGER',
            'team_pending_count' => 0,
            'team_pending_total' => 0.00,
            'team_approved_month' => 0,
            'direct_reports_count' => 0,
            'pending_approval_reports' => [],
            'monthly_trend' => $this->fillLastSixMonths([]),
            'category_breakdown' => [],
        ];

        if ($empId === null) {
            return $metrics;
        }

        // Subordinates
        $directReports = $this->employeeRepo->getDirectReports($empId);
        $metrics['direct_reports_count'] = count($directReports);

        if (empty($directReports)) {
            $metrics['monthly_trend'] = $this->getEmployeeMonthlyTrend($empId);
            $metrics['category_breakdown'] = $this->getEmployeeCategoryBreakdown($empId);
            return $metrics;
        }

        $subIds = array_column($directReports, 'employee_id');
        $inPlaceholders = implode(',', array_fill(0, count($subIds), '?'));

        // Pending approval reports
        $sql = "
            SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0.00) as total
            FROM expense_reports
            WHERE employee_id IN ({$inPlaceholders}) AND status = 'SUBMITTED'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($subIds);
        $row = $stmt->fetch();
        $metrics['team_pending_count'] = (int) ($row['cnt'] ?? 0);
        $metrics['team_pending_total'] = (float) ($row['total'] ?? 0);

        // Approved this month
        $sqlApp = "
            SELECT COUNT(*)
            FROM expense_reports
            WHERE employee_id IN ({$inPlaceholders})
              AND status IN ('APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID')
              AND approved_at >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
        ";
        $stmtApp = $pdo->prepare($sqlApp);
        $stmtApp->execute($subIds);
        $metrics['team_approved_month'] = (int) $stmtApp->fetchColumn();

        // List of pending approval reports
        $sqlList = "
            SELECT r.report_id, r.report_number, r.title, r.total_amount, r.submitted_at, emp.full_name as employee_name
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            WHERE r.employee_id IN ({$inPlaceholders}) AND r.status = 'SUBMITTED'
            ORDER BY r.submitted_at ASC
            LIMIT 5
        ";
        $stmtList = $pdo->prepare($sqlList);
        $stmtList->execute($subIds);
        $metrics['pending_approval_reports'] = $stmtList->fetchAll();

        // Include manager + subordinates in team trends
        $teamIds = $subIds;
        if (!in_array($empId, $teamIds, true)) {
            $teamIds[] = $empId;
        }
        $metrics['monthly_trend'] = $this->getTeamMonthlyTrend($teamIds);
        $metrics['category_breakdown'] = $this->getTeamCategoryBreakdown($teamIds);

        return $metrics;
    }

    private function getFinanceMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();

        // 1. Awaiting posting
        $stmt1 = $pdo->query('
            SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0.00) as total
            FROM expense_reports
            WHERE status = "APPROVED"
        ');
        $row1 = $stmt1->fetch();

        // 2. Awaiting reimbursement payment
        $stmt2 = $pdo->query('
            SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0.00) as total
            FROM reimbursements
            WHERE status IN ("PENDING", "PROCESSING")
        ');
        $row2 = $stmt2->fetch();

        // 3. Paid this month
        $stmt3 = $pdo->query('
            SELECT COALESCE(SUM(amount), 0.00)
            FROM reimbursements
            WHERE status = "PAID"
              AND payment_date >= DATE_FORMAT(NOW(), "%Y-%m-01")
        ');
        $paidMonth = (float) $stmt3->fetchColumn();

        // Recent awaiting posting reports
        $stmtPost = $pdo->query('
            SELECT r.report_id, r.report_number, r.title, r.total_amount, r.approved_at, emp.full_name as employee_name
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            WHERE r.status = "APPROVED"
            ORDER BY r.approved_at ASC
            LIMIT 5
        ');

        // Recent reimbursements queue
        $stmtRem = $pdo->query('
            SELECT rem.reimbursement_id, rem.amount, rem.status, r.report_number, r.title, emp.full_name as employee_name, emp.bank_name
            FROM reimbursements rem
            JOIN expense_reports r ON r.report_id = rem.report_id
            JOIN employees emp ON emp.employee_id = rem.employee_id
            WHERE rem.status IN ("PENDING", "PROCESSING")
            ORDER BY rem.reimbursement_id ASC
            LIMIT 5
        ');

        return [
            'role' => 'FINANCE',
            'awaiting_post_count' => (int) ($row1['cnt'] ?? 0),
            'awaiting_post_total' => (float) ($row1['total'] ?? 0),
            'awaiting_reimb_count' => (int) ($row2['cnt'] ?? 0),
            'awaiting_reimb_total' => (float) ($row2['total'] ?? 0),
            'paid_month_total' => $paidMonth,
            'posting_queue' => $stmtPost->fetchAll(),
            'reimbursement_queue' => $stmtRem->fetchAll(),
            'monthly_trend' => $this->getCompanyMonthlyTrend(),
            'category_breakdown' => $this->getCompanyCategoryBreakdown(),
        ];
    }

    private function getAdminMetrics(array $user, ?int $empId): array
    {
        $pdo = Database::pdo();

        $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn();
        $categoriesCount = (int) $pdo->query('SELECT COUNT(*) FROM expense_categories WHERE is_active = 1')->fetchColumn();
        $expensesCount = (int) $pdo->query('SELECT COUNT(*) FROM expenses')->fetchColumn();
        $totalExpenses = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0.00) FROM expenses WHERE status = "PAID"')->fetchColumn();

        $recentActivity = $pdo->query('
            SELECT a.*, u.username
            FROM activity_logs a
            LEFT JOIN users u ON u.user_id = a.user_id
            ORDER BY a.activity_id DESC
            LIMIT 6
        ')->fetchAll();

        return [
            'role' => 'ADMIN',
            'users_count' => $usersCount,
            'categories_count' => $categoriesCount,
            'expenses_count' => $expensesCount,
            'total_paid_expenses' => $totalExpenses,
            'recent_activity' => $recentActivity,
            'monthly_trend' => $this->getCompanyMonthlyTrend(),
            'category_breakdown' => $this->getCompanyCategoryBreakdown(),
        ];
    }

    private function fillLastSixMonths(array $dbRows): array
    {
        $mapped = [];
        foreach ($dbRows as $row) {
            $mapped[$row['ym']] = (float) $row['total'];
        }

        $idMonths = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
            '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        $months = [];
        $startDate = new \DateTimeImmutable('first day of this month - 5 months');
        $endDate = new \DateTimeImmutable('first day of this month');

        $curr = $startDate;
        while ($curr <= $endDate) {
            $ym = $curr->format('Y-m');
            $mNum = $curr->format('m');
            $year = $curr->format('Y');
            $label = ($idMonths[$mNum] ?? $curr->format('M')) . ' ' . $year;

            $months[] = [
                'month' => $ym,
                'label' => $label,
                'total' => $mapped[$ym] ?? 0.00,
            ];
            $curr = $curr->modify('+1 month');
        }

        return $months;
    }

    private function getEmployeeMonthlyTrend(int $empId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT DATE_FORMAT(expense_date, "%Y-%m") as ym, COALESCE(SUM(amount), 0.00) as total
            FROM expenses
            WHERE employee_id = :emp_id
              AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(expense_date, "%Y-%m")
            ORDER BY ym ASC
        ');
        $stmt->execute(['emp_id' => $empId]);
        return $this->fillLastSixMonths($stmt->fetchAll());
    }

    private function getEmployeeCategoryBreakdown(int $empId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT c.category_name, COALESCE(SUM(e.amount), 0.00) as total
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE e.employee_id = :emp_id
            GROUP BY c.category_id, c.category_name
            ORDER BY total DESC
            LIMIT 6
        ');
        $stmt->execute(['emp_id' => $empId]);
        return $stmt->fetchAll();
    }

    private function getTeamMonthlyTrend(array $teamIds): array
    {
        if (empty($teamIds)) {
            return $this->fillLastSixMonths([]);
        }
        $pdo = Database::pdo();
        $inPl = implode(',', array_fill(0, count($teamIds), '?'));
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(expense_date, '%Y-%m') as ym, COALESCE(SUM(amount), 0.00) as total
            FROM expenses
            WHERE employee_id IN ({$inPl})
              AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
            ORDER BY ym ASC
        ");
        $stmt->execute($teamIds);
        return $this->fillLastSixMonths($stmt->fetchAll());
    }

    private function getTeamCategoryBreakdown(array $teamIds): array
    {
        if (empty($teamIds)) {
            return [];
        }
        $pdo = Database::pdo();
        $inPl = implode(',', array_fill(0, count($teamIds), '?'));
        $stmt = $pdo->prepare("
            SELECT c.category_name, COALESCE(SUM(e.amount), 0.00) as total
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE e.employee_id IN ({$inPl})
            GROUP BY c.category_id, c.category_name
            ORDER BY total DESC
            LIMIT 6
        ");
        $stmt->execute($teamIds);
        return $stmt->fetchAll();
    }

    private function getCompanyMonthlyTrend(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('
            SELECT DATE_FORMAT(expense_date, "%Y-%m") as ym, COALESCE(SUM(amount), 0.00) as total
            FROM expenses
            WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(expense_date, "%Y-%m")
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
            GROUP BY c.category_id, c.category_name
            ORDER BY total DESC
            LIMIT 6
        ');
        return $stmt->fetchAll();
    }
}
