<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/bootstrap.php';

$pdo = getDbConnection();

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$desc}" . ($detail ? " -> {$detail}" : "") . "\n";
    }
}

echo "======================================================================\n";
echo "SAFE INVENTORY INTEGRATION TEST SUITE\n";
echo "HRIS & ERP MANAGEMENT SYSTEM\n";
echo "======================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. DATABASE SCHEMA & DATA INTEGRITY TESTS
// -----------------------------------------------------------------------------
echo "--- 1. Database Schema & Tables Verification ---\n";

// 1.1 settings table
$hasSettings = (bool)$pdo->query("SHOW TABLES LIKE 'settings'")->fetchColumn();
assertTest("1.1 Table 'settings' exists", $hasSettings);
$compName = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'company_name'")->fetchColumn();
assertTest("1.1b Company name in settings preserved as 'PT Sinar Nusantara Mandiri'", $compName === 'PT Sinar Nusantara Mandiri', "Got: " . var_export($compName, true));

// 1.2 storage_locations table
$hasLocations = (bool)$pdo->query("SHOW TABLES LIKE 'storage_locations'")->fetchColumn();
assertTest("1.2 Table 'storage_locations' exists", $hasLocations);
$locCols = $pdo->query("SHOW COLUMNS FROM storage_locations")->fetchAll(PDO::FETCH_COLUMN);
assertTest("1.2b storage_locations has required columns", in_array('location_id', $locCols) && in_array('location_code', $locCols) && in_array('location_name', $locCols));
$locCount = (int)$pdo->query("SELECT COUNT(*) FROM storage_locations")->fetchColumn();
assertTest("1.2c storage_locations has pre-seeded locations", $locCount >= 15, "Count: {$locCount}");

// 1.3 stock_movements status column
$smCols = $pdo->query("SHOW COLUMNS FROM stock_movements")->fetchAll(PDO::FETCH_COLUMN);
assertTest("1.3 Column 'status' exists in 'stock_movements'", in_array('status', $smCols));

// 1.4 categories columns
$catCols = $pdo->query("SHOW COLUMNS FROM categories")->fetchAll(PDO::FETCH_COLUMN);
assertTest("1.4 categories has 'creation_reason', 'deletion_reason', 'created_by', 'deleted_at'", 
    in_array('creation_reason', $catCols) && in_array('deletion_reason', $catCols) && in_array('created_by', $catCols) && in_array('deleted_at', $catCols));

