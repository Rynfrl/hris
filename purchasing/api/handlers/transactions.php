<?php
/**
 * api/handlers/transactions.php
 * Everything under /purchasing/transactions* (Purchase Orders), memakai
 * tabel ERP purchase_transactions / purchase_transaction_details.
 *
 * Status PO di database mengikuti ERP (DRAFT, SENT, CONFIRMED,
 * PARTIALLY_RECEIVED, RECEIVED, INVOICED, COMPLETED, CANCELLED). API tetap
 * menampilkan label lama (Pending / Partially Received / Completed /
 * Cancelled) - pemetaan ada di helpers.php (PUR_PO_STATUS_TO_ERP). Kolom
 * `status_erp` pada respons berisi nilai aslinya.
 *
 * Status PO digerakkan oleh Goods Receipt nyata (lihat
 * pur_recompute_po_status()). PO baru dibuat berstatus SENT (langsung
 * diterbitkan ke supplier). Ubah/batalkan hanya boleh selama belum ada
 * barang yang diterima (DRAFT/SENT/CONFIRMED); setelah itu gunakan Purchase Return.
 */

/** Kolom PO dengan alias `id`; SELALU dipakai dengan alias tabel `t`. */
const PUR_PO_COLS = 't.purchase_transaction_id AS id, t.transaction_no, t.purchase_requisition_id, t.supplier_id, t.user_id,
                     t.transaction_date, t.expected_delivery_date, t.subtotal, t.tax, t.grand_total, t.status,
                     t.notes, t.created_at, t.updated_at, t.completed_at';

/**
 * Attach supplier/user/product info to a raw PO row (dari pur_find_transaction
 * atau query PUR_PO_COLS). Mengubah status ERP menjadi label frontend.
 */
function pur_serialize_transaction(PDO $pdo, array $tx): array
{
    $supplierStmt = $pdo->prepare(
        'SELECT supplier_id AS id, supplier_code AS code, supplier_name AS name, phone, email, address, created_at
         FROM suppliers WHERE supplier_id = ?'
    );
    $supplierStmt->execute([$tx['supplier_id']]);
    $tx['supplier'] = $supplierStmt->fetch() ?: null;

    $tx['user'] = pur_fetch_user_brief($pdo, (int) $tx['user_id']);

    $detailStmt = $pdo->prepare(
        'SELECT d.purchase_transaction_detail_id AS id, d.purchase_transaction_id, d.product_id, d.qty, d.unit_price, d.subtotal,
                p.sku AS product_code, p.product_name AS product_name, p.unit AS unit,
                COALESCE((SELECT SUM(g.qty_received) FROM goods_receipt_details g
                          WHERE g.purchase_transaction_detail_id = d.purchase_transaction_detail_id), 0) AS qty_received
         FROM purchase_transaction_details d
         LEFT JOIN products p ON p.product_id = d.product_id
         WHERE d.purchase_transaction_id = ?
         ORDER BY d.purchase_transaction_detail_id'
    );
    $detailStmt->execute([$tx['id']]);
    $tx['details'] = array_map(function ($d) {
        $d['qty'] = (float) $d['qty'];
        $d['unit_price'] = (float) $d['unit_price'];
        $d['subtotal'] = (float) $d['subtotal'];
        $d['qty_received'] = (float) $d['qty_received'];
        $d['qty_outstanding'] = max(0, $d['qty'] - $d['qty_received']);
        $d['product_name'] = $d['product_name'] ?? '(produk dihapus)';
        $d['product_code'] = $d['product_code'] ?? '-';
        $d['unit'] = $d['unit'] ?? '-';
        return $d;
    }, $detailStmt->fetchAll());

    if (!empty($tx['purchase_requisition_id'])) {
        $prStmt = $pdo->prepare('SELECT purchase_requisition_id AS id, pr_no FROM purchase_requisitions WHERE purchase_requisition_id = ?');
        $prStmt->execute([$tx['purchase_requisition_id']]);
        $tx['purchase_requisition'] = $prStmt->fetch() ?: null;
    } else {
        $tx['purchase_requisition'] = null;
    }

    $tx['id'] = (int) $tx['id'];
    $tx['subtotal'] = (float) $tx['subtotal'];
    $tx['tax'] = (float) $tx['tax'];
    $tx['grand_total'] = (float) $tx['grand_total'];
    $tx['status_erp'] = $tx['status'];
    $tx['status'] = pur_po_status_label($tx['status']);

    return $tx;
}

/** Baris PO mentah (status masih nilai ERP) - dipakai untuk logika bisnis sebelum diserialisasi. */
function pur_find_transaction(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT ' . PUR_PO_COLS . ' FROM purchase_transactions t WHERE t.purchase_transaction_id = ? AND t.deleted_at IS NULL');
    $stmt->execute([$id]);
    $tx = $stmt->fetch();
    if (!$tx) {
        throw new ApiException('Transaksi tidak ditemukan.', 404);
    }
    return $tx;
}

