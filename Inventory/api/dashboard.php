<?php

require_once __DIR__ . '/bootstrap.php';
inventoryRequireApiAccess();

try {
    $pdo = getDbConnection();
    $isCompleteSql = "(p.product_name IS NOT NULL AND TRIM(p.product_name) <> '' AND p.sku IS NOT NULL AND TRIM(p.sku) <> '' AND p.category_id IS NOT NULL AND p.category_id > 0 AND p.unit IS NOT NULL AND TRIM(p.unit) <> '' AND p.purchase_price IS NOT NULL AND p.purchase_price > 0 AND p.selling_price IS NOT NULL AND p.selling_price > 0 AND p.location IS NOT NULL AND TRIM(p.location) <> '' AND p.minimum_stock IS NOT NULL AND p.minimum_stock > 0)";

    $smSubquery = "LEFT JOIN (
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

    $activeStockExpr = "GREATEST(0, COALESCE(sm_sum.in_active, 0) - COALESCE(sm_sum.out_active, 0))";
    $quarantineStockExpr = "GREATEST(0, COALESCE(sm_sum.in_quarantine, 0) - COALESCE(sm_sum.out_quarantine, 0))";
    $draftStockExpr = "COALESCE(sm_sum.in_draft, 0)";
    $currentStockExpr = "GREATEST(0, $activeStockExpr + $quarantineStockExpr + $draftStockExpr)";

    $metrics = $pdo->query(
        "SELECT COUNT(*) AS total_products,
                COALESCE(SUM($currentStockExpr * p.purchase_price), 0) AS total_valuation,
                COALESCE(SUM(CASE WHEN $isCompleteSql AND $activeStockExpr <= p.minimum_stock THEN 1 ELSE 0 END), 0) AS low_stock_count
         FROM products p
         $smSubquery
         WHERE p.is_active = 1 AND p.deleted_at IS NULL"
    )->fetch();
    $stockIn = $pdo->query(
        "SELECT COALESCE(SUM(qty), 0) AS total_qty, COUNT(*) AS total_trx
         FROM stock_movements
         WHERE type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN')
           AND YEAR(created_at) = YEAR(CURRENT_DATE())
           AND MONTH(created_at) = MONTH(CURRENT_DATE())"
    )->fetch();
    $lowStockItems = $pdo->query(
        "SELECT p.product_id AS id, p.sku, p.product_name AS name,
                $activeStockExpr AS current_stock,
                $activeStockExpr AS stock_active,
                $currentStockExpr AS total_stock,
                p.minimum_stock, p.unit,
                CASE WHEN $activeStockExpr <= 0 THEN 'out' ELSE 'low' END AS status,
                c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         $smSubquery
         WHERE p.is_active = 1 AND p.deleted_at IS NULL 
           AND $isCompleteSql 
           AND $activeStockExpr <= p.minimum_stock
         ORDER BY $activeStockExpr ASC, p.product_name ASC"
    )->fetchAll();
    $recent = $pdo->query(
        "SELECT m.stock_movement_id AS id,
                IF(m.status = 'transfer' OR m.reference_no LIKE 'TRF-%' OR m.notes LIKE '%[TRANSFER STATUS%', 'TRANSFER', IF(m.type IN ('PURCHASE', 'SALES_RETURN', 'ADJUSTMENT_IN'), 'IN', 'OUT')) AS type,
                m.type AS raw_type,
                m.created_at AS date,
                m.reference_no AS ref_no,
                p.product_id,
                p.product_name,
                p.sku,
                p.unit,
                m.qty AS quantity,
                COALESCE(grd.condition_note, prt.condition) AS condition_note,
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
            ) AS recorded_by
         FROM stock_movements m
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
         WHERE m.reference_no NOT LIKE 'LOT-SPLIT-%'
         ORDER BY m.created_at DESC, m.stock_movement_id DESC
         LIMIT 10"
    )->fetchAll();
    $totalValuation = (float)$metrics['total_valuation'];

    // Format qty di low stock items
    foreach ($lowStockItems as &$item) {
        $item['current_stock'] = fmtQty($item['current_stock']);
        $item['minimum_stock'] = fmtQty($item['minimum_stock']);
    }
    unset($item);

    // Format qty di recent activities
    foreach ($recent as &$act) {
        if ($act['stock_status'] === 'transfer' || strpos($act['notes'] ?? '', '[TRANSFER STATUS') !== false) {
            $act['is_transfer'] = true;
            if (preg_match('/Sebesar\s+([\d\.\,]+)/i', $act['notes'] ?? '', $matches)) {
                $act['transfer_qty'] = (float)str_replace(',', '.', $matches[1]);
            }
        }
        $act['quantity'] = fmtQty($act['quantity']);
    }
    unset($act);

    sendJsonResponse([
        'status' => 'success',
        'metrics' => [
            'total_products' => (int)$metrics['total_products'],
            'total_valuation' => $totalValuation,
            'total_valuation_formatted' => 'Rp ' . number_format($totalValuation, 0, ',', '.'),
            'stock_in_month_qty' => fmtQty($stockIn['total_qty']),
            'stock_in_month_trx' => (int)$stockIn['total_trx'],
            'low_stock_count' => (int)$metrics['low_stock_count'],
        ],
        'low_stock_items' => $lowStockItems,
        'recent_activities' => $recent,
    ]);
} catch (Throwable $error) {
    inventoryJsonError($error, 'Loading dashboard');
}
