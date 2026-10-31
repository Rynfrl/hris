<?php
/**
 * api/handlers/requisitions.php
 * Purchase Requisition (PR): Draft -> Pending Approval / Approved (auto,
 * if under the approval-matrix threshold) -> Approved/Rejected -> Converted
 * (into a PO). See pur_config('pr_approval_threshold').
 */

/** Kolom PR dengan alias `id` + nama divisi HR; dipakai dengan FROM PUR_PR_FROM. */
const PUR_PR_COLS = 'pr.purchase_requisition_id AS id, pr.pr_no, pr.requester_id, d.id_divisi AS department_id, pr.department,
                     pr.needed_date, pr.subtotal, pr.tax, pr.grand_total, pr.status, pr.notes, pr.approval_threshold_snapshot,
                     pr.approved_by, pr.approved_at, pr.rejection_reason, pr.created_at, pr.updated_at';
const PUR_PR_FROM = 'FROM purchase_requisitions pr LEFT JOIN divisi d ON d.divisi = pr.department COLLATE utf8mb4_general_ci';

function pur_serialize_pr(PDO $pdo, array $pr): array
{
    $pr['requester'] = pur_fetch_user_brief($pdo, (int) $pr['requester_id']);
    $pr['approver'] = !empty($pr['approved_by']) ? pur_fetch_user_brief($pdo, (int) $pr['approved_by']) : null;

    $detailStmt = $pdo->prepare(
        'SELECT d.purchase_requisition_detail_id AS id, d.product_id, d.qty, d.estimated_unit_price, d.subtotal,
                p.sku AS product_code, p.product_name AS product_name, p.unit AS unit
         FROM purchase_requisition_details d
         LEFT JOIN products p ON p.product_id = d.product_id
         WHERE d.purchase_requisition_id = ? ORDER BY d.purchase_requisition_detail_id'
    );
    $detailStmt->execute([$pr['id']]);
    $pr['details'] = array_map(function ($d) {
        $d['qty'] = (float) $d['qty'];
        $d['estimated_unit_price'] = (float) $d['estimated_unit_price'];
        $d['subtotal'] = (float) $d['subtotal'];
        return $d;
    }, $detailStmt->fetchAll());

    $poStmt = $pdo->prepare('SELECT purchase_transaction_id AS id, transaction_no, status FROM purchase_transactions WHERE purchase_requisition_id = ? AND deleted_at IS NULL');
    $poStmt->execute([$pr['id']]);
    $po = $poStmt->fetch() ?: null;
    if ($po) {
        $po['status'] = pur_po_status_label($po['status']);
    }
    $pr['converted_po'] = $po;

    $pr['id'] = (int) $pr['id'];
    $pr['department_id'] = $pr['department_id'] !== null ? (int) $pr['department_id'] : null;
    $pr['subtotal'] = (float) $pr['subtotal'];
    $pr['tax'] = (float) $pr['tax'];
    $pr['grand_total'] = (float) $pr['grand_total'];
    $pr['approval_threshold_snapshot'] = $pr['approval_threshold_snapshot'] !== null ? (float) $pr['approval_threshold_snapshot'] : null;
    $pr['status_erp'] = $pr['status'];
    $pr['status'] = pur_pr_status_label($pr['status']);

    return $pr;
}

/** Baris PR mentah (status masih nilai ERP) - dipakai untuk logika bisnis sebelum diserialisasi. */
function pur_find_pr(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT ' . PUR_PR_COLS . ' ' . PUR_PR_FROM . ' WHERE pr.purchase_requisition_id = ? AND pr.deleted_at IS NULL');
    $stmt->execute([$id]);
    $pr = $stmt->fetch();
    if (!$pr) throw new ApiException('Purchase Requisition tidak ditemukan.', 404);
    return $pr;
}

/**
 * HRIS menyimpan divisi sebagai nama teks. Purchasing menerima id divisi
 * dari frontend, lalu menyimpan nama agar riwayat PR tetap terbaca jika
 * divisi diubah atau dihapus. Bila tidak dikirim, gunakan divisi karyawan.
 */
