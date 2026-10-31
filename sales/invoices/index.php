<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

$pdo = getConnection();
$invoices = $pdo->query("
    SELECT inv.*, so.order_no, c.name AS customer_name
    FROM invoices inv
    JOIN sales_orders so ON so.id = inv.order_id
    JOIN customers c ON c.id = so.customer_id
    ORDER BY inv.created_at DESC
")->fetchAll();

$statusColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];

$pageTitle = 'Invoice';
$currentMenu = 'invoices';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' . htmlspecialchars($_GET['success']) . '</div>';
}
?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Daftar Invoice</h3></div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>No. Invoice</th>
                <th>No. Order</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Jumlah</th>
                <th>Status</th>
                <th width="100">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Belum ada invoice</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><?= htmlspecialchars($inv['invoice_no']) ?></td>
                    <td><?= htmlspecialchars($inv['order_no']) ?></td>
                    <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                    <td><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                    <td><?= $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '-' ?></td>
                    <td class="text-right">Rp<?= number_format($inv['amount'], 0, ',', '.') ?></td>
                    <td><span class="badge badge-<?= $statusColors[$inv['status']] ?? 'secondary' ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td><a href="view.php?id=<?= $inv['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
