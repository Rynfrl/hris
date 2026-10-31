<?php
/**
 * api/handlers/suppliers.php
 * Master supplier (activates the "Master Supplier" sidebar link).
 *
 * Pemetaan tabel ERP `suppliers` -> JSON API:
 *   supplier_id -> id, supplier_code -> code, supplier_name -> name.
 */

const PUR_SUPPLIER_COLS = 'supplier_id AS id, supplier_code AS code, supplier_name AS name, phone, email, address, created_at, updated_at';

function h_suppliers_list(PDO $pdo): void
{
    $search = trim((string) ($_GET['search'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 20)));

    $where = ['deleted_at IS NULL', 'is_active = 1'];
    $params = [];
    if ($search !== '') {
        $where[] = '(supplier_code LIKE ? OR supplier_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_SUPPLIER_COLS . " FROM suppliers $whereSql ORDER BY supplier_code LIMIT $pageSize OFFSET $offset");
    $stmt->execute($params);
    pur_json_response(['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function pur_validate_supplier_payload(array $payload): array
{
    $code = trim((string) ($payload['code'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $address = trim((string) ($payload['address'] ?? ''));

    if ($code === '') throw new ApiException('Kode supplier wajib diisi.', 422, ['code' => 'Wajib diisi']);
    if (pur_strlen($code) > 50) throw new ApiException('Kode supplier maksimal 50 karakter.', 422, ['code' => 'Maks. 50 karakter']);
    if ($name === '') throw new ApiException('Nama supplier wajib diisi.', 422, ['name' => 'Wajib diisi']);
    if (pur_strlen($name) > 150) throw new ApiException('Nama supplier maksimal 150 karakter.', 422, ['name' => 'Maks. 150 karakter']);
    if (pur_strlen($phone) > 50) throw new ApiException('Nomor telepon maksimal 50 karakter.', 422, ['phone' => 'Maks. 50 karakter']);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new ApiException('Format email tidak valid.', 422, ['email' => 'Format tidak valid']);
    if (pur_strlen($email) > 190) throw new ApiException('Email maksimal 190 karakter.', 422, ['email' => 'Maks. 190 karakter']);

    return [$code, $name, $phone ?: null, $email ?: null, $address ?: null];
}

/** supplier_code unik di seluruh tabel ERP termasuk baris soft-deleted (lihat catatan serupa di products.php). */
function pur_supplier_code_taken(PDO $pdo, string $code, ?int $exceptId = null): bool
{
    $stmt = $pdo->prepare('SELECT supplier_id FROM suppliers WHERE supplier_code = ? AND supplier_id != ? LIMIT 1');
    $stmt->execute([$code, $exceptId ?? 0]);
    return (bool) $stmt->fetch();
}

function h_suppliers_create(PDO $pdo): void
{
    $user = pur_require_role(['Admin', 'Purchasing Staff']);
    [$code, $name, $phone, $email, $address] = pur_validate_supplier_payload(pur_read_json_body());

    if (pur_supplier_code_taken($pdo, $code)) throw new ApiException('Kode supplier sudah dipakai (termasuk oleh supplier yang sudah dihapus).', 422, ['code' => 'Sudah dipakai']);

    $pdo->prepare('INSERT INTO suppliers (supplier_code, supplier_name, phone, email, address) VALUES (?, ?, ?, ?, ?)')
        ->execute([$code, $name, $phone, $email, $address]);
    $id = (int) $pdo->lastInsertId();

    pur_log_activity($pdo, $user['id'], 'CREATE', 'supplier', $id, "Menambah supplier {$code} - {$name}");
    pur_json_response(['id' => $id, 'code' => $code, 'name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address], 201);
}

function h_suppliers_update(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Admin', 'Purchasing Staff']);
    $existing = $pdo->prepare('SELECT supplier_id FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
    $existing->execute([$id]);
    if (!$existing->fetch()) throw new ApiException('Supplier tidak ditemukan.', 404);

    [$code, $name, $phone, $email, $address] = pur_validate_supplier_payload(pur_read_json_body());

    if (pur_supplier_code_taken($pdo, $code, $id)) throw new ApiException('Kode supplier sudah dipakai supplier lain.', 422, ['code' => 'Sudah dipakai']);

    $pdo->prepare('UPDATE suppliers SET supplier_code = ?, supplier_name = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?')
        ->execute([$code, $name, $phone, $email, $address, $id]);

    pur_log_activity($pdo, $user['id'], 'UPDATE', 'supplier', $id, "Mengubah supplier {$code} - {$name}");
    pur_json_response(['id' => $id, 'code' => $code, 'name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address]);
}

function h_suppliers_delete(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Admin']);
    $stmt = $pdo->prepare('SELECT supplier_code FROM suppliers WHERE supplier_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $supplier = $stmt->fetch();
    if (!$supplier) throw new ApiException('Supplier tidak ditemukan.', 404);

    $pdo->prepare('UPDATE suppliers SET deleted_at = NOW() WHERE supplier_id = ?')->execute([$id]);
    pur_log_activity($pdo, $user['id'], 'DELETE', 'supplier', $id, "Menghapus (soft delete) supplier {$supplier['supplier_code']}");
    pur_json_response(['ok' => true]);
}
