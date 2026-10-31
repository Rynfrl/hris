<?php
/**
 * api/helpers.php
 * Shared, dependency-free helper functions used by every route handler.
 */

/** Load (and memoize) api/config.php as an array, or a single top-level key. */
function pur_config(?string $key = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $key === null ? $config : ($config[$key] ?? null);
}

/** Thrown for any expected/business-rule error; caught centrally in index.php. */
class ApiException extends Exception
{
    public int $status;
    public ?array $fieldErrors;

    public function __construct(string $message, int $status = 400, ?array $fieldErrors = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->fieldErrors = $fieldErrors;
    }
}

function pur_json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pur_json_error(int $status, string $message, ?array $fieldErrors = null): void
{
    pur_json_response(array_filter([
        'message' => $message,
        'errors' => $fieldErrors,
    ], fn($v) => $v !== null), $status);
}

/** Decode the JSON request body into an associative array (never trust it as-is). */
function pur_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new ApiException('Body request harus berupa JSON yang valid.', 400);
    }
    return $decoded;
}

/** Reject oversized request bodies before they are even read into memory. */
function pur_enforce_body_size_limit(): void
{
    $max = (int) pur_config('max_request_body_bytes');
    $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($max > 0 && $len > $max) {
        pur_json_error(413, 'Ukuran request terlalu besar.');
    }
}

/**
 * Minimal per-IP request-rate guard, backed by a small JSON file per IP
 * (no Redis/Memcached required, so it works on a bare local PHP install).
 * Deliberately simple: this stops a single client hammering the API (e.g.
 * a buggy script retrying in a tight loop, or basic scripted abuse) - it is
 * NOT a substitute for real DDoS protection at the network/CDN layer,
 * which is the only thing that can stop a genuinely distributed attack.
 */
function pur_enforce_rate_limit(): void
{
    $cfg = pur_config('rate_limit');
    if (!($cfg['enabled'] ?? false)) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = __DIR__ . '/storage/rate_limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_.:-]/', '_', $ip) . '.json';

    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        return; // fail open: never let a filesystem hiccup take the whole API down
    }

    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = $raw ? json_decode($raw, true) : null;
    $now = time();
    $windowStart = $now - (int) $cfg['window_seconds'];

    $timestamps = is_array($data) ? array_filter($data, fn($t) => $t > $windowStart) : [];
    $timestamps[] = $now;

    $exceeded = count($timestamps) > (int) $cfg['max_requests'];

    if (!$exceeded) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode(array_values($timestamps)));
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    if ($exceeded) {
        header('Retry-After: ' . (int) $cfg['window_seconds']);
        pur_json_error(429, 'Terlalu banyak permintaan dari alamat ini. Coba lagi sesaat lagi.');
    }
}

function pur_send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
    header('Referrer-Policy: same-origin');
}

/**
 * Panjang / potong string dalam KARAKTER (bukan byte). Memakai mbstring bila
 * terpasang; bila tidak, fallback ke PCRE (/u) supaya aplikasi tidak
 * bergantung pada ekstensi opsional dan tidak memotong karakter UTF-8 di tengah.
 */
function pur_strlen(string $s): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($s, 'UTF-8');
    }
    return preg_match_all('/./us', $s);
}

/**
 * Writes one row to activity_logs. Called from every handler that
 * creates/updates/approves/rejects/cancels/receives something, so there
 * is always a "who did what, when" trail independent of the business
 * tables themselves.
 */
