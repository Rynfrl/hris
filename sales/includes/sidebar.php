<?php
$bp = isset($basePath) ? $basePath : '';
$current = $currentMenu ?? '';
$isSalesAdmin = salesIsAdmin();
function menuActive($name, $current) {
    return $name === $current ? 'active' : '';
}
?>
<!-- SIDEBAR -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= $bp ?>index.php" class="brand-link">
        <i class="fas fa-chart-line brand-image ml-2" style="opacity:.9"></i>
        <span class="brand-text font-weight-light ml-2">Sales Module</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                <li class="nav-item">
                    <a href="<?= $bp ?>index.php" class="nav-link <?= menuActive('dashboard', $current) ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= $bp ?>customers/index.php" class="nav-link <?= menuActive('customers', $current) ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Customers</p>
                    </a>
                </li>

                <?php if ($isSalesAdmin): ?>
                    <li class="nav-item">
                        <a href="<?= $bp ?>products/index.php" class="nav-link <?= menuActive('products', $current) ?>">
                            <i class="nav-icon fas fa-box"></i>
                            <p>Produk <span class="badge badge-info right">ref</span></p>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="<?= $bp ?>orders/index.php" class="nav-link <?= menuActive('orders', $current) ?>">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Sales Order</p>
                    </a>
                </li>

                <?php if ($isSalesAdmin): ?>
                    <li class="nav-item">
                        <a href="<?= $bp ?>invoices/index.php" class="nav-link <?= menuActive('invoices', $current) ?>">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>Invoice</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= $bp ?>reports/sales_report.php" class="nav-link <?= menuActive('reports', $current) ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Laporan Penjualan</p>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-header text-uppercase text-secondary small px-3 mt-3 mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">Navigasi ERP</li>
                <li class="nav-item">
                    <a href="<?= $bp ?>../dashboard.php" class="nav-link text-info">
                        <i class="nav-icon fas fa-arrow-circle-left"></i>
                        <p>Kembali ke HRIS</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $bp ?>../purchasing/dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>Modul Purchasing</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $bp ?>../Inventory/pages/dashboard.html" class="nav-link">
                        <i class="nav-icon fas fa-warehouse"></i>
                        <p>Modul Inventory</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $bp ?>../expenses/public/dashboard" class="nav-link">
                        <i class="nav-icon fas fa-money-bill-wave"></i>
                        <p>Modul Expenses</p>
                    </a>
                </li>

                <li class="nav-item mt-3 pt-3 sidebar-logout-item">
                    <form method="POST" action="<?= $bp ?>../logout.php" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <button type="submit" class="nav-link sidebar-logout-btn">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </button>
                    </form>
                </li>

            </ul>
        </nav>
    </div>
</aside>
<!-- /SIDEBAR -->

<div class="content-wrapper">
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : '' ?></h1>
            </div>
        </div>
    </div>
</div>
<section class="content">
<div class="container-fluid">
