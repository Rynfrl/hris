<?php
/**
 * api/handlers/vendor_invoices.php
 * Vendor Invoice entry (Finance) + automatic 3-Way Matching against the
 * PO and everything received via GRN so far - see
 * pur_compute_three_way_match() in helpers.php.
 */

/**
 * Pemetaan ERP -> API: status ERP (DRAFT/SUBMITTED/APPROVED/REJECTED/PAID/CANCELLED)
 * tampil sebagai label lama (Pending Review / Approved for Payment / Rejected / ...),
 * dan match_status ERP (PENDING/MATCHED/MISMATCHED/EXCEPTION) sebagai Pending/Matched/Mismatched/Exception.
 * Nilai aslinya tetap tersedia di `status_erp` / `match_status_erp`.
 */
const PUR_INVOICE_COLS = 'vendor_invoice_id AS id, invoice_no, purchase_transaction_id, amount, invoice_date, entered_by, match_status,
                          match_notes, status, approved_by, approved_at, approval_notes, created_at';

function pur_brief_user_short(PDO $pdo, ?int $userId): ?array
{
    $u = pur_fetch_user_brief($pdo, $userId);
    return $u ? ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']] : null;
}

function pur_serialize_invoice(PDO $pdo, array $inv): array
{
    $poStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, grand_total, status FROM purchase_transactions WHERE purchase_transaction_id = ?');
    $poStmt->execute([$inv['purchase_transaction_id']]);
    $po = $poStmt->fetch() ?: null;
    if ($po) {
        $po['status'] = pur_po_status_label($po['status']);
    }
    $inv['purchase_transaction'] = $po;

    $inv['entered_by_user'] = pur_brief_user_short($pdo, (int) $inv['entered_by']);
    $inv['approved_by_user'] = !empty($inv['approved_by']) ? pur_brief_user_short($pdo, (int) $inv['approved_by']) : null;

    $inv['id'] = (int) $inv['id'];
    $inv['amount'] = (float) $inv['amount'];
    $inv['status_erp'] = $inv['status'];
    $inv['status'] = pur_invoice_status_label($inv['status']);
    $inv['match_status_erp'] = $inv['match_status'];
    $inv['match_status'] = PUR_MATCH_TO_LABEL[$inv['match_status']] ?? $inv['match_status'];
    return $inv;
}

function pur_find_invoice(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT ' . PUR_INVOICE_COLS . ' FROM vendor_invoices WHERE vendor_invoice_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
    if (!$inv) throw new ApiException('Vendor invoice tidak ditemukan.', 404);
    return $inv;
}

function h_invoices_list(PDO $pdo): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));
    $status = trim((string) ($_GET['status'] ?? ''));

    $where = ['deleted_at IS NULL'];
    $params = [];
    $erpStatuses = $status !== '' ? pur_status_filter(PUR_INVOICE_STATUS_TO_ERP, $status) : null;
    if ($erpStatuses) {
        $where[] = 'status IN (' . pur_placeholders($erpStatuses) . ')';
        array_push($params, ...$erpStatuses);
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM vendor_invoices $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_INVOICE_COLS . " FROM vendor_invoices $whereSql ORDER BY created_at DESC, vendor_invoice_id DESC LIMIT $pageSize OFFSET $offset");
    $stmt->execute($params);
    $rows = array_map(fn($i) => pur_serialize_invoice($pdo, $i), $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function h_invoices_get(PDO $pdo, int $id): void
{
    pur_json_response(pur_serialize_invoice($pdo, pur_find_invoice($pdo, $id)));
}

function h_invoices_create(PDO $pdo): void
{
    $user = pur_require_role(['Finance Manager', 'Admin']);
    $payload = pur_read_json_body();

    $poId = (int) ($payload['purchase_transaction_id'] ?? 0);
    $invoiceNo = trim((string) ($payload['invoice_no'] ?? ''));
    $amount = isset($payload['amount']) ? (float) $payload['amount'] : null;
    $invoiceDate = (string) ($payload['invoice_date'] ?? '');

    if (!$poId) throw new ApiException('PO wajib dipilih.', 422);
    if ($invoiceNo === '') throw new ApiException('Nomor invoice vendor wajib diisi.', 422, ['invoice_no' => 'Wajib diisi']);
    if ($amount === null || $amount <= 0) throw new ApiException('Nominal invoice tidak valid.', 422, ['amount' => 'Wajib > 0']);
    if ($invoiceDate === '' || !strtotime($invoiceDate)) throw new ApiException('Tanggal invoice wajib diisi.', 422);

    $poStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, grand_total, status FROM purchase_transactions WHERE purchase_transaction_id = ? AND deleted_at IS NULL');
    $poStmt->execute([$poId]);
    $po = $poStmt->fetch();
    if (!$po) throw new ApiException('PO tidak ditemukan.', 404);
    if ($po['status'] === 'CANCELLED') throw new ApiException('PO ini sudah Cancelled, tidak dapat menerima invoice.', 409);

    // Skema gabungan menjamin (purchase_transaction_id, invoice_no) unik; cek dulu supaya user
    // mendapat pesan yang jelas, bukan error 500 dari constraint.
    $dupStmt = $pdo->prepare('SELECT vendor_invoice_id FROM vendor_invoices WHERE purchase_transaction_id = ? AND invoice_no = ? LIMIT 1');
    $dupStmt->execute([$poId, $invoiceNo]);
    if ($dupStmt->fetch()) throw new ApiException("Invoice dengan nomor {$invoiceNo} sudah pernah dicatat untuk PO ini.", 422, ['invoice_no' => 'Sudah pernah dicatat']);

    $match = pur_compute_three_way_match($pdo, $po, $amount);

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO vendor_invoices (invoice_no, purchase_transaction_id, amount, invoice_date, entered_by, match_status, match_notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'SUBMITTED')"
        )->execute([$invoiceNo, $poId, $amount, $invoiceDate, $user['id'], $match['match_status'], $match['match_notes']]);
        $id = (int) $pdo->lastInsertId();

        pur_log_activity($pdo, $user['id'], 'CREATE', 'vendor_invoice', $id, "Mencatat invoice {$invoiceNo} untuk PO {$po['transaction_no']} - hasil 3-way match: " . (PUR_MATCH_TO_LABEL[$match['match_status']] ?? $match['match_status']));
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_invoice($pdo, pur_find_invoice($pdo, $id)), 201);
}

