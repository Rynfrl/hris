<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Core\Database;
use PDO;

class AuthorizationService
{
    private EmployeeRepository $employeeRepo;

    public function __construct(?EmployeeRepository $employeeRepo = null)
    {
        $this->employeeRepo = $employeeRepo ?? new EmployeeRepository();
    }

    /**
     * Checks if user has Admin Expense role or explicit permission to manage expenses.
     * Admin_HR is a general HRIS role and does NOT automatically grant Admin Expense rights.
     */
    public function isAdminExpense(array $user): bool
    {
        $role = $user['role_name'] ?? '';
        $perms = $user['permissions'] ?? [];
        return in_array($role, ['ADMIN', 'ADMIN_EXPENSE'], true)
            || in_array('expenses.manage', $perms, true)
            || in_array('expense.manage', $perms, true);
    }

    /**
     * Checks if user is an authorized Manager Approver for a specific report.
     * Enforces Anti Self-Approval (creator !== approver) and Record-Level Assignment.
     */
    public function isReportApprover(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $perms = $user['permissions'] ?? [];
        $userId = (int) ($user['user_id'] ?? 0);

        if ($userId <= 0) {
            return false;
        }

        // Anti Self-Approval: creator cannot approve their own report
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return false;
        }

        // Must have Manager role or explicit expense.approve permission
        $isManager = ($role === 'MANAGER' || $role === 'MANAGER_EXPENSE' || in_array('expense.approve', $perms, true));
        if (!$isManager) {
            return false;
        }

        $reportId = (int) ($report['report_id'] ?? 0);
        if ($reportId <= 0) {
            return true;
        }

        // 1. Direct record-level assignment check from array
        if (isset($report['approver_id'])) {
            if ((int) $report['approver_id'] === $userId) {
                return true;
            }
            if ($report['approver_id'] !== null && (int) $report['approver_id'] !== 0) {
                // Explicitly assigned to someone else; check multi-approver or past approvals
                return $this->isAssignedOrPastApprover($reportId, $userId);
            }
        }