function h_transactions_list(PDO $pdo): void
{
    $search = trim((string) ($_GET['search'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 5)));

    $where = ['t.deleted_at IS NULL'];
    $params = [];

    $erpStatuses = $status !== '' ? pur_status_filter(PUR_PO_STATUS_TO_ERP, $status) : null;
    if ($erpStatuses) {
        $where[] = 't.status IN (' . pur_placeholders($erpStatuses) . ')';
        array_push($params, ...$erpStatuses);
    }
    if ($search !== '') {
        $where[] = '(t.transaction_no LIKE ? OR s.supplier_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM purchase_transactions t LEFT JOIN suppliers s ON s.supplier_id = t.supplier_id $whereSql"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $listStmt = $pdo->prepare(
        'SELECT ' . PUR_PO_COLS . " FROM purchase_transactions t LEFT JOIN suppliers s ON s.supplier_id = t.supplier_id
         $whereSql ORDER BY t.created_at DESC, t.purchase_transaction_id DESC LIMIT $pageSize OFFSET $offset"
    );
    $listStmt->execute($params);
    $rows = array_map(fn($tx) => pur_serialize_transaction($pdo, $tx), $listStmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function h_transactions_get(PDO $pdo, int $id): void
{
    $tx = pur_find_transaction($pdo, $id);
    pur_json_response(pur_serialize_transaction($pdo, $tx));
}

function h_transactions_create(PDO $pdo): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $payload = pur_read_json_body();
    $tx = pur_create_po_from_payload($pdo, $user, $payload);
    pur_json_response($tx, 201);
}

/**
 * Core PO-creation logic, taking an already-parsed payload rather than
 * reading php://input itself - this lets h_pr_convert() (in
 * requisitions.php) inject purchase_requisition_id into the payload and
 * reuse this exact logic, instead of unsafely trying to re-read the
 * request body a second time.
 */
function pur_create_po_from_payload(PDO $pdo, array $user, array $payload): array
{
    $supplierId = (int) ($payload['supplier_id'] ?? 0);
    $date = (string) ($payload['transaction_date'] ?? '');
    $expectedDelivery = trim((string) ($payload['expected_delivery_date'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $items = $payload['items'] ?? [];
    $prId = isset($payload['purchase_requisition_id']) ? (int) $payload['purchase_requisition_id'] : null;

    if (!$supplierId) throw new ApiException('Supplier wajib dipilih.', 422, ['supplier_id' => 'Wajib diisi']);
    if ($date === '' || !strtotime($date)) throw new ApiException('Tanggal transaksi wajib diisi.', 422, ['transaction_date' => 'Wajib diisi']);
    if ($expectedDelivery !== '' && !strtotime($expectedDelivery)) throw new ApiException('Tanggal perkiraan kirim tidak valid.', 422);

    $supplierStmt = $pdo->prepare('SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL AND is_active = 1');
    $supplierStmt->execute([$supplierId]);
    if (!$supplierStmt->fetch()) throw new ApiException('Supplier tidak valid atau sudah tidak aktif.', 422);

    ['details' => $details, 'subtotal' => $subtotal] = pur_compute_details($pdo, $items);
    $tax = pur_calculate_tax($subtotal);
    $grandTotal = round($subtotal + $tax, 2);

    $pdo->beginTransaction();
    try {
        if ($prId) {
            // Dikunci FOR UPDATE agar dua orang tidak bisa mengonversi PR yang sama secara bersamaan.
            $prStmt = $pdo->prepare('SELECT purchase_requisition_id AS id, status FROM purchase_requisitions WHERE purchase_requisition_id = ? AND deleted_at IS NULL FOR UPDATE');
            $prStmt->execute([$prId]);
            $pr = $prStmt->fetch();
            if (!$pr) throw new ApiException('PR sumber tidak ditemukan.', 422);
            if ($pr['status'] !== 'APPROVED') throw new ApiException('PR hanya dapat dikonversi menjadi PO setelah berstatus Approved.', 409);
        }

        $insert = $pdo->prepare(
            'INSERT INTO purchase_transactions
                (transaction_no, purchase_requisition_id, supplier_id, user_id, transaction_date, expected_delivery_date, subtotal, tax, grand_total, status, notes)
             VALUES (:tmp_no, :pr_id, :supplier_id, :user_id, :date, :expected, :subtotal, :tax, :grand_total, :status, :notes)'
        );
        $tmpNo = 'TMP-' . bin2hex(random_bytes(6));
        $insert->execute([
            ':tmp_no' => $tmpNo,
            ':pr_id' => $prId ?: null,
            ':supplier_id' => $supplierId,
            ':user_id' => $user['id'],
            ':date' => $date,
            ':expected' => $expectedDelivery ?: null,
            ':subtotal' => $subtotal,
            ':tax' => $tax,
            ':grand_total' => $grandTotal,
            ':status' => 'SENT',
            ':notes' => $notes,
        ]);
        $id = (int) $pdo->lastInsertId();
        $transactionNo = pur_generate_transaction_no($date, $id);

        $pdo->prepare('UPDATE purchase_transactions SET transaction_no = ? WHERE purchase_transaction_id = ?')
            ->execute([$transactionNo, $id]);

        $detailInsert = $pdo->prepare(
            'INSERT INTO purchase_transaction_details (purchase_transaction_id, product_id, qty, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($details as $d) {
            $detailInsert->execute([$id, $d['product_id'], $d['qty'], $d['unit_price'], $d['subtotal']]);
        }

        if ($prId) {
            $pdo->prepare("UPDATE purchase_requisitions SET status = 'CONVERTED' WHERE purchase_requisition_id = ?")->execute([$prId]);
        }

        pur_log_activity($pdo, $user['id'], 'CREATE', 'purchase_transaction', $id, "Membuat PO {$transactionNo} senilai " . number_format($grandTotal, 0, ',', '.') . ($prId ? " (konversi dari PR id {$prId})" : ''));

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $tx = pur_find_transaction($pdo, $id);
    return pur_serialize_transaction($pdo, $tx);
}

function h_transactions_update(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $tx = pur_find_transaction($pdo, $id);
    if (!in_array($tx['status'], PUR_PO_OPEN, true)) {
        throw new ApiException('Hanya PO berstatus Pending yang dapat diubah.', 409);
    }

    $payload = pur_read_json_body();
    $supplierId = (int) ($payload['supplier_id'] ?? 0);
    $date = (string) ($payload['transaction_date'] ?? '');
    $expectedDelivery = trim((string) ($payload['expected_delivery_date'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $items = $payload['items'] ?? [];

    if (!$supplierId) throw new ApiException('Supplier wajib dipilih.', 422);
    if ($date === '' || !strtotime($date)) throw new ApiException('Tanggal transaksi wajib diisi.', 422);

    $supplierStmt = $pdo->prepare('SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL AND is_active = 1');
    $supplierStmt->execute([$supplierId]);
    if (!$supplierStmt->fetch()) throw new ApiException('Supplier tidak valid atau sudah tidak aktif.', 422);

    ['details' => $details, 'subtotal' => $subtotal] = pur_compute_details($pdo, $items);
    $tax = pur_calculate_tax($subtotal);
    $grandTotal = round($subtotal + $tax, 2);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT status FROM purchase_transactions WHERE purchase_transaction_id = ? FOR UPDATE');
        $lockStmt->execute([$id]);
        $locked = $lockStmt->fetch();
        if (!$locked || !in_array($locked['status'], PUR_PO_OPEN, true)) {
            throw new ApiException('Hanya PO berstatus Pending yang dapat diubah.', 409);
        }

        $pdo->prepare(
            'UPDATE purchase_transactions
             SET supplier_id = ?, transaction_date = ?, expected_delivery_date = ?, notes = ?, subtotal = ?, tax = ?, grand_total = ?
             WHERE purchase_transaction_id = ?'
        )->execute([$supplierId, $date, $expectedDelivery ?: null, $notes, $subtotal, $tax, $grandTotal, $id]);

        $pdo->prepare('DELETE FROM purchase_transaction_details WHERE purchase_transaction_id = ?')->execute([$id]);
        $detailInsert = $pdo->prepare(
            'INSERT INTO purchase_transaction_details (purchase_transaction_id, product_id, qty, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($details as $d) {
            $detailInsert->execute([$id, $d['product_id'], $d['qty'], $d['unit_price'], $d['subtotal']]);
        }

        pur_log_activity($pdo, $user['id'], 'UPDATE', 'purchase_transaction', $id, "Mengubah PO {$tx['transaction_no']}");

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $tx = pur_find_transaction($pdo, $id);
    pur_json_response(pur_serialize_transaction($pdo, $tx));
}

function h_transactions_cancel(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $payload = pur_read_json_body();
    $reason = trim((string) ($payload['reason'] ?? ''));

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, status, notes FROM purchase_transactions WHERE purchase_transaction_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $tx = $lockStmt->fetch();
        if (!$tx) throw new ApiException('Transaksi tidak ditemukan.', 404);
        if (!in_array($tx['status'], PUR_PO_OPEN, true)) {
            throw new ApiException('Hanya PO berstatus Pending yang dapat dibatalkan (PO yang sudah menerima barang tidak bisa dibatalkan - gunakan Purchase Return).', 409);
        }

        $notes = $tx['notes'];
        if ($reason !== '') {
            $notes = ($notes ? $notes . ' | ' : '') . 'Dibatalkan: ' . $reason;
        }

        $pdo->prepare("UPDATE purchase_transactions SET status = 'CANCELLED', notes = ? WHERE purchase_transaction_id = ?")
            ->execute([$notes, $id]);

        pur_log_activity($pdo, $user['id'], 'CANCEL', 'purchase_transaction', $id, "Membatalkan PO {$tx['transaction_no']}" . ($reason ? ": {$reason}" : ''));

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $tx = pur_find_transaction($pdo, $id);
    pur_json_response(pur_serialize_transaction($pdo, $tx));
}
