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

        $subquery = "
            SELECT
                CONCAT('audit_', a.audit_id) AS log_id,
                a.audit_id AS source_id,
                'audit_logs' AS source_table,
                a.user_id,
                (a.action COLLATE utf8mb4_unicode_ci) AS action,
                (a.module COLLATE utf8mb4_unicode_ci) AS module,
                a.record_id,
                CAST(a.old_value AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                CAST(a.new_value AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci AS summary,
                (a.ip_address COLLATE utf8mb4_unicode_ci) AS ip_address,
                (a.user_agent COLLATE utf8mb4_unicode_ci) AS user_agent,
                a.created_at
            FROM audit_logs a

            UNION ALL

            SELECT
                CONCAT('activity_', act.activity_id) AS log_id,
                act.activity_id AS source_id,
                'activity_logs' AS source_table,
                act.user_id,
                (act.action COLLATE utf8mb4_unicode_ci) AS action,
                (CASE
                    WHEN act.entity_type IN ('expense_report_employees', 'expense_report_items') THEN 'expense_reports'
                    WHEN act.entity_type = 'expense_reimbursements' THEN 'reimbursements'
                    WHEN act.entity_type = 'auth' THEN 'users'
                    WHEN act.entity_type = 'category' THEN 'expenses'
                    ELSE COALESCE(act.entity_type, 'system')
                END COLLATE utf8mb4_unicode_ci) AS module,
                act.entity_id AS record_id,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci AS old_value,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci AS new_value,
                (act.summary COLLATE utf8mb4_unicode_ci) AS summary,
                (act.ip_address COLLATE utf8mb4_unicode_ci) AS ip_address,
                CAST(NULL AS CHAR) COLLATE utf8mb4_unicode_ci AS user_agent,
                act.created_at
            FROM activity_logs act
        ";

        $where = ['1=1'];
        $params = [];

        if (!empty($module) && $module !== 'all') {
            $where[] = 'u.module = :module';
            $params['module'] = $module;
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$subquery}) AS u WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT u.*, us.username, emp.full_name AS employee_name
            FROM ({$subquery}) AS u
            LEFT JOIN users us ON us.user_id = u.user_id
            LEFT JOIN employees emp ON emp.employee_id = us.employee_id
            WHERE {$whereClause}
            ORDER BY u.created_at DESC, u.source_id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
}
