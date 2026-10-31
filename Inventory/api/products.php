<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

inventoryRequireApiAccess($method !== 'GET');

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $select = "SELECT p.product_id AS id, p.product_id, p.sku, p.product_name AS name,
                          p.product_name, p.description, p.category_id, p.unit,
                          p.purchase_price, p.selling_price, p.stock AS current_stock, p.stock,
                          p.minimum_stock, p.location, p.is_active,
                          CASE WHEN p.stock <= 0 THEN 'out'
                               WHEN p.stock <= p.minimum_stock THEN 'low'
                               ELSE 'available' END AS status,
                          c.name AS category_name, c.code AS category_code
                   FROM products p
                   LEFT JOIN categories c ON c.id = p.category_id";

        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $stmt = $pdo->prepare(
                "$select
                 WHERE p.product_id = ? AND p.deleted_at IS NULL AND p.is_active = 1"
            );
            $stmt->execute([(int)$_GET['id']]);
            $product = $stmt->fetch();
            if (!$product) {
                sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
            }

            $history = $pdo->prepare(
                "SELECT IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT') AS type,
                        m.created_at AS date, m.reference_no AS ref_no, m.qty AS quantity,
                        IF(m.type IN ('PURCHASE', 'ADJUSTMENT_IN'), p.purchase_price, p.selling_price) AS price,
                        COALESCE(s.supplier_name, c.name) AS partner_name
                 FROM stock_movements m
                 JOIN products p ON p.product_id = m.product_id
                 LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
                 LEFT JOIN customers c ON c.id = m.customer_id
                 WHERE m.product_id = ?
                 ORDER BY m.created_at DESC, m.stock_movement_id DESC
                 LIMIT 10"
            );
            $history->execute([(int)$_GET['id']]);
            $product['stock_history'] = $history->fetchAll();
            sendJsonResponse(['status' => 'success', 'data' => $product]);
        }

        $conditions = ['p.deleted_at IS NULL', 'p.is_active = 1'];
        $params = [];
        $search = trim((string)($_GET['search'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));

        if ($search !== '') {
            $conditions[] = '(p.product_name LIKE ? OR p.sku LIKE ? OR p.location LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term);
        }
        if ($category !== '') {
            if (ctype_digit($category)) {
                $conditions[] = 'p.category_id = ?';
                $params[] = (int)$category;
            } else {
                $conditions[] = '(c.code LIKE ? OR c.name LIKE ?)';
                $term = '%' . $category . '%';
                array_push($params, $term, $term);
            }
        }
        if (in_array($status, ['low', 'out', 'available'], true)) {
            if ($status === 'low') {
                $conditions[] = 'p.stock > 0 AND p.stock <= p.minimum_stock';
            } elseif ($status === 'out') {
                $conditions[] = 'p.stock <= 0';
            } else {
                $conditions[] = 'p.stock > p.minimum_stock';
            }
        }

        $stmt = $pdo->prepare(
            "$select WHERE " . implode(' AND ', $conditions) . " ORDER BY p.product_id"
        );
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        $totalValuation = array_reduce(
            $products,
            static fn(float $total, array $product): float => $total
                + ((float)$product['purchase_price'] * (float)$product['current_stock']),
            0.0
        );

        sendJsonResponse([
            'status' => 'success',
            'count' => count($products),
            'total_valuation' => $totalValuation,
            'data' => $products,
        ]);
    }

    if ($method === 'POST') {
        $body = inventoryRequestBody();
        $sku = trim((string)($body['sku'] ?? ''));
        $name = trim((string)($body['name'] ?? $body['product_name'] ?? ''));
        $categoryValue = trim((string)($body['category_id'] ?? ''));
        if ($categoryValue !== '' && (!ctype_digit($categoryValue) || (int)$categoryValue <= 0)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak valid.'], 400);
        }
        $categoryId = $categoryValue === '' ? null : (int)$categoryValue;
        $description = trim((string)($body['description'] ?? ''));
        $purchasePrice = filter_var($body['purchase_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sellingPrice = filter_var($body['selling_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $unit = trim((string)($body['unit'] ?? 'unit'));
        $minimumStock = filter_var($body['minimum_stock'] ?? 5, FILTER_VALIDATE_FLOAT);
        $location = trim((string)($body['location'] ?? 'Rak Utama'));

        if ($sku === '' || inventoryTextLength($sku) > 50 || $name === '' || inventoryTextLength($name) > 150
            || $unit === '' || inventoryTextLength($unit) > 20 || inventoryTextLength($location) > 100
            || $purchasePrice === false || $sellingPrice === false || $minimumStock === false
            || !is_finite($purchasePrice) || !is_finite($sellingPrice)
            || !is_finite($minimumStock) || $purchasePrice < 0 || $sellingPrice < 0 || $minimumStock < 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'Data produk tidak valid. Periksa SKU, nama, satuan, harga, dan batas minimum stok.'], 400);
        }
        inventoryValidateProductReferences($pdo, $categoryId, $sku);

        $stmt = $pdo->prepare(
            "INSERT INTO products
                (category_id, sku, product_name, description, unit, purchase_price, selling_price,
                 stock, minimum_stock, location, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)"
        );
        $stmt->execute([
            $categoryId, $sku, $name, $description, $unit,
            $purchasePrice, $sellingPrice, $minimumStock, $location,
        ]);

        sendJsonResponse([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'product_id' => (int)$pdo->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        $body = inventoryRequestBody();
        $id = (int)($body['id'] ?? ($_GET['id'] ?? 0));
        $sku = trim((string)($body['sku'] ?? ''));
        $name = trim((string)($body['name'] ?? $body['product_name'] ?? ''));
        $categoryValue = trim((string)($body['category_id'] ?? ''));
        if ($categoryValue !== '' && (!ctype_digit($categoryValue) || (int)$categoryValue <= 0)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak valid.'], 400);
        }
        $categoryId = $categoryValue === '' ? null : (int)$categoryValue;
        $description = trim((string)($body['description'] ?? ''));
        $purchasePrice = filter_var($body['purchase_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sellingPrice = filter_var($body['selling_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $unit = trim((string)($body['unit'] ?? 'unit'));
        $minimumStock = filter_var($body['minimum_stock'] ?? 5, FILTER_VALIDATE_FLOAT);
        $location = trim((string)($body['location'] ?? 'Rak Utama'));

        if ($id <= 0 || $sku === '' || inventoryTextLength($sku) > 50
            || $name === '' || inventoryTextLength($name) > 150
            || $unit === '' || inventoryTextLength($unit) > 20 || inventoryTextLength($location) > 100
            || $purchasePrice === false || $sellingPrice === false || $minimumStock === false
            || !is_finite($purchasePrice) || !is_finite($sellingPrice)
            || !is_finite($minimumStock) || $purchasePrice < 0 || $sellingPrice < 0 || $minimumStock < 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'Data produk tidak valid.'], 400);
        }
        inventoryValidateProductReferences($pdo, $categoryId, $sku, $id);

        $stmt = $pdo->prepare(
            "UPDATE products
             SET category_id = ?, sku = ?, product_name = ?, description = ?, unit = ?,
                 purchase_price = ?, selling_price = ?, minimum_stock = ?, location = ?
             WHERE product_id = ? AND deleted_at IS NULL AND is_active = 1"
        );
        $stmt->execute([
            $categoryId, $sku, $name, $description, $unit,
            $purchasePrice, $sellingPrice, $minimumStock, $location, $id,
        ]);

        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM products WHERE product_id = ? AND is_active = 1 AND deleted_at IS NULL');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
            }
        }

        sendJsonResponse(['status' => 'success', 'message' => 'Data produk berhasil diperbarui.']);
    }

    if ($method === 'DELETE') {
        $body = inventoryRequestBody();
        $id = (int)($body['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID produk tidak valid.'], 400);
        }

        $stmt = $pdo->prepare(
            'UPDATE products SET is_active = 0 WHERE product_id = ? AND deleted_at IS NULL AND is_active = 1'
        );
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan atau sudah dinonaktifkan.'], 404);
        }
        sendJsonResponse(['status' => 'success', 'message' => 'Produk berhasil dinonaktifkan.']);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
} catch (Throwable $error) {
    if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0) === 1062) {
        sendJsonResponse(['status' => 'error', 'message' => 'SKU sudah digunakan oleh produk lain.'], 409);
    }
    inventoryJsonError($error, 'Managing products');
}
