<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}
inventoryRequireApiAccess($method !== 'GET' && $method !== 'POST');

try {
    $pdo = getDbConnection();

    // ======================================================================
    // GET: Ambil riwayat mutasi stok (dengan nama petugas)
    // ======================================================================
    if ($method === 'GET') {
        $limit = max(1, min(1000, (int)($_GET['limit'] ?? 500)));
        $type = strtolower(trim((string)($_GET['type'] ?? '')));
        $conditions = [];
        if ($type === 'in') {
            $conditions[] = "m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') AND m.status NOT IN ('transfer', 'deleted') AND m.reference_no NOT LIKE 'TRF-%' AND m.reference_no NOT LIKE 'DEL-%'";
        } elseif ($type === 'out') {
            $conditions[] = "m.type IN ('PURCHASE_RETURN', 'SALE', 'ADJUSTMENT_OUT') AND m.status NOT IN ('transfer', 'deleted') AND m.reference_no NOT LIKE 'TRF-%' AND m.reference_no NOT LIKE 'DEL-%'";
        } elseif ($type === 'transfer') {
            $conditions[] = "(m.status = 'transfer' OR m.reference_no LIKE 'TRF-%' OR m.notes LIKE '%[TRANSFER STATUS%')";
        } elseif ($type !== '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Filter tipe harus in, out, atau transfer.'], 400);
        }
        $conditions[] = "m.reference_no NOT LIKE 'LOT-SPLIT-%'";

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->query(
            "WITH all_stock_movements AS (
                SELECT 
                    m.stock_movement_id,
                    m.product_id,
                    m.type,
                    m.qty,
                    m.reference_no,
                    m.status,
                    m.supplier_id,
                    m.customer_id,
                    m.created_by,
                    m.notes,
                    m.created_at,
                    SUM(
                        CASE 
                            WHEN m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN m.qty 
                            WHEN m.type IN ('PURCHASE_RETURN', 'SALE', 'ADJUSTMENT_OUT') THEN -m.qty 
                            ELSE 0 
                        END
                    ) OVER (
                        PARTITION BY m.product_id 
                        ORDER BY m.created_at ASC, m.stock_movement_id ASC
                    ) AS balance_stock
                FROM stock_movements m
             )
             SELECT IF(m.status = 'transfer' OR m.reference_no LIKE 'TRF-%' OR m.notes LIKE '%[TRANSFER STATUS%', 'TRANSFER', IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT')) AS movement_type,
                    m.type AS raw_type,
                    m.created_at AS movement_date,
                    m.reference_no AS ref_number,
                    CASE 
                        WHEN m.status = 'deleted' OR m.notes LIKE '%[PRODUK DIHAPUS]%' THEN 'deleted'
                        WHEN m.status = 'transfer' OR m.reference_no LIKE 'TRF-%' OR m.notes LIKE '%[TRANSFER STATUS%' THEN 'transfer'
                        WHEN m.type = 'PURCHASE_RETURN' AND (prt.condition IN ('Defective', 'Shortage') OR grd_ret.condition_note IN ('Damaged', 'Shortage') OR grd.condition_note IN ('Damaged', 'Shortage')) THEN 'karantina'
                        WHEN grd.condition_note IN ('Damaged', 'Shortage') THEN 'karantina'
                        WHEN m.status IN ('draft', 'karantina', 'aktif') THEN m.status
                        WHEN m.notes LIKE '%Status: Draft%' THEN 'draft'
                        WHEN m.notes LIKE '%Status: Karantina%' THEN 'karantina'
                        ELSE 'aktif'
                    END AS stock_status,
                    'Completed' AS status,
                    COALESCE(
                        NULLIF(m.notes, ''),
                        NULLIF(prt.reason, ''),
                        NULLIF(so.notes, ''),
                        NULLIF(gr.notes, ''),
                        NULLIF(pt.notes, ''),
                        NULLIF(pr.notes, ''),
                        CONCAT('Penerimaan PO #', COALESCE(pt.transaction_no, pt_ret.transaction_no, '-'))
                    ) AS notes,
                    p.product_id AS product_id,
                    p.sku,
                    p.product_name,
                    p.unit,
                    m.qty AS quantity,
                    m.balance_stock,
                    COALESCE(grd.condition_note, prt.condition) AS condition_note,
                    IF(m.type IN ('PURCHASE', 'ADJUSTMENT_IN'), p.purchase_price, p.selling_price) AS price,
                    m.qty * IF(m.type IN ('PURCHASE', 'ADJUSTMENT_IN'), p.purchase_price, p.selling_price) AS subtotal,
                    COALESCE(s.supplier_name, s_gr.supplier_name, s_ret.supplier_name, c_so.name, c.name, 'Internal / Gudang') AS partner_name,
                    COALESCE(pr.department, pr_ret.department) AS department,
                    COALESCE(pt.transaction_no, pt_ret.transaction_no) AS po_no,
                    COALESCE(gr.grn_no, gr_ret.grn_no) AS grn_no,
                    so.order_no AS so_no,
                    CASE WHEN m.supplier_id IS NOT NULL OR s_gr.supplier_id IS NOT NULL OR s_ret.supplier_id IS NOT NULL THEN 'Supplier'
                         WHEN m.customer_id IS NOT NULL OR c_so.id IS NOT NULL THEN 'Customer'
                         ELSE 'Warehouse' END AS partner_type,
                    COALESCE(
                        IF(u.name IS NOT NULL, CONCAT(u.name, ' (', 
                            CASE u.role
                              WHEN 'ADMIN_WAREHOUSE' THEN 'Admin_Warehouse'
                              WHEN 'Admin_Warehouse' THEN 'Admin_Warehouse'
                              WHEN 'WAREHOUSE_STAFF' THEN 'Warehouse_Staff'
                              WHEN 'Warehouse_Staff' THEN 'Warehouse_Staff'
                              WHEN 'Admin_Purchase'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASE'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASING' THEN 'Admin_Purchase'
                              WHEN 'Purchasing'      THEN 'Admin_Purchase'
                              WHEN 'Purchasing_Staff' THEN 'Purchasing_Staff'
                              WHEN 'Admin_Sales'     THEN 'Admin_Sales'
                              WHEN 'Marketing_Staff' THEN 'Marketing_Staff'
                              WHEN 'Admin_HR'        THEN 'Admin_HR'
                              WHEN 'ADMIN'           THEN 'Admin'
                              ELSE u.role
                            END,
                        ')'), NULL),
                        IF(u_so.name IS NOT NULL, CONCAT(u_so.name, ' (', 
                            CASE u_so.role
                              WHEN 'ADMIN_WAREHOUSE' THEN 'Admin_Warehouse'
                              WHEN 'Admin_Warehouse' THEN 'Admin_Warehouse'
                              WHEN 'WAREHOUSE_STAFF' THEN 'Warehouse_Staff'
                              WHEN 'Warehouse_Staff' THEN 'Warehouse_Staff'
                              WHEN 'Admin_Purchase'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASE'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASING' THEN 'Admin_Purchase'
                              WHEN 'Purchasing'      THEN 'Admin_Purchase'
                              WHEN 'Purchasing_Staff' THEN 'Purchasing_Staff'
                              WHEN 'Admin_Sales'     THEN 'Admin_Sales'
                              WHEN 'ADMIN_SALES'     THEN 'Admin_Sales'
                              WHEN 'SALES'           THEN 'Admin_Sales'
                              WHEN 'Marketing_Staff' THEN 'Marketing_Staff'
                              WHEN 'Admin_HR'        THEN 'Admin_HR'
                              WHEN 'ADMIN'           THEN 'Admin'
                              ELSE u_so.role
                            END,
                        ')'), NULL),
                        IF(u_prt.name IS NOT NULL, CONCAT(u_prt.name, ' (', 
                            CASE u_prt.role
                              WHEN 'ADMIN_WAREHOUSE' THEN 'Admin_Warehouse'
                              WHEN 'Admin_Warehouse' THEN 'Admin_Warehouse'
                              WHEN 'WAREHOUSE_STAFF' THEN 'Warehouse_Staff'
                              WHEN 'Warehouse_Staff' THEN 'Warehouse_Staff'
                              WHEN 'Admin_Purchase'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASE'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASING' THEN 'Admin_Purchase'
                              WHEN 'Purchasing'      THEN 'Admin_Purchase'
                              WHEN 'Purchasing_Staff' THEN 'Purchasing_Staff'
                              WHEN 'Admin_Sales'     THEN 'Admin_Sales'
                              WHEN 'Marketing_Staff' THEN 'Marketing_Staff'
                              WHEN 'Admin_HR'        THEN 'Admin_HR'
                              WHEN 'ADMIN'           THEN 'Admin'
                              ELSE u_prt.role
                            END,
                        ')'), NULL),
                        IF(u_gr.name IS NOT NULL, CONCAT(u_gr.name, ' (', 
                            CASE u_gr.role
                              WHEN 'ADMIN_WAREHOUSE' THEN 'Admin_Warehouse'
                              WHEN 'Admin_Warehouse' THEN 'Admin_Warehouse'
                              WHEN 'WAREHOUSE_STAFF' THEN 'Warehouse_Staff'
                              WHEN 'Warehouse_Staff' THEN 'Warehouse_Staff'
                              WHEN 'Admin_Purchase'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASE'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASING' THEN 'Admin_Purchase'
                              WHEN 'Purchasing'      THEN 'Admin_Purchase'
                              WHEN 'Purchasing_Staff' THEN 'Purchasing_Staff'
                              WHEN 'Admin_Sales'     THEN 'Admin_Sales'
                              WHEN 'Marketing_Staff' THEN 'Marketing_Staff'
                              WHEN 'Admin_HR'        THEN 'Admin_HR'
                              WHEN 'ADMIN'           THEN 'Admin'
                              ELSE u_gr.role
                            END,
                        ')'), NULL),
                        IF(u_po.name IS NOT NULL, CONCAT(u_po.name, ' (', 
                            CASE u_po.role
                              WHEN 'ADMIN_WAREHOUSE' THEN 'Admin_Warehouse'
                              WHEN 'Admin_Warehouse' THEN 'Admin_Warehouse'
                              WHEN 'WAREHOUSE_STAFF' THEN 'Warehouse_Staff'
                              WHEN 'Warehouse_Staff' THEN 'Warehouse_Staff'
                              WHEN 'Admin_Purchase'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASE'  THEN 'Admin_Purchase'
                              WHEN 'ADMIN_PURCHASING' THEN 'Admin_Purchase'
                              WHEN 'Purchasing'      THEN 'Admin_Purchase'
                              WHEN 'Purchasing_Staff' THEN 'Purchasing_Staff'
                              WHEN 'Admin_Sales'     THEN 'Admin_Sales'
                              WHEN 'Marketing_Staff' THEN 'Marketing_Staff'
                              WHEN 'Admin_HR'        THEN 'Admin_HR'
                              WHEN 'ADMIN'           THEN 'Admin'
                              ELSE u_po.role
                            END,
                        ')'), NULL),
                        'Petugas Gudang'
                    ) AS performed_by_name
             FROM all_stock_movements m
             JOIN products p ON p.product_id = m.product_id
             LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
             LEFT JOIN customers c ON c.id = m.customer_id
             LEFT JOIN users u ON u.id = m.created_by
             LEFT JOIN goods_receipts gr ON gr.grn_no = m.reference_no
             LEFT JOIN goods_receipt_details grd ON grd.goods_receipt_id = gr.goods_receipt_id AND grd.product_id = m.product_id
             LEFT JOIN purchase_transactions pt ON pt.purchase_transaction_id = gr.purchase_transaction_id
             LEFT JOIN suppliers s_gr ON s_gr.supplier_id = pt.supplier_id
             LEFT JOIN users u_gr ON u_gr.id = gr.received_by
             LEFT JOIN users u_po ON u_po.id = pt.user_id
             LEFT JOIN purchase_requisitions pr ON pr.purchase_requisition_id = pt.purchase_requisition_id
             LEFT JOIN purchase_returns prt ON prt.return_no = m.reference_no
             LEFT JOIN users u_prt ON u_prt.id = prt.created_by
             LEFT JOIN goods_receipts gr_ret ON gr_ret.goods_receipt_id = prt.goods_receipt_id
             LEFT JOIN goods_receipt_details grd_ret ON grd_ret.goods_receipt_id = gr_ret.goods_receipt_id AND grd_ret.product_id = m.product_id
             LEFT JOIN purchase_transactions pt_ret ON pt_ret.purchase_transaction_id = gr_ret.purchase_transaction_id
             LEFT JOIN suppliers s_ret ON s_ret.supplier_id = pt_ret.supplier_id
             LEFT JOIN purchase_requisitions pr_ret ON pr_ret.purchase_requisition_id = pt_ret.purchase_requisition_id
             LEFT JOIN sales_orders so ON (m.reference_no LIKE CONCAT('SO-', so.order_no, '%') OR m.reference_no = so.order_no)
             LEFT JOIN customers c_so ON c_so.id = so.customer_id
             LEFT JOIN users u_so ON u_so.id = so.created_by
             $where
             ORDER BY m.created_at DESC, m.stock_movement_id DESC
             LIMIT $limit"
        );
        $movements = $stmt->fetchAll();
        foreach ($movements as &$movement) {
            $movement['product_name'] = (string)$movement['product_name'];
            // Format qty: hilangkan trailing decimal zeros (10.00 â†’ 10, 10.50 â†’ 10.5)
            $qty = (float)$movement['quantity'];
            $movement['quantity'] = $qty == floor($qty) ? (int)$qty : $qty;
            $bal = (float)($movement['balance_stock'] ?? 0);
            $movement['balance_stock'] = $bal == floor($bal) ? (int)$bal : $bal;
            if ($movement['stock_status'] === 'transfer' || strpos($movement['notes'] ?? '', '[TRANSFER STATUS') !== false) {
                $movement['is_transfer'] = true;
                if (preg_match('/Sebesar\s+([\d\.\,]+)/i', $movement['notes'] ?? '', $matches)) {
                    $movement['transfer_qty'] = (float)str_replace(',', '.', $matches[1]);
                }
            }
        }
        unset($movement);

        sendJsonResponse(['status' => 'success', 'count' => count($movements), 'data' => $movements]);
    }

    if ($method !== 'POST') {
        sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
    }

    // ======================================================================
    // POST: Catat mutasi stok (Masuk / Keluar)
    // ======================================================================
    $body       = inventoryRequestBody();
    $type       = strtolower(trim((string)($body['type'] ?? 'in')));
    $productId  = (int)($body['product_id'] ?? 0);
    $quantity   = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $date       = trim((string)($body['date'] ?? date('Y-m-d')));
    $notes      = trim((string)($body['notes'] ?? ''));
    $rawSupplier = $body['supplier_id'] ?? null;
    $supplierId  = ($rawSupplier !== null && $rawSupplier !== '' && $rawSupplier !== 'undefined')
                   ? (int)$rawSupplier : null;

    // No. Surat Jalan (IN) atau No. SPK (OUT)
    $noSuratJalan = trim((string)($body['no_surat_jalan'] ?? ''));
    $noSpk        = trim((string)($body['no_spk'] ?? ''));

    // Lokasi / Rak Penyimpanan (Ambil dari input atau alias)
    $location = trim((string)($body['location'] ?? $body['lokasi'] ?? $body['rack'] ?? ''));

    // Batas Stok Minimum (Opsional, batas minimum dikelola dari master produk)
    $rawMinStock  = $body['minimum_stock'] ?? null;
    $minimumStock = ($rawMinStock !== null && $rawMinStock !== '') ? filter_var($rawMinStock, FILTER_VALIDATE_FLOAT) : null;
    if ($minimumStock !== null && ($minimumStock === false || $minimumStock < 0)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Batas stok minimum tidak valid.'], 400);
    }

    // Status Stok / Produk (Khusus Stok Masuk: 'aktif', 'draft', 'karantina')
    $stockStatus = strtolower(trim((string)($body['stock_status'] ?? $body['status'] ?? 'aktif')));
    if ($type === 'in' && !in_array($stockStatus, ['aktif', 'draft', 'karantina'], true)) {
        $stockStatus = 'aktif';
    }

    $dateValue  = DateTime::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();
    if (!in_array($type, ['in', 'out'], true) || $productId <= 0
        || $quantity === false || !is_finite((float)$quantity) || $quantity <= 0
        || !$dateValue || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))
        || $dateValue->format('Y-m-d') !== $date) {
        sendJsonResponse(['status' => 'error', 'message' => 'Tipe, produk, jumlah, atau tanggal mutasi tidak valid.'], 400);
    }
    if ($type === 'out' && $supplierId !== null) {
        sendJsonResponse(['status' => 'error', 'message' => 'Supplier hanya dapat dipilih untuk stok masuk.'], 400);
    }

    // Supplier wajib untuk stok masuk
    if ($type === 'in' && $supplierId === null) {
        sendJsonResponse(['status' => 'error', 'message' => 'Pemasok (Supplier) wajib dipilih untuk stok masuk.'], 400);
    }

    $pdo->beginTransaction();
    $productStmt = $pdo->prepare(
        "SELECT product_id, sku, product_name, category_id, unit, stock, purchase_price, selling_price, location, minimum_stock, is_active
         FROM products
         WHERE product_id = ? AND deleted_at IS NULL
         FOR UPDATE"
    );
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    if (!$product) {
        $pdo->rollBack();
        sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan di database.'], 404);
    }

    // ----------------------------------------------------------------------
    // Validasi Kesiapan Data Produk (Product Readiness & Completeness Check)
    // Sesuai Aturan: Produk yang belum lengkap konfigurasinya (nama, SKU, kategori,
    // satuan, harga beli, harga jual, lokasi rak, batas minimum stok, status aktif)
    // TIDAK DAPAT dimasukkan (Stok Masuk) maupun dikeluarkan (Stok Keluar).
    // ----------------------------------------------------------------------
    $completeness = inventoryCheckProductCompleteness($product);
    if (!$completeness['is_complete']) {
        $pdo->rollBack();
        sendJsonResponse([
            'status'         => 'error',
            'error_code'     => 'PRODUCT_INCOMPLETE',
            'product_id'     => (int)$productId,
            'product_name'   => $product['product_name'],
            'missing_fields' => $completeness['missing_fields'],
            'missing_keys'   => $completeness['missing_keys'],
            'message'        => sprintf(
                'Transaksi stok %s tidak dapat diproses! Data produk "%s" belum lengkap (%s belum diisi/ditetapkan). Silakan lengkapi data produk terlebih dahulu melalui menu Edit Produk sebelum melakukan mutasi stok.',
                $type === 'in' ? 'masuk' : 'keluar',
                $product['product_name'],
                implode(', ', $completeness['missing_fields'])
            ),
        ], 422);
    }

    // Validasi Kesesuaian Lokasi Rak dengan Master Data Produk
    $masterLocation = trim((string)($product['location'] ?? ''));
    if ($location === '') {
        $location = $masterLocation;
    }

    if ($location === '') {
        $pdo->rollBack();
        sendJsonResponse(['status' => 'error', 'message' => 'Lokasi / Rak Penyimpanan wajib diisi.'], 400);
    }

    // Jika lokasi yang dimasukkan berbeda dengan master lokasi rak produk, transaksi dicekal
    if ($masterLocation !== '' && strcasecmp(trim($location), $masterLocation) !== 0) {
        $pdo->rollBack();
        sendJsonResponse([
            'status'            => 'error',
            'error_code'        => 'LOCATION_MISMATCH',
            'product_id'        => (int)$productId,
            'expected_location' => $masterLocation,
            'input_location'    => $location,
            'message'           => sprintf(
                'Transaksi stok %s gagal! Lokasi rak yang Anda masukkan ("%s") tidak sesuai dengan master lokasi rak produk ("%s"). Barang wajib disimpan atau diambil dari lokasi rak penyimpanan resminya.',
                $type === 'in' ? 'masuk' : 'keluar',
                $location,
                $masterLocation
            ),
        ], 422);
    }

    // Label status untuk log catatan transaksi
    $statusLabels = [
        'aktif'     => 'Aktif (Siap Edar)',
        'draft'     => 'Draft (Belum Dipublikasikan)',
        'karantina' => 'Karantina / Hold (Inspeksi QC)',
    ];
    $statusTag = ($type === 'in') ? "[Status: " . ($statusLabels[$stockStatus] ?? 'Aktif') . "]" : "";
    $locTag    = "[Lokasi: $location]";
    $prefixNotes = trim("$locTag $statusTag");
    $notesWithLoc = $notes !== '' ? "$prefixNotes $notes" : $prefixNotes;

    // Hitung rincian stok berdasarkan status (Aktif, Draft, Karantina)
    $stmtBreakdown = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') AND status = 'draft' THEN qty ELSE 0 END), 0) AS in_draft,
            COALESCE(SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') AND status = 'karantina' THEN qty ELSE 0 END), 0) AS in_quarantine,
            COALESCE(SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') AND (status = 'aktif' OR status IS NULL OR status = '') THEN qty ELSE 0 END), 0) AS in_active,
            COALESCE(SUM(CASE WHEN type IN ('PURCHASE_RETURN', 'SALE', 'ADJUSTMENT_OUT') THEN qty ELSE 0 END), 0) AS out_total
        FROM stock_movements
        WHERE product_id = ?
    ");
    $stmtBreakdown->execute([$productId]);
    $bRow = $stmtBreakdown->fetch(PDO::FETCH_ASSOC);
    $inDraft = (float)$bRow['in_draft'];
    $inQuarantine = (float)$bRow['in_quarantine'];
    $inActive = (float)$bRow['in_active'];
    $outTotal = (float)$bRow['out_total'];
    $availableActive = max(0, $inActive - $outTotal);

    if ($type === 'out') {
        if ($availableActive <= 0) {
            $pdo->rollBack();
            sendJsonResponse([
                'status'  => 'error',
                'message' => sprintf(
                    'Tidak ada stok aktif yang tersedia untuk dikeluarkan! (Tersedia %s %s tertahan di status Draft dan %s %s dalam Karantina).',
                    fmtQty($inDraft), $product['unit'],
                    fmtQty($inQuarantine), $product['unit']
                ),
            ], 409);
        }
        if ((float)$quantity > $availableActive) {
            $pdo->rollBack();
            sendJsonResponse([
                'status'  => 'error',
                'message' => sprintf(
                    'Stok aktif tidak mencukupi! Hanya tersedia %s %s stok aktif yang dapat dikeluarkan. (Saat ini terdapat %s %s tertahan di status Draft dan %s %s dalam Karantina).',
                    fmtQty($availableActive), $product['unit'],
                    fmtQty($inDraft), $product['unit'],
                    fmtQty($inQuarantine), $product['unit']
                ),
            ], 409);
        }
    }

    if ($supplierId !== null) {
        $supplierStmt = $pdo->prepare(
            'SELECT 1 FROM suppliers WHERE supplier_id = ? AND is_active = 1 AND deleted_at IS NULL'
        );
        $supplierStmt->execute([$supplierId]);
        if (!$supplierStmt->fetchColumn()) {
            $pdo->rollBack();
            sendJsonResponse(['status' => 'error', 'message' => 'Supplier tidak ditemukan atau tidak aktif.'], 400);
        }
    }

    // Auto-generate referensi jika kosong
    // Stok masuk â†’ SJ-YYYYMMDDHHiiss-XXXX, Stok keluar â†’ SPK-YYYYMMDDHHiiss-XXXX
    $prefix    = $type === 'in' ? 'SJ' : 'SPK';
    $reference = $type === 'in'
        ? ($noSuratJalan !== '' ? $noSuratJalan : sprintf('%s-%s-%s', $prefix, date('Ymd'), bin2hex(random_bytes(3))))
        : ($noSpk        !== '' ? $noSpk        : sprintf('%s-%s-%s', $prefix, date('Ymd'), bin2hex(random_bytes(3))));
    $movementType = $type === 'in' ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
    $stmt = $pdo->prepare(
        'INSERT INTO stock_movements
            (product_id, type, qty, reference_no, status, supplier_id, created_by, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $productId,
        $movementType,
        $quantity,
        $reference,
        $type === 'in' ? $stockStatus : 'aktif',
        $supplierId,
        (int)$_SESSION['user_id'],
        $notesWithLoc,
    ]);

    // Catatan: master lokasi rak dan status master produk tetap utuh (tidak diubah oleh mutasi stok).
    // Mutasi stok fisik otomatis dihitung oleh trigger database dan query breakdown pergerakan.
    $stockStmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ?');
    $stockStmt->execute([$productId]);
    $rawStock = (float)$stockStmt->fetchColumn();
    $newStock = $rawStock == floor($rawStock) ? (int)$rawStock : $rawStock;
    $pdo->commit();

    $statusDesc = '';
    if ($type === 'in') {
        if ($stockStatus === 'aktif') {
            $statusDesc = ' [Status: Aktif / Siap Edar]';
        } elseif ($stockStatus === 'draft') {
            $statusDesc = ' [Status: Draft / Belum Dipublikasikan]';
        } else {
            $statusDesc = ' [Status: Karantina / Inspeksi QC]';
        }
    }

    sendJsonResponse([
        'status'       => 'success',
        'message'      => sprintf(
            'Stok %s berhasil dicatat. Stok %s sekarang %s %s%s.',
            $type === 'in' ? 'masuk' : 'keluar',
            $product['product_name'],
            $newStock,
            $product['unit'],
            $statusDesc
        ),
        'ref_number'   => $reference,
        'new_stock'    => $newStock,
        'stock_status' => $stockStatus,
    ], 201);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    inventoryJsonError($error, 'Recording stock movement');
}
