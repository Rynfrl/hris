<?php
/**
 * api/handlers/goods_receipts.php
 * Goods Receipt Note (GRN): Warehouse Staff records what physically
 * arrived against a PO line-by-line (partial receipt supported). Creating
 * a GRN posts stock_movements (type PURCHASE) dan menghitung ulang status PO
 * induknya - lihat pur_recompute_po_status() di helpers.php.
 *
 * PENTING (integrasi Gudang): products.stock TIDAK di-update manual di sini.
 * Trigger `trg_stock_movement_after_insert` menambah stok otomatis begitu
 * baris stock_movements dimasukkan; meng-update stock juga dari kode ini akan
 * membuat stok terhitung dua kali.
 */

const PUR_GRN_COLS = 'goods_receipt_id AS id, grn_no, purchase_transaction_id, received_by, received_date, notes, created_at';

function pur_serialize_grn(PDO $pdo, array $grn): array
{
    $poStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, status FROM purchase_transactions WHERE purchase_transaction_id = ?');
    $poStmt->execute([$grn['purchase_transaction_id']]);
    $po = $poStmt->fetch() ?: null;
    if ($po) {
        $po['status'] = pur_po_status_label($po['status']);
    }
    $grn['purchase_transaction'] = $po;

    $receiver = pur_fetch_user_brief($pdo, (int) $grn['received_by']);
    $grn['receiver'] = $receiver ? ['id' => $receiver['id'], 'name' => $receiver['name'], 'role' => $receiver['role']] : null;

    $detailStmt = $pdo->prepare(
        'SELECT g.goods_receipt_detail_id AS id, g.purchase_transaction_detail_id, g.product_id, g.qty_received, g.condition_note,
                p.sku AS product_code, p.product_name AS product_name, p.unit AS unit
         FROM goods_receipt_details g
         LEFT JOIN products p ON p.product_id = g.product_id
         WHERE g.goods_receipt_id = ? ORDER BY g.goods_receipt_detail_id'
    );
    $detailStmt->execute([$grn['id']]);
    $grn['details'] = array_map(function ($d) {
        $d['qty_received'] = (float) $d['qty_received'];
        return $d;
    }, $detailStmt->fetchAll());

    $grn['id'] = (int) $grn['id'];
    return $grn;
}

function pur_find_grn(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT ' . PUR_GRN_COLS . ' FROM goods_receipts WHERE goods_receipt_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $grn = $stmt->fetch();
    if (!$grn) throw new ApiException('GRN tidak ditemukan.', 404);
    return $grn;
}

