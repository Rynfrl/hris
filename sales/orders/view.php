<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();
$isSalesAdmin = salesIsAdmin();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT so.*, c.name AS customer_name, c.email, c.phone, c.address FROM sales_orders so JOIN customers c ON c.id = so.customer_id WHERE so.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT soi.*, p.product_name, p.sku FROM sales_order_items soi JOIN products p ON p.product_id = soi.product_id WHERE soi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$invoice = null;
if ($isSalesAdmin) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE order_id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
}

$statusColors = [
    'draft' => 'secondary', 'confirmed' => 'info', 'invoiced' => 'warning',
    'paid' => 'success', 'cancelled' => 'danger',
];

$pageTitle = 'Detail Order';
$currentMenu = 'orders';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' . htmlspecialchars($_GET['success']) . '</div>';
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order <?= htmlspecialchars($order['order_no']) ?></h3>
                <div class="card-tools">
                    <span class="badge badge-<?= $statusColors[$order['status']] ?? 'secondary' ?> p-2"><?= ucfirst($order['status']) ?></span>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
                   <strong>Tanggal:</strong> <?= date('d M Y', strtotime($order['order_date'])) ?><br>
                   <?php if ($order['notes']): ?><strong>Catatan:</strong> <?= htmlspecialchars($order['notes']) ?><?php endif; ?>
                </p>

                <table class="table table-bordered">
                    <thead>
                    <tr><th>Produk</th><th class="text-right">Harga</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?> (<?= htmlspecialchars($it['sku']) ?>)</td>
                            <td class="text-right">Rp<?= number_format($it['price'], 0, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($it['quantity']) ?></td>
                            <td class="text-right">Rp<?= number_format($it['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td colspan="3" class="text-right">Subtotal</td><td class="text-right">Rp<?= number_format($order['subtotal'], 0, ',', '.') ?></td></tr>
                    <tr><td colspan="3" class="text-right">Diskon</td><td class="text-right">- Rp<?= number_format($order['discount'], 0, ',', '.') ?></td></tr>
                    <tr><td colspan="3" class="text-right">Pajak</td><td class="text-right">Rp<?= number_format($order['tax'], 0, ',', '.') ?></td></tr>
                    <tr class="font-weight-bold"><td colspan="3" class="text-right">Total</td><td class="text-right">Rp<?= number_format($order['total_amount'], 0, ',', '.') ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body">
                <?php if ($isSalesAdmin && $order['status'] === 'draft'): ?>
                    <form method="POST" action="update_status.php" class="mb-2">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                        <input type="hidden" name="action" value="confirm">
                        <button class="btn btn-info btn-block"><i class="fas fa-check"></i> Konfirmasi Order</button>
                    </form>
                <?php endif; ?>

                <?php if ($isSalesAdmin && $order['status'] === 'confirmed' && !$invoice): ?>
                    <a href="../invoices/create.php?order_id=<?= $order['id'] ?>" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-receipt"></i> Buat Invoice
                    </a>
                <?php endif; ?>

                <?php if ($isSalesAdmin && $invoice): ?>
                    <a href="../invoices/view.php?id=<?= $invoice['id'] ?>" class="btn btn-outline-secondary btn-block mb-2">
                        <i class="fas fa-file-invoice"></i> Lihat Invoice <?= htmlspecialchars($invoice['invoice_no']) ?>
                    </a>
                <?php endif; ?>

                <?php if ($isSalesAdmin && in_array($order['status'], ['draft', 'confirmed'])): ?>
                    <form method="POST" action="update_status.php" onsubmit="return confirm('Batalkan order ini?')">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-danger btn-block"><i class="fas fa-times"></i> Batalkan Order</button>
                    </form>
                <?php endif; ?>

                <a href="index.php" class="btn btn-secondary btn-block mt-2">Kembali ke Daftar</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Info Customer</h3></div>
            <div class="card-body">
                <p><?= htmlspecialchars($order['customer_name']) ?><br>
                   <?= htmlspecialchars($order['email']) ?><br>
                   <?= htmlspecialchars($order['phone']) ?><br>
                   <?= nl2br(htmlspecialchars($order['address'])) ?></p>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