function pur_resolve_department(PDO $pdo, array $payload, int $userId): ?string
{
    $deptId = (int) ($payload['department_id'] ?? 0);
    $deptName = trim((string) ($payload['department'] ?? ''));

    if ($deptId) {
        $stmt = $pdo->prepare('SELECT divisi FROM divisi WHERE id_divisi = ?');
        $stmt->execute([$deptId]);
        $division = $stmt->fetchColumn();
        if (!$division) throw new ApiException('Departemen tidak ditemukan.', 422, ['department_id' => 'Tidak valid']);
        return (string) $division;
    }
    if ($deptName !== '') {
        $stmt = $pdo->prepare('SELECT divisi FROM divisi WHERE divisi = ? OR CAST(id_divisi AS CHAR) = ? LIMIT 1');
        $stmt->execute([$deptName, $deptName]);
        $found = $stmt->fetchColumn();
        if (!$found) throw new ApiException("Departemen \"{$deptName}\" tidak ditemukan di data HR. Pilih dari daftar departemen.", 422, ['department_id' => 'Tidak ditemukan']);
        return (string) $found;
    }

    $stmt = $pdo->prepare('SELECT e.divisi FROM users u JOIN employees e ON e.id_user = u.id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $own = $stmt->fetchColumn();
    return $own !== false ? (string) $own : null;
}

function h_pr_list(PDO $pdo): void
{
    $status = trim((string) ($_GET['status'] ?? ''));
    $search = trim((string) ($_GET['search'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 10)));

    $where = ['pr.deleted_at IS NULL'];
    $params = [];
    $erpStatuses = $status !== '' ? pur_status_filter(PUR_PR_STATUS_TO_ERP, $status) : null;
    if ($erpStatuses) {
        $where[] = 'pr.status IN (' . pur_placeholders($erpStatuses) . ')';
        array_push($params, ...$erpStatuses);
    }
    if ($search !== '') {
        $where[] = 'pr.pr_no LIKE ?';
        $params[] = '%' . $search . '%';
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_requisitions pr $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_PR_COLS . ' ' . PUR_PR_FROM . " $whereSql ORDER BY pr.created_at DESC, pr.purchase_requisition_id DESC LIMIT $pageSize OFFSET $offset");
    $stmt->execute($params);
    $rows = array_map(fn($pr) => pur_serialize_pr($pdo, $pr), $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function h_pr_get(PDO $pdo, int $id): void
{
    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)));
}

function pur_pr_payload_to_details(PDO $pdo, array $items): array
{
    // Reuse the same generic computation as PO (product_id/qty/unit_price
    // keys); PR items send "estimated_unit_price", mapped here.
    $mapped = array_map(function ($it) {
        return [
            'product_id' => $it['product_id'] ?? null,
            'qty' => $it['qty'] ?? null,
            'unit_price' => $it['estimated_unit_price'] ?? null,
        ];
    }, $items);
    return pur_compute_details($pdo, $mapped);
}

function h_pr_create(PDO $pdo): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $payload = pur_read_json_body();

    $departmentId = pur_resolve_department($pdo, $payload, $user['id']);
    $neededDate = trim((string) ($payload['needed_date'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $items = $payload['items'] ?? [];

    ['details' => $details, 'subtotal' => $subtotal] = pur_pr_payload_to_details($pdo, $items);
    $tax = pur_calculate_tax($subtotal);
    $grandTotal = round($subtotal + $tax, 2);

    $pdo->beginTransaction();
    try {
        $tmpNo = 'TMP-' . bin2hex(random_bytes(6));
        $pdo->prepare(
            "INSERT INTO purchase_requisitions (pr_no, requester_id, department, needed_date, subtotal, tax, grand_total, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?)"
        )->execute([$tmpNo, $user['id'], $departmentId, $neededDate ?: null, $subtotal, $tax, $grandTotal, $notes]);

        $id = (int) $pdo->lastInsertId();
        $prNo = pur_generate_pr_no($id);
        $pdo->prepare('UPDATE purchase_requisitions SET pr_no = ? WHERE purchase_requisition_id = ?')->execute([$prNo, $id]);

        $detailInsert = $pdo->prepare(
            'INSERT INTO purchase_requisition_details (purchase_requisition_id, product_id, qty, estimated_unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($details as $d) {
            $detailInsert->execute([$id, $d['product_id'], $d['qty'], $d['unit_price'], $d['subtotal']]);
        }

        pur_log_activity($pdo, $user['id'], 'CREATE', 'purchase_requisition', $id, "Membuat PR {$prNo} (Draft)");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)), 201);
}

