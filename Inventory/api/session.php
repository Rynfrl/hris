<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

sendJsonResponse([
    'status' => 'success',
    'user' => [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'name' => (string)($_SESSION['name'] ?? ''),
        'role' => trim((string)$_SESSION['role']),
    ],
    'permissions' => [
        'manage_products' => strtoupper(trim((string)$_SESSION['role'])) === 'ADMIN_WAREHOUSE',
        'record_stock_movement' => true,
    ],
]);
