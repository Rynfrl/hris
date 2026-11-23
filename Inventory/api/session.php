<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

sendJsonResponse([
    'status' => 'success',
    'user' => [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'name' => (string)($_SESSION['name'] ?? ''),
        'username' => (string)($_SESSION['username'] ?? ''),
        'email' => (string)($_SESSION['email'] ?? ''),
        'role' => trim((string)$_SESSION['role']),
        'role_label' => strtoupper(trim((string)$_SESSION['role'])) === 'ADMIN_WAREHOUSE' ? 'Admin Warehouse' : 'Warehouse Staff',
    ],
    'permissions' => [
        'manage_products' => strtoupper(trim((string)$_SESSION['role'])) === 'ADMIN_WAREHOUSE',
        'manage_stock_status' => in_array(strtoupper(trim((string)$_SESSION['role'])), ['ADMIN_WAREHOUSE', 'WAREHOUSE_STAFF', 'ADMIN'], true),
        'record_stock_movement' => true,
    ],
]);