function pur_log_activity(PDO $pdo, ?int $userId, string $action, string $entityType, ?int $entityId, string $summary): void
{
    $pdo->prepare(
        'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, summary, ip_address, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    )->execute([$userId, $action, $entityType, $entityId, $summary, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/**
 * Recompute subtotal/line-subtotals from product_id + qty + unit_price -
 * exactly like assets/js/purchasing-api.js computeTotals(). The frontend
 * total is only a preview; THIS is the number that gets persisted.
 * Also enforces "no duplicate product per transaction".
 *
 * @return array{details: array, subtotal: float}
 */
function pur_compute_details(PDO $pdo, array $items): array
{
    if (!is_array($items) || count($items) === 0) {
        throw new ApiException('Minimal harus ada satu item produk.', 422, ['items' => 'Minimal 1 item']);
    }
    if (count($items) > 200) {
        throw new ApiException('Jumlah item per transaksi terlalu banyak (maksimal 200 baris).', 422);
    }

    // Kolom produk pada skema gabungan: product_id / sku / product_name / purchase_price.
    // Produk yang sudah dinonaktifkan (is_active = 0) atau dihapus tidak boleh dipesan lagi.
    $productStmt = $pdo->prepare(
        'SELECT product_id AS id, sku AS code, product_name AS name, unit, purchase_price AS unit_price
         FROM products WHERE product_id = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1'
    );

    $details = [];
    $subtotal = 0.0;
    $seenProductIds = [];

    foreach ($items as $raw) {
        $productId = (int) ($raw['product_id'] ?? 0);
        $qty = (float) ($raw['qty'] ?? 0);
        $unitPrice = isset($raw['unit_price']) ? (float) $raw['unit_price'] : null;

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        if (!$product) {
            throw new ApiException("Produk dengan id {$productId} tidak ditemukan atau sudah tidak aktif.", 422);
        }

        if (in_array($productId, $seenProductIds, true)) {
            throw new ApiException('Produk yang sama tidak boleh dipilih lebih dari satu kali.', 422);
        }
        $seenProductIds[] = $productId;

        if ($qty <= 0) {
            throw new ApiException("Qty untuk produk \"{$product['name']}\" harus lebih dari 0.", 422);
        }
        if ($qty > 1_000_000) {
            throw new ApiException("Qty untuk produk \"{$product['name']}\" melebihi batas wajar (maks. 1.000.000).", 422);
        }
        if ($unitPrice === null || $unitPrice < 0) {
            throw new ApiException("Harga satuan untuk produk \"{$product['name']}\" tidak valid.", 422);
        }
        if ($unitPrice > 1_000_000_000_000) {
            throw new ApiException("Harga satuan untuk produk \"{$product['name']}\" melebihi batas wajar.", 422);
        }

        $lineSubtotal = round($qty * $unitPrice, 2);
        $subtotal += $lineSubtotal;

        $details[] = [
            'product_id' => $productId,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => $lineSubtotal,
        ];
    }

    return ['details' => $details, 'subtotal' => round($subtotal, 2)];
}

function pur_calculate_tax(float $subtotal): float
{
    return round($subtotal * (float) pur_config('tax_rate'), 2);
}

/** "PO-{YYYYMM}-{4-digit id}", matching the mock's nextTransactionNo() pattern. */
function pur_generate_transaction_no(string $transactionDate, int $id): string
{
    $ym = date('Ym', strtotime($transactionDate));
    return sprintf('PO-%s-%04d', $ym, $id);
}

function pur_generate_pr_no(int $id): string
{
    return sprintf('PR-%s-%04d', date('Ym'), $id);
}

function pur_generate_grn_no(int $id): string
{
    return sprintf('GRN-%s-%04d', date('Ym'), $id);
}

function pur_generate_return_no(int $id): string
{
    return sprintf('RET-%s-%04d', date('Ym'), $id);
}

/**
 * Recompute a PO's status from actual GRN receipts (replaces the old v1
 * manual "Complete Transaction" button). Driven purely by SUM(qty_received)
 * vs ordered qty per line. Called after every GRN is created.
 *
 * Status ERP: PARTIALLY_RECEIVED bila baru sebagian diterima, RECEIVED bila
 * semua baris sudah diterima penuh. Status INVOICED / COMPLETED sengaja
 * TIDAK diset di sini - itu urusan proses lanjutan (finance) di modul lain.
 * Returns the resulting status as the LABEL the frontend understands.
 */
function pur_recompute_po_status(PDO $pdo, int $poId): string
{
    $stmt = $pdo->prepare(
        'SELECT d.purchase_transaction_detail_id AS id, d.qty AS ordered_qty, COALESCE(SUM(g.qty_received), 0) AS received_qty
         FROM purchase_transaction_details d
         LEFT JOIN goods_receipt_details g ON g.purchase_transaction_detail_id = d.purchase_transaction_detail_id
         WHERE d.purchase_transaction_id = ?
         GROUP BY d.purchase_transaction_detail_id, d.qty'
    );
    $stmt->execute([$poId]);
    $lines = $stmt->fetchAll();

    $anyReceived = false;
    $allFullyReceived = true;
    foreach ($lines as $line) {
        $received = (float) $line['received_qty'];
        $ordered = (float) $line['ordered_qty'];
        if ($received > 0) {
            $anyReceived = true;
        }
        if ($received + 0.0001 < $ordered) {
            $allFullyReceived = false;
        }
    }

    $curStmt = $pdo->prepare('SELECT status FROM purchase_transactions WHERE purchase_transaction_id = ?');
    $curStmt->execute([$poId]);
    $current = (string) $curStmt->fetchColumn();

    if (!$anyReceived) {
        return pur_po_status_label($current); // belum ada penerimaan: status tidak diubah
    }

    $status = $allFullyReceived ? 'RECEIVED' : 'PARTIALLY_RECEIVED';
    $pdo->prepare('UPDATE purchase_transactions SET status = ?, updated_at = NOW() WHERE purchase_transaction_id = ?')
        ->execute([$status, $poId]);

    return pur_po_status_label($status);
}

/**
 * 3-way match: compares the PO's own grand_total, the value of everything
 * actually received via GRN so far (SUM of received qty * the PO's own
 * unit price per line - i.e. "what we should be billed for what arrived"),
 * and the vendor invoice amount. Flags MISMATCHED if any pair differs by
 * more than the configured tolerance.
 *
 * match_status yang dikembalikan memakai nilai ENUM ERP (MATCHED / MISMATCHED).
 */
function pur_compute_three_way_match(PDO $pdo, array $po, float $invoiceAmount): array
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(g.qty_received * d.unit_price), 0) AS grn_value
         FROM goods_receipt_details g
         JOIN purchase_transaction_details d ON d.purchase_transaction_detail_id = g.purchase_transaction_detail_id
         WHERE d.purchase_transaction_id = ?'
    );
    $stmt->execute([$po['id']]);
    $grnValueSubtotal = (float) $stmt->fetchColumn();
    // GRN value above is subtotal (pre-tax); add tax proportionally so it is
    // comparable to the PO's tax-inclusive grand_total and the invoice amount.
    $taxRate = (float) pur_config('tax_rate');
    $grnValue = round($grnValueSubtotal * (1 + $taxRate), 2);

    $poValue = (float) $po['grand_total'];
    $tolerance = (float) pur_config('three_way_match_tolerance');

    $withinTolerance = function (float $a, float $b) use ($tolerance) {
        if ($a == 0 && $b == 0) return true;
        $base = max(abs($a), abs($b), 1);
        return abs($a - $b) / $base <= $tolerance;
    };

    $poVsGrn = $withinTolerance($poValue, $grnValue);
    $poVsInvoice = $withinTolerance($poValue, $invoiceAmount);
    $grnVsInvoice = $withinTolerance($grnValue, $invoiceAmount);

    $matched = $poVsGrn && $poVsInvoice && $grnVsInvoice;

    $fmt = fn($n) => number_format($n, 0, ',', '.');
    if ($matched) {
        $notes = "Cocok: PO Rp {$fmt($poValue)}, nilai barang diterima (GRN) Rp {$fmt($grnValue)}, invoice Rp {$fmt($invoiceAmount)}.";
    } else {
        $notes = "Selisih terdeteksi - PO: Rp {$fmt($poValue)}, GRN (barang diterima): Rp {$fmt($grnValue)}, Invoice: Rp {$fmt($invoiceAmount)}.";
    }

    return ['match_status' => $matched ? 'MATCHED' : 'MISMATCHED', 'match_notes' => $notes, 'grn_value' => $grnValue];
}