function h_pr_update(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $pr = pur_find_pr($pdo, $id);
    if ($pr['status'] !== 'DRAFT') {
        throw new ApiException('Hanya PR berstatus Draft yang dapat diubah.', 409);
    }

    $payload = pur_read_json_body();
    $departmentId = pur_resolve_department($pdo, $payload, $user['id']);
    $neededDate = trim((string) ($payload['needed_date'] ?? ''));
    $notes = trim((string) ($payload['notes'] ?? ''));
    $items = $payload['items'] ?? [];

    ['details' => $details, 'subtotal' => $subtotal] = pur_pr_payload_to_details($pdo, $items);
    $tax = pur_calculate_tax($subtotal);
    $grandTotal = round($subtotal + $tax, 2);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT status FROM purchase_requisitions WHERE purchase_requisition_id = ? FOR UPDATE');
        $lockStmt->execute([$id]);
        $locked = $lockStmt->fetch();
        if (!$locked || $locked['status'] !== 'DRAFT') {
            throw new ApiException('Hanya PR berstatus Draft yang dapat diubah.', 409);
        }

        $pdo->prepare('UPDATE purchase_requisitions SET department = ?, needed_date = ?, notes = ?, subtotal = ?, tax = ?, grand_total = ? WHERE purchase_requisition_id = ?')
            ->execute([$departmentId, $neededDate ?: null, $notes, $subtotal, $tax, $grandTotal, $id]);

        $pdo->prepare('DELETE FROM purchase_requisition_details WHERE purchase_requisition_id = ?')->execute([$id]);
        $detailInsert = $pdo->prepare(
            'INSERT INTO purchase_requisition_details (purchase_requisition_id, product_id, qty, estimated_unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($details as $d) {
            $detailInsert->execute([$id, $d['product_id'], $d['qty'], $d['unit_price'], $d['subtotal']]);
        }

        pur_log_activity($pdo, $user['id'], 'UPDATE', 'purchase_requisition', $id, "Mengubah PR {$pr['pr_no']}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)));
}

/** Submit for approval - the Approval Matrix decision point. */
function h_pr_submit(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT pr_no, status, grand_total FROM purchase_requisitions WHERE purchase_requisition_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $pr = $lockStmt->fetch();
        if (!$pr) throw new ApiException('PR tidak ditemukan.', 404);
        if ($pr['status'] !== 'DRAFT') throw new ApiException('Hanya PR berstatus Draft yang dapat diajukan.', 409);

        $detailCountStmt = $pdo->prepare('SELECT COUNT(*) FROM purchase_requisition_details WHERE purchase_requisition_id = ?');
        $detailCountStmt->execute([$id]);
        if ((int) $detailCountStmt->fetchColumn() === 0) {
            throw new ApiException('PR tidak memiliki item, tidak dapat diajukan.', 422);
        }

        $threshold = (float) pur_config('pr_approval_threshold');
        $grandTotal = (float) $pr['grand_total'];

        if ($grandTotal <= $threshold) {
            // Approval Matrix: at/under threshold -> auto-approved, no
            // Finance Manager involvement needed.
            $newStatus = 'APPROVED';
            $pdo->prepare('UPDATE purchase_requisitions SET status = ?, approval_threshold_snapshot = ?, approved_by = NULL, approved_at = NOW() WHERE purchase_requisition_id = ?')
                ->execute([$newStatus, $threshold, $id]);
            $summary = "Mengajukan PR {$pr['pr_no']} - otomatis Approved (di bawah ambang batas Rp " . number_format($threshold, 0, ',', '.') . ')';
        } else {
            $newStatus = 'SUBMITTED'; // di UI tampil sebagai "Pending Approval"
            $pdo->prepare('UPDATE purchase_requisitions SET status = ?, approval_threshold_snapshot = ? WHERE purchase_requisition_id = ?')
                ->execute([$newStatus, $threshold, $id]);
            $summary = "Mengajukan PR {$pr['pr_no']} - memerlukan approval Finance Manager/Manager (di atas ambang batas Rp " . number_format($threshold, 0, ',', '.') . ')';
        }

        pur_log_activity($pdo, $user['id'], 'UPDATE', 'purchase_requisition', $id, $summary);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)));
}

