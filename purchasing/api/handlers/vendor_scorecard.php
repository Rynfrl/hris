<?php
/**
 * api/handlers/vendor_scorecard.php
 * Vendor Performance Scorecard: computed on-the-fly (no stored/duplicated
 * table to keep in sync) from PO/GRN timeliness and Purchase Return
 * quality incidents, grouped by supplier.
 */

function h_vendor_scorecard(PDO $pdo): void
{
    // On-time delivery: for each PO that has an expected_delivery_date and
    // at least one GRN, compare expected date to the EARLIEST GRN date
    // (i.e. did the first delivery arrive on/before the promised date).
    $stmt = $pdo->query(
        "SELECT
            s.supplier_id AS supplier_id, s.supplier_code AS code, s.supplier_name AS name,
            COUNT(DISTINCT t.purchase_transaction_id) AS total_po,
            SUM(CASE WHEN t.expected_delivery_date IS NOT NULL AND first_grn.first_received_date IS NOT NULL THEN 1 ELSE 0 END) AS po_with_delivery_data,
            SUM(CASE WHEN t.expected_delivery_date IS NOT NULL AND first_grn.first_received_date IS NOT NULL
                     AND first_grn.first_received_date <= t.expected_delivery_date THEN 1 ELSE 0 END) AS on_time_count,
            COALESCE(ret.return_count, 0) AS return_count,
            COALESCE(ret.total_qty_returned, 0) AS total_qty_returned
         FROM suppliers s
         LEFT JOIN purchase_transactions t ON t.supplier_id = s.supplier_id AND t.deleted_at IS NULL AND t.status != 'CANCELLED'
         LEFT JOIN (
             SELECT purchase_transaction_id, MIN(received_date) AS first_received_date
             FROM goods_receipts WHERE deleted_at IS NULL GROUP BY purchase_transaction_id
         ) first_grn ON first_grn.purchase_transaction_id = t.purchase_transaction_id
         LEFT JOIN (
             SELECT t2.supplier_id, COUNT(pr.purchase_return_id) AS return_count, SUM(pr.qty_returned) AS total_qty_returned
             FROM purchase_returns pr
             JOIN goods_receipts gr ON gr.goods_receipt_id = pr.goods_receipt_id
             JOIN purchase_transactions t2 ON t2.purchase_transaction_id = gr.purchase_transaction_id
             WHERE pr.deleted_at IS NULL
             GROUP BY t2.supplier_id
         ) ret ON ret.supplier_id = s.supplier_id
         WHERE s.deleted_at IS NULL AND s.is_active = 1
         GROUP BY s.supplier_id, s.supplier_code, s.supplier_name, ret.return_count, ret.total_qty_returned
         ORDER BY s.supplier_name"
    );

    $rows = array_map(function ($r) {
        $withData = (int) $r['po_with_delivery_data'];
        $onTime = (int) $r['on_time_count'];
        $onTimeRate = $withData > 0 ? round(($onTime / $withData) * 100, 1) : null;
        return [
            'supplier_id' => (int) $r['supplier_id'],
            'code' => $r['code'],
            'name' => $r['name'],
            'total_po' => (int) $r['total_po'],
            'po_with_delivery_data' => $withData,
            'on_time_count' => $onTime,
            'on_time_rate_percent' => $onTimeRate,
            'return_count' => (int) $r['return_count'],
            'total_qty_returned' => (float) $r['total_qty_returned'],
        ];
    }, $stmt->fetchAll());

    pur_json_response(['data' => $rows]);
}
