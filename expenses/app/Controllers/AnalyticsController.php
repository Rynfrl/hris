<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\EmployeeRepository;
use App\Services\CsvExportService;
use PDO;

class AnalyticsController
{
    private CategoryRepository $categoryRepo;
    private EmployeeRepository $employeeRepo;
    private CsvExportService $csvService;

    public function __construct(
        ?CategoryRepository $categoryRepo = null,
        ?EmployeeRepository $employeeRepo = null,
        ?CsvExportService $csvService = null
    ) {
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
        $this->csvService = $csvService ?? new CsvExportService();
    }

    private function getScopeConditions(array $user, string $alias = 'e'): array
    {
        $role = $user['role_name'] ?? 'EMPLOYEE';
        $isAdmin = in_array($role, ['ADMIN', 'ADMIN_EXPENSE'], true) || Auth::hasPermission('expenses.manage');
        $isManager = ($role === 'MANAGER') || Auth::hasPermission('expense.approve');
        $isFinance = ($role === 'FINANCE') || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse');
        $userId = (int) ($user['user_id'] ?? 0);

        if ($isAdmin) {
            return ['', []];
        }

        if ($isFinance) {
            // Finance only ever sees approved, posted, reimbursement pending, or paid
            return [
                "{$alias}.status IN ('APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID')",
                []
            ];
        }

        if ($isManager) {
            // Manager sees assigned reports, decided reports, and own created drafts (Prioritas 1)
            return [
                "({$alias}.approver_id = :scope_approver_id OR EXISTS (SELECT 1 FROM expense_report_approvers era WHERE era.report_id = {$alias}.report_id AND era.approver_id = :scope_approver_id_era) OR {$alias}.created_by = :scope_user_id)",
                [
                    'scope_approver_id' => $userId,
                    'scope_approver_id_era' => $userId,
                    'scope_user_id' => $userId,
                ]
            ];
        }

        return ['1=0', []];
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        // Employee has no access to operational expense menus
        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['e.expense_date >= :start_date', 'e.expense_date <= :end_date'];
        $params = ['start_date' => $startDate, 'end_date' => $endDate];

        // Apply strict role scoping (Perbaikan 1)
        [$scopeSql, $scopeParams] = $this->getScopeConditions($user, 'e');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if (!empty($status)) {
            $statusUpper = strtoupper($status);
            $role = $user['role_name'] ?? '';
            $isFinance = ($role === 'FINANCE') || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse');
            $isAdmin = in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || Auth::hasPermission('expenses.manage');

            // Prevent Finance from broadening access via status parameter
            if ($isFinance && !$isAdmin) {
                if (in_array($statusUpper, ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                    $where[] = 'e.status = :status';
                    $params['status'] = $statusUpper;
                }
            } else {
                $where[] = 'e.status = :status';
                $params['status'] = $statusUpper;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Overall summary
        $stmtSum = $pdo->prepare("
            SELECT COUNT(*) as total_count, COALESCE(SUM(e.amount), 0.00) as total_amount
            FROM expenses e
            WHERE {$whereClause}
        ");
        $stmtSum->execute($params);
        $summary = $stmtSum->fetch();

        // Breakdown by Category
        $stmtCat = $pdo->prepare("
            SELECT c.category_name, c.category_code, COUNT(e.expense_id) as count, COALESCE(SUM(e.amount), 0.00) as total
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            WHERE {$whereClause}
            GROUP BY c.category_id, c.category_name, c.category_code
            ORDER BY total DESC
        ");
        $stmtCat->execute($params);
        $byCategory = $stmtCat->fetchAll();

        // Monthly Breakdown
        $stmtMonth = $pdo->prepare("
            SELECT DATE_FORMAT(e.expense_date, '%Y-%m') as month, COUNT(e.expense_id) as count, COALESCE(SUM(e.amount), 0.00) as total
            FROM expenses e
            WHERE {$whereClause}
            GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmtMonth->execute($params);
        $byMonth = $stmtMonth->fetchAll();

        return View::render('analytics/index', [
            'title' => 'Analitik & Laporan Pengeluaran',
            'summary' => $summary,
            'byCategory' => $byCategory,
            'byMonth' => $byMonth,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'user' => $user,
        ]);
    }

    public function exportExpensesCsv(): void
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        // Apply role scoping (Perbaikan 1)
        [$scopeSql, $scopeParams] = $this->getScopeConditions($user, 'e');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if (!empty($startDate)) {
            $where[] = 'e.expense_date >= :start_date';
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = 'e.expense_date <= :end_date';
            $params['end_date'] = $endDate;
        }
        if (!empty($status)) {
            $statusUpper = strtoupper($status);
            $isFinance = ($role === 'FINANCE') || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse');
            $isAdmin = in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || Auth::hasPermission('expenses.manage');

            if ($isFinance && !$isAdmin) {
                if (in_array($statusUpper, ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                    $where[] = 'e.status = :status';
                    $params['status'] = $statusUpper;
                }
            } else {
                $where[] = 'e.status = :status';
                $params['status'] = $statusUpper;
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT e.expense_id, e.expense_number, e.expense_date, e.description,
                   c.category_code, c.category_name,
                   e.amount, e.currency, e.payment_method, e.bank_name, e.merchant,
                   emp.employee_number, emp.full_name as employee_name,
                   d.department_code, d.department_name,
                   e.status, er.report_number, e.is_reimbursable, e.created_at
            FROM expenses e
            JOIN expense_categories c ON c.category_id = e.category_id
            LEFT JOIN employees emp ON emp.employee_id = e.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            LEFT JOIN expense_report_items eri ON eri.expense_id = e.expense_id
            LEFT JOIN expense_reports er ON er.report_id = eri.report_id
            WHERE {$whereClause}
            ORDER BY e.expense_date DESC, e.expense_id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        $headers = [
            'ID Pengeluaran', 'No. Pengeluaran', 'Tanggal', 'Deskripsi',
            'Kode Kategori', 'Nama Kategori', 'Nominal', 'Mata Uang', 'Metode Pembayaran', 'Bank', 'Merchant',
            'No. Karyawan', 'Nama Karyawan', 'Kode Dept', 'Nama Dept',
            'Status', 'No. Laporan', 'Dapat Diganti (Reimbursable)', 'Dibuat Pada'
        ];

        $filename = 'expenses-' . date('Y-m-d') . '.csv';
        $this->csvService->export($filename, $headers, $rows);
    }

    public function exportReportsCsv(): void
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        // Apply role scoping (Perbaikan 1)
        [$scopeSql, $scopeParams] = $this->getScopeConditions($user, 'r');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }

        if (!empty($startDate)) {
            $where[] = 'r.created_at >= :start_date';
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        if (!empty($endDate)) {
            $where[] = 'r.created_at <= :end_date';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        if (!empty($status)) {
            $statusUpper = strtoupper($status);
            $isFinance = ($role === 'FINANCE') || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse');
            $isAdmin = in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || Auth::hasPermission('expenses.manage');

            if ($isFinance && !$isAdmin) {
                if (in_array($statusUpper, ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                    $where[] = 'r.status = :status';
                    $params['status'] = $statusUpper;
                }
            } else {
                $where[] = 'r.status = :status';
                $params['status'] = $statusUpper;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Multi-employee consolidated query (Perbaikan 11)
        $sql = "
            SELECT r.report_id, r.report_number, r.title,
                   COALESCE(emp_cre.full_name, u_cre.username, 'Admin') as creator_name,
                   (SELECT COUNT(DISTINCT ere.employee_id) FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as employee_count,
                   (SELECT GROUP_CONCAT(DISTINCT ere.employee_name_snapshot ORDER BY ere.employee_name_snapshot ASC SEPARATOR '; ') 
                    FROM expense_report_employees ere WHERE ere.report_id = r.report_id) as participant_names,
                   (SELECT COUNT(*) FROM expense_report_items eri WHERE eri.report_id = r.report_id) as transaction_count,
                   r.total_amount, r.status,
                   r.submitted_at, r.approved_at, r.posted_at, r.refused_at,
                   r.rejection_reason, r.paid_at
            FROM expense_reports r
            JOIN users u_cre ON u_cre.user_id = r.created_by
            LEFT JOIN employees emp_cre ON emp_cre.employee_id = u_cre.employee_id
            WHERE {$whereClause}
            ORDER BY r.created_at DESC, r.report_id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        $headers = [
            'ID Laporan', 'No. Laporan', 'Judul Laporan', 'Pembuat Laporan',
            'Jumlah Karyawan', 'Daftar Karyawan Terlibat', 'Jumlah Transaksi', 'Total Nominal', 'Status',
            'Diajukan Pada', 'Disetujui Pada', 'Dibukukan Pada', 'Ditolak Pada', 'Alasan Penolakan', 'Dibayar Pada'
        ];

        $filename = 'expense-reports-' . date('Y-m-d') . '.csv';
        $this->csvService->export($filename, $headers, $rows);
    }
}
