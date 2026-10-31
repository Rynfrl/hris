<?php
require_once 'auth/check.php';
require_once 'config/database.php';

$pdo = getConnection();
$isSalesAdmin = salesIsAdmin();

$totalCustomers = $pdo->query("SELECT COUNT(*) c FROM customers")->fetch()['c'];
$totalOrders = $pdo->query("SELECT COUNT(*) c FROM sales_orders")->fetch()['c'];
$totalRevenue = $isSalesAdmin
    ? $pdo->query("SELECT COALESCE(SUM(total_amount),0) t FROM sales_orders WHERE status IN ('confirmed','invoiced','paid')")->fetch()['t']
    : null;
$unpaidInvoices = $isSalesAdmin
    ? $pdo->query("SELECT COUNT(*) c FROM invoices WHERE status != 'paid'")->fetch()['c']
    : null;

$recentOrders = $pdo->query("
    SELECT so.*, c.name AS customer_name
    FROM sales_orders so
    JOIN customers c ON c.id = so.customer_id
    ORDER BY so.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
$currentMenu = 'dashboard';
$basePath = '';
require 'includes/header.php';
require 'includes/sidebar.php';
?>

<div class="row">
    <div class="<?= $isSalesAdmin ? 'col-lg-3 col-6' : 'col-lg-6 col-6' ?>">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($totalCustomers) ?></h3>
                <p>Total Customer</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="customers/index.php" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="<?= $isSalesAdmin ? 'col-lg-3 col-6' : 'col-lg-6 col-6' ?>">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($totalOrders) ?></h3>
                <p>Total Sales Order</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <a href="orders/index.php" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <?php if ($isSalesAdmin): ?>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>Rp<?= number_format($totalRevenue, 0, ',', '.') ?></h3>
                <p>Total Penjualan</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="reports/sales_report.php" class="small-box-footer">Lihat laporan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= number_format($unpaidInvoices) ?></h3>
                <p>Invoice Belum Lunas</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
            <a href="invoices/index.php" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Order Terbaru</h3>
        <div class="card-tools">
            <a href="orders/create.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Order Baru</a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>No. Order</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th class="text-right">Total</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recentOrders)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada order</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['order_no']) ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                    <td><span class="badge badge-secondary"><?= ucfirst($o['status']) ?></span></td>
                    <td class="text-right">Rp<?= number_format($o['total_amount'], 0, ',', '.') ?></td>
                    <td><a href="orders/view.php?id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
