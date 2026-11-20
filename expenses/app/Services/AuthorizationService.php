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

        // Owner check
        if (($empId !== null && (int) $expense['employee_id'] === $empId) || (int) $expense['created_by'] === $userId) {
            return true;
        }

        // Admin check
        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
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
        return !empty($user['employee_id']) || in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true);
    }

    public function canCreateReport(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        if ($role === 'MANAGER') {
            return false;
        }
        return !empty($user['employee_id']) || in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true);
    }

    public function canEditExpense(array $user, array $expense): bool
    {
        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Must be status DRAFT
        if ($expense['status'] !== 'DRAFT') {
            return false;
        }

        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return true;
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
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        // Admin check
        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return true;
        }

        // Creator check
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return true;
        }

        // Owner check (legacy employee_id)
        if ($empId !== null && !empty($report['employee_id']) && (int) $report['employee_id'] === $empId) {
            return true;
        }

        // Employee in multi-employee report check
        if ($empId !== null && !empty($report['report_id'])) {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM expense_report_employees WHERE report_id = :r_id AND employee_id = :e_id');
            $stmt->execute(['r_id' => $report['report_id'], 'e_id' => $empId]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }

        // Manager check
        if (($role === 'MANAGER' || in_array('expense.approve', $user['permissions'] ?? [])) && $empId !== null) {
            if (!empty($report['employee_id']) && $this->employeeRepo->isDirectManager($empId, (int) $report['employee_id'])) {
                return true;
            }
            // Or direct manager of any employee in report
            if (!empty($report['report_id'])) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('
                    SELECT COUNT(*)
                    FROM expense_report_employees ere
                    JOIN employees emp ON emp.employee_id = ere.employee_id
                    WHERE ere.report_id = :r_id AND emp.manager_id = :mgr_id
                ');
                $stmt->execute(['r_id' => $report['report_id'], 'mgr_id' => $empId]);
                if ((int) $stmt->fetchColumn() > 0) {
                    return true;
                }
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
        if ($report['status'] !== 'DRAFT') {
            return false;
        }

        $role = $user['role_name'] ?? '';
        $userId = (int) ($user['user_id'] ?? 0);
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return true;
        }

        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return true;
        }

        return $empId !== null && !empty($report['employee_id']) && (int) $report['employee_id'] === $empId;
    }

    public function canSubmitReport(array $user, array $report): bool
    {
        return $this->canEditReport($user, $report);
    }

    public function canDecideReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;
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

        // Finance without manager role cannot decide
        if ($role === 'FINANCE' && !in_array('expense.approve', $user['permissions'] ?? [])) {
            return false;
        }

        // Cannot decide own report if user is the sole employee
        if ($empId !== null && !empty($report['employee_id']) && (int) $report['employee_id'] === $empId) {
            // Check if there are other employees
            if (!empty($report['report_id'])) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM expense_report_employees WHERE report_id = :r_id AND employee_id != :e_id');
                $stmt->execute(['r_id' => $report['report_id'], 'e_id' => $empId]);
                if ((int) $stmt->fetchColumn() === 0) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // Direct manager check
        if ($empId !== null) {
            if (!empty($report['employee_id']) && $this->employeeRepo->isDirectManager($empId, (int) $report['employee_id'])) {
                return true;
            }
            if (!empty($report['report_id'])) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('
                    SELECT COUNT(*)
                    FROM expense_report_employees ere
                    JOIN employees emp ON emp.employee_id = ere.employee_id
                    WHERE ere.report_id = :r_id AND emp.manager_id = :mgr_id
                ');
                $stmt->execute(['r_id' => $report['report_id'], 'mgr_id' => $empId]);
                if ((int) $stmt->fetchColumn() > 0) {
                    return true;
                }
            }
        }

        // General manager with permission
        if (in_array('expense.approve', $user['permissions'] ?? [])) {
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
        $empId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;

        if (in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE'], true)) {
            return true;
        }

        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return true;
        }

        return $empId !== null && !empty($report['employee_id']) && (int) $report['employee_id'] === $empId;
    }

    public function canPostReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasPostPerm = in_array($role, ['FINANCE', 'ADMIN'], true) || in_array('expense.post', $user['permissions'] ?? []);
        return $hasPostPerm && $report['status'] === 'APPROVED';
    }

    public function canReimburseReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = in_array($role, ['FINANCE', 'ADMIN'], true) || in_array('expense.reimburse', $user['permissions'] ?? []);
        return $hasReimbursePerm && in_array($report['status'], ['POSTED', 'REIMBURSEMENT_PENDING'], true);
    }

    public function canRecordPayment(array $user, array $reimbursement): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = in_array($role, ['FINANCE', 'ADMIN'], true) || in_array('expense.reimburse', $user['permissions'] ?? []);
        return $hasReimbursePerm && in_array($reimbursement['status'], ['PENDING', 'PROCESSING', 'PARTIALLY_PAID'], true);
    }
}