// =========================================================================
// ERP <-> aplikasi: penerjemah role, status, dan data user
// -------------------------------------------------------------------------
// Skema Purchasing memakai role/status UPPERCASE (PURCHASING, SUBMITTED, ...),
// sedangkan frontend memakai label yang sudah ada (Purchasing Staff,
// Pending Approval, ...). Seluruh terjemahan dipusatkan di sini supaya JSON
// API tidak berubah dan modul lain tetap bebas memakai nilai ERP aslinya.
// =========================================================================

/** role ERP (roles.role_name) => role di aplikasi. Role lain (HR, SALES) tidak punya akses. */
const PUR_ROLE_MAP = [
    'ADMIN' => 'Admin',
    'ADMIN_PURCHASE' => 'Admin',
    'ADMIN_PURCHASING' => 'Admin',
    'PURCHASING' => 'Purchasing Staff',
    'PURCHASING_STAFF' => 'Purchasing Staff',
    'WAREHOUSE' => 'Warehouse Staff',
    'FINANCE' => 'Finance Manager',
    'MANAGER' => 'Manager',
];

function pur_map_erp_role(?string $erpRole): ?string
{
    if ($erpRole === null) {
        return null;
    }
    $roleKey = strtoupper(trim($erpRole));
    $roleKey = preg_replace('/[\s-]+/', '_', $roleKey);
    return PUR_ROLE_MAP[$roleKey] ?? null;
}

