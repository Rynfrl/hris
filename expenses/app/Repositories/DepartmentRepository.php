<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class DepartmentRepository
{
    public function getAll(): array
    {
        $pdo = Database::pdo();
        return $pdo->query('
            SELECT d.department_id, d.department_code, d.department_name, d.manager_id, d.description, d.is_active,
                   e.full_name as manager_name
            FROM departments d
            LEFT JOIN employees e ON e.employee_id = d.manager_id
            ORDER BY d.department_name ASC
        ')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT d.department_id, d.department_code, d.department_name, d.manager_id, d.description, d.is_active
            FROM departments d
            WHERE d.department_id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO departments (department_code, department_name, manager_id, description, is_active)
            VALUES (:code, :name, :mgr, :desc, :active)
        ');
        $stmt->execute([
            'code' => strtoupper(trim($data['department_code'])),
            'name' => trim($data['department_name']),
            'mgr' => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            'desc' => trim($data['description'] ?? ''),
            'active' => (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE departments
            SET department_code = :code,
                department_name = :name,
                manager_id = :mgr,
                description = :desc,
                is_active = :active
            WHERE department_id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'code' => strtoupper(trim($data['department_code'])),
            'name' => trim($data['department_name']),
            'mgr' => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            'desc' => trim($data['description'] ?? ''),
            'active' => (int) ($data['is_active'] ?? 1),
        ]);
    }
}
