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

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['e.expense_date >= :start_date', 'e.expense_date <= :end_date'];
        $params = ['start_date' => $startDate, 'end_date' => $endDate];

        // Scope filter
        if ($role === 'EMPLOYEE' || ($role !== 'ADMIN' && $role !== 'FINANCE' && $role !== 'MANAGER')) {
            $where[] = 'e.employee_id = :emp_id';
            $params['emp_id'] = $empId ?? -1;
        } elseif ($role === 'MANAGER') {
            $directReports = $this->employeeRepo->getDirectReports($empId ?? 0);
            $empIds = array_column($directReports, 'employee_id');
            if ($empId) {
                $empIds[] = $empId;
            }
            if (!empty($empIds)) {
                $inPl = implode(',', array_map(fn($k) => ':e_' . $k, array_keys($empIds)));
                foreach ($empIds as $k => $id) {
                    $params['e_' . $k] = $id;
                }
                $where[] = "e.employee_id IN ({$inPl})";
            } else {
                $where[] = '1=0';
            }
        }

        if (!empty($status)) {
            $where[] = 'e.status = :status';
            $params['status'] = strtoupper($status);
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
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if ($role === 'EMPLOYEE' || ($role !== 'ADMIN' && $role !== 'FINANCE' && $role !== 'MANAGER')) {
            $where[] = 'e.employee_id = :emp_id';
            $params['emp_id'] = $empId ?? -1;
        } elseif ($role === 'MANAGER') {
            $directReports = $this->employeeRepo->getDirectReports($empId ?? 0);
            $empIds = array_column($directReports, 'employee_id');
            if ($empId) {
                $empIds[] = $empId;
            }
            if (!empty($empIds)) {
                $inPl = implode(',', array_map(fn($k) => ':e_' . $k, array_keys($empIds)));
                foreach ($empIds as $k => $id) {
                    $params['e_' . $k] = $id;
                }
                $where[] = "e.employee_id IN ({$inPl})";
            } else {
                $where[] = '1=0';
            }
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
            $where[] = 'e.status = :status';
            $params['status'] = strtoupper($status);
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
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;

        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if ($role === 'EMPLOYEE' || ($role !== 'ADMIN' && $role !== 'FINANCE' && $role !== 'MANAGER')) {
            $where[] = 'r.employee_id = :emp_id';
            $params['emp_id'] = $empId ?? -1;
        } elseif ($role === 'MANAGER') {
            $directReports = $this->employeeRepo->getDirectReports($empId ?? 0);
            $empIds = array_column($directReports, 'employee_id');
            if ($empId) {
                $empIds[] = $empId;
            }
            if (!empty($empIds)) {
                $inPl = implode(',', array_map(fn($k) => ':r_' . $k, array_keys($empIds)));
                foreach ($empIds as $k => $id) {
                    $params['r_' . $k] = $id;
                }
                $where[] = "r.employee_id IN ({$inPl})";
            } else {
                $where[] = '1=0';
            }
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
            $where[] = 'r.status = :status';
            $params['status'] = strtoupper($status);
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT r.report_id, r.report_number, r.title,
                   emp.employee_number, emp.full_name as employee_name,
                   d.department_name, r.status,
                   (SELECT COUNT(*) FROM expense_report_items WHERE report_id = r.report_id) as expense_count,
                   r.total_amount, r.submitted_at, r.approved_at, r.posted_at, r.refused_at,
                   r.rejection_reason, r.paid_at
            FROM expense_reports r
            JOIN employees emp ON emp.employee_id = r.employee_id
            LEFT JOIN departments d ON d.department_id = emp.department_id
            WHERE {$whereClause}
            ORDER BY r.created_at DESC, r.report_id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        $headers = [
            'ID Laporan', 'No. Laporan', 'Judul Laporan',
            'No. Karyawan', 'Nama Karyawan', 'Departemen', 'Status',
            'Jumlah Item', 'Total Nominal', 'Diajukan Pada', 'Disetujui Pada',
            'Dibukukan Pada', 'Ditolak Pada', 'Alasan Penolakan', 'Dibayar Pada'
        ];

        $filename = 'expense-reports-' . date('Y-m-d') . '.csv';
        $this->csvService->export($filename, $headers, $rows);
    }
}