function h_pr_approve(PDO $pdo, int $id): void
{
    // Manager = role ERP MANAGER (approver/manajer); boleh menyetujui PR selain Finance Manager & Admin.
    $user = pur_require_role(['Finance Manager', 'Manager', 'Admin']);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT pr_no, status FROM purchase_requisitions WHERE purchase_requisition_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $pr = $lockStmt->fetch();
        if (!$pr) throw new ApiException('PR tidak ditemukan.', 404);
        if ($pr['status'] !== 'SUBMITTED') {
            throw new ApiException('Hanya PR berstatus Pending Approval yang dapat disetujui.', 409);
        }

        $pdo->prepare("UPDATE purchase_requisitions SET status = 'APPROVED', approved_by = ?, approved_at = NOW() WHERE purchase_requisition_id = ?")
            ->execute([$user['id'], $id]);

        pur_log_activity($pdo, $user['id'], 'APPROVE', 'purchase_requisition', $id, "Menyetujui PR {$pr['pr_no']}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)));
}

function h_pr_reject(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Finance Manager', 'Manager', 'Admin']);
    $payload = pur_read_json_body();
    $reason = trim((string) ($payload['reason'] ?? ''));
    if ($reason === '') throw new ApiException('Alasan penolakan wajib diisi.', 422, ['reason' => 'Wajib diisi']);

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT pr_no, status FROM purchase_requisitions WHERE purchase_requisition_id = ? AND deleted_at IS NULL FOR UPDATE');
        $lockStmt->execute([$id]);
        $pr = $lockStmt->fetch();
        if (!$pr) throw new ApiException('PR tidak ditemukan.', 404);
        if ($pr['status'] !== 'SUBMITTED') {
            throw new ApiException('Hanya PR berstatus Pending Approval yang dapat ditolak.', 409);
        }

        $pdo->prepare("UPDATE purchase_requisitions SET status = 'REJECTED', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE purchase_requisition_id = ?")
            ->execute([$user['id'], $reason, $id]);

        pur_log_activity($pdo, $user['id'], 'REJECT', 'purchase_requisition', $id, "Menolak PR {$pr['pr_no']}: {$reason}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    pur_json_response(pur_serialize_pr($pdo, pur_find_pr($pdo, $id)));
}

/** Convert an Approved PR into a PO. Supplier + final negotiated prices are chosen here. */
function h_pr_convert(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Purchasing Staff', 'Admin']);
    $pr = pur_find_pr($pdo, $id);
    if ($pr['status'] !== 'APPROVED') {
        throw new ApiException('Hanya PR berstatus Approved yang dapat dikonversi menjadi PO.', 409);
    }

    $payload = pur_read_json_body();
    $payload['purchase_requisition_id'] = $id;
    $tx = pur_create_po_from_payload($pdo, $user, $payload);
    pur_json_response($tx, 201);
}
