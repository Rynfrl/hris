<?php
/**
 * api/handlers/products.php
 * Master produk (activates the "Master Produk" sidebar link).
 *
 * `products` adalah tabel ERP BERSAMA (dipakai juga Gudang & Sales), dengan
 * pemetaan ke JSON API purchasing:
 *   product_id -> id, sku -> code, product_name -> name,
 *   purchase_price -> unit_price (harga beli acuan).
 * Purchasing HANYA mengelola kode/nama/satuan/harga beli. Kolom milik modul
 * lain (selling_price, stock, minimum_stock) tidak pernah diubah dari sini;
 * stok berubah hanya lewat stock_movements (trigger database).
 */

const PUR_PRODUCT_COLS = 'product_id AS id, sku AS code, product_name AS name, unit, purchase_price AS unit_price,
                          stock, minimum_stock, created_at, updated_at';

function pur_product_out(array $p): array
{
    $p['id'] = (int) $p['id'];
    $p['unit_price'] = (float) $p['unit_price'];
    $p['stock'] = (float) $p['stock'];
    $p['minimum_stock'] = (float) $p['minimum_stock'];
    $p['is_low_stock'] = $p['stock'] <= $p['minimum_stock'];
    return $p;
}

function h_products_list(PDO $pdo): void
{
    $search = trim((string) ($_GET['search'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($_GET['pageSize'] ?? 20)));

    $where = ['deleted_at IS NULL', 'is_active = 1'];
    $params = [];
    if ($search !== '') {
        $where[] = '(sku LIKE ? OR product_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $pageSize;
    $stmt = $pdo->prepare('SELECT ' . PUR_PRODUCT_COLS . " FROM products $whereSql ORDER BY sku LIMIT $pageSize OFFSET $offset");
    $stmt->execute($params);
    $rows = array_map('pur_product_out', $stmt->fetchAll());

    pur_json_response(['data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
}

function pur_validate_product_payload(array $payload): array
{
    $code = trim((string) ($payload['code'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));
    $unit = trim((string) ($payload['unit'] ?? ''));
    $unitPrice = isset($payload['unit_price']) ? (float) $payload['unit_price'] : null;

    if ($code === '') throw new ApiException('Kode produk wajib diisi.', 422, ['code' => 'Wajib diisi']);
    if (pur_strlen($code) > 50) throw new ApiException('Kode produk maksimal 50 karakter.', 422, ['code' => 'Maks. 50 karakter']);
    if ($name === '') throw new ApiException('Nama produk wajib diisi.', 422, ['name' => 'Wajib diisi']);
    if (pur_strlen($name) > 150) throw new ApiException('Nama produk maksimal 150 karakter.', 422, ['name' => 'Maks. 150 karakter']);
    if ($unit === '') throw new ApiException('Satuan wajib diisi.', 422, ['unit' => 'Wajib diisi']);
    if (pur_strlen($unit) > 20) throw new ApiException('Satuan maksimal 20 karakter.', 422, ['unit' => 'Maks. 20 karakter']);
    if ($unitPrice === null || $unitPrice < 0) throw new ApiException('Harga satuan tidak valid.', 422, ['unit_price' => 'Wajib >= 0']);
    if ($unitPrice > 1_000_000_000_000) throw new ApiException('Harga satuan melebihi batas wajar.', 422, ['unit_price' => 'Terlalu besar']);

    return [$code, $name, $unit, $unitPrice];
}

/**
 * SKU unik di SELURUH tabel ERP, termasuk produk yang sudah di-soft-delete
 * (UNIQUE KEY sku tidak memedulikan deleted_at) - jadi pengecekan duplikat
 * tidak boleh mengabaikan baris yang sudah dihapus, kalau tidak INSERT gagal 500.
 */
function pur_product_code_taken(PDO $pdo, string $code, ?int $exceptId = null): bool
{
    $stmt = $pdo->prepare('SELECT product_id FROM products WHERE sku = ? AND product_id != ? LIMIT 1');
    $stmt->execute([$code, $exceptId ?? 0]);
    return (bool) $stmt->fetch();
}

function h_products_create(PDO $pdo): void
{
    $user = pur_require_role(['Admin', 'Purchasing Staff']);
    [$code, $name, $unit, $unitPrice] = pur_validate_product_payload(pur_read_json_body());

    if (pur_product_code_taken($pdo, $code)) throw new ApiException('Kode produk sudah dipakai (termasuk oleh produk yang sudah dihapus).', 422, ['code' => 'Sudah dipakai']);

    // stock = 0 & minimum_stock = 0: stok awal dicatat Gudang lewat stock_movements, bukan diisi manual di sini.
    $pdo->prepare('INSERT INTO products (sku, product_name, unit, purchase_price) VALUES (?, ?, ?, ?)')
        ->execute([$code, $name, $unit, $unitPrice]);
    $id = (int) $pdo->lastInsertId();

    pur_log_activity($pdo, $user['id'], 'CREATE', 'product', $id, "Menambah produk {$code} - {$name}");
    pur_json_response(['id' => $id, 'code' => $code, 'name' => $name, 'unit' => $unit, 'unit_price' => $unitPrice], 201);
}

function h_products_update(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Admin', 'Purchasing Staff']);
    $existing = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ? AND deleted_at IS NULL');
    $existing->execute([$id]);
    if (!$existing->fetch()) throw new ApiException('Produk tidak ditemukan.', 404);

    [$code, $name, $unit, $unitPrice] = pur_validate_product_payload(pur_read_json_body());

    if (pur_product_code_taken($pdo, $code, $id)) throw new ApiException('Kode produk sudah dipakai produk lain.', 422, ['code' => 'Sudah dipakai']);

    $pdo->prepare('UPDATE products SET sku = ?, product_name = ?, unit = ?, purchase_price = ? WHERE product_id = ?')
        ->execute([$code, $name, $unit, $unitPrice, $id]);

    pur_log_activity($pdo, $user['id'], 'UPDATE', 'product', $id, "Mengubah produk {$code} - {$name}");
    pur_json_response(['id' => $id, 'code' => $code, 'name' => $name, 'unit' => $unit, 'unit_price' => $unitPrice]);
}

function h_products_delete(PDO $pdo, int $id): void
{
    $user = pur_require_role(['Admin']);
    $stmt = $pdo->prepare('SELECT sku, stock FROM products WHERE product_id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) throw new ApiException('Produk tidak ditemukan.', 404);

    // Produk ini dipakai bersama Gudang & Sales: jangan hilangkan dari seluruh ERP selagi masih ada stoknya.
    if ((float) $product['stock'] != 0.0) {
        throw new ApiException("Produk {$product['sku']} masih memiliki stok " . (float) $product['stock'] . ' di gudang, tidak dapat dihapus. Koordinasikan dengan tim Gudang.', 409);
    }

    // Soft delete only - history (PO/PR/GRN lines referencing this product)
    // must remain intact and readable.
    $pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE product_id = ?')->execute([$id]);
    pur_log_activity($pdo, $user['id'], 'DELETE', 'product', $id, "Menghapus (soft delete) produk {$product['sku']}");
    pur_json_response(['ok' => true]);
}
