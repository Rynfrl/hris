<?php
$databaseConfig = require __DIR__ . '/../../config/database.php';

function getConnection(): PDO
{
    global $databaseConfig;

    try {
        return new PDO(
            "mysql:host={$databaseConfig['host']};port={$databaseConfig['port']};dbname={$databaseConfig['name']};charset={$databaseConfig['charset']}",
            $databaseConfig['user'],
            $databaseConfig['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Koneksi database gagal. Periksa konfigurasi database.');
    }
}

function acquireDocumentLock(PDO $pdo, string $prefix): string
{
    $today = date('Ymd');
    $lockName = 'hris_sales_' . strtolower($prefix) . '_' . $today;
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 5) AS locked');
    $lockStmt->execute([$lockName]);
    if ((int)$lockStmt->fetch()['locked'] !== 1) {
        throw new RuntimeException('Gagal memperoleh lock penomoran dokumen. Silakan coba lagi.');
    }
    return $lockName;
}

function releaseDocumentLock(PDO $pdo, string $lockName): void
{
    $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
}

function nextCustomerCode(PDO $pdo): array
{
    $lockName = acquireDocumentLock($pdo, 'CUSTOMER');
    try {
        $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED)), 0) AS last_no FROM customers WHERE code LIKE 'CUST-%'");
        $next = ((int)$stmt->fetch()['last_no']) + 1;
        return ['CUST-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT), $lockName];
    } catch (Throwable $e) {
        releaseDocumentLock($pdo, $lockName);
        throw $e;
    }
}

function nextDocumentNo(PDO $pdo, string $prefix): string
{
    $today = date('Ymd');
    $lockName = acquireDocumentLock($pdo, $prefix);
    try {
        if ($prefix === 'INV') {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(invoice_no, '-', -1) AS UNSIGNED)), 0) AS last_no FROM invoices WHERE invoice_no LIKE ?");
        } else {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(order_no, '-', -1) AS UNSIGNED)), 0) AS last_no FROM sales_orders WHERE order_no LIKE ?");
        }
        $stmt->execute([$prefix . '-' . $today . '-%']);
        $next = ((int)$stmt->fetch()['last_no']) + 1;
        return $prefix . '-' . $today . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        releaseDocumentLock($pdo, $lockName);
        throw $e;
    }
}
