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
        'status' => 'error',
        'message' => 'Terjadi kesalahan server. Detail teknis dicatat di log server.',
    ], 500);
}
