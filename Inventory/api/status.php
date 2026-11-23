<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT DATABASE() AS current_db, VERSION() AS db_version');
    $info = $stmt->fetch();
    $tableNames = ['categories', 'products', 'suppliers', 'stock_movements', 'storage_locations', 'settings'];
    $tableCounts = [];

    foreach ($tableNames as $tableName) {
        $count = $pdo->query("SELECT COUNT(*) AS total FROM `$tableName`")->fetch();
        $tableCounts[$tableName] = (int)$count['total'];
    }

    sendJsonResponse([
        'status' => 'success',
        'connected' => true,
        'database' => $info['current_db'],
        'version' => $info['db_version'],
        'table_counts' => $tableCounts,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Checking database status');
}