        // 2. Database verification for multi-approver or historical decider
        return $this->isAssignedOrPastApprover($reportId, $userId);
    }

    private function isAssignedOrPastApprover(int $reportId, int $userId): bool
    {
        try {
            $pdo = Database::pdo();
            // Check expense_reports.approver_id
            $stmt = $pdo->prepare('SELECT approver_id FROM expense_reports WHERE report_id = :rep_id');
            $stmt->execute(['rep_id' => $reportId]);
            $assignedApprover = $stmt->fetchColumn();

            if ($assignedApprover !== false && $assignedApprover !== null && (int) $assignedApprover > 0) {
                if ((int) $assignedApprover === $userId) {
                    return true;
                }
            }

            // Check expense_report_approvers
            $stmt2 = $pdo->prepare('SELECT 1 FROM expense_report_approvers WHERE report_id = :rep_id AND approver_id = :user_id LIMIT 1');
            $stmt2->execute(['rep_id' => $reportId, 'user_id' => $userId]);
            if ($stmt2->fetchColumn()) {
                return true;
            }

            // Check past approvals (if manager already acted on this report)
            $stmt3 = $pdo->prepare('SELECT 1 FROM expense_approvals WHERE report_id = :rep_id AND approver_id = :user_id LIMIT 1');
            $stmt3->execute(['rep_id' => $reportId, 'user_id' => $userId]);
            if ($stmt3->fetchColumn()) {
                return true;
            }

            // If completely unassigned, allow any qualified manager (fallback)
            if ($assignedApprover === null || $assignedApprover === false || (int) $assignedApprover === 0) {
                $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM expense_report_approvers WHERE report_id = :rep_id');
                $stmtCount->execute(['rep_id' => $reportId]);
                if ((int) $stmtCount->fetchColumn() === 0) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    public function canViewExpense(array $user, array $expense): bool
    {
        if ($this->isAdminExpense($user)) {
            return true;
        }

        $userId = (int) ($user['user_id'] ?? 0);

        // Creator check
        if ((int) ($expense['created_by'] ?? 0) === $userId) {
            return true;
        }

        // If linked to a report, strictly verify report viewing rights
        if (!empty($expense['report_id'])) {
            $report = [
                'report_id' => (int) $expense['report_id'],
                'created_by' => $expense['report_created_by'] ?? null,
                'approver_id' => $expense['report_approver_id'] ?? ($expense['approver_id'] ?? null),
                'status' => $expense['report_status'] ?? ($expense['status'] ?? null),
            ];
            return $this->canViewReport($user, $report);
        }

        // Unassigned draft item belonging to someone else cannot be viewed
        return false;
    }

    public function canCreateExpense(array $user): bool
    {
        return $this->isAdminExpense($user);
    }

    public function canCreateReport(array $user): bool
    {
        return $this->isAdminExpense($user);
    }

    public function canEditExpense(array $user, array $expense): bool
    {
        // Expense item must be status DRAFT
        if (($expense['status'] ?? '') !== 'DRAFT') {
            return false;
        }

        // Parent report check: if linked to a report, report must also be DRAFT
        if (!empty($expense['report_id'])) {
            $reportStatus = $expense['report_status'] ?? null;
            if ($reportStatus === null) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT status FROM expense_reports WHERE report_id = :id');
                $stmt->execute(['id' => (int) $expense['report_id']]);
                $reportStatus = $stmt->fetchColumn() ?: null;
            }
            if ($reportStatus !== null && $reportStatus !== 'DRAFT') {
                return false;
            }
        }

        if ($this->isAdminExpense($user)) {
            return true;
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return (int) ($expense['created_by'] ?? 0) === $userId;
    }

    public function canDeleteExpense(array $user, array $expense): bool
    {
        return $this->canEditExpense($user, $expense);
    }

    public function canViewReceipt(array $user, array $expense): bool
    {
        return $this->canViewExpense($user, $expense);
    }

    public function canViewReport(array $user, array $report): bool
    {
        // 1. Admin Expense
        if ($this->isAdminExpense($user)) {
            return true;
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $role = $user['role_name'] ?? '';
        $userPerms = $user['permissions'] ?? [];

        // 2. Creator
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return true;
        }

        // 3. Manager: scoped to assigned reports or decided reports
        if ($role === 'MANAGER' || $role === 'MANAGER_EXPENSE' || in_array('expense.approve', $userPerms, true)) {
            return $this->isReportApprover($user, $report);
        }

        // 4. Finance: can view approved, posted, reimbursement_pending, paid reports
        if ($role === 'FINANCE' || in_array('expense.post', $userPerms, true) || in_array('expense.reimburse', $userPerms, true)) {
            if (in_array($report['status'] ?? '', ['APPROVED', 'POSTED', 'REIMBURSEMENT_PENDING', 'PAID'], true)) {
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

        if ($this->isAdminExpense($user)) {
            return true;
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return !empty($report['created_by']) && (int) $report['created_by'] === $userId;
    }

    public function canSubmitReport(array $user, array $report): bool
    {
        return $this->canEditReport($user, $report);
    }

    public function canDecideReport(array $user, array $report): bool
    {
        if (($report['status'] ?? '') !== 'SUBMITTED') {
            return false;
        }

        $userId = (int) ($user['user_id'] ?? 0);

        // Anti Self-Approval: creator cannot approve their own submitted report
        if (!empty($report['created_by']) && (int) $report['created_by'] === $userId) {
            return false;
        }

        // Must be assigned approver
        return $this->isReportApprover($user, $report);
    }

    public function canReviseReport(array $user, array $report): bool
    {
        if ($report['status'] !== 'REFUSED') {
            return false;
        }

        if ($this->isAdminExpense($user)) {
            return true;
        }

        $userId = (int) ($user['user_id'] ?? 0);
        return !empty($report['created_by']) && (int) $report['created_by'] === $userId;
    }

    public function canPostReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasPostPerm = $role === 'FINANCE' || in_array('expense.post', $user['permissions'] ?? [], true);
        return $hasPostPerm && ($report['status'] ?? '') === 'APPROVED';
    }

    public function canReimburseReport(array $user, array $report): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = $role === 'FINANCE' || in_array('expense.reimburse', $user['permissions'] ?? [], true);
        return $hasReimbursePerm && in_array($report['status'] ?? '', ['POSTED', 'REIMBURSEMENT_PENDING'], true);
    }

    public function canRecordPayment(array $user, array $reimbursement): bool
    {
        $role = $user['role_name'] ?? '';
        $hasReimbursePerm = $role === 'FINANCE' || in_array('expense.reimburse', $user['permissions'] ?? [], true);
        // Payment can only be recorded when status is PROCESSING (or initial PENDING)
        // Never allowed on FAILED without prior retry
        return $hasReimbursePerm && in_array($reimbursement['status'] ?? '', ['PROCESSING', 'PENDING'], true);
    }
}
