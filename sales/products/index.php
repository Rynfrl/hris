<?php
require_once '../auth/check.php';
salesRequireAdmin();
require_once '../config/database.php';

$pdo = getConnection();
$products = $pdo->query("SELECT sku, product_name AS name, selling_price AS price, stock, unit, is_active FROM products WHERE deleted_at IS NULL ORDER BY product_name ASC")->fetchAll();

$pageTitle = 'Produk';
$currentMenu = 'products';
$basePath = '../';
require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Data produk dan stok berasal dari katalog bersama HRIS/Purchasing.
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Produk</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Stok</th>
                <th>Satuan</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['sku']) ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td class="text-right">Rp<?= number_format($p['price'], 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($p['stock']) ?></td>
                    <td><?= htmlspecialchars($p['unit']) ?></td>
                    <td>
                        <?php if ($p['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Nonaktif</span>
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
