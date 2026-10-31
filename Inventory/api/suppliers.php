<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        "SELECT supplier_id AS id, supplier_code AS code, supplier_name AS name,
                phone, email, address, is_active AS active, created_at
         FROM suppliers
         WHERE is_active = 1 AND deleted_at IS NULL
         ORDER BY supplier_name"
    );

    sendJsonResponse(['status' => 'success', 'data' => $stmt->fetchAll()]);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Loading suppliers');
}
