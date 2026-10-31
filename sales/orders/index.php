<?php
require_once '../auth/check.php';
require_once '../config/database.php';

$pdo = getConnection();

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT so.*, c.name AS customer_name FROM sales_orders so JOIN customers c ON c.id = so.customer_id";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE so.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY so.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'draft' => 'secondary',
    'confirmed' => 'info',
    'invoiced' => 'warning',
    'paid' => 'success',
    'cancelled' => 'danger',
];

$pageTitle = 'Sales Order';
$currentMenu = 'orders';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';

if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' . htmlspecialchars($_GET['success']) . '</div>';
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Sales Order</h3>
        <div class="card-tools">
            <a href="create.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Order Baru</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3 form-inline">
            <label class="mr-2">Filter Status:</label>
            <select name="status" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">Semua</option>
                <?php foreach (['draft','confirmed','invoiced','paid','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>No. Order</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th class="text-right">Total</th>
                <th width="160">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada order</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['order_no']) ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                    <td><span class="badge badge-<?= $statusColors[$o['status']] ?? 'secondary' ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td class="text-right">Rp<?= number_format($o['total_amount'], 0, ',', '.') ?></td>
                    <td>
                        <a href="view.php?id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i> Detail</a>
                        <?php if (salesIsAdmin() && $o['status'] === 'draft'): ?>
                            <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Hapus order ini?')">
                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
