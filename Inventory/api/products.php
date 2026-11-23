<?php

require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    sendJsonResponse(['status' => 'ok']);
}

inventoryRequireApiAccess(false);

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $select = "SELECT p.product_id AS id, p.product_id, p.sku, p.product_name AS name,
                          p.product_name, p.description, p.category_id, p.unit,
                          p.purchase_price, p.selling_price,
                          GREATEST(0, (GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0))) + (GREATEST(0, COALESCE(sm_sum.in_quarantine, 0) - COALESCE(sm_sum.out_quarantine, 0))) + COALESCE(sm_sum.in_draft, 0)) AS current_stock,
                          GREATEST(0, (GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0))) + (GREATEST(0, COALESCE(sm_sum.in_quarantine, 0) - COALESCE(sm_sum.out_quarantine, 0))) + COALESCE(sm_sum.in_draft, 0)) AS stock,
                          p.minimum_stock, p.location, p.is_active,
                          p.created_at, p.updated_at,
                          COALESCE(
                              p.updated_at,
                              (SELECT MAX(created_at) FROM stock_movements WHERE product_id = p.product_id),
                              p.created_at
                          ) AS last_updated_at,
                          CASE WHEN GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) <= 0 THEN 'out'
                               WHEN GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) <= p.minimum_stock THEN 'low'
                               ELSE 'available' END AS status,
                          c.name AS category_name, c.code AS category_code,
                          COALESCE(sm_sum.in_draft, 0) AS stock_draft,
                          GREATEST(0, COALESCE(sm_sum.in_quarantine, 0) - COALESCE(sm_sum.out_quarantine, 0)) AS stock_quarantine,
                          GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) AS stock_active
                   FROM products p
                   LEFT JOIN categories c ON c.id = p.category_id
                   LEFT JOIN (
                       SELECT sm.product_id,
                              SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND sm.status = 'draft' THEN sm.qty ELSE 0 END) AS in_draft,
                              SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND (sm.status = 'karantina' OR grd_sm.condition_note IN ('Damaged', 'Shortage')) THEN sm.qty ELSE 0 END) AS in_quarantine,
                              SUM(CASE WHEN sm.type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') AND (sm.status = 'aktif' OR sm.status IS NULL OR sm.status = '') AND (grd_sm.condition_note IS NULL OR grd_sm.condition_note = 'Good') THEN sm.qty ELSE 0 END) AS in_active,
                              SUM(CASE WHEN sm.type = 'PURCHASE_RETURN' AND (prt_sm.condition IN ('Defective', 'Shortage') OR grd_ret_sm.condition_note IN ('Damaged', 'Shortage')) THEN sm.qty ELSE 0 END) AS out_quarantine,
                              SUM(CASE WHEN sm.type IN ('SALE', 'ADJUSTMENT_OUT') OR (sm.type = 'PURCHASE_RETURN' AND (prt_sm.condition IS NULL OR prt_sm.condition NOT IN ('Defective', 'Shortage')) AND (grd_ret_sm.condition_note IS NULL OR grd_ret_sm.condition_note NOT IN ('Damaged', 'Shortage'))) THEN sm.qty ELSE 0 END) AS out_active,
                              SUM(CASE WHEN sm.type IN ('PURCHASE_RETURN','SALE','ADJUSTMENT_OUT') THEN sm.qty ELSE 0 END) AS out_total
                       FROM stock_movements sm
                       LEFT JOIN goods_receipts gr_sm ON gr_sm.grn_no = sm.reference_no
                       LEFT JOIN goods_receipt_details grd_sm ON grd_sm.goods_receipt_id = gr_sm.goods_receipt_id AND grd_sm.product_id = sm.product_id
                       LEFT JOIN purchase_returns prt_sm ON prt_sm.return_no = sm.reference_no
                       LEFT JOIN goods_receipts gr_ret_sm ON gr_ret_sm.goods_receipt_id = prt_sm.goods_receipt_id
                       LEFT JOIN goods_receipt_details grd_ret_sm ON grd_ret_sm.goods_receipt_id = gr_ret_sm.goods_receipt_id AND grd_ret_sm.product_id = sm.product_id
                       GROUP BY sm.product_id
                   ) sm_sum ON sm_sum.product_id = p.product_id";

        if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
            $stmt = $pdo->prepare(
                "$select
                 WHERE p.product_id = ? AND p.deleted_at IS NULL"
            );
            $stmt->execute([(int)$_GET['id']]);
            $product = $stmt->fetch();
            if (!$product) {
                sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
            }

            $history = $pdo->prepare(
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
                    WHERE m.product_id = ?
                )
                SELECT * FROM (
                    SELECT m.stock_movement_id,
                           IF(m.status = 'transfer' OR m.reference_no LIKE 'TRF-%' OR m.notes LIKE '%[TRANSFER STATUS%', 'TRANSFER', IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT')) AS type,
                           m.type AS raw_type,
                           m.created_at AS date,
                           m.reference_no AS ref_no,
                           m.qty AS quantity,
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
                           COALESCE(
                               NULLIF(m.notes, ''),
                               NULLIF(prt.reason, ''),
                               NULLIF(so.notes, ''),
                               NULLIF(gr.notes, ''),
                               NULLIF(pt.notes, ''),
                               NULLIF(pr.notes, ''),
                               CONCAT('Penerimaan PO #', COALESCE(pt.transaction_no, pt_ret.transaction_no, '-'))
                           ) AS notes,
                           m.balance_stock,
                           COALESCE(grd.condition_note, prt.condition) AS condition_note,
                           COALESCE(s.supplier_name, s_gr.supplier_name, s_ret.supplier_name, c_so.name, c.name, 'Internal / Gudang') AS partner_name,
                           COALESCE(pr.department, pr_ret.department) AS department,
                           COALESCE(pt.transaction_no, pt_ret.transaction_no) AS po_no,
                           COALESCE(gr.grn_no, gr_ret.grn_no) AS grn_no,
                           so.order_no AS so_no,
                           CASE WHEN m.supplier_id IS NOT NULL OR s_gr.supplier_id IS NOT NULL OR s_ret.supplier_id IS NOT NULL THEN 'Supplier'
                                WHEN m.customer_id IS NOT NULL OR c_so.id IS NOT NULL THEN 'Customer'
                                ELSE 'Internal' END AS partner_type,
                           COALESCE(
                IF(u.name IS NOT NULL, CONCAT(u.name, ' (', CASE u.role
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
                END, ')'), NULL),
                IF(u_so.name IS NOT NULL, CONCAT(u_so.name, ' (', CASE u_so.role
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
                END, ')'), NULL),
                IF(u_prt.name IS NOT NULL, CONCAT(u_prt.name, ' (', CASE u_prt.role
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
                END, ')'), NULL),
                IF(u_gr.name IS NOT NULL, CONCAT(u_gr.name, ' (', CASE u_gr.role
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
                END, ')'), NULL),
                IF(u_po.name IS NOT NULL, CONCAT(u_po.name, ' (', CASE u_po.role
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
                END, ')'), NULL),
                'Petugas Gudang'
            ) AS performed_by_name
                    FROM all_stock_movements m
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
                    WHERE m.reference_no NOT LIKE 'LOT-SPLIT-%'
                ) history_sub
                ORDER BY date DESC, stock_movement_id DESC
                LIMIT 50"
            );
            $history->execute([(int)$_GET['id']]);
            $product['stock_history'] = $history->fetchAll();
            // Format qty & balance di history
            foreach ($product['stock_history'] as &$h) {
                if ($h['stock_status'] === 'transfer' || strpos($h['notes'] ?? '', '[TRANSFER STATUS') !== false) {
                    $h['is_transfer'] = true;
                    if (preg_match('/Sebesar\s+([\d\.\,]+)/i', $h['notes'] ?? '', $matches)) {
                        $h['transfer_qty'] = (float)str_replace(',', '.', $matches[1]);
                    }
                }
                $h['quantity'] = fmtQty($h['quantity']);
                $h['balance_stock'] = fmtQty($h['balance_stock']);
            }
            unset($h);
            // Format stok produk
            $product['current_stock']    = fmtQty($product['current_stock']);
            $product['stock']            = fmtQty($product['stock']);
            $product['minimum_stock']    = fmtQty($product['minimum_stock']);
            $product['stock_active']     = fmtQty($product['stock_active']);
            $product['stock_draft']      = fmtQty($product['stock_draft']);
            $product['stock_quarantine'] = fmtQty($product['stock_quarantine']);

            // Evaluasi Kesiapan & Kelengkapan Konfigurasi Data Produk (9 Kriteria)
            $completeness = inventoryCheckProductCompleteness($product);
            $product['is_complete']     = $completeness['is_complete'];
            $product['missing_fields']   = $completeness['missing_fields'];
            $product['missing_keys']     = $completeness['missing_keys'];
            if (!$completeness['is_complete']) {
                $product['status'] = 'incomplete';
            }

            sendJsonResponse(['status' => 'success', 'data' => $product]);
        }

        $conditions = ['p.deleted_at IS NULL'];
        $params = [];
        $search = trim((string)($_GET['search'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $location = trim((string)($_GET['location'] ?? ''));
        $sort = strtolower(trim((string)($_GET['sort'] ?? '')));

        // Filter eksplisit is_active jika dipanggil dari luar (misal: is_active=1 atau include_inactive=0)
        if (isset($_GET['is_active']) && ($_GET['is_active'] === '0' || $_GET['is_active'] === '1')) {
            $conditions[] = 'p.is_active = ?';
            $params[] = (int)$_GET['is_active'];
        } elseif (isset($_GET['include_inactive']) && ($_GET['include_inactive'] === '0' || $_GET['include_inactive'] === 'false')) {
            $conditions[] = 'p.is_active = 1';
        }

        if ($search !== '') {
            $conditions[] = '(p.product_name LIKE ? OR p.sku LIKE ? OR p.location LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term);
        }
        if ($category !== '') {
            if (ctype_digit($category)) {
                $conditions[] = 'p.category_id = ?';
                $params[] = (int)$category;
            } else {
                $conditions[] = '(c.code LIKE ? OR c.name LIKE ?)';
                $term = '%' . $category . '%';
                array_push($params, $term, $term);
            }
        }
        if ($location !== '') {
            if ($location === '__none__') {
                $conditions[] = "(p.location IS NULL OR TRIM(p.location) = '')";
            } else {
                $conditions[] = 'p.location = ?';
                $params[] = $location;
            }
        }
        $isCompleteSql = "(p.product_name IS NOT NULL AND TRIM(p.product_name) <> '' AND p.sku IS NOT NULL AND TRIM(p.sku) <> '' AND p.category_id IS NOT NULL AND p.category_id > 0 AND p.unit IS NOT NULL AND TRIM(p.unit) <> '' AND p.purchase_price IS NOT NULL AND p.purchase_price > 0 AND p.selling_price IS NOT NULL AND p.selling_price > 0 AND p.location IS NOT NULL AND TRIM(p.location) <> '' AND p.minimum_stock IS NOT NULL AND p.minimum_stock > 0)";
        $isIncompleteSql = "(p.product_name IS NULL OR TRIM(p.product_name) = '' OR p.sku IS NULL OR TRIM(p.sku) = '' OR p.category_id IS NULL OR p.category_id <= 0 OR p.unit IS NULL OR TRIM(p.unit) = '' OR p.purchase_price IS NULL OR p.purchase_price <= 0 OR p.selling_price IS NULL OR p.selling_price <= 0 OR p.location IS NULL OR TRIM(p.location) = '' OR p.minimum_stock IS NULL OR p.minimum_stock <= 0)";

        if (in_array($status, ['low', 'out', 'available', 'incomplete'], true)) {
            if ($status === 'incomplete') {
                $conditions[] = $isIncompleteSql;
            } elseif ($status === 'low') {
                $conditions[] = "$isCompleteSql AND GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) > 0 AND GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) <= p.minimum_stock";
            } elseif ($status === 'out') {
                $conditions[] = "$isCompleteSql AND GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) <= 0";
            } else {
                $conditions[] = "$isCompleteSql AND GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0)) > p.minimum_stock";
            }
        }

        $orderBy = 'p.product_id ASC';
        if ($sort === 'location_asc') {
            $orderBy = "(p.location IS NULL OR TRIM(p.location) = '') ASC, p.location ASC, p.product_name ASC";
        } elseif ($sort === 'location_desc') {
            $orderBy = "(p.location IS NULL OR TRIM(p.location) = '') ASC, p.location DESC, p.product_name ASC";
        } elseif ($sort === 'name_asc') {
            $orderBy = 'p.product_name ASC';
        } elseif ($sort === 'name_desc') {
            $orderBy = 'p.product_name DESC';
        } elseif ($sort === 'stock_desc') {
            $orderBy = 'current_stock DESC, p.product_name ASC';
        } elseif ($sort === 'stock_asc') {
            $orderBy = 'current_stock ASC, p.product_name ASC';
        }

        $stmt = $pdo->prepare(
            "$select WHERE " . implode(' AND ', $conditions) . " ORDER BY $orderBy"
        );
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        // Format qty semua produk & periksa kelengkapan
        foreach ($products as &$p) {
            $p['current_stock']    = fmtQty($p['current_stock']);
            $p['stock']            = fmtQty($p['stock']);
            $p['minimum_stock']    = fmtQty($p['minimum_stock']);
            $p['stock_active']     = fmtQty($p['stock_active']);
            $p['stock_draft']      = fmtQty($p['stock_draft']);
            $p['stock_quarantine'] = fmtQty($p['stock_quarantine']);

            // Evaluasi Kesiapan & Kelengkapan Konfigurasi Data Produk
            $comp = inventoryCheckProductCompleteness($p);
            $p['is_complete']     = $comp['is_complete'];
            $p['missing_fields']   = $comp['missing_fields'];
            $p['missing_keys']     = $comp['missing_keys'];
            if (!$comp['is_complete']) {
                $p['status'] = 'incomplete';
            }
        }
        unset($p);
        $totalValuation = array_reduce(
            $products,
            static fn(float $total, array $product): float => $total
                + ((float)$product['purchase_price'] * (float)$product['current_stock']),
            0.0
        );

        sendJsonResponse([
            'status' => 'success',
            'count' => count($products),
            'total_valuation' => $totalValuation,
            'data' => $products,
        ]);
    }

    if ($method === 'POST') {
        $body = inventoryRequestBody();
        $action = trim((string)($body['action'] ?? ($_GET['action'] ?? '')));

        // ==================================================================
        // ACTION: Transfer / Alihkan Status Stok (Aktif, Draft, Karantina)
        // ==================================================================
        if ($action === 'transfer_status') {
            $userRole = strtoupper(trim((string)($_SESSION['role'] ?? '')));
            if (!in_array($userRole, ['ADMIN_WAREHOUSE', 'WAREHOUSE_STAFF', 'ADMIN'], true)) {
                sendJsonResponse([
                    'status'  => 'error',
                    'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengatur status stok. Fitur ini hanya dapat dilakukan oleh Petugas Gudang.'
                ], 403);
            }

            $productId  = (int)($body['product_id'] ?? 0);
            $fromStatus = strtolower(trim((string)($body['from_status'] ?? '')));
            $toStatus   = strtolower(trim((string)($body['to_status'] ?? '')));
            $rawQty     = $body['quantity'] ?? null;
            $quantity   = filter_var($rawQty, FILTER_VALIDATE_FLOAT);
            $userNotes  = trim((string)($body['notes'] ?? ''));

            if ($productId <= 0) {
                sendJsonResponse(['status' => 'error', 'message' => 'ID produk tidak valid.'], 400);
            }
            if (!in_array($fromStatus, ['draft', 'karantina', 'aktif'], true)) {
                sendJsonResponse(['status' => 'error', 'message' => 'Status sumber tidak valid (harus draft, karantina, atau aktif).'], 400);
            }
            if (!in_array($toStatus, ['draft', 'karantina', 'aktif'], true)) {
                sendJsonResponse(['status' => 'error', 'message' => 'Status tujuan tidak valid (harus draft, karantina, atau aktif).'], 400);
            }
            if ($fromStatus === $toStatus) {
                sendJsonResponse(['status' => 'error', 'message' => 'Status sumber dan status tujuan tidak boleh sama.'], 400);
            }
            if ($quantity === false || $quantity <= 0 || !is_finite($quantity)) {
                sendJsonResponse(['status' => 'error', 'message' => 'Jumlah barang yang ingin dipindahkan harus berupa angka lebih dari 0.'], 400);
            }

            $pdo->beginTransaction();

            // 1. Ambil data produk & lock baris
            $prodStmt = $pdo->prepare('SELECT product_id, product_name, unit, is_active FROM products WHERE product_id = ? AND deleted_at IS NULL FOR UPDATE');
            $prodStmt->execute([$productId]);
            $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
            if (!$prod) {
                $pdo->rollBack();
                sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan di database.'], 404);
            }

            // 2. Hitung ketersediaan stok fisik di masing-masing status
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

            $availableInFrom = 0;
            if ($fromStatus === 'draft') $availableInFrom = $inDraft;
            elseif ($fromStatus === 'karantina') $availableInFrom = $inQuarantine;
            elseif ($fromStatus === 'aktif') $availableInFrom = $availableActive;

            if ($quantity > $availableInFrom + 0.0001) {
                $pdo->rollBack();
                sendJsonResponse([
                    'status' => 'error',
                    'message' => sprintf(
                        'Jumlah yang diminta (%s %s) melebihi stok yang tersedia pada status %s (%s %s).',
                        fmtQty($quantity), $prod['unit'], ucfirst($fromStatus), fmtQty($availableInFrom), $prod['unit']
                    )
                ], 409);
            }

            // 3. Ambil baris pergerakan stok sumber (FIFO: created_at ASC)
            $statusCondition = ($fromStatus === 'aktif') ? "(status = 'aktif' OR status IS NULL OR status = '')" : "status = ?";
            $fetchSql = "SELECT stock_movement_id, reference_no, qty, supplier_id, notes, created_at
                         FROM stock_movements
                         WHERE product_id = ?
                           AND type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN')
                           AND $statusCondition
                           AND qty > 0
                         ORDER BY created_at ASC, stock_movement_id ASC
                         FOR UPDATE";
            $fetchStmt = $pdo->prepare($fetchSql);
            if ($fromStatus === 'aktif') {
                $fetchStmt->execute([$productId]);
            } else {
                $fetchStmt->execute([$productId, $fromStatus]);
            }
            $sourceRows = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

            $remaining = (float)$quantity;
            $statusLabels = [
                'aktif'     => 'Aktif (Siap Edar)',
                'draft'     => 'Draft (Tertahan)',
                'karantina' => 'Karantina (QC)'
            ];
            $labelFrom = $statusLabels[$fromStatus];
            $labelTo   = $statusLabels[$toStatus];
            $userName  = $_SESSION['name'] ?? 'Petugas Gudang';
            $userId    = (int)($_SESSION['user_id'] ?? 0);

            $timeNowStr = date('d M Y, H:i');

            foreach ($sourceRows as $row) {
                if ($remaining <= 0.00001) break;
                $rowQty = (float)$row['qty'];
                $rowId  = (int)$row['stock_movement_id'];

                if ($rowQty <= $remaining + 0.00001) {
                    // Seluruh qty baris ini dialihkan ke status baru
                    $logNote = sprintf('[Status dialihkan: %s â†’ %s sebesar %s %s oleh %s pada %s%s]',
                        $labelFrom, $labelTo, fmtQty($rowQty), $prod['unit'], $userName, $timeNowStr,
                        $userNotes !== '' ? ' - Alasan: ' . $userNotes : ''
                    );
                    $newNotes = trim($row['notes'] . ' | ' . $logNote);
                    $upd = $pdo->prepare('UPDATE stock_movements SET status = ?, notes = ? WHERE stock_movement_id = ?');
                    $upd->execute([$toStatus, $newNotes, $rowId]);
                    $remaining -= $rowQty;
                } else {
                    // Split baris: sisa tetap di status sumber, bagian yang dipindah dibuat baris baru
                    $keepQty = $rowQty - $remaining;
                    $moveQty = $remaining;

                    $logNoteOld = sprintf('[Sebagian stok (%s %s) dialihkan ke %s oleh %s pada %s]',
                        fmtQty($moveQty), $prod['unit'], $labelTo, $userName, $timeNowStr
                    );
                    $newNotesOld = trim($row['notes'] . ' | ' . $logNoteOld);
                    $updOld = $pdo->prepare('UPDATE stock_movements SET qty = ?, notes = ? WHERE stock_movement_id = ?');
                    $updOld->execute([$keepQty, $newNotesOld, $rowId]);

                    // Baris baru dengan status tujuan (internal split lot)
                    $newRef = sprintf('LOT-SPLIT-%s-%s', date('Ymd'), substr(bin2hex(random_bytes(4)), 0, 6));
                    $logNoteNew = sprintf('[Dialihkan dari %s (%s %s, ref asal: %s) oleh %s pada %s%s]',
                        $labelFrom, fmtQty($moveQty), $prod['unit'], $row['reference_no'], $userName, $timeNowStr,
                        $userNotes !== '' ? ' - Alasan: ' . $userNotes : ''
                    );
                    $insNew = $pdo->prepare(
                        'INSERT INTO stock_movements (product_id, type, qty, reference_no, status, supplier_id, created_by, notes, created_at)
                         VALUES (?, "ADJUSTMENT_IN", ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insNew->execute([
                        $productId,
                        $moveQty,
                        $newRef,
                        $toStatus,
                        $row['supplier_id'],
                        $userId,
                        $logNoteNew,
                        !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s')
                    ]);
                    $remaining = 0;
                    break;
                }
            }

            // 3.5 Catat transaksi transfer status secara resmi ke stock_movements (real-time)
            $transferRef = sprintf('TRF-STS-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
            $logTransferNote = sprintf(
                '[TRANSFER STATUS: %s -> %s] Sebesar %s %s | Asal: %s | Tujuan: %s | Qty: %s%s',
                $labelFrom, $labelTo, fmtQty($quantity), $prod['unit'],
                $labelFrom, $labelTo, fmtQty($quantity),
                $userNotes !== '' ? ' | Alasan: ' . $userNotes : ''
            );

            $insTransfer = $pdo->prepare(
                'INSERT INTO stock_movements (product_id, type, qty, reference_no, status, supplier_id, created_by, notes, created_at)
                 VALUES (?, "ADJUSTMENT_OUT", 0.00, ?, "transfer", NULL, ?, ?, NOW())'
            );
            $insTransfer->execute([
                $productId,
                $transferRef,
                $userId > 0 ? $userId : ($_SESSION['user_id'] ?? null),
                $logTransferNote
            ]);

            // 4. Update products: sinkronkan total stock aktual, updated_at, dan is_active
            $updProd = $pdo->prepare(
                "UPDATE products
                 SET stock = (
                     SELECT COALESCE(SUM(CASE WHEN type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN') THEN qty ELSE -qty END), 0)
                     FROM stock_movements
                     WHERE product_id = products.product_id
                 ),
                 updated_at = NOW(),
                 is_active = IF(? = 'aktif', 1, is_active)
                 WHERE product_id = ?"
            );
            $updProd->execute([$toStatus, $productId]);

            $pdo->commit();

            sendJsonResponse([
                'status'  => 'success',
                'message' => sprintf(
                    'Berhasil memindahkan %s %s dari status %s ke status %s.',
                    fmtQty($quantity), $prod['unit'], $labelFrom, $labelTo
                )
            ]);
        }

        // Penambahan produk baru hanya boleh dilakukan oleh Admin Gudang
        inventoryRequireApiAccess(true);

        $sku = trim((string)($body['sku'] ?? ''));
        $name = trim((string)($body['name'] ?? $body['product_name'] ?? ''));
        $categoryValue = trim((string)($body['category_id'] ?? ''));
        if ($categoryValue !== '' && (!ctype_digit($categoryValue) || (int)$categoryValue <= 0)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak valid.'], 400);
        }
        $categoryId = $categoryValue === '' ? null : (int)$categoryValue;
        $description = trim((string)($body['description'] ?? ''));
        $purchasePrice = filter_var($body['purchase_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sellingPrice = filter_var($body['selling_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $unit = trim((string)($body['unit'] ?? 'unit'));
        $minimumStock = filter_var($body['minimum_stock'] ?? 5, FILTER_VALIDATE_FLOAT);
        $location = trim((string)($body['location'] ?? 'Rak Utama'));

        if ($sku === '' || inventoryTextLength($sku) > 50 || $name === '' || inventoryTextLength($name) > 150
            || $unit === '' || inventoryTextLength($unit) > 20 || inventoryTextLength($location) > 100
            || $purchasePrice === false || $sellingPrice === false || $minimumStock === false
            || !is_finite($purchasePrice) || !is_finite($sellingPrice)
            || !is_finite($minimumStock) || $purchasePrice < 0 || $sellingPrice < 0 || $minimumStock < 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'Data produk tidak valid. Periksa SKU, nama, satuan, harga, dan batas minimum stok.'], 400);
        }
        inventoryValidateProductReferences($pdo, $categoryId, $sku);

        $stmt = $pdo->prepare(
            "INSERT INTO products
                (category_id, sku, product_name, description, unit, purchase_price, selling_price,
                 stock, minimum_stock, location, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1, NOW(), NOW())"
        );
        $stmt->execute([
            $categoryId, $sku, $name, $description, $unit,
            $purchasePrice, $sellingPrice, $minimumStock, $location,
        ]);

        sendJsonResponse([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'product_id' => (int)$pdo->lastInsertId(),
        ], 201);
    }

    if ($method === 'PUT') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $id = (int)($body['id'] ?? ($_GET['id'] ?? 0));
        $sku = trim((string)($body['sku'] ?? ''));
        $name = trim((string)($body['name'] ?? $body['product_name'] ?? ''));
        $categoryValue = trim((string)($body['category_id'] ?? ''));
        if ($categoryValue !== '' && (!ctype_digit($categoryValue) || (int)$categoryValue <= 0)) {
            sendJsonResponse(['status' => 'error', 'message' => 'Kategori tidak valid.'], 400);
        }
        $categoryId = $categoryValue === '' ? null : (int)$categoryValue;
        $description = trim((string)($body['description'] ?? ''));
        $purchasePrice = filter_var($body['purchase_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $sellingPrice = filter_var($body['selling_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $unit = trim((string)($body['unit'] ?? 'unit'));
        $minimumStock = filter_var($body['minimum_stock'] ?? 5, FILTER_VALIDATE_FLOAT);
        $location = trim((string)($body['location'] ?? 'Rak Utama'));

        if ($id <= 0 || $sku === '' || inventoryTextLength($sku) > 50
            || $name === '' || inventoryTextLength($name) > 150
            || $unit === '' || inventoryTextLength($unit) > 20 || inventoryTextLength($location) > 100
            || $purchasePrice === false || $sellingPrice === false || $minimumStock === false
            || !is_finite($purchasePrice) || !is_finite($sellingPrice)
            || !is_finite($minimumStock) || $purchasePrice < 0 || $sellingPrice < 0 || $minimumStock < 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'Data produk tidak valid.'], 400);
        }
        inventoryValidateProductReferences($pdo, $categoryId, $sku, $id);

        $isActive = 1;

        $stmt = $pdo->prepare(
            "UPDATE products
             SET category_id = ?, sku = ?, product_name = ?, description = ?, unit = ?,
                 purchase_price = ?, selling_price = ?, minimum_stock = ?, location = ?,
                 is_active = ?, updated_at = NOW()
             WHERE product_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([
            $categoryId, $sku, $name, $description, $unit,
            $purchasePrice, $sellingPrice, $minimumStock, $location, $isActive, $id,
        ]);

        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM products WHERE product_id = ? AND deleted_at IS NULL');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) {
                sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
            }
        }

        sendJsonResponse(['status' => 'success', 'message' => 'Data produk berhasil diperbarui.']);
    }

    if ($method === 'DELETE') {
        inventoryRequireApiAccess(true);
        $body = inventoryRequestBody();
        $id = (int)($body['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            sendJsonResponse(['status' => 'error', 'message' => 'ID produk tidak valid.'], 400);
        }

        $reason = trim((string)($body['reason'] ?? ''));
        $notes  = trim((string)($body['notes'] ?? ''));

        if ($reason === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Alasan penghapusan produk wajib dipilih.'], 422);
        }

        if ($notes === '') {
            sendJsonResponse(['status' => 'error', 'message' => 'Catatan rincian alasan penghapusan produk wajib diisi dengan jelas untuk keperluan audit manajer.'], 422);
        }

        // Cek data produk & sisa stok fisik saat ini
        $pdo->beginTransaction();
        $prodStmt = $pdo->prepare('SELECT product_id, sku, product_name, stock, unit, is_active FROM products WHERE product_id = ? AND deleted_at IS NULL FOR UPDATE');
        $prodStmt->execute([$id]);
        $prod = $prodStmt->fetch();

        if (!$prod) {
            $pdo->rollBack();
            sendJsonResponse(['status' => 'error', 'message' => 'Produk tidak ditemukan atau sudah dinonaktifkan sebelumnya.'], 404);
        }

        // Hitung total fisik dari riwayat mutasi dan kolom stock
        $stmtStock = $pdo->prepare("
            SELECT GREATEST(0, COALESCE(SUM(CASE WHEN type IN ('PURCHASE','SALES_RETURN','ADJUSTMENT_IN') THEN qty ELSE -qty END), 0)) AS total_stock
            FROM stock_movements
            WHERE product_id = ?
        ");
        $stmtStock->execute([$id]);
        $totalStock = (float)$stmtStock->fetchColumn();
        $colStock   = (float)$prod['stock'];

        $currentStock = max($totalStock, $colStock);
        if ($currentStock > 0) {
            $pdo->rollBack();
            sendJsonResponse([
                'status'  => 'error',
                'message' => sprintf(
                    'Produk "%s (%s)" tidak dapat dihapus karena masih memiliki sisa stok fisik sebanyak %s %s di gudang. Produk hanya dapat dihapus jika stok fisik sudah 0. Harap lakukan transaksi Stok Keluar terlebih dahulu.',
                    $prod['product_name'],
                    $prod['sku'],
                    fmtQty($currentStock),
                    $prod['unit'] ?? 'unit'
                ),
                'stock'   => fmtQty($currentStock),
                'unit'    => $prod['unit'] ?? 'unit'
            ], 409);
        }

        // Eksekusi soft-delete dengan mencatat siapa yang menghapus, kapan, dan alasannya
        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $userName = $_SESSION['name'] ?? 'Admin Gudang';

        $updateStmt = $pdo->prepare('
            UPDATE products 
            SET deleted_at = NOW(), 
                is_active = 0, 
                deleted_by = ?, 
                deletion_reason = ?, 
                deletion_notes = ? 
            WHERE product_id = ?
        ');
        $updateStmt->execute([
            $userId > 0 ? $userId : null,
            $reason,
            $notes !== '' ? $notes : null,
            $id
        ]);

        // Catat ke Riwayat Mutasi Stok (stock_movements) agar otomatis tampil di page Riwayat Stok!
        $cleanSku = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$prod['sku']);
        $delRefNo = sprintf('DEL-%s-%s', $cleanSku !== '' ? $cleanSku : $id, date('Ymd-His'));
        $delNote = sprintf(
            '[PRODUK DIHAPUS] Alasan: %s%s',
            $reason,
            $notes !== '' ? ' | Catatan: ' . $notes : ''
        );

        $smStmt = $pdo->prepare('
            INSERT INTO stock_movements 
                (product_id, type, qty, reference_no, status, supplier_id, created_by, notes, created_at)
            VALUES 
                (?, ?, ?, ?, ?, NULL, ?, ?, NOW())
        ');
        $smStmt->execute([
            $id,
            'ADJUSTMENT_OUT',
            0.00,
            $delRefNo,
            'deleted',
            $userId > 0 ? $userId : null,
            $delNote
        ]);

        // Catat ke log aktivitas sistem (activity_logs)
        $summary = sprintf(
            'Menonaktifkan produk %s (%s). Alasan: %s%s',
            $prod['sku'],
            $prod['product_name'],
            $reason,
            $notes !== '' ? " | Catatan: {$notes}" : ''
        );
        $actStmt = $pdo->prepare('
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $actStmt->execute([
            $userId > 0 ? $userId : null,
            'DELETE',
            'product',
            $id,
            $summary,
            $ip
        ]);

        $pdo->commit();

        sendJsonResponse([
            'status'  => 'success',
            'message' => sprintf('Produk "%s" berhasil dihapus/dinonaktifkan.', $prod['product_name']),
            'data'    => [
                'id'              => $id,
                'name'            => $prod['product_name'],
                'sku'             => $prod['sku'],
                'deleted_at'      => date('d/m/Y H:i'),
                'deleted_by_name' => $userName,
                'deletion_reason' => $reason,
                'deletion_notes'  => $notes
            ]
        ]);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'Metode request tidak didukung.'], 405);
} catch (Throwable $error) {
    if ($error instanceof PDOException && (int)($error->errorInfo[1] ?? 0) === 1062) {
        sendJsonResponse(['status' => 'error', 'message' => 'SKU sudah digunakan oleh produk lain.'], 409);
    }
    inventoryJsonError($error, 'Managing products');
}
