<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

$pdo = getConnection();
$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT so.*, c.name AS customer_name FROM sales_orders so JOIN customers c ON c.id = so.customer_id WHERE so.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order || $order['status'] !== 'confirmed') { header('Location: ../orders/index.php'); exit; }

$existingStmt = $pdo->prepare("SELECT id FROM invoices WHERE order_id = ? LIMIT 1");
$existingStmt->execute([$orderId]);
if ($existing = $existingStmt->fetch()) { header('Location: view.php?id=' . $existing['id']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $invoiceDate = trim($_POST['invoice_date'] ?? date('Y-m-d'));
    $dueDate = trim($_POST['due_date'] ?? '');
    if (!validDate($invoiceDate)) $errors[] = 'Tanggal invoice tidak valid.';
    if ($dueDate !== '' && !validDate($dueDate)) $errors[] = 'Jatuh tempo tidak valid.';
    if ($dueDate !== '' && validDate($invoiceDate) && $dueDate < $invoiceDate) $errors[] = 'Jatuh tempo tidak boleh sebelum tanggal invoice.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT status, total_amount FROM sales_orders WHERE id = ? FOR UPDATE");
            $lock->execute([$orderId]);
            $lockedOrder = $lock->fetch();
            if (!$lockedOrder || $lockedOrder['status'] !== 'confirmed') throw new RuntimeException('Order sudah tidak dapat dibuatkan invoice.');
            $check = $pdo->prepare("SELECT id FROM invoices WHERE order_id = ? FOR UPDATE");
            $check->execute([$orderId]);
            if ($already = $check->fetch()) {
                $pdo->rollBack(); header('Location: view.php?id=' . $already['id']); exit;
            }
            $invoiceLock = null;
            $invoiceNo = nextDocumentNo($pdo, 'INV');
            $invoiceLock = 'hris_sales_inv_' . date('Ymd');
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no, order_id, invoice_date, due_date, amount, status) VALUES (?, ?, ?, ?, ?, 'unpaid')");
            $stmt->execute([$invoiceNo, $orderId, $invoiceDate, $dueDate !== '' ? $dueDate : null, $lockedOrder['total_amount']]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE sales_orders SET status = 'invoiced' WHERE id = ? AND status = 'confirmed'")->execute([$orderId]);
            $pdo->commit();
            if ($invoiceLock) releaseDocumentLock($pdo, $invoiceLock);
            header('Location: view.php?id=' . $newId . '&success=' . urlencode('Invoice berhasil dibuat')); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (!empty($invoiceLock)) { try { releaseDocumentLock($pdo, $invoiceLock); } catch (Throwable $ignore) {} }
            error_log('Create invoice failed: ' . $e->getMessage());
            $errors[] = 'Gagal membuat invoice: ' . $e->getMessage();
        }
    }
}

$pageTitle='Buat Invoice'; $currentMenu='invoices'; $basePath='../'; require '../includes/header.php'; require '../includes/sidebar.php';
?>
<div class="card card-primary"><div class="card-header"><h3 class="card-title">Buat Invoice untuk Order <?= h($order['order_no']) ?></h3></div>
<form method="POST"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
<div class="card-body"><?php foreach($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>
<p><strong>Customer:</strong> <?= h($order['customer_name']) ?><br><strong>Total Order:</strong> Rp<?= number_format($order['total_amount'],0,',','.') ?></p>
<div class="form-row"><div class="form-group col-md-6"><label class="required">Tanggal Invoice</label><input type="date" name="invoice_date" class="form-control" required value="<?= h($_POST['invoice_date'] ?? date('Y-m-d')) ?>"></div><div class="form-group col-md-6"><label>Jatuh Tempo</label><input type="date" name="due_date" class="form-control" value="<?= h($_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>"></div></div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary">Buat Invoice</button> <a href="../orders/view.php?id=<?= (int)$order['id'] ?>" class="btn btn-secondary">Batal</a></div></form></div>
<?php require '../includes/footer.php'; ?>
