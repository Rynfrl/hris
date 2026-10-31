<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT u.user_id, u.username, u.email, u.password_hash, u.employee_id, u.role_id, u.is_active,
                   r.role_name,
                   e.full_name as employee_name, e.department_id, e.manager_id, e.status as employee_status,
                   d.department_name, d.department_code
            FROM users u
            JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            LEFT JOIN departments d ON d.department_id = e.department_id
            WHERE (u.email = :identifier1 OR u.username = :identifier2)
              AND u.deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            'identifier1' => strtolower($identifier),
            'identifier2' => $identifier,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT u.user_id, u.username, u.email, u.employee_id, u.role_id, u.is_active, u.created_at, u.last_login,
                   r.role_name,
                   e.full_name as employee_name, e.department_id, e.manager_id,
                   d.department_name
            FROM users u
            JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            LEFT JOIN departments d ON d.department_id = e.department_id
            WHERE u.user_id = :id AND u.deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $where = ['u.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['role_id'])) {
            $where[] = 'u.role_id = :role_id';
            $params['role_id'] = (int) $filters['role_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'u.is_active = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(u.username LIKE :q OR u.email LIKE :q OR e.full_name LIKE :q)';
            $params['q'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users u
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT u.user_id, u.username, u.email, u.employee_id, u.role_id, u.is_active, u.created_at, u.last_login,
                   r.role_name,
                   e.full_name as employee_name, e.employee_number,
                   d.department_name
            FROM users u
            JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            LEFT JOIN departments d ON d.department_id = e.department_id
            WHERE {$whereClause}
            ORDER BY u.user_id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password_hash, employee_id, role_id, is_active)
            VALUES (:username, :email, :password_hash, :employee_id, :role_id, :is_active)
        ');
        $stmt->execute([
            'username' => trim($data['username']),
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'employee_id' => !empty($data['employee_id']) ? (int) $data['employee_id'] : null,
            'role_id' => (int) $data['role_id'],
            'is_active' => (int) ($data['is_active'] ?? 1),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['username'])) {
            $fields[] = 'username = :username';
            $params['username'] = trim($data['username']);
        }
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = strtolower(trim($data['email']));
        }
        if (!empty($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }
        if (array_key_exists('employee_id', $data)) {
            $fields[] = 'employee_id = :employee_id';
            $params['employee_id'] = !empty($data['employee_id']) ? (int) $data['employee_id'] : null;
        }
        if (isset($data['role_id'])) {
            $fields[] = 'role_id = :role_id';
            $params['role_id'] = (int) $data['role_id'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = :is_active';
            $params['is_active'] = (int) $data['is_active'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = :id';
        return $pdo->prepare($sql)->execute($params);
    }

    public function recordLoginAttempt(string $identifier, ?string $ip, bool $success): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO login_attempts (identifier, ip_address, success)
                VALUES (:identifier, :ip, :success)
            ');
            $stmt->execute([
                'identifier' => substr($identifier, 0, 190),
                'ip' => $ip,
                'success' => $success ? 1 : 0,
            ]);
        } catch (\Throwable) {
            // Non-blocking
        }
    }

    public function getRoles(): array
    {
        $pdo = Database::pdo();
        return $pdo->query('SELECT role_id, role_name, description FROM roles ORDER BY role_id ASC')->fetchAll();
    }
}
