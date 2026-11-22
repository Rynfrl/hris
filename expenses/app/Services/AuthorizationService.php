<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Core\Database;

class AuthorizationService
{
    private EmployeeRepository $employeeRepo;

    public function __construct(?EmployeeRepository $employeeRepo = null)
    {
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
    }

    public function canViewExpense(array $user, array $expense): bool
    {
        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Admin Expense check
        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])) {
            return true;
        }

        // Creator check
        if ((int) ($expense['created_by'] ?? 0) === $userId) {
            return true;
        }

        // Manager check
        if ($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) {
            return true;
        }

        // Finance check: report is approved or later
        if ($role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? []) || in_array('expense.reimburse', $user['permissions'] ?? [])) {
            if (in_array($expense['status'] ?? '', ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                return true;
            }
        }

        return false;
    }

    public function canCreateExpense(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        // Only Admin Expense can create transactions
        if (in_array($role, ['EMPLOYEE', 'MANAGER', 'FINANCE'], true) && !in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return false;
        }
        return in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? []);
    }

    public function canCreateReport(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        // Only Admin Expense can create reports
        if (in_array($role, ['EMPLOYEE', 'MANAGER', 'FINANCE'], true) && !in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return false;
        }
        return in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? []);
    }

    public function canEditExpense(array $user, array $expense): bool
    {
        // Must be status DRAFT
        if (($expense['status'] ?? '') !== 'DRAFT') {
            return false;
        }

        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);

        // Only Admin Expense (or creator) can edit transactions
        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])) {
            return true;
        }

        return (int) ($expense['created_by'] ?? 0) === $userId;
    }

    public function canDeleteExpense(array $user, array $expense): bool
    {
        if (!$this->canEditExpense($user, $expense)) {
            return false;
        }

        // Cannot delete if already submitted or approved
        return empty($expense['report_id']) || ($expense['status'] ?? '') === 'DRAFT';
    }

    public function canViewReceipt(array $user, array $expense): bool
    {
        return $this->canViewExpense($user, $expense);
    }

    public function canViewReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Admin check
        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])) {
            return true;
        }

        // Creator check
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return true;
        }

        // Manager check: can view any submitted, approved, posted, paid report
        if ($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) {
            return true;
        }

        // Finance check: can view approved, posted, reimbursement_pending, paid reports
        if ($role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? []) || in_array('expense.reimburse', $user['permissions'] ?? [])) {
            if (in_array($report['status'], ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                return true;
            }
        }

        return false;
    }

    public function canEditReport(array $user, array $report): bool
    {
        if ($report['status'] !== 'DRAFT') {
            return false;
        }

        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);

        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])) {
            return true;
        }

        return !empty($report['created_by']) && (int) $report['created_by'] === $userId;
    }

    public function canSubmitReport(array $user, array $report): bool
    {
        return $this->canEditReport($user, $report);
    }

    public function canDecideReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);

        if ($report['status'] !== 'SUBMITTED') {
            return false;
        }

        // Creator cannot approve their own submitted report (Segregation of Duties)
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return false;
        }

        // Admin Expense cannot approve reports (Approval is strictly reserved for Manager Expense)
        if (in_array($role, ['ADMIN', 'ADMIN_EXPENSE', 'Admin_HR'], true) && !in_array('expense.approve', $user['permissions'] ?? [])) {
            return false;
        }

        // Finance cannot decide approval
        if ($role === 'FINANCE' && !in_array('expense.approve', $user['permissions'] ?? [])) {
            return false;
        }

        // Manager Expense with authority
        if ($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) {
            return true;
        }

        return false;
    }

    public function canReviseReport(array $user, array $report): bool
    {
        if ($report['status'] !== 'REFUSED') {
            return false;
        }

        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);

        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true) || in_array('expenses.manage', $user['permissions'] ?? [])) {
            return true;
        }

        return !empty($report['created_by']) && (int) $report['created_by'] === $userId;
    }

    public function canPostReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasPostPerm = $role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? []);
        return $hasPostPerm && $report['status'] === 'APPROVED';
    }

    public function canReimburseReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = $role === 'FINANCE' || in_array('expense.reimburse', $user['permissions'] ?? []);
        return $hasReimbursePerm && in_array($report['status'], ['POSTED', 'REIMBURSEMENT_PENDING'], true);
    }

    public function canRecordPayment(array $user, array $reimbursement): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = $role === 'FINANCE' || in_array('expense.reimburse', $user['permissions'] ?? []);
        return $hasReimbursePerm && in_array($reimbursement['status'], ['PENDING', 'PROCESSING'], true);
    }
}
