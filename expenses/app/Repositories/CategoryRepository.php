<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CategoryRepository
{
    private static ?bool $hasExtendedColumns = null;

    private function hasExtendedColumns(): bool
    {
        if (self::$hasExtendedColumns !== null) {
            return self::$hasExtendedColumns;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SHOW COLUMNS FROM expense_categories LIKE 'default_amount'");
            self::$hasExtendedColumns = (bool) $stmt->fetch();
        } catch (\Throwable) {
            self::$hasExtendedColumns = false;
        }

        return self::$hasExtendedColumns;
    }

    public function getAll(bool $activeOnly = false): array
    {
        $pdo = Database::pdo();
        $fields = $this->hasExtendedColumns()
            ? 'category_id, category_code, category_name, description, default_amount, is_fixed_price, is_active, created_at'
            : 'category_id, category_code, category_name, description, NULL AS default_amount, 0 AS is_fixed_price, is_active, created_at';

        $sql = "SELECT {$fields} FROM expense_categories";
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY category_name ASC';
        return $pdo->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $fields = $this->hasExtendedColumns()
            ? 'category_id, category_code, category_name, description, default_amount, is_fixed_price, is_active, created_at'
            : 'category_id, category_code, category_name, description, NULL AS default_amount, 0 AS is_fixed_price, is_active, created_at';

        $stmt = $pdo->prepare("SELECT {$fields} FROM expense_categories WHERE category_id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $pdo = Database::pdo();
        $fields = $this->hasExtendedColumns()
            ? 'category_id, category_code, category_name, description, default_amount, is_fixed_price, is_active'
            : 'category_id, category_code, category_name, description, NULL AS default_amount, 0 AS is_fixed_price, is_active';

        $stmt = $pdo->prepare("SELECT {$fields} FROM expense_categories WHERE category_code = :code LIMIT 1");
        $stmt->execute(['code' => strtoupper(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $pdo = Database::pdo();
        if ($this->hasExtendedColumns()) {
            $stmt = $pdo->prepare('
                INSERT INTO expense_categories (category_code, category_name, description, default_amount, is_fixed_price, is_active)
                VALUES (:code, :name, :desc, :default_amount, :fixed, :active)
            ');
            $stmt->execute([
                'code' => strtoupper(trim($data['category_code'])),
                'name' => trim($data['category_name']),
                'desc' => trim($data['description'] ?? ''),
                'default_amount' => !empty($data['default_amount']) ? $data['default_amount'] : null,
                'fixed' => (int) ($data['is_fixed_price'] ?? 0),
                'active' => (int) ($data['is_active'] ?? 1),
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO expense_categories (category_code, category_name, description, is_active)
                VALUES (:code, :name, :desc, :active)
            ');
            $stmt->execute([
                'code' => strtoupper(trim($data['category_code'])),
                'name' => trim($data['category_name']),
                'desc' => trim($data['description'] ?? ''),
                'active' => (int) ($data['is_active'] ?? 1),
            ]);
        }
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        if ($this->hasExtendedColumns()) {
            $stmt = $pdo->prepare('
                UPDATE expense_categories
                SET category_code = :code,
                    category_name = :name,
                    description = :desc,
                    default_amount = :default_amount,
                    is_fixed_price = :fixed,
                    is_active = :active
                WHERE category_id = :id
            ');
            return $stmt->execute([
                'id' => $id,
                'code' => strtoupper(trim($data['category_code'])),
                'name' => trim($data['category_name']),
                'desc' => trim($data['description'] ?? ''),
                'default_amount' => !empty($data['default_amount']) ? $data['default_amount'] : null,
                'fixed' => (int) ($data['is_fixed_price'] ?? 0),
                'active' => (int) ($data['is_active'] ?? 1),
            ]);
        }

        $stmt = $pdo->prepare('
            UPDATE expense_categories
            SET category_code = :code,
                category_name = :name,
                description = :desc,
                is_active = :active
            WHERE category_id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'code' => strtoupper(trim($data['category_code'])),
            'name' => trim($data['category_name']),
            'desc' => trim($data['description'] ?? ''),
            'active' => (int) ($data['is_active'] ?? 1),
        ]);
    }
}
