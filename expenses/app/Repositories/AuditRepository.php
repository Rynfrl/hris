<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditRepository
{
    public function logActivity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $summary = null, ?string $ip = null): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address)
                VALUES (:user_id, :action, :entity_type, :entity_id, :summary, :ip)
            ');
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'summary' => $summary ? substr($summary, 0, 255) : null,
                'ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            ]);
        } catch (\Throwable) {
            // Non-blocking for logs
        }
    }

    public function logAudit(?int $userId, string $action, string $module, ?int $recordId = null, ?array $oldValue = null, ?array $newValue = null, ?string $ip = null, ?string $userAgent = null): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value, ip_address, user_agent)
                VALUES (:user_id, :action, :module, :record_id, :old_val, :new_val, :ip, :user_agent)
            ');
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'old_val' => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_SLASHES) : null,
                'new_val' => $newValue ? json_encode($newValue, JSON_UNESCAPED_SLASHES) : null,
                'ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent' => $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ]);
        } catch (\Throwable) {
            // Non-blocking
        }
    }

    public function getAuditLogs(int $page = 1, int $perPage = 25, ?string $module = null): array
    {
        $pdo = Database::pdo();
        $where = ['1=1'];
        $params = [];

        if (!empty($module)) {
            $where[] = 'a.module = :module';
            $params['module'] = $module;
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "
            SELECT a.*, u.username, emp.full_name as employee_name
            FROM audit_logs a
            LEFT JOIN users u ON u.user_id = a.user_id
            LEFT JOIN employees emp ON emp.employee_id = u.employee_id
            WHERE {$whereClause}
            ORDER BY a.audit_id DESC
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
}
