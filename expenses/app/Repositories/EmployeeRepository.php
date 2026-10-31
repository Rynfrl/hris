<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EmployeeRepository
{
    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.employee_id, e.employee_number, e.full_name, e.email, e.phone, e.department_id,
                   e.manager_id, e.position, e.bank_name, e.bank_account, e.status,
                   d.department_name, d.department_code,
                   m.full_name as manager_name
            FROM employees e
            LEFT JOIN departments d ON d.department_id = e.department_id
            LEFT JOIN employees m ON m.employee_id = e.manager_id
            WHERE e.employee_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(bool $activeOnly = false): array
    {
        $pdo = Database::pdo();
        $sql = '
            SELECT e.employee_id, e.employee_number, e.full_name, e.department_id, e.manager_id, e.status,
                   d.department_name
            FROM employees e
            LEFT JOIN departments d ON d.department_id = e.department_id
        ';
        if ($activeOnly) {
            $sql .= " WHERE e.status = 'ACTIVE'";
        }
        $sql .= ' ORDER BY e.full_name ASC';
        return $pdo->query($sql)->fetchAll();
    }

    public function getDirectReports(int $managerEmployeeId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT e.employee_id, e.employee_number, e.full_name, e.department_id, e.status,
                   d.department_name
            FROM employees e
            LEFT JOIN departments d ON d.department_id = e.department_id
            WHERE e.manager_id = :mgr_id
            ORDER BY e.full_name ASC
        ");
        $stmt->execute(['mgr_id' => $managerEmployeeId]);
        return $stmt->fetchAll();
    }

    public function isDirectManager(int $managerEmployeeId, int $subordinateEmployeeId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM employees
            WHERE employee_id = :sub_id AND manager_id = :mgr_id
        ');
        $stmt->execute([
            'sub_id' => $subordinateEmployeeId,
            'mgr_id' => $managerEmployeeId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