/** Untuk tampilan (mis. activity log lintas modul): role dikenal -> label aplikasi, selain itu apa adanya. */
function pur_role_label(?string $erpRole): ?string
{
    return pur_map_erp_role($erpRole) ?? $erpRole;
}

/** Status PO: label frontend <-> daftar status ERP. */
const PUR_PO_STATUS_TO_ERP = [
    'Pending' => ['DRAFT', 'SENT', 'CONFIRMED'],
    'Partially Received' => ['PARTIALLY_RECEIVED'],
    'Completed' => ['RECEIVED', 'INVOICED', 'COMPLETED'],
    'Cancelled' => ['CANCELLED'],
];
/** PO yang belum menerima barang sama sekali (boleh diubah/dibatalkan). */
const PUR_PO_OPEN = ['DRAFT', 'SENT', 'CONFIRMED'];
/** PO yang masih boleh menerima barang (GRN). */
const PUR_PO_RECEIVABLE = ['DRAFT', 'SENT', 'CONFIRMED', 'PARTIALLY_RECEIVED'];

const PUR_PR_STATUS_TO_ERP = [
    'Draft' => ['DRAFT'],
    'Pending Approval' => ['SUBMITTED'],
    'Approved' => ['APPROVED'],
    'Rejected' => ['REJECTED'],
    'Converted' => ['CONVERTED'],
    'Cancelled' => ['CANCELLED'],
];

const PUR_INVOICE_STATUS_TO_ERP = [
    'Pending Review' => ['DRAFT', 'SUBMITTED'],
    'Approved for Payment' => ['APPROVED'],
    'Rejected' => ['REJECTED'],
    'Paid' => ['PAID'],
    'Cancelled' => ['CANCELLED'],
];
/** Invoice yang masih menunggu keputusan approve/reject. */
const PUR_INVOICE_PENDING = ['DRAFT', 'SUBMITTED'];

const PUR_MATCH_TO_LABEL = ['PENDING' => 'Pending', 'MATCHED' => 'Matched', 'MISMATCHED' => 'Mismatched', 'EXCEPTION' => 'Exception'];

function pur_status_label(array $map, string $erpStatus): string
{
    foreach ($map as $label => $erpList) {
        if (in_array($erpStatus, $erpList, true)) {
            return $label;
        }
    }
    return $erpStatus;
}
function pur_po_status_label(string $s): string { return pur_status_label(PUR_PO_STATUS_TO_ERP, $s); }
function pur_pr_status_label(string $s): string { return pur_status_label(PUR_PR_STATUS_TO_ERP, $s); }
function pur_invoice_status_label(string $s): string { return pur_status_label(PUR_INVOICE_STATUS_TO_ERP, $s); }

/** Label frontend -> daftar status ERP untuk filter ?status=..., atau null bila label tidak dikenal. */
function pur_status_filter(array $map, string $label): ?array
{
    return $map[$label] ?? null;
}

/** "?, ?, ?" untuk klausa IN (...) dengan jumlah parameter dinamis. */
function pur_placeholders(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}

/**
 * Ringkasan user untuk JSON API: {id, name, email, role}. Nama diambil dari
 * data HR (employees.nama), fallback ke username bila akun tidak
 * ditautkan ke karyawan. Role diterjemahkan ke label aplikasi.
 */
function pur_fetch_user_brief(PDO $pdo, ?int $userId): ?array
{
    if (!$userId) {
        return null;
    }
    static $stmt = null;
    if ($stmt === null) {
        $stmt = $pdo->prepare(
            'SELECT u.id, COALESCE(e.nama, u.name, u.username) AS name, u.email,
                    COALESCE(NULLIF(u.role, \'\'), r.role_name) AS role, u.created_at
            FROM users u
            LEFT JOIN roles r ON r.id_role = u.role_id
            LEFT JOIN employees e ON e.id_user = u.id
            WHERE u.id = ?'
        );
    }
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    $row['role'] = pur_role_label($row['role']);
    return $row;
}

function pur_send_cors_headers(): void
{
    $allowed = pur_config('cors_allowed_origins') ?? [];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    // Same-origin requests (the supported default setup) send no Origin
    // header needing a response here at all. Only echo back the origin -
    // and only then set Allow-Credentials - when it is on the explicit
    // allow-list; never use '*', which browsers reject for credentialed
    // (cookie-carrying) requests anyway.
    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
