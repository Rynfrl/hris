<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();
    $metrics = $pdo->query(
        "SELECT COUNT(*) AS total_products,
                COALESCE(SUM(stock * purchase_price), 0) AS total_valuation,
                COALESCE(SUM(stock <= minimum_stock), 0) AS low_stock_count
         FROM products
         WHERE is_active = 1 AND deleted_at IS NULL"
    )->fetch();
    $stockIn = $pdo->query(
        "SELECT COALESCE(SUM(qty), 0) AS total_qty, COUNT(*) AS total_trx
         FROM stock_movements
         WHERE type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN')
           AND YEAR(created_at) = YEAR(CURRENT_DATE())
           AND MONTH(created_at) = MONTH(CURRENT_DATE())"
    )->fetch();
    $lowStockItems = $pdo->query(
        "SELECT p.product_id AS id, p.sku, p.product_name AS name, p.stock AS current_stock,
                p.minimum_stock, p.unit,
                CASE WHEN p.stock <= 0 THEN 'out' ELSE 'low' END AS status,
                c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1 AND p.deleted_at IS NULL AND p.stock <= p.minimum_stock
         ORDER BY p.stock ASC
         LIMIT 5"
    )->fetchAll();
    $recent = $pdo->query(
        "SELECT IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT') AS type,
                m.created_at AS date, m.reference_no AS ref_no, p.product_name,
                m.qty AS quantity, p.unit,
                COALESCE(s.supplier_name, c.name) AS partner_name
         FROM stock_movements m
         JOIN products p ON p.product_id = m.product_id
         LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
         LEFT JOIN customers c ON c.id = m.customer_id
         ORDER BY m.created_at DESC, m.stock_movement_id DESC
         LIMIT 5"
    )->fetchAll();
    $totalValuation = (float)$metrics['total_valuation'];

    sendJsonResponse([
        'status' => 'success',
        'metrics' => [
            'total_products' => (int)$metrics['total_products'],
            'total_valuation' => $totalValuation,
            'total_valuation_formatted' => 'Rp ' . number_format($totalValuation, 0, ',', '.'),
            'stock_in_month_qty' => (float)$stockIn['total_qty'],
            'stock_in_month_trx' => (int)$stockIn['total_trx'],
            'low_stock_count' => (int)$metrics['low_stock_count'],
        ],
        'low_stock_items' => $lowStockItems,
        'recent_activities' => $recent,
    ]);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Loading dashboard');
}