function h_invoices_approve(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Finance Manager', 'Admin']);
    $payload = pur_read_json_body();
    $notes = trim((string) ($payload['notes'] ?? ''));

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT invoice_no, status, match_status FROM vendor_invoices WHERE vendor_invoice_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $inv = $lockStmt->fetch();
        if (!$inv) throw new ApiException('Invoice tidak ditemukan.', 404);
        if (!in_array($inv['status'], PUR_INVOICE_PENDING, true)) throw new ApiException('Hanya invoice berstatus Pending Review yang dapat diproses.', 409);

        // An invoice that is not a clean match (MISMATCHED / EXCEPTION) can still be
        // approved, but only with an explicit override note explaining why - never silently.
        if (in_array($inv['match_status'], ['MISMATCHED', 'EXCEPTION'], true) && $notes === '') {
            throw new ApiException('Invoice ini Mismatched (3-way match gagal). Berikan catatan alasan sebelum menyetujui.', 422, ['notes' => 'Wajib diisi untuk invoice mismatched']);
        }

        $pdo->prepare("UPDATE vendor_invoices SET status = 'APPROVED', approved_by = ?, approved_at = NOW(), approval_notes = ? WHERE vendor_invoice_id = ?")
            ->execute([$user['id'], $notes ?: null, $id]);

        pur_log_activity($pdo, $user['id'], 'APPROVE', 'vendor_invoice', $id, "Menyetujui invoice {$inv['invoice_no']} untuk pembayaran" . ($notes ? " ({$notes})" : ''));
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_invoice($pdo, pur_find_invoice($pdo, $id)));
}

function h_invoices_reject(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Finance Manager', 'Admin']);
    $payload = pur_read_json_body();
    $notes = trim((string) ($payload['notes'] ?? ''));
    if ($notes === '') throw new ApiException('Alasan penolakan wajib diisi.', 422, ['notes' => 'Wajib diisi']);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT invoice_no, status FROM vendor_invoices WHERE vendor_invoice_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $inv = $lockStmt->fetch();
        if (!$inv) throw new ApiException('Invoice tidak ditemukan.', 404);
        if (!in_array($inv['status'], PUR_INVOICE_PENDING, true)) throw new ApiException('Hanya invoice berstatus Pending Review yang dapat diproses.', 409);

        $pdo->prepare("UPDATE vendor_invoices SET status = 'REJECTED', approved_by = ?, approved_at = NOW(), approval_notes = ? WHERE vendor_invoice_id = ?")
            ->execute([$user['id'], $notes, $id]);

        pur_log_activity($pdo, $user['id'], 'REJECT', 'vendor_invoice', $id, "Menolak invoice {$inv['invoice_no']}: {$notes}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_invoice($pdo, pur_find_invoice($pdo, $id)));
}
