<?php
/**
 * api/handlers/dashboard.php
 */

function h_dashboard_get(PDO $pdo): void
{
    $monthStart = date('Y-m-01');
    $nextMonthStart = date('Y-m-01', strtotime('+1 month'));

    // --- Metric cards -----------------------------------------------------
    $totalTxStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM purchase_transactions WHERE deleted_at IS NULL AND transaction_date >= ? AND transaction_date < ?'
    );
    $totalTxStmt->execute([$monthStart, $nextMonthStart]);
    $totalTransactionsThisMonth = (int) $totalTxStmt->fetchColumn();

    // "Pending" di UI = PO yang belum selesai diterima (DRAFT/SENT/CONFIRMED/PARTIALLY_RECEIVED).
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM purchase_transactions WHERE deleted_at IS NULL AND status IN ('DRAFT','SENT','CONFIRMED','PARTIALLY_RECEIVED')");
    $pendingCount = (int) $pendingStmt->fetchColumn();

    $spendStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(grand_total), 0) FROM purchase_transactions
         WHERE deleted_at IS NULL AND transaction_date >= ? AND transaction_date < ? AND status != 'CANCELLED'"
    );
    $spendStmt->execute([$monthStart, $nextMonthStart]);
    $totalSpendThisMonth = (float) $spendStmt->fetchColumn();

    $supplierStmt = $pdo->query(
        "SELECT COUNT(DISTINCT supplier_id) FROM purchase_transactions WHERE deleted_at IS NULL AND status != 'CANCELLED'"
    );
    $activeSuppliers = (int) $supplierStmt->fetchColumn();

    $prPendingStmt = $pdo->query("SELECT COUNT(*) FROM purchase_requisitions WHERE deleted_at IS NULL AND status = 'SUBMITTED'");
    $prPendingApproval = (int) $prPendingStmt->fetchColumn();

    $invoicePendingStmt = $pdo->query("SELECT COUNT(*) FROM vendor_invoices WHERE deleted_at IS NULL AND status IN ('DRAFT','SUBMITTED')");
    $invoicePendingReview = (int) $invoicePendingStmt->fetchColumn();

    // --- 6-month spend chart ----------------------------------------------
    $labels = [];
    $values = [];
    $chartStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(grand_total), 0) FROM purchase_transactions
         WHERE deleted_at IS NULL AND status != 'CANCELLED' AND transaction_date >= ? AND transaction_date < ?"
    );
    for ($i = 5; $i >= 0; $i--) {
        $start = date('Y-m-01', strtotime("-$i months"));
        $end = date('Y-m-01', strtotime("-$i months +1 month"));
        $labels[] = ucfirst(strftime_compat($start));
        $chartStmt->execute([$start, $end]);
        $values[] = (float) $chartStmt->fetchColumn();
    }

    // --- Recent transactions (dashboard.js paginates/filters this client-side) ---
    $recentStmt = $pdo->query('SELECT ' . PUR_PO_COLS . ' FROM purchase_transactions t WHERE t.deleted_at IS NULL ORDER BY t.created_at DESC, t.purchase_transaction_id DESC LIMIT 25');
    $recent = array_map(fn($tx) => pur_serialize_transaction($pdo, $tx), $recentStmt->fetchAll());

    pur_json_response([
        'metrics' => [
            'total_transactions_this_month' => $totalTransactionsThisMonth,
            'pending_transactions' => $pendingCount,
            'total_spend_this_month' => $totalSpendThisMonth,
            'active_suppliers' => $activeSuppliers,
            'pr_pending_approval' => $prPendingApproval,
            'invoice_pending_review' => $invoicePendingReview,
        ],
        'chart' => ['labels' => $labels, 'values' => $values],
        'recent_transactions' => $recent,
    ]);
}

/** PHP 8+ dropped strftime(); this gives an Indonesian "Sep'26"-style label without needing the intl extension. */
function strftime_compat(string $ymd): string
{
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($ymd);
    $m = $months[(int) date('n', $ts) - 1];
    $y = date('y', $ts);
    return "{$m}'{$y}";
}
