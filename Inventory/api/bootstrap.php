<?php

require_once __DIR__ . '/../config/database.php';
require_once dirname(__DIR__, 2) . '/session.php';

function inventoryRequireApiAccess(bool $adminOnly = false): void
{
    $role = strtoupper(trim((string)($_SESSION['role'] ?? '')));
    $isLoggedIn = ($_SESSION['login'] ?? false) === true;

    if (!$isLoggedIn) {
        sendJsonResponse(['status' => 'error', 'message' => 'Silakan login melalui HRIS terlebih dahulu.'], 401);
    }

    if (!in_array($role, ['ADMIN_WAREHOUSE', 'WAREHOUSE_STAFF'], true)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Akses Inventory tidak diizinkan untuk role ini.'], 403);
    }

    if ($adminOnly && $role !== 'ADMIN_WAREHOUSE') {
        sendJsonResponse(['status' => 'error', 'message' => 'Fitur ini hanya dapat diakses Admin_Warehouse.'], 403);
    }
}

function inventoryRequestBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function inventoryTextLength(string $value): int
{
    $length = preg_match_all('/./us', $value);
    return $length === false ? PHP_INT_MAX : $length;
}

function inventoryValidateProductReferences(PDO $pdo, ?int $categoryId, string $sku, ?int $excludeProductId = null): void
{
    if ($categoryId !== null) {
        $categoryStmt = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
        $categoryStmt->execute([$categoryId]);
        if (!$categoryStmt->fetchColumn()) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak ditemukan.'], 400);
        }
    }

    $duplicateSql = 'SELECT 1 FROM products WHERE sku = ?';
    $params = [$sku];
    if ($excludeProductId !== null) {
        $duplicateSql .= ' AND product_id <> ?';
        $params[] = $excludeProductId;
    }

    $duplicateStmt = $pdo->prepare($duplicateSql);
    $duplicateStmt->execute($params);
    if ($duplicateStmt->fetchColumn()) {
        sendJsonResponse(['status' => 'error', 'message' => 'SKU sudah digunakan oleh produk lain.'], 409);
    }
}

function inventoryJsonError(Throwable $error, string $context): void
{
    error_log(sprintf('[Inventory] %s: %s', $context, $error));
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan server. Detail teknis dicatat di log server.',
    ], 500);
}

/**
 * Format angka stok: hilangkan desimal trailing zeros
 * 10.00 → 10 | 10.50 → 10.5 | 0.00 → 0
 */
function fmtQty($val): int|float
{
    $f = (float)$val;
    return $f == floor($f) ? (int)$f : $f;
}

/**
 * Memeriksa kelengkapan konfigurasi data produk (Product Readiness Check)
 * Sesuai Standar Operasional Gudang (9 Kriteria Wajib):
 * 1. Nama Produk (product_name)
 * 2. Kode SKU (sku)
 * 3. Kategori Produk (category_id > 0)
 * 4. Satuan Barang (unit)
 * 5. Harga Beli / Modal (purchase_price > 0)
 * 6. Harga Jual Patokan (selling_price > 0)
 * 7. Lokasi Rak / Penyimpanan Gudang (location)
 * 8. Batas Minimum Stok (minimum_stock > 0)
 * 9. Status Produk (is_active == 1)
 *
 * @param array $p Baris produk dari database
 * @return array ['is_complete' => bool, 'missing_fields' => string[], 'missing_keys' => string[]]
 */
function inventoryCheckProductCompleteness(array $p): array
{
    $missing = [];
    $missingKeys = [];

    // 1. Nama Produk
    $name = trim((string)($p['product_name'] ?? $p['name'] ?? ''));
    if ($name === '') {
        $missing[] = 'Nama Produk';
        $missingKeys[] = 'product_name';
    }

    // 2. SKU / Kode Produk
    $sku = trim((string)($p['sku'] ?? ''));
    if ($sku === '') {
        $missing[] = 'Kode SKU';
        $missingKeys[] = 'sku';
    }

    // 3. Kategori Produk
    $catId = (int)($p['category_id'] ?? 0);
    if ($catId <= 0) {
        $missing[] = 'Kategori Produk';
        $missingKeys[] = 'category_id';
    }

    // 4. Satuan Barang
    $unit = trim((string)($p['unit'] ?? ''));
    if ($unit === '') {
        $missing[] = 'Satuan Barang';
        $missingKeys[] = 'unit';
    }

    // 5. Harga Beli / Modal (harus > 0)
    $buyPrice = (float)($p['purchase_price'] ?? 0);
    if ($buyPrice <= 0) {
        $missing[] = 'Harga Beli (Modal)';
        $missingKeys[] = 'purchase_price';
    }

    // 6. Harga Jual Patokan (harus > 0)
    $sellPrice = (float)($p['selling_price'] ?? 0);
    if ($sellPrice <= 0) {
        $missing[] = 'Harga Jual';
        $missingKeys[] = 'selling_price';
    }

    // 7. Lokasi Rak / Penyimpanan Gudang
    $location = trim((string)($p['location'] ?? ''));
    if ($location === '') {
        $missing[] = 'Lokasi Rak Gudang';
        $missingKeys[] = 'location';
    }

    // 8. Batas Minimum Stok (harus > 0)
    $minStock = $p['minimum_stock'] ?? null;
    if ($minStock === null || $minStock === '' || (float)$minStock <= 0) {
        $missing[] = 'Batas Minimum Stok';
        $missingKeys[] = 'minimum_stock';
    }

    return [
        'is_complete'    => empty($missing),
        'missing_fields' => $missing,
        'missing_keys'   => $missingKeys,
    ];
}
