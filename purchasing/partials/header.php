<?php
/**
 * partials/header.php
 * Included by every protected page AFTER guard.php has authenticated the
 * current user; each page may also set $pageTitle + $activeNavKey.
 */
if (!isset($currentUser)) {
    throw new RuntimeException('partials/header.php included without guard.php running first.');
}

$csrfToken = pur_csrf_token();
$currentMenu = pur_role_menu($currentUser['role']);
$pageTitle = $pageTitle ?? 'Purchasing ERP';
$activeNavKey = $activeNavKey ?? '';

// [menu key => [label, bootstrap-icon suffix, href]] - order here is the
// order rendered. Filtered against $currentMenu (see api/auth.php
// pur_role_menu()) so each role only sees what it can actually use.
$navItems = [
    'dashboard' => ['Dashboard', 'speedometer2', 'dashboard.php'],
    'pr' => ['Purchase Requisition', 'file-earmark-text', 'pr-list.php'],
    'grn' => ['Goods Receipt (GRN)', 'box-seam', 'grn-list.php'],
    'invoices' => ['Vendor Invoice', 'receipt-cutoff', 'vendor-invoices.php'],
    'returns' => ['Purchase Return', 'arrow-return-left', 'purchase-returns.php'],
    'scorecard' => ['Vendor Scorecard', 'bar-chart-line', 'vendor-scorecard.php'],
    'master_products' => ['Master Produk', 'box', 'master-products.php'],
    'master_suppliers' => ['Master Supplier', 'truck', 'master-suppliers.php'],
    'activity_log' => ['Activity Log', 'clock-history', 'activity-log.php'],
];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($pageTitle) ?> | Purchasing ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />

    <!-- Server-rendered auth context for assets/js/purchasing-common.js (Purchasing.Auth) -->
    <meta name="pur-user-id" content="<?= (int) $currentUser['id'] ?>" />
    <meta name="pur-user-name" content="<?= htmlspecialchars($currentUser['name']) ?>" />
    <meta name="pur-user-email" content="<?= htmlspecialchars($currentUser['email']) ?>" />
    <meta name="pur-user-role" content="<?= htmlspecialchars($currentUser['role']) ?>" />
    <meta name="pur-csrf-token" content="<?= htmlspecialchars($csrfToken) ?>" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="./assets/vendor/adminlte/css/adminlte.min.css" />
    <link rel="stylesheet" href="./assets/css/purchasing.css" />
  </head>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <span class="nav-link disabled">Purchasing / <?= htmlspecialchars($pageTitle) ?></span>
            </li>
          </ul>
          <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item dropdown">
              <a class="nav-link d-flex align-items-center" data-bs-toggle="dropdown" href="#" aria-label="Akun pengguna">
                <span class="pur-avatar-circle me-2"><?= htmlspecialchars(strtoupper(substr($currentUser['name'], 0, 1))) ?></span>
                <span class="d-none d-md-inline">
                  <span><?= htmlspecialchars($currentUser['name']) ?></span>
                  <small class="d-block text-secondary"><?= htmlspecialchars($currentUser['role']) ?></small>
                </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end">
                <span class="dropdown-item-text small text-secondary"><?= htmlspecialchars($currentUser['email']) ?></span>
                <div class="dropdown-divider"></div>
                <form method="post" action="../logout.php">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                  <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                </form>
              </div>
            </li>
          </ul>
        </div>
      </nav>
      <!--end::Header-->

      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
          <a href="./dashboard.php" class="brand-link">
            <img src="./assets/vendor/adminlte/img/logo.png" alt="Logo" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">ERP<span class="pur-module-badge">PURCHASING</span></span>
          </a>
        </div>
        <div class="sidebar-wrapper">
          <nav class="mt-2" aria-label="Main navigation">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false">
              <?php foreach ($navItems as $key => [$label, $icon, $href]): ?>
                <?php if (in_array($key, $currentMenu, true)): ?>
                  <li class="nav-item">
                    <a href="./<?= $href ?>" class="nav-link <?= $activeNavKey === $key ? 'active' : '' ?>">
                      <i class="nav-icon bi bi-<?= $icon ?>"></i>
                      <p><?= htmlspecialchars($label) ?></p>
                    </a>
                  </li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </nav>
        </div>
      </aside>
      <!--end::Sidebar-->

      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row align-items-center">
              <div class="col-sm-6">
                <h1 class="mb-0 fs-3"><?= htmlspecialchars($pageTitle) ?></h1>
              </div>
              <div class="col-sm-6">
                <nav aria-label="breadcrumb">
                  <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="./dashboard.php">Purchasing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($pageTitle) ?></li>
                  </ol>
                </nav>
              </div>
            </div>
          </div>
        </div>

        <div class="app-content" id="pur-page-content">
          <div class="container-fluid">
