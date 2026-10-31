<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmployeeRepository;

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

        // Owner check
        if (($empId !== null && (int) $expense['employee_id'] === $empId) || (int) $expense['created_by'] === $userId) {
            return true;
        }

        // Admin check
        if ($role === 'ADMIN') {
            return true;
        }

        // Manager check: direct manager of expense employee
        if (($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) && $empId !== null) {
            if ($this->employeeRepo->isDirectManager($empId, (int) $expense['employee_id'])) {
                return true;
            }
        }

        // Finance check: report is approved or later
        if ($role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? []) || in_array('expense.reimburse', $user['permissions'] ?? [])) {
            if (in_array($expense['status'], ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                return true;
            }
        }

        return false;
    }

    public function canCreateExpense(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        if ($role === 'MANAGER') {
            return false;
        }
        return !empty($user['employee_id']);
    }

    public function canCreateReport(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        if ($role === 'MANAGER') {
            return false;
        }
        return !empty($user['employee_id']);
    }

    public function canEditExpense(array $user, array $expense): bool
    {
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Must be owner and status must be DRAFT
        if ($expense['status'] !== 'DRAFT') {
            return false;
        }

        return ($empId !== null && (int) $expense['employee_id'] === $empId) || (int) $expense['created_by'] === $userId;
    }

    public function canDeleteExpense(array $user, array $expense): bool
    {
        if (!$this->canEditExpense($user, $expense)) {
            return false;
        }

        // Cannot delete if already part of a report
        return empty($expense['report_id']);
    }

    public function canViewReceipt(array $user, array $expense): bool
    {
        return $this->canViewExpense($user, $expense);
    }

    public function canViewReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Owner check
        if ($empId !== null && (int) $report['employee_id'] === $empId) {
            return true;
        }

        // Admin check
        if ($role === 'ADMIN') {
            return true;
        }

        // Manager check
        if (($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) && $empId !== null) {
            if ($this->employeeRepo->isDirectManager($empId, (int) $report['employee_id'])) {
                return true;
            }
        }

        // Finance check
        if ($role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? []) || in_array('expense.reimburse', $user['permissions'] ?? [])) {
            if (in_array($report['status'], ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
                return true;
            }
        }

        return false;
    }

    public function canEditReport(array $user, array $report): bool
    {
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;
        return $empId !== null && (int) $report['employee_id'] === $empId && $report['status'] === 'DRAFT';
    }

    public function canSubmitReport(array $user, array $report): bool
    {
        return $this->canEditReport($user, $report);
    }

    public function canDecideReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        if ($empId === null || $report['status'] !== 'SUBMITTED') {
            return false;
        }

        // Cannot decide own report
        if ((int) $report['employee_id'] === $empId) {
            return false;
        }

        // Must be direct manager
        return $this->employeeRepo->isDirectManager($empId, (int) $report['employee_id']);
    }

    public function canReviseReport(array $user, array $report): bool
    {
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;
        return $empId !== null && (int) $report['employee_id'] === $empId && $report['status'] === 'REFUSED';
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
}
