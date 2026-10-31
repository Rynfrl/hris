<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

$pdo = getConnection();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if (!validDate($startDate)) $startDate = date('Y-m-01');
if (!validDate($endDate)) $endDate = date('Y-m-d');
if ($startDate > $endDate) { [$startDate, $endDate] = [$endDate, $startDate]; }

$stmt = $pdo->prepare("
    SELECT so.*, c.name AS customer_name
    FROM sales_orders so
    JOIN customers c ON c.id = so.customer_id
    WHERE so.order_date BETWEEN ? AND ?
    AND so.status IN ('confirmed', 'invoiced', 'paid')
    ORDER BY so.order_date ASC
");
$stmt->execute([$startDate, $endDate]);
$orders = $stmt->fetchAll();

$totalRevenue = array_sum(array_column($orders, 'total_amount'));
$totalOrders = count($orders);
$avgOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// produk terlaris pada rentang tanggal ini
$stmt = $pdo->prepare("
    SELECT p.product_name AS name, p.sku, SUM(soi.quantity) AS total_qty, SUM(soi.subtotal) AS total_sales
    FROM sales_order_items soi
    JOIN sales_orders so ON so.id = soi.order_id
    JOIN products p ON p.product_id = soi.product_id
    WHERE so.order_date BETWEEN ? AND ?
    AND so.status IN ('confirmed', 'invoiced', 'paid')
    GROUP BY p.product_id, p.product_name, p.sku
    ORDER BY total_qty DESC
    LIMIT 5
");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll();

// data harian untuk chart
$stmt = $pdo->prepare("
    SELECT order_date, SUM(total_amount) AS daily_total
    FROM sales_orders
    WHERE order_date BETWEEN ? AND ? AND status IN ('confirmed','invoiced','paid')
    GROUP BY order_date
    ORDER BY order_date ASC
");
$stmt->execute([$startDate, $endDate]);
$dailyData = $stmt->fetchAll();

$pageTitle = 'Laporan Penjualan';
$currentMenu = 'reports';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Dari</label>
            <input type="date" name="start_date" class="form-control mr-3" value="<?= htmlspecialchars($startDate) ?>">
            <label class="mr-2">Sampai</label>
            <input type="date" name="end_date" class="form-control mr-3" value="<?= htmlspecialchars($endDate) ?>">
            <button class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner"><h3>Rp<?= number_format($totalRevenue, 0, ',', '.') ?></h3><p>Total Penjualan</p></div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= number_format($totalOrders) ?></h3><p>Jumlah Order</p></div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner"><h3>Rp<?= number_format($avgOrder, 0, ',', '.') ?></h3><p>Rata-rata / Order</p></div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Tren Penjualan Harian</h3></div>
    <div class="card-body">
        <canvas id="salesChart" style="min-height:250px"></canvas>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Produk Terlaris</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead><tr><th>Produk</th><th class="text-right">Qty Terjual</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topProducts as $tp): ?>
                        <tr>
                            <td><?= htmlspecialchars($tp['name']) ?> (<?= htmlspecialchars($tp['sku']) ?>)</td>
                            <td class="text-right"><?= number_format($tp['total_qty']) ?></td>
                            <td class="text-right">Rp<?= number_format($tp['total_sales'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Detail Order Periode Ini</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead><tr><th>No. Order</th><th>Customer</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= htmlspecialchars($o['order_no']) ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td class="text-right">Rp<?= number_format($o['total_amount'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "<script src='https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js'></script>
<script>
const ctx = document.getElementById('salesChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: " . json_encode(array_map(fn($d) => date('d M', strtotime($d['order_date'])), $dailyData)) . ",
        datasets: [{
            label: 'Penjualan Harian (Rp)',
            data: " . json_encode(array_map(fn($d) => (float)$d['daily_total'], $dailyData)) . ",
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: true } } }
});
</script>";
require '../includes/footer.php';
?>
