<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}
inventoryRequireApiAccess($method !== 'GET' && $method !== 'POST');

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
        $type = strtolower(trim((string)($_GET['type'] ?? '')));
        $conditions = [];
        if ($type === 'in') {
            $conditions[] = "m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN')";
        } elseif ($type === 'out') {
            $conditions[] = "m.type IN ('PURCHASE_RETURN', 'SALE', 'ADJUSTMENT_OUT')";
        } elseif ($type !== '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Filter tipe harus in atau out.'], 400);
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->query(
            "SELECT IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT') AS movement_type,
                    m.created_at AS movement_date, m.reference_no AS ref_number,
                    'Completed' AS status, m.notes, p.product_id AS product_id,
                    p.sku, p.product_name, p.unit, m.qty AS quantity,
                    IF(m.type IN ('PURCHASE', 'ADJUSTMENT_IN'), p.purchase_price, p.selling_price) AS price,
                    m.qty * IF(m.type IN ('PURCHASE', 'ADJUSTMENT_IN'), p.purchase_price, p.selling_price) AS subtotal,
                    COALESCE(s.supplier_name, c.name) AS partner_name,
                    CASE WHEN m.supplier_id IS NOT NULL THEN 'Supplier'
                         WHEN m.customer_id IS NOT NULL THEN 'Customer'
                         ELSE 'Warehouse' END AS partner_type
             FROM stock_movements m
             JOIN products p ON p.product_id = m.product_id
             LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
             LEFT JOIN customers c ON c.id = m.customer_id
             $where
             ORDER BY m.created_at DESC, m.stock_movement_id DESC
             LIMIT $limit"
        );
        $movements = $stmt->fetchAll();
        foreach ($movements as &$movement) {
            $movement['product_name'] = (string)$movement['product_name'];
        }
        unset($movement);

        sendJsonResponse(['status' => 'success', 'count' => count($movements), 'data' => $movements]);
    }

    if ($method !== 'POST') {
        sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
    }

    $body = inventoryRequestBody();
    $type = strtolower(trim((string)($body['type'] ?? 'in')));
    $productId = (int)($body['product_id'] ?? 0);
    $quantity = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $date = trim((string)($body['date'] ?? date('Y-m-d')));
    $notes = trim((string)($body['notes'] ?? ''));
    $supplierId = !empty($body['supplier_id']) ? (int)$body['supplier_id'] : null;

    $dateValue = DateTime::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();
    if (!in_array($type, ['in', 'out'], true) || $productId <= 0
        || $quantity === false || !is_finite((float)$quantity) || $quantity <= 0
        || !$dateValue || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))
        || $dateValue->format('Y-m-d') !== $date) {
        sendJsonResponse(['status' => 'error', 'message' => 'Tipe, produk, jumlah, atau tanggal mutasi tidak valid.'], 400);
    }
    if ($type === 'out' && $supplierId !== null) {
        sendJsonResponse(['status' => 'error', 'message' => 'Supplier hanya dapat dipilih untuk stok masuk.'], 400);
    }

    $pdo->beginTransaction();
    $productStmt = $pdo->prepare(
        "SELECT product_id, product_name, unit, stock, purchase_price, selling_price
         FROM products
         WHERE product_id = ? AND is_active = 1 AND deleted_at IS NULL
         FOR UPDATE"
    );
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    if (!$product) {
        $pdo->rollBack();
        sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan atau tidak aktif.'], 404);
    }

    if ($type === 'out' && (float)$product['stock'] < (float)$quantity) {
        $pdo->rollBack();
        sendJsonResponse([
            'status' => 'error',
            'message' => sprintf('Stok tidak mencukupi. Tersedia %s %s.', $product['stock'], $product['unit']),
        ], 409);
    }

    if ($supplierId !== null) {
        $supplierStmt = $pdo->prepare(
            'SELECT 1 FROM suppliers WHERE supplier_id = ? AND is_active = 1 AND deleted_at IS NULL'
        );
        $supplierStmt->execute([$supplierId]);
        if (!$supplierStmt->fetchColumn()) {
            $pdo->rollBack();
            sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan atau tidak aktif.'], 400);
        }
    }

    $reference = sprintf('INV-%s-%s', date('YmdHis'), bin2hex(random_bytes(4)));
    $movementType = $type === 'in' ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
    $stmt = $pdo->prepare(
        'INSERT INTO stock_movements
            (product_id, type, qty, reference_no, supplier_id, created_by, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $productId,
        $movementType,
        $quantity,
        $reference,
        $supplierId,
        (int)$_SESSION['user_id'],
        $notes !== '' ? $notes : null,
        $date . ' ' . date('H:i:s'),
    ]);

    $stockStmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ?');
    $stockStmt->execute([$productId]);
    $newStock = (float)$stockStmt->fetchColumn();
    $pdo->commit();

    sendJsonResponse([
        'status' => 'success',
        'message' => sprintf(
            'Stok %s berhasil dicatat. Stok %s sekarang %s %s.',
            $type === 'in' ? 'masuk' : 'keluar',
            $product['product_name'],
            $newStock,
            $product['unit']
        ),
        'ref_number' => $reference,
        'new_stock' => $newStock,
    ], 201);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    inventoryJsonError($error, 'Recording stock movement');
}
