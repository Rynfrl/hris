<?php
/**
 * api/handlers/activity_log.php
 * Read-only audit trail viewer (Admin only - enforced in the handler,
 * matching every other action-level check in this codebase).
 */

function h_activity_log_list(PDO $pdo): void
{
    pur_require_role(['Admin']);

    $entityType = trim((string) ($_GET['entity_type'] ?? ''));
    $action = trim((string) ($_GET['action'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 20)));

    $where = [];
    $params = [];
    if ($entityType !== '') {
        $where[] = 'al.entity_type = ?';
        $params[] = $entityType;
    }
    if ($action !== '') {
        $where[] = 'al.action = ?';
        $params[] = $action;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare(
        "SELECT al.activity_id AS id, al.user_id, al.action, al.entity_type, al.entity_id, al.summary, al.ip_address, al.created_at,
                COALESCE(e.nama, u.name, u.username) AS user_name,
                COALESCE(NULLIF(u.role, ''), r.role_name) AS user_role
         FROM activity_logs al
         LEFT JOIN users u ON u.id = al.user_id
         LEFT JOIN roles r ON r.id_role = u.role_id
         LEFT JOIN employees e ON e.id_user = u.id
         $whereSql ORDER BY al.created_at DESC, al.activity_id DESC LIMIT $pageSize OFFSET $offset"
    );
    $stmt->execute($params);

    // activity_logs adalah tabel ERP bersama: berisi aktivitas modul lain juga (HR, Gudang, dst.).
    $rows = array_map(function ($r) {
        $r['id'] = (int) $r['id'];
        $r['user_role'] = pur_role_label($r['user_role']);
        return $r;
    }, $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}
