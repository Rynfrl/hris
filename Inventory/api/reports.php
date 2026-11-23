<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

try {
    $pdo = getDbConnection();

    // 1. Ambil Parameter Filter
    $startDateInput = trim((string)($_GET['start_date'] ?? ''));
    $endDateInput = trim((string)($_GET['end_date'] ?? ''));
    $categoryId = trim((string)($_GET['category_id'] ?? 'all'));
    $scope = strtolower(trim((string)($_GET['scope'] ?? ($_GET['movement_type'] ?? 'all'))));
    $sortBy = strtolower(trim((string)($_GET['sort_by'] ?? 'out_desc')));
    $search = trim((string)($_GET['search'] ?? ''));
    $singleProductId = isset($_GET['product_id']) && ctype_digit((string)$_GET['product_id']) ? (int)$_GET['product_id'] : null;

    // Validasi Format Tanggal YYYY-MM-DD jika diisi
    $hasStart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDateInput) === 1;
    $hasEnd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDateInput) === 1;

    if ($hasStart && $hasEnd && $startDateInput > $endDateInput) {
        $temp = $startDateInput;
        $startDateInput = $endDateInput;
        $endDateInput = $temp;
    }

    if ($hasStart && $hasEnd) {
        $startDateTime = $startDateInput . ' 00:00:00';
        $endDateTime = $endDateInput . ' 23:59:59';
        $formattedRange = date('d/m/Y', strtotime($startDateInput)) . ' – ' . date('d/m/Y', strtotime($endDateInput));
    } elseif ($hasStart) {
        $startDateTime = $startDateInput . ' 00:00:00';
        $endDateTime = date('Y-m-d 23:59:59');
        $formattedRange = date('d/m/Y', strtotime($startDateInput)) . ' – Hari Ini (' . date('d/m/Y') . ')';
    } elseif ($hasEnd) {
        $startDateTime = '1970-01-01 00:00:00';
        $endDateTime = $endDateInput . ' 23:59:59';
        $formattedRange = 'Hingga ' . date('d/m/Y', strtotime($endDateInput));
    } else {
        $startDateTime = '1970-01-01 00:00:00';
        $endDateTime = date('Y-m-d 23:59:59');
        $formattedRange = 'Keseluruhan Periode (s/d ' . date('d/m/Y') . ')';
    }

    // 2. Jika Request Khusus: Kartu Stok Produk Tunggal (Modal Detail)
    if ($singleProductId !== null) {
        $productStmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.product_id = ? AND p.deleted_at IS NULL
        ");
        $productStmt->execute([$singleProductId]);
        $prod = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
        }

        // Stok Awal sebelum periode
        $beforeStmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN qty ELSE 0 END) AS in_qty,
                SUM(CASE WHEN type IN ('SALE', 'PURCHASE_RETURN', 'ADJUSTMENT_OUT') THEN qty ELSE 0 END) AS out_qty
            FROM stock_movements
            WHERE product_id = ? AND created_at < ? AND status NOT IN ('deleted')
        ");
        $beforeStmt->execute([$singleProductId, $startDateTime]);
        $beforeRow = $beforeStmt->fetch(PDO::FETCH_ASSOC);
        $initialStock = max(0, (float)($beforeRow['in_qty'] ?? 0) - (float)($beforeRow['out_qty'] ?? 0));

        // Rincian mutasi dalam periode
        $movementsStmt = $pdo->prepare("
            SELECT 
                sm.stock_movement_id,
                sm.created_at,
                sm.reference_no,
                sm.type AS raw_type,
                IF(sm.status = 'transfer' OR sm.reference_no LIKE 'TRF-%' OR sm.notes LIKE '%[TRANSFER STATUS%', 'TRANSFER', IF(sm.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT')) AS movement_type,
                sm.qty,
                sm.status AS stock_status,
                sm.notes,
                COALESCE(s.supplier_name, c.name, 'Internal / Gudang') AS partner_name,
                COALESCE(u.name, 'Petugas Gudang') AS operator_name
            FROM stock_movements sm
            LEFT JOIN suppliers s ON s.supplier_id = sm.supplier_id
            LEFT JOIN customers c ON c.id = sm.customer_id
            LEFT JOIN users u ON u.id = sm.created_by
            WHERE sm.product_id = ? AND sm.created_at >= ? AND sm.created_at <= ? AND sm.status NOT IN ('deleted')
            ORDER BY sm.created_at ASC, sm.stock_movement_id ASC
        ");
        $movementsStmt->execute([$singleProductId, $startDateTime, $endDateTime]);
        $rawMovements = $movementsStmt->fetchAll(PDO::FETCH_ASSOC);

        $runningBalance = $initialStock;
        $ledgerMovements = [];
        $totalIn = 0;
        $totalOut = 0;

        foreach ($rawMovements as $mv) {
            $qty = (float)$mv['qty'];
            if ($mv['movement_type'] === 'IN') {
                $runningBalance += $qty;
                $totalIn += $qty;
            } elseif ($mv['movement_type'] === 'OUT') {
                $runningBalance = max(0, $runningBalance - $qty);
                $totalOut += $qty;
            }

            $ledgerMovements[] = [
                'id'            => (int)$mv['stock_movement_id'],
                'date'          => date('d/m/Y H:i', strtotime($mv['created_at'])),
                'ref_no'        => $mv['reference_no'] ?: '-',
                'type'          => $mv['movement_type'],
                'raw_type'      => $mv['raw_type'],
                'qty'           => fmtQty($qty),
                'balance'       => fmtQty($runningBalance),
                'partner_name'  => $mv['partner_name'],
                'operator_name' => $mv['operator_name'],
                'notes'         => $mv['notes'] ?: '-'
            ];
        }

        sendJsonResponse([
            'status' => 'success',
            'data'   => [
                'product' => [
                    'product_id'    => (int)$prod['product_id'],
                    'sku'           => $prod['sku'],
                    'product_name'  => $prod['product_name'],
                    'category_name' => $prod['category_name'] ?: 'Tanpa Kategori',
                    'unit'          => $prod['unit'],
                    'location'      => $prod['location'] ?: 'Gudang Utama',
                    'purchase_price'=> (float)$prod['purchase_price'],
                    'selling_price' => (float)$prod['selling_price'],
                    'minimum_stock' => fmtQty($prod['minimum_stock']),
                ],
                'period' => [
                    'start_date'      => $startDateInput,
                    'end_date'        => $endDateInput,
                    'formatted_range' => $formattedRange
                ],
                'initial_stock' => fmtQty($initialStock),
                'total_in'      => fmtQty($totalIn),
                'total_out'     => fmtQty($totalOut),
                'final_stock'   => fmtQty($runningBalance),
                'movements'     => $ledgerMovements
            ]
        ]);
        exit;
    }

    // 3. Ambil Daftar Kategori untuk Filter Dropdown
    $categoriesStmt = $pdo->query("SELECT id, name, code FROM categories WHERE deleted_at IS NULL ORDER BY name ASC");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Query Mutasi Stok per Produk (Stok Awal, In, Out, Akhir)
    $productParams = [
        'start_before' => $startDateTime,
        'start_period' => $startDateTime,
        'end_period'   => $endDateTime
    ];

    $whereProductConditions = ["p.deleted_at IS NULL"];

    if ($categoryId !== 'all' && ctype_digit($categoryId)) {
        $whereProductConditions[] = "p.category_id = :category_id";
        $productParams['category_id'] = (int)$categoryId;
    }

    if ($search !== '') {
        $whereProductConditions[] = "(p.sku LIKE :search OR p.product_name LIKE :search)";
        $productParams['search'] = '%' . $search . '%';
    }

    $whereProductSql = implode(' AND ', $whereProductConditions);

    // Subquery status fisik stok saat ini (Aktif, Karantina, Draft)
    $smSubquery = "LEFT JOIN (
        SELECT sm.product_id,
               SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND sm.status = 'draft' THEN sm.qty ELSE 0 END) AS in_draft,
               SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND (sm.status = 'karantina' OR grd_sm.condition_note IN ('Damaged', 'Shortage')) THEN sm.qty ELSE 0 END) AS in_quarantine,
               SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND (sm.status = 'aktif' OR sm.status IS NULL OR sm.status = '') AND (grd_sm.condition_note IS NULL OR grd_sm.condition_note = 'Good') THEN sm.qty ELSE 0 END) AS in_active,
               SUM(CASE WHEN sm.type = 'PURCHASE_RETURN' AND (prt_sm.condition IN ('Defective', 'Shortage') OR grd_ret_sm.condition_note IN ('Damaged', 'Shortage')) THEN sm.qty ELSE 0 END) AS out_quarantine,
               SUM(CASE WHEN sm.type IN ('SALE', 'ADJUSTMENT_OUT') OR (sm.type = 'PURCHASE_RETURN' AND (prt_sm.condition IS NULL OR prt_sm.condition NOT IN ('Defective', 'Shortage')) AND (grd_ret_sm.condition_note IS NULL OR grd_ret_sm.condition_note NOT IN ('Damaged', 'Shortage'))) THEN sm.qty ELSE 0 END) AS out_active
        FROM stock_movements sm
        LEFT JOIN goods_receipts gr_sm ON gr_sm.grn_no = sm.reference_no
        LEFT JOIN goods_receipt_details grd_sm ON grd_sm.goods_receipt_id = gr_sm.goods_receipt_id AND grd_sm.product_id = sm.product_id
        LEFT JOIN purchase_returns prt_sm ON prt_sm.return_no = sm.reference_no
        LEFT JOIN goods_receipts gr_ret_sm ON gr_ret_sm.goods_receipt_id = prt_sm.goods_receipt_id
        LEFT JOIN goods_receipt_details grd_ret_sm ON grd_ret_sm.goods_receipt_id = gr_ret_sm.goods_receipt_id AND grd_ret_sm.product_id = sm.product_id
        GROUP BY sm.product_id
    ) sm_sum ON sm_sum.product_id = p.product_id";

    $sql = "
        SELECT 
            p.product_id,
            p.sku,
            p.product_name,
            p.unit,
            p.purchase_price,
            p.selling_price,
            p.minimum_stock,
            p.location,
            c.id AS category_id,
            COALESCE(c.name, 'Tanpa Kategori') AS category_name,
            COALESCE(before_m.in_qty, 0) - COALESCE(before_m.out_qty, 0) AS initial_stock_raw,
            COALESCE(period_m.in_qty, 0) AS in_qty_raw,
            COALESCE(period_m.out_qty, 0) AS out_qty_raw,
            COALESCE(sm_sum.in_draft, 0) AS cur_draft,
            GREATEST(0, COALESCE(sm_sum.in_quarantine, 0) - COALESCE(sm_sum.out_quarantine, 0)) AS cur_quarantine,
            GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) AS cur_active
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        {$smSubquery}
        LEFT JOIN (
            SELECT product_id,
                   SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN qty ELSE 0 END) AS in_qty,
                   SUM(CASE WHEN type IN ('SALE', 'PURCHASE_RETURN', 'ADJUSTMENT_OUT') THEN qty ELSE 0 END) AS out_qty
            FROM stock_movements
            WHERE created_at < :start_before AND status NOT IN ('deleted')
            GROUP BY product_id
        ) before_m ON before_m.product_id = p.product_id
        LEFT JOIN (
            SELECT product_id,
                   SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN qty ELSE 0 END) AS in_qty,
                   SUM(CASE WHEN type IN ('SALE', 'PURCHASE_RETURN', 'ADJUSTMENT_OUT') THEN qty ELSE 0 END) AS out_qty
            FROM stock_movements
            WHERE created_at >= :start_period AND created_at <= :end_period AND status NOT IN ('deleted')
            GROUP BY product_id
        ) period_m ON period_m.product_id = p.product_id
        WHERE {$whereProductSql}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($productParams);
    $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Olah Data Produk & Hitung Metrik
    $items = [];
    $totalInitial = 0;
    $totalInQty = 0;
    $totalInVal = 0;
    $totalOutQty = 0;
    $totalOutVal = 0;
    $totalFinalQty = 0;
    $totalFinalVal = 0;
    $criticalCount = 0;

    $totalCurActive = 0;
    $totalCurQuarantine = 0;
    $totalCurDraft = 0;

    $categoryStats = [];
    $criticalProducts = [];
    $dispatchedProducts = [];

    foreach ($rawItems as $row) {
        $initialStock = max(0, (float)$row['initial_stock_raw']);
        $inQty = (float)$row['in_qty_raw'];
        $outQty = (float)$row['out_qty_raw'];
        $finalStock = max(0, $initialStock + $inQty - $outQty);
        $minStock = (float)$row['minimum_stock'];
        $purchasePrice = (float)$row['purchase_price'];
        $sellingPrice = (float)$row['selling_price'];

        $inVal = $inQty * $purchasePrice;
        $outVal = $outQty * $purchasePrice;
        $finalVal = $finalStock * $purchasePrice;

        // Status Stok
        if ($finalStock <= 0) {
            $statusStock = 'Habis';
            $statusCode = 'out';
        } elseif ($finalStock <= $minStock) {
            $statusStock = 'Menipis';
            $statusCode = 'low';
        } else {
            $statusStock = 'Aman';
            $statusCode = 'safe';
        }

        // Filter Cakupan (Scope / Movement Type)
        if ($scope === 'active' && $inQty <= 0 && $outQty <= 0) {
            continue;
        }
        if ($scope === 'in' && $inQty <= 0) {
            continue;
        }
        if ($scope === 'out' && $outQty <= 0) {
            continue;
        }
        if ($scope === 'critical' && $statusCode === 'safe') {
            continue;
        }

        $formattedItem = [
            'product_id'     => (int)$row['product_id'],
            'sku'            => $row['sku'],
            'product_name'   => $row['product_name'],
            'category_id'    => (int)$row['category_id'],
            'category_name'  => $row['category_name'],
            'unit'           => $row['unit'],
            'location'       => $row['location'] ?: 'Gudang Utama',
            'purchase_price' => $purchasePrice,
            'selling_price'  => $sellingPrice,
            'minimum_stock'  => fmtQty($minStock),
            'initial_stock'  => fmtQty($initialStock),
            'in_qty'         => fmtQty($inQty),
            'out_qty'        => fmtQty($outQty),
            'final_stock'    => fmtQty($finalStock),
            'status'         => $statusStock,
            'status_code'    => $statusCode,
            'in_val'         => $inVal,
            'out_val'        => $outVal,
            'final_val'      => $finalVal,
            'cur_active'     => fmtQty($row['cur_active']),
            'cur_quarantine' => fmtQty($row['cur_quarantine']),
            'cur_draft'      => fmtQty($row['cur_draft'])
        ];

        $items[] = $formattedItem;

        // Akumulasi Totals
        $totalInitial += $initialStock;
        $totalInQty += $inQty;
        $totalInVal += $inVal;
        $totalOutQty += $outQty;
        $totalOutVal += $outVal;
        $totalFinalQty += $finalStock;
        $totalFinalVal += $finalVal;

        $totalCurActive += (float)$row['cur_active'];
        $totalCurQuarantine += (float)$row['cur_quarantine'];
        $totalCurDraft += (float)$row['cur_draft'];

        // Akumulasi Kategori
        $catName = $row['category_name'] ?: 'Tanpa Kategori';
        if (!isset($categoryStats[$catName])) {
            $categoryStats[$catName] = [
                'name'          => $catName,
                'count'         => 0,
                'stock_qty'     => 0,
                'valuation'     => 0,
                'potential_sale'=> 0
            ];
        }
        $categoryStats[$catName]['count']++;
        $categoryStats[$catName]['stock_qty'] += $finalStock;
        $categoryStats[$catName]['valuation'] += $finalVal;
        $categoryStats[$catName]['potential_sale'] += ($finalStock * $sellingPrice);

        if ($statusCode !== 'safe') {
            $criticalCount++;
            $criticalProducts[] = [
                'sku'           => $row['sku'],
                'product_name'  => $row['product_name'],
                'category_name' => $row['category_name'],
                'final_stock'   => fmtQty($finalStock),
                'minimum_stock' => fmtQty($minStock),
                'unit'          => $row['unit'],
                'status'        => $statusStock,
                'status_code'   => $statusCode
            ];
        }

        if ($outQty > 0) {
            $dispatchedProducts[] = [
                'sku'           => $row['sku'],
                'product_name'  => $row['product_name'],
                'category_name' => $row['category_name'],
                'out_qty'       => fmtQty($outQty),
                'unit'          => $row['unit'],
                'out_val'       => $outVal
            ];
        }
    }

    // 6. Urutkan Data (Sorting) Berdasarkan Pilihan Pengguna
    usort($items, static function ($a, $b) use ($sortBy) {
        return match ($sortBy) {
            'out_desc'   => $b['out_qty'] <=> $a['out_qty'] ?: $b['final_val'] <=> $a['final_val'],
            'in_desc'    => $b['in_qty'] <=> $a['in_qty'],
            'stock_desc' => $b['final_stock'] <=> $a['final_stock'],
            'stock_asc'  => $a['final_stock'] <=> $b['final_stock'],
            'val_desc'   => $b['final_val'] <=> $a['final_val'],
            'name_asc'   => strcasecmp($a['product_name'], $b['product_name']),
            'sku_asc'    => strcasecmp($a['sku'], $b['sku']),
            default      => $b['out_qty'] <=> $a['out_qty']
        };
    });

    // Top Dispatched Products (Terlaris)
    usort($dispatchedProducts, static fn($a, $b) => $b['out_qty'] <=> $a['out_qty']);
    $topProducts = array_slice($dispatchedProducts, 0, 5);
    foreach ($topProducts as &$tp) {
        $tp['percentage'] = $totalOutQty > 0 ? round(($tp['out_qty'] / $totalOutQty) * 100, 1) : 0;
    }
    unset($tp);

    // Olah Statistik Kategori
    $categoryAnalysis = [];
    foreach ($categoryStats as $cName => $cs) {
        $pct = $totalFinalVal > 0 ? round(($cs['valuation'] / $totalFinalVal) * 100, 1) : 0;
        $categoryAnalysis[] = [
            'name'           => $cName,
            'count'          => $cs['count'],
            'stock_qty'      => fmtQty($cs['stock_qty']),
            'valuation'      => $cs['valuation'],
            'potential_sale' => $cs['potential_sale'],
            'percentage'     => $pct
        ];
    }
    usort($categoryAnalysis, static fn($a, $b) => $b['valuation'] <=> $a['valuation']);

    // 7. Query Tren Harian untuk Grafik Mutasi Stok
    $trendParams = [
        'start_period' => $startDateTime,
        'end_period'   => $endDateTime
    ];
    $trendCategorySql = '';
    if ($categoryId !== 'all' && ctype_digit($categoryId)) {
        $trendCategorySql = "AND p.category_id = :trend_category_id";
        $trendParams['trend_category_id'] = (int)$categoryId;
    }

    $trendSql = "
        SELECT 
            DATE(sm.created_at) AS date_str,
            SUM(CASE WHEN sm.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN sm.qty ELSE 0 END) AS in_qty,
            SUM(CASE WHEN sm.type IN ('SALE', 'PURCHASE_RETURN', 'ADJUSTMENT_OUT') THEN sm.qty ELSE 0 END) AS out_qty
        FROM stock_movements sm
        JOIN products p ON p.product_id = sm.product_id
        WHERE sm.created_at >= :start_period AND sm.created_at <= :end_period
          AND sm.status NOT IN ('deleted')
          AND p.deleted_at IS NULL
          {$trendCategorySql}
        GROUP BY DATE(sm.created_at)
        ORDER BY DATE(sm.created_at) ASC
    ";
    $trendStmt = $pdo->prepare($trendSql);
    $trendStmt->execute($trendParams);
    $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    $chartLabels = [];
    $chartInData = [];
    $chartOutData = [];
    foreach ($trendRows as $tr) {
        $chartLabels[] = date('d M', strtotime($tr['date_str']));
        $chartInData[] = fmtQty($tr['in_qty']);
        $chartOutData[] = fmtQty($tr['out_qty']);
    }

    // 8. Query Jurnal Transaksi Rinci (Log Mutasi Masuk/Keluar) pada Periode Terpilih
    $journalParams = [
        'start_period' => $startDateTime,
        'end_period'   => $endDateTime
    ];
    $journalConditions = [
        "sm.created_at >= :start_period",
        "sm.created_at <= :end_period",
        "sm.status NOT IN ('deleted')",
        "p.deleted_at IS NULL"
    ];

    if ($categoryId !== 'all' && ctype_digit($categoryId)) {
        $journalConditions[] = "p.category_id = :j_cat_id";
        $journalParams['j_cat_id'] = (int)$categoryId;
    }

    if ($search !== '') {
        $journalConditions[] = "(p.sku LIKE :j_search OR p.product_name LIKE :j_search OR sm.reference_no LIKE :j_search)";
        $journalParams['j_search'] = '%' . $search . '%';
    }

    $journalWhereSql = implode(' AND ', $journalConditions);

    $journalSql = "
        SELECT 
            sm.stock_movement_id,
            sm.created_at,
            sm.reference_no,
            sm.type AS raw_type,
            IF(sm.status = 'transfer' OR sm.reference_no LIKE 'TRF-%' OR sm.notes LIKE '%[TRANSFER STATUS%', 'TRANSFER', IF(sm.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT')) AS movement_type,
            sm.qty,
            sm.status AS stock_status,
            sm.notes,
            p.sku,
            p.product_name,
            p.unit,
            p.purchase_price,
            COALESCE(s.supplier_name, c.name, 'Internal / Gudang') AS partner_name,
            COALESCE(u.name, 'Petugas Gudang') AS operator_name
        FROM stock_movements sm
        JOIN products p ON p.product_id = sm.product_id
        LEFT JOIN suppliers s ON s.supplier_id = sm.supplier_id
        LEFT JOIN customers c ON c.id = sm.customer_id
        LEFT JOIN users u ON u.id = sm.created_by
        WHERE {$journalWhereSql}
        ORDER BY sm.created_at DESC, sm.stock_movement_id DESC
        LIMIT 200
    ";
    $journalStmt = $pdo->prepare($journalSql);
    $journalStmt->execute($journalParams);
    $rawJournal = $journalStmt->fetchAll(PDO::FETCH_ASSOC);

    $journalMovements = [];
    foreach ($rawJournal as $jm) {
        $qty = (float)$jm['qty'];
        $val = $qty * (float)$jm['purchase_price'];

        $journalMovements[] = [
            'id'            => (int)$jm['stock_movement_id'],
            'date'          => date('d/m/Y H:i', strtotime($jm['created_at'])),
            'ref_no'        => $jm['reference_no'] ?: '-',
            'type'          => $jm['movement_type'],
            'raw_type'      => $jm['raw_type'],
            'sku'           => $jm['sku'],
            'product_name'  => $jm['product_name'],
            'unit'          => $jm['unit'],
            'qty'           => fmtQty($qty),
            'value'         => $val,
            'partner_name'  => $jm['partner_name'],
            'operator_name' => $jm['operator_name'],
            'notes'         => $jm['notes'] ?: '-'
        ];
    }

    // 9. Ambil Identitas Perusahaan dari Pengaturan
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settingsMap = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $company = [
        'name'    => $settingsMap['company_name'] ?? 'PT Inventoria Solusi Nusantara',
        'npwp'    => $settingsMap['company_npwp'] ?? '01.234.567.8-901.000',
        'email'   => $settingsMap['company_email'] ?? 'inventory@inventoria.id',
        'phone'   => $settingsMap['company_phone'] ?? '(021) 555-0192',
        'address' => $settingsMap['company_address'] ?? 'Kawasan Industri Surya Cipta Kav. 18, Karawang Timur, Jawa Barat 41361',
    ];

    // 10. Kirim Respon Lengkap
    sendJsonResponse([
        'status' => 'success',
        'data'   => [
            'company' => $company,
            'period'  => [
                'start_date'      => $startDateInput,
                'end_date'        => $endDateInput,
                'formatted_range' => $formattedRange
            ],
            'filters' => [
                'category_id' => $categoryId,
                'scope'       => $scope,
                'sort_by'     => $sortBy,
                'search'      => $search
            ],
            'categories' => $categories,
            'kpi' => [
                'total_initial_stock' => fmtQty($totalInitial),
                'total_in_qty'        => fmtQty($totalInQty),
                'total_in_val'        => $totalInVal,
                'total_out_qty'       => fmtQty($totalOutQty),
                'total_out_val'       => $totalOutVal,
                'final_stock_qty'     => fmtQty($totalFinalQty),
                'final_stock_val'     => $totalFinalVal,
                'critical_count'      => $criticalCount,
                'cur_active'          => fmtQty($totalCurActive),
                'cur_quarantine'      => fmtQty($totalCurQuarantine),
                'cur_draft'           => fmtQty($totalCurDraft),
                'total_transactions'  => count($journalMovements)
            ],
            'chart_trend' => [
                'labels'   => $chartLabels,
                'in_data'  => $chartInData,
                'out_data' => $chartOutData
            ],
            'top_products'      => $topProducts,
            'critical_products' => array_slice($criticalProducts, 0, 10),
            'category_analysis' => $categoryAnalysis,
            'items'             => $items,
            'journal_movements' => $journalMovements,
            'totals' => [
                'initial_stock' => fmtQty($totalInitial),
                'in_qty'        => fmtQty($totalInQty),
                'out_qty'       => fmtQty($totalOutQty),
                'final_stock'   => fmtQty($totalFinalQty),
                'final_val'     => $totalFinalVal
            ]
        ]
    ]);

} catch (Throwable $e) {
    inventoryJsonError($e, 'Laporan Inventory');
}
