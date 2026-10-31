<?php
/**
 * api/handlers/purchase_returns.php
 * Purchase Return: Warehouse Staff flags defective/short/wrong items from
 * an existing GRN. Posts a stock_movements OUT and feeds the Vendor
 * Scorecard's quality metric.
 */

/**
 * Kolom `condition` adalah reserved word MySQL - WAJIB diberi backtick di setiap query.
 * Alasan kosong disimpan sebagai string kosong dan dikirim sebagai null pada respons API.
 */
const PUR_RETURN_COLS = 'purchase_return_id AS id, return_no, goods_receipt_id, product_id, qty_returned, `condition`, reason, created_by, created_at';

function pur_serialize_return(PDO $pdo, array $ret): array
{
    $grnStmt = $pdo->prepare(
        'SELECT gr.goods_receipt_id AS id, gr.grn_no, gr.purchase_transaction_id, t.transaction_no, t.supplier_id
         FROM goods_receipts gr JOIN purchase_transactions t ON t.purchase_transaction_id = gr.purchase_transaction_id
         WHERE gr.goods_receipt_id = ?'
    );
    $grnStmt->execute([$ret['goods_receipt_id']]);
    $ret['goods_receipt'] = $grnStmt->fetch() ?: null;

    $productStmt = $pdo->prepare('SELECT product_id AS id, sku AS code, product_name AS name, unit FROM products WHERE product_id = ?');
    $productStmt->execute([$ret['product_id']]);
    $ret['product'] = $productStmt->fetch() ?: null;

    $creator = pur_fetch_user_brief($pdo, (int) $ret['created_by']);
    $ret['created_by_user'] = $creator ? ['id' => $creator['id'], 'name' => $creator['name'], 'role' => $creator['role']] : null;

    $ret['id'] = (int) $ret['id'];
    $ret['qty_returned'] = (float) $ret['qty_returned'];
    if ($ret['reason'] === '') {
        $ret['reason'] = null;
    }
    return $ret;
}

function h_returns_list(PDO $pdo): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));

    $countStmt = $pdo->query('SELECT COUNT(*) FROM purchase_returns WHERE deleted_at IS NULL');
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_RETURN_COLS . " FROM purchase_returns WHERE deleted_at IS NULL ORDER BY created_at DESC, purchase_return_id DESC LIMIT $pageSize OFFSET $offset");
    $stmt->execute();
    $rows = array_map(fn($r) => pur_serialize_return($pdo, $r), $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function h_returns_create(PDO $pdo): void
{
    $user = pur_require_role(['Warehouse Staff', 'Admin']);
    $payload = pur_read_json_body();

    $grnId = (int) ($payload['goods_receipt_id'] ?? 0);
    $productId = (int) ($payload['product_id'] ?? 0);
    $qty = isset($payload['qty_returned']) ? (float) $payload['qty_returned'] : null;
    $condition = (string) ($payload['condition'] ?? '');
    $reason = trim((string) ($payload['reason'] ?? ''));

    if (!$grnId) throw new ApiException('GRN wajib dipilih.', 422);
    if (!$productId) throw new ApiException('Produk wajib dipilih.', 422);
    if ($qty === null || $qty <= 0) throw new ApiException('Qty retur tidak valid.', 422, ['qty_returned' => 'Wajib > 0']);
    if (!in_array($condition, ['Defective', 'Shortage', 'Wrong Item', 'Other'], true)) {
        throw new ApiException('Kondisi retur tidak valid.', 422);
    }

    $pdo->beginTransaction();
    try {
        $grnDetailStmt = $pdo->prepare(
            'SELECT gd.qty_received FROM goods_receipt_details gd
             WHERE gd.goods_receipt_id = ? AND gd.product_id = ?'
        );
        $grnDetailStmt->execute([$grnId, $productId]);
        $grnDetail = $grnDetailStmt->fetch();
        if (!$grnDetail) throw new ApiException('Produk ini tidak ditemukan pada GRN yang dipilih.', 422);

        $alreadyReturnedStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(qty_returned), 0) FROM purchase_returns WHERE goods_receipt_id = ? AND product_id = ? AND deleted_at IS NULL'
        );
        $alreadyReturnedStmt->execute([$grnId, $productId]);
        $alreadyReturned = (float) $alreadyReturnedStmt->fetchColumn();

        $maxReturnable = (float) $grnDetail['qty_received'] - $alreadyReturned;
        if ($qty > $maxReturnable + 0.0001) {
            throw new ApiException("Qty retur ({$qty}) melebihi qty yang diterima dan belum diretur ({$maxReturnable}).", 422);
        }

        $tmpNo = 'TMP-' . bin2hex(random_bytes(6));
        $pdo->prepare(
            'INSERT INTO purchase_returns (return_no, goods_receipt_id, product_id, qty_returned, `condition`, reason, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$tmpNo, $grnId, $productId, $qty, $condition, $reason, $user['id']]);
        $id = (int) $pdo->lastInsertId();
        $returnNo = pur_generate_return_no($id);
        $pdo->prepare('UPDATE purchase_returns SET return_no = ? WHERE purchase_return_id = ?')->execute([$returnNo, $id]);

        // Tipe PURCHASE_RETURN mengurangi products.stock melalui trigger; jangan update stok manual.
        $pdo->prepare("INSERT IGNORE INTO stock_movements (product_id, type, qty, reference_no) VALUES (?, 'PURCHASE_RETURN', ?, ?)")
            ->execute([$productId, $qty, $returnNo]);

        pur_log_activity($pdo, $user['id'], 'RETURN', 'purchase_return', $id, "Membuat retur {$returnNo} ({$condition}, qty {$qty})");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $stmt = $pdo->prepare('SELECT ' . PUR_RETURN_COLS . ' FROM purchase_returns WHERE purchase_return_id = ?');
    $stmt->execute([$id]);
    pur_json_response(pur_serialize_return($pdo, $stmt->fetch()), 201);
}
