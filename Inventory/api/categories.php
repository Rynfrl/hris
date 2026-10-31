<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        "SELECT c.id, c.code, c.name, c.description,
                COUNT(p.product_id) AS product_count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.deleted_at IS NULL
         GROUP BY c.id, c.code, c.name, c.description
         ORDER BY c.name"
    );

    sendJsonResponse(['status' => 'success', 'data' => $stmt->fetchAll()]);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Loading categories');
}