// 1.5 suppliers columns
$supCols = $pdo->query("SHOW COLUMNS FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
assertTest("1.5 suppliers has 'creation_reason', 'deletion_reason', 'created_by', 'original_code'", 
    in_array('creation_reason', $supCols) && in_array('deletion_reason', $supCols) && in_array('created_by', $supCols) && in_array('original_code', $supCols));

// 1.6 products columns
$prodCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
assertTest("1.6 products has 'deletion_reason', 'deletion_notes', 'deleted_by'", 
    in_array('deletion_reason', $prodCols) && in_array('deletion_notes', $prodCols) && in_array('deleted_by', $prodCols));

// 1.7 Database trigger trg_stock_movement_after_insert
$hasTrigger = (bool)$pdo->query("SHOW TRIGGERS WHERE `Trigger` = 'trg_stock_movement_after_insert'")->fetchColumn();
assertTest("1.7 Trigger 'trg_stock_movement_after_insert' is intact", $hasTrigger);


// -----------------------------------------------------------------------------
// 2. BASELINE DATA PRESERVATION (ZERO LOSS)
// -----------------------------------------------------------------------------
echo "\n--- 2. Baseline Stock & Master Data Preservation ---\n";

$prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
assertTest("2.1 Baseline products preserved (at least 15 active products)", $prodCount >= 15, "Got: {$prodCount}");

$stockSum = (float)$pdo->query("SELECT SUM(stock) FROM products WHERE deleted_at IS NULL")->fetchColumn();
assertTest("2.2 Total product stock is preserved (> 0 and >= 1058)", $stockSum >= 1058, "Got: {$stockSum}");

$smCount = (int)$pdo->query("SELECT COUNT(*) FROM stock_movements")->fetchColumn();
assertTest("2.3 stock_movements records preserved (at least 15 rows)", $smCount >= 15, "Got: {$smCount}");


// -----------------------------------------------------------------------------
// 3. ROLE-BASED ACCESS CONTROL (RBAC) TESTS
// -----------------------------------------------------------------------------
echo "\n--- 3. Role-Based Access Control Verification ---\n";

// Helper function to simulate HTTP API call via isolated sub-process
function simulateApiCall(string $file, string $method, array $body = [], array $getParams = [], array $sessionData = []): array {
    $sessionCode = '';
    if (!empty($sessionData)) {
        $sessionCode = sprintf('
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            $_SESSION = %s;
        ', var_export($sessionData, true));
    }

    $script = sprintf('
        %s
        $_SERVER["REQUEST_METHOD"] = %s;
        $_GET = %s;
        $_POST = %s;
        require __DIR__ . "/../api/" . %s;
    ', 
        $sessionCode,
        var_export($method, true),
        var_export($getParams, true),
        var_export($body, true),
        var_export($file, true)
    );

    $tempFile = __DIR__ . '/_temp_api_test.php';
    file_put_contents($tempFile, "<?php\n" . $script);

    $output = [];
    $retCode = 0;
    exec('php ' . escapeshellarg($tempFile) . ' 2>&1', $output, $retCode);
    @unlink($tempFile);

    $raw = implode("\n", $output);
    $json = null;
    $httpCode = 200;

    // Parse JSON
    if (preg_match('/(\{.*\})/s', $raw, $m)) {
        $json = json_decode($m[1], true);
    }

    // Determine status code
    if ($json && isset($json['status'])) {
        if ($json['status'] === 'error') {
            if (str_contains($json['message'] ?? '', 'login')) {
                $httpCode = 401;
            } elseif (str_contains($json['message'] ?? '', 'tidak diizinkan') || str_contains($json['message'] ?? '', 'hanya dapat diakses') || str_contains($json['message'] ?? '', 'Akses ditolak')) {
                $httpCode = 403;
            } elseif (str_contains($json['message'] ?? '', 'tidak ditemukan')) {
                $httpCode = 404;
            } elseif (str_contains($json['message'] ?? '', 'sudah digunakan') || str_contains($json['message'] ?? '', 'tidak dapat dihapus') || str_contains($json['message'] ?? '', 'tidak mencukupi') || str_contains($json['message'] ?? '', 'Tidak ada stok')) {
                $httpCode = 409;
            } else {
                $httpCode = 400;
            }
        } elseif ($json['status'] === 'success') {
            $httpCode = str_contains($raw, '201') || str_contains($json['message'] ?? '', 'berhasil ditambahkan') || str_contains($json['message'] ?? '', 'berhasil dicatat') ? 201 : 200;
        }
    }

    return ['code' => $httpCode, 'raw' => $raw, 'json' => $json];
}

// 3.1 Unauthenticated access -> 401
$resUnauth = simulateApiCall('status.php', 'GET', [], [], []);
assertTest("3.1 Unauthenticated API request returns 401 Unauthorized", $resUnauth['code'] === 401);

// 3.2 Unauthorized role (EMPLOYEE) -> 403
$resEmp = simulateApiCall('status.php', 'GET', [], [], ['login' => true, 'role' => 'EMPLOYEE', 'user_id' => 15]);
assertTest("3.2 Unauthorized role (EMPLOYEE) returns 403 Forbidden", $resEmp['code'] === 403);

// 3.3 WAREHOUSE_STAFF can read GET status.php -> 200
$resStaffGet = simulateApiCall('status.php', 'GET', [], [], ['login' => true, 'role' => 'WAREHOUSE_STAFF', 'user_id' => 7, 'name' => 'Staf Gudang']);
assertTest("3.3 WAREHOUSE_STAFF can access GET status.php (200 OK)", $resStaffGet['code'] === 200 && ($resStaffGet['json']['status'] ?? '') === 'success');

// 3.4 WAREHOUSE_STAFF cannot create location -> 403
$resStaffLocPost = simulateApiCall('locations.php', 'POST', ['location_code' => 'TEST-01', 'location_name' => 'Test Location'], [], [
    'login' => true, 'role' => 'WAREHOUSE_STAFF', 'user_id' => 7, 'name' => 'Staf Gudang'
]);
assertTest("3.4 WAREHOUSE_STAFF cannot create location (403 Forbidden)", $resStaffLocPost['code'] === 403);

// 3.5 WAREHOUSE_STAFF cannot create product -> 403
$resStaffProdPost = simulateApiCall('products.php', 'POST', ['sku' => 'TEST-SKU', 'name' => 'Test Product'], [], [
    'login' => true, 'role' => 'WAREHOUSE_STAFF', 'user_id' => 7, 'name' => 'Staf Gudang'
]);
assertTest("3.5 WAREHOUSE_STAFF cannot create product (403 Forbidden)", $resStaffProdPost['code'] === 403);

// 3.6 WAREHOUSE_STAFF cannot create category -> 403
$resStaffCatPost = simulateApiCall('categories.php', 'POST', ['name' => 'Test Cat', 'description' => 'Test Desc'], [], [
    'login' => true, 'role' => 'WAREHOUSE_STAFF', 'user_id' => 7, 'name' => 'Staf Gudang'
]);
assertTest("3.6 WAREHOUSE_STAFF cannot create category (403 Forbidden)", $resStaffCatPost['code'] === 403);

// 3.7 WAREHOUSE_STAFF cannot create supplier -> 403
$resStaffSupPost = simulateApiCall('suppliers.php', 'POST', ['supplier_name' => 'Test Sup'], [], [
    'login' => true, 'role' => 'WAREHOUSE_STAFF', 'user_id' => 7, 'name' => 'Staf Gudang'
]);
assertTest("3.7 WAREHOUSE_STAFF cannot create supplier (403 Forbidden)", $resStaffSupPost['code'] === 403);


// -----------------------------------------------------------------------------
// 4. INVENTORY MASTER DATA CRUD (ADMIN_WAREHOUSE)
// -----------------------------------------------------------------------------
echo "\n--- 4. Master Data Operations (ADMIN_WAREHOUSE) ---\n";

$adminSession = [
    'login' => true,
    'role' => 'ADMIN_WAREHOUSE',
    'user_id' => 6,
    'name' => 'Kepala Gudang',
    'username' => 'admin_warehouse',
    'email' => 'warehouse@sinarnusantara.co.id'
];

// 4.1 Create Location
$testLocCode = 'TST-LOC-' . time();
$resLoc = simulateApiCall('locations.php', 'POST', [
    'location_code' => $testLocCode,
    'location_name' => 'Lokasi Uji ' . time(),
    'area' => 'Area Uji'
], [], $adminSession);
assertTest("4.1 ADMIN_WAREHOUSE can create location (201 Created)", $resLoc['code'] === 201 && ($resLoc['json']['status'] ?? '') === 'success');
$testLocId = (int)($resLoc['json']['data']['location_id'] ?? 0);

// 4.2 Create Category
$testCatCode = 'CAT-U' . substr((string)time(), -4);
$resCat = simulateApiCall('categories.php', 'POST', [
    'code' => $testCatCode,
    'name' => 'Kategori Uji ' . time(),
    'description' => 'Deskripsi Kategori Pengujian Terpadu',
    'reason' => 'Pengujian integrasi aman'
], [], $adminSession);
assertTest("4.2 ADMIN_WAREHOUSE can create category (201 Created)", $resCat['code'] === 201 && ($resCat['json']['status'] ?? '') === 'success');
$testCatId = (int)($resCat['json']['data']['id'] ?? 0);

// 4.3 Create Supplier
$testSupCode = 'SUP-U' . substr((string)time(), -4);
$resSup = simulateApiCall('suppliers.php', 'POST', [
    'supplier_code' => $testSupCode,
    'supplier_name' => 'Pemasok Uji ' . time(),
    'phone' => '081234567890',
    'email' => 'supplier.test@example.com',
    'address' => 'Jl. Pengujian No. 12',
    'reason' => 'Pengujian integrasi pemasok'
], [], $adminSession);
assertTest("4.3 ADMIN_WAREHOUSE can create supplier (201 Created)", $resSup['code'] === 201 && ($resSup['json']['status'] ?? '') === 'success');
$testSupId = (int)($resSup['json']['data']['id'] ?? 0);

// 4.4 Create Product
$testSku = 'SKU-UJI-' . time();
$resProd = simulateApiCall('products.php', 'POST', [
    'sku' => $testSku,
    'name' => 'Produk Uji Integrasi ' . time(),
    'category_id' => $testCatId,
    'unit' => 'Pcs',
    'purchase_price' => 50000.00,
    'selling_price' => 75000.00,
    'minimum_stock' => 10,
    'location' => 'Rak IT-A1',
    'description' => 'Produk untuk validasi mutasi stok'
], [], $adminSession);
assertTest("4.4 ADMIN_WAREHOUSE can create product (201 Created)", $resProd['code'] === 201 && ($resProd['json']['status'] ?? '') === 'success');
$testProdId = (int)($resProd['json']['product_id'] ?? ($resProd['json']['data']['id'] ?? 0));


// -----------------------------------------------------------------------------
// 5. STOCK MOVEMENTS & TRIGGER INTEGRATION
// -----------------------------------------------------------------------------
echo "\n--- 5. Stock Movement Transactions & Trigger Verification ---\n";

$initialProdStock = (float)$pdo->query("SELECT stock FROM products WHERE product_id = {$testProdId}")->fetchColumn();
assertTest("5.1 Initial product stock is 0", $initialProdStock === 0.0);

// 5.2 Stock In (50 Pcs)
$resStockIn = simulateApiCall('stock.php', 'POST', [
    'type' => 'in',
    'product_id' => $testProdId,
    'quantity' => 50,
    'stock_status' => 'aktif',
    'reference_no' => 'SJ-TEST-' . time(),
    'supplier_id' => $testSupId,
    'notes' => 'Penerimaan stok uji coba'
], [], $adminSession);
assertTest("5.2 Record Stock In 50 Pcs succeeds (201 Created)", $resStockIn['code'] === 201 && ($resStockIn['json']['status'] ?? '') === 'success');

// Verify products.stock updated by trigger (MUST BE 50.000)
$afterInStock = (float)$pdo->query("SELECT stock FROM products WHERE product_id = {$testProdId}")->fetchColumn();
assertTest("5.3 products.stock updated to exactly 50 by database trigger (single source of truth)", $afterInStock === 50.0, "Got: {$afterInStock}");

// 5.4 Stock Out (20 Pcs)
$resStockOut = simulateApiCall('stock.php', 'POST', [
    'type' => 'out',
    'product_id' => $testProdId,
    'quantity' => 20,
    'reference_no' => 'SPK-TEST-' . time(),
    'notes' => 'Pengeluaran stok uji coba'
], [], $adminSession);
assertTest("5.4 Record Stock Out 20 Pcs succeeds (201 Created)", $resStockOut['code'] === 201 && ($resStockOut['json']['status'] ?? '') === 'success');

// Verify products.stock updated by trigger (MUST BE 30.000)
$afterOutStock = (float)$pdo->query("SELECT stock FROM products WHERE product_id = {$testProdId}")->fetchColumn();
assertTest("5.5 products.stock updated to exactly 30 after Stock Out", $afterOutStock === 30.0, "Got: {$afterOutStock}");

// 5.6 Stock Out exceeding available stock -> 409 Conflict
$resExceed = simulateApiCall('stock.php', 'POST', [
    'type' => 'out',
    'product_id' => $testProdId,
    'quantity' => 100, // available is only 30
    'reference_no' => 'SPK-EXCEED',
    'notes' => 'Should fail due to insufficient stock'
], [], $adminSession);
assertTest("5.6 Stock Out exceeding available stock blocked with 409 Conflict", $resExceed['code'] === 409);


// -----------------------------------------------------------------------------
// 6. REPORTS & SETTINGS API VERIFICATION
// -----------------------------------------------------------------------------
echo "\n--- 6. Reports & Settings API Verification ---\n";

// 6.1 Reports API
$resReports = simulateApiCall('reports.php', 'GET', [], [], $adminSession);
assertTest("6.1 Reports API returns 200 OK with summary and movements", 
    $resReports['code'] === 200 && ($resReports['json']['status'] ?? '') === 'success' && isset($resReports['json']['data']['items']));

// 6.2 Settings API GET
$resSettingsGet = simulateApiCall('settings.php', 'GET', [], [], $adminSession);
assertTest("6.2 Settings API GET returns 200 OK", 
    $resSettingsGet['code'] === 200 && ($resSettingsGet['json']['status'] ?? '') === 'success' && isset($resSettingsGet['json']['data']['company_name']));

// 6.3 Dashboard API GET
$resDash = simulateApiCall('dashboard.php', 'GET', [], [], $adminSession);
assertTest("6.3 Dashboard API GET returns 200 OK with metrics", 
    $resDash['code'] === 200 && ($resDash['json']['status'] ?? '') === 'success' && isset($resDash['json']['metrics']));


// -----------------------------------------------------------------------------
// 7. CLEANUP TEST ENTITIES
// -----------------------------------------------------------------------------
echo "\n--- 7. Cleaning Up Test Fixtures ---\n";

// Clean test stock_movements & master entities
$pdo->exec("DELETE FROM stock_movements WHERE product_id IN (SELECT product_id FROM products WHERE sku LIKE 'SKU-UJI-%')");
$pdo->exec("DELETE FROM products WHERE sku LIKE 'SKU-UJI-%'");
$pdo->exec("DELETE FROM categories WHERE code LIKE 'CAT-U%'");
$pdo->exec("DELETE FROM suppliers WHERE supplier_code LIKE 'SUP-U%'");
$pdo->exec("DELETE FROM storage_locations WHERE location_code LIKE 'TST-LOC-%'");

// Final assertion: products count and stock sum still intact
$finalProdCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$finalStockSum = (float)$pdo->query("SELECT SUM(stock) FROM products WHERE deleted_at IS NULL")->fetchColumn();
assertTest("7.1 Baseline product count restored to 15", $finalProdCount === 15, "Got: {$finalProdCount}");
assertTest("7.2 Baseline stock sum restored to 1058", $finalStockSum === 1058.0, "Got: {$finalStockSum}");


// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n======================================================================\n";
echo "TEST RESULTS: {$passedTests} PASSED, {$failedTests} FAILED (TOTAL: {$totalTests})\n";
echo "======================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