function h_grn_list(PDO $pdo): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
    $poId = isset($_GET['purchase_transaction_id']) ? (int) $_GET['purchase_transaction_id'] : null;

    $where = ['deleted_at IS NULL'];
    $params = [];
    if ($poId) {
        $where[] = 'purchase_transaction_id = ?';
        $params[] = $poId;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM goods_receipts $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_GRN_COLS . " FROM goods_receipts $whereSql ORDER BY created_at DESC, goods_receipt_id DESC LIMIT $pageSize OFFSET $offset");
    $stmt->execute($params);
    $rows = array_map(fn($g) => pur_serialize_grn($pdo, $g), $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function h_grn_get(PDO $pdo, int $id): void
{
    pur_json_response(pur_serialize_grn($pdo, pur_find_grn($pdo, $id)));
}

function h_grn_create(PDO $pdo): void
{
    $user = pur_require_role(['Warehouse Staff', 'Admin']);
    $payload = pur_read_json_body();

    $poId = (int) ($payload['purchase_transaction_id'] ?? 0);
    $receivedDate = (string) ($payload['received_date'] ?? '');
    $notes = trim((string) ($payload['notes'] ?? ''));
    $lines = $payload['lines'] ?? [];

    if (!$poId) throw new ApiException('PO wajib dipilih.', 422);
    if ($receivedDate === '' || !strtotime($receivedDate)) throw new ApiException('Tanggal penerimaan wajib diisi.', 422);
    if (!is_array($lines) || count($lines) === 0) throw new ApiException('Minimal satu baris item yang diterima.', 422);
    if (count($lines) > 200) throw new ApiException('Jumlah baris terlalu banyak.', 422);

    $pdo->beginTransaction();
    try {
        $poStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, status FROM purchase_transactions WHERE purchase_transaction_id = ? AND deleted_at IS NULL FOR UPDATE');
        $poStmt->execute([$poId]);
        $po = $poStmt->fetch();
        if (!$po) throw new ApiException('PO tidak ditemukan.', 404);
        if (!in_array($po['status'], PUR_PO_RECEIVABLE, true)) {
            throw new ApiException('GRN hanya dapat dibuat untuk PO berstatus Pending atau Partially Received.', 409);
        }

        $poLineStmt = $pdo->prepare(
            'SELECT purchase_transaction_detail_id AS id, product_id, qty,
                    COALESCE((SELECT SUM(qty_received) FROM goods_receipt_details WHERE purchase_transaction_detail_id = purchase_transaction_details.purchase_transaction_detail_id), 0) AS already_received
             FROM purchase_transaction_details WHERE purchase_transaction_detail_id = ? AND purchase_transaction_id = ?'
        );

        $validatedLines = [];
        $seenLineIds = [];
        foreach ($lines as $line) {
            $poDetailId = (int) ($line['purchase_transaction_detail_id'] ?? 0);
            $qtyReceived = (float) ($line['qty_received'] ?? 0);
            $condition = (string) ($line['condition_note'] ?? 'Good');
            if (!in_array($condition, ['Good', 'Damaged', 'Shortage'], true)) {
                $condition = 'Good';
            }

            if (in_array($poDetailId, $seenLineIds, true)) {
                throw new ApiException('Satu baris item PO tidak boleh muncul dua kali dalam satu GRN.', 422);
            }
            $seenLineIds[] = $poDetailId;

            $poLineStmt->execute([$poDetailId, $poId]);
            $poLine = $poLineStmt->fetch();
            if (!$poLine) throw new ApiException("Baris item PO id {$poDetailId} tidak ditemukan pada PO ini.", 422);

            if ($qtyReceived <= 0) {
                continue; // allow the caller to send a 0-qty line for "not received yet" without erroring
            }
            if ($qtyReceived > 1_000_000) {
                throw new ApiException('Qty diterima melebihi batas wajar.', 422);
            }

            $outstanding = (float) $poLine['qty'] - (float) $poLine['already_received'];
            if ($qtyReceived > $outstanding + 0.0001) {
                throw new ApiException(
                    "Qty diterima ({$qtyReceived}) melebihi sisa outstanding ({$outstanding}) untuk produk id {$poLine['product_id']}.",
                    422
                );
            }

            $validatedLines[] = [
                'purchase_transaction_detail_id' => $poDetailId,
                'product_id' => (int) $poLine['product_id'],
                'qty_received' => $qtyReceived,
                'condition_note' => $condition,
            ];
        }

        if (count($validatedLines) === 0) {
            throw new ApiException('Tidak ada qty yang diterima pada GRN ini (semua baris kosong/0).', 422);
        }

        $tmpNo = 'TMP-' . bin2hex(random_bytes(6));
        $pdo->prepare('INSERT INTO goods_receipts (grn_no, purchase_transaction_id, received_by, received_date, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([$tmpNo, $poId, $user['id'], $receivedDate, $notes ?: null]);
        $grnId = (int) $pdo->lastInsertId();
        $grnNo = pur_generate_grn_no($grnId);
        $pdo->prepare('UPDATE goods_receipts SET grn_no = ? WHERE goods_receipt_id = ?')->execute([$grnNo, $grnId]);

        $detailInsert = $pdo->prepare(
            'INSERT INTO goods_receipt_details (goods_receipt_id, purchase_transaction_detail_id, product_id, qty_received, condition_note)
             VALUES (?, ?, ?, ?, ?)'
        );
        // stock_movements unik per (reference_no, product_id). PO yang dibuat modul lain
        // bisa memuat produk yang sama di beberapa baris, jadi qty dijumlahkan per produk
        // dulu - kalau tidak, INSERT IGNORE akan membuang baris kedua dan stok terhitung kurang.
        $moveInsert = $pdo->prepare(
            "INSERT IGNORE INTO stock_movements (product_id, type, qty, reference_no) VALUES (?, 'PURCHASE', ?, ?)"
        );
        $qtyByProduct = [];
        foreach ($validatedLines as $line) {
            $detailInsert->execute([$grnId, $line['purchase_transaction_detail_id'], $line['product_id'], $line['qty_received'], $line['condition_note']]);
            $qtyByProduct[$line['product_id']] = ($qtyByProduct[$line['product_id']] ?? 0) + $line['qty_received'];
        }
        foreach ($qtyByProduct as $productId => $qty) {
            $moveInsert->execute([$productId, $qty, $grnNo]);
        }

        $newPoStatus = pur_recompute_po_status($pdo, $poId);

        pur_log_activity($pdo, $user['id'], 'RECEIVE', 'goods_receipt', $grnId, "Membuat {$grnNo} untuk PO {$po['transaction_no']} - PO menjadi {$newPoStatus}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_grn($pdo, pur_find_grn($pdo, $grnId)), 201);
}
