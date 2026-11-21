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
            SELECT e.employee_id, e.id_employee, e.employee_number, e.full_name, e.nama, e.email, e.phone, e.department_id,
                   e.manager_id, e.position, e.jabatan, e.bank_name, e.bank_account, e.status,
                   d.department_name, d.department_code,
                   m.full_name as manager_name
            FROM employees e
            LEFT JOIN departments d ON d.department_id = e.department_id
            LEFT JOIN employees m ON m.employee_id = e.manager_id
            WHERE e.employee_id = :id1 OR e.id_employee = :id2
            LIMIT 1
        ');
        $stmt->execute(['id1' => $id, 'id2' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['full_name'] = !empty($row['full_name']) ? $row['full_name'] : ($row['nama'] ?? 'Karyawan');
            $row['position'] = !empty($row['position']) ? $row['position'] : ($row['jabatan'] ?? 'Staff');
            $empNumId = (int) ($row['employee_id'] ?: $row['id_employee']);
            $row['employee_number'] = !empty($row['employee_number']) ? $row['employee_number'] : ('EMP-' . str_pad((string)$empNumId, 4, '0', STR_PAD_LEFT));
            $row['employee_id'] = $empNumId;
            $row['is_active'] = ($row['status'] ?? 'ACTIVE') === 'ACTIVE' ? 1 : 0;
        }
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
