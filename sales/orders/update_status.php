<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verifyCsrf();

$pdo = getConnection();
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0 || !in_array($action, ['confirm', 'cancel'], true)) {
    header('Location: index.php?success=' . urlencode('Aksi order tidak valid.')); exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM sales_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) throw new RuntimeException('Order tidak ditemukan.');

    if ($action === 'confirm') {
        if ($order['status'] !== 'draft') throw new RuntimeException('Hanya order draft yang dapat dikonfirmasi.');
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM sales_order_items WHERE order_id = ?");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();
        if (!$items) throw new RuntimeException('Order tidak memiliki item.');

        $stockStmt = $pdo->prepare("SELECT product_name, stock FROM products WHERE product_id = ? AND deleted_at IS NULL AND is_active = 1 FOR UPDATE");
        $movementStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, type, qty, reference_no) VALUES (?, 'SALE', ?, ?)");
        foreach ($items as $it) {
            $stockStmt->execute([(int)$it['product_id']]);
            $product = $stockStmt->fetch();
            if (!$product) throw new RuntimeException('Produk pada order tidak tersedia.');
            $qty = (int)$it['quantity'];
            if ((float)$product['stock'] < $qty) throw new RuntimeException('Stok ' . $product['product_name'] . ' tidak mencukupi. Tersedia: ' . $product['stock'] . '.');
            $movementStmt->execute([(int)$it['product_id'], $qty, 'SO-' . $order['order_no'] . '-SALE']);
        }
        $pdo->prepare("UPDATE sales_orders SET status = 'confirmed' WHERE id = ?")->execute([$id]);
        $message = 'Order berhasil dikonfirmasi dan stok dikurangi.';
    } else {
        if (!in_array($order['status'], ['draft', 'confirmed'], true)) {
            throw new RuntimeException('Order hanya dapat dibatalkan saat draft atau confirmed.');
        }
        if ($order['status'] === 'confirmed') {
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM sales_order_items WHERE order_id = ?");
            $itemsStmt->execute([$id]);
            $returnMovementStmt = $pdo->prepare("INSERT INTO stock_movements (product_id, type, qty, reference_no) VALUES (?, 'SALES_RETURN', ?, ?)");
            foreach ($itemsStmt->fetchAll() as $it) {
                $returnMovementStmt->execute([(int)$it['product_id'], (int)$it['quantity'], 'SO-' . $order['order_no'] . '-CANCEL']);
            }
        }
        $pdo->prepare("UPDATE sales_orders SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        $message = 'Order berhasil dibatalkan.';
    }
    $pdo->commit();
    header('Location: view.php?id=' . $id . '&success=' . urlencode($message)); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Update order status failed: ' . $e->getMessage());
    header('Location: view.php?id=' . $id . '&success=' . urlencode($e->getMessage())); exit;
}
