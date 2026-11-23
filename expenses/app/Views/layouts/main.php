<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;

$user = Auth::user();
$role = Auth::role();
$currentUri = Url::currentPath();
$flashMessages = Flash::getMessages();

$hasExpenseManage = Auth::hasPermission('expenses.manage') || Auth::hasPermission('expense.manage');
$isAdminExpense = in_array($role, ['ADMIN', 'ADMIN_EXPENSE'], true) || ($role === 'Admin_HR' && $hasExpenseManage) || $hasExpenseManage;
$isManagerExpense = ($role === 'MANAGER') || Auth::hasPermission('expense.approve');
$hasPostPerm = in_array($role, ['FINANCE', 'ADMIN'], true) || Auth::hasPermission('expense.post');
$hasReimbursePerm = in_array($role, ['FINANCE', 'ADMIN'], true) || Auth::hasPermission('expense.reimburse');
$isFinance = ($role === 'FINANCE') || $hasPostPerm || $hasReimbursePerm;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Expenses') ?> — GT Expense</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="<?= Url::to('/dashboard') ?>" class="sidebar-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
            <span>GT Expense</span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu Utama</div>
            <a href="<?= Url::to('/dashboard') ?>" class="sidebar-link <?= Url::isActive('/dashboard') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Dashboard</span>
            </a>

            <?php if ($isAdminExpense): ?>
                <!-- ADMIN EXPENSE SIDEBAR -->
                <div class="nav-section-title">Admin Expense</div>
                <a href="<?= Url::to('/reports/create') ?>" class="sidebar-link <?= Url::isActive('/reports/create') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Buat Laporan Baru</span>
                </a>
                <a href="<?= Url::to('/reports') ?>" class="sidebar-link <?= (Url::isActive('/reports') && !Url::isActive('/reports/create')) ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Daftar Laporan</span>
                </a>

                <?php if (in_array($role, ['ADMIN'], true) || Auth::hasPermission('settings.manage')): ?>
                    <div class="nav-section-title">Master Data</div>
                    <a href="<?= Url::to('/settings/users') ?>" class="sidebar-link <?= Url::isActive('/settings/users') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Manajemen Akun Pengguna</span>
                    </a>
                    <a href="<?= Url::to('/settings/categories') ?>" class="sidebar-link <?= Url::isActive('/settings/categories') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Kategori Pengeluaran</span>
                    </a>
                <?php endif; ?>

                <div class="nav-section-title">Laporan &amp; Log</div>
                <a href="<?= Url::to('/analytics') ?>" class="sidebar-link <?= (Url::isActive('/analytics') || Url::isActive('/exports')) ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Analitik &amp; Ekspor</span>
                </a>
                <?php if (in_array($role, ['ADMIN'], true) || Auth::hasPermission('settings.manage')): ?>
                    <a href="<?= Url::to('/settings/audit-logs') ?>" class="sidebar-link <?= Url::isActive('/settings/audit-logs') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Audit Log</span>
                    </a>
                <?php endif; ?>

            <?php elseif ($isManagerExpense): ?>
                <!-- MANAGER EXPENSE SIDEBAR -->
                <div class="nav-section-title">Persetujuan Manajer</div>
                <a href="<?= Url::to('/approvals') ?>" class="sidebar-link <?= (Url::isActive('/approvals') && ($_GET['status'] ?? '') !== 'HISTORY') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>Menunggu Persetujuan</span>
                </a>
                <a href="<?= Url::to('/approvals?status=HISTORY') ?>" class="sidebar-link <?= (Url::isActive('/approvals') && ($_GET['status'] ?? '') === 'HISTORY') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Riwayat Persetujuan</span>
                </a>
                <a href="<?= Url::to('/reports') ?>" class="sidebar-link <?= (Url::isActive('/reports') && !Url::isActive('/reports/create')) ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Semua Laporan</span>
                </a>

                <div class="nav-section-title">Analisis</div>
                <a href="<?= Url::to('/analytics') ?>" class="sidebar-link <?= Url::isActive('/analytics') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Analitik Pengeluaran</span>
                </a>

            <?php elseif ($isFinance): ?>
                <!-- FINANCE SIDEBAR -->
                <?php if ($hasPostPerm): ?>
                    <div class="nav-section-title">Pembukuan Finance</div>
                    <a href="<?= Url::to('/finance/posting') ?>" class="sidebar-link <?= Url::isActive('/finance/posting') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        <span>Antrean Pembukuan</span>
                    </a>
                    <a href="<?= Url::to('/finance/history') ?>" class="sidebar-link <?= Url::isActive('/finance/history') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        <span>Riwayat Pembukuan</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasReimbursePerm): ?>
                    <div class="nav-section-title">Pencairan Dana</div>
                    <a href="<?= Url::to('/reimbursements') ?>" class="sidebar-link <?= (Url::isActive('/reimbursements') && !Url::isActive('/reimbursements/history') && ($_GET['status'] ?? '') !== 'FAILED') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Antrean Reimbursement</span>
                    </a>
                    <a href="<?= Url::to('/reimbursements?status=FAILED') ?>" class="sidebar-link <?= (Url::isActive('/reimbursements') && ($_GET['status'] ?? '') === 'FAILED') ? 'active text-danger' : 'text-danger' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <span>Pembayaran Gagal</span>
                    </a>
                    <a href="<?= Url::to('/reimbursements/history') ?>" class="sidebar-link <?= Url::isActive('/reimbursements/history') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Riwayat Reimbursement</span>
                    </a>
                <?php endif; ?>

                <div class="nav-section-title">Laporan Keuangan</div>
                <a href="<?= Url::to('/reports') ?>" class="sidebar-link <?= (Url::isActive('/reports') && !Url::isActive('/reports/create')) ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Daftar Laporan</span>
                </a>
                <a href="<?= Url::to('/analytics') ?>" class="sidebar-link <?= Url::isActive('/analytics') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    <span>Laporan Pengeluaran</span>
                </a>
            <?php endif; ?>

            <div class="nav-section-title">Navigasi ERP</div>
            <a href="/dashboard.php" class="sidebar-link text-info">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>
                <span>Kembali ke HRIS</span>
            </a>
            <a href="/purchasing/dashboard.php" class="sidebar-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                <span>Modul Purchasing</span>
            </a>
            <a href="/sales/index.php" class="sidebar-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Modul Sales</span>
            </a>
            <a href="/Inventory/pages/dashboard.html" class="sidebar-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                <span>Modul Inventory</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>

            <div class="d-none d-sm-block text-secondary small">
                <span>Selamat datang, <strong><?= View::e($user['employee_name'] ?? $user['username'] ?? 'Pengguna') ?></strong></span>
                <span class="badge bg-light text-dark border ms-2"><?= View::e($role ?? 'USER') ?></span>
                <?php if (!empty($user['department_name'])): ?>
                    <span class="text-muted ms-1">&bull; <?= View::e($user['department_name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="/dashboard.php" class="btn btn-sm btn-outline-secondary d-none d-md-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                    <span>Menu Utama HRIS</span>
                </a>
                <form action="<?= Url::to('/logout') ?>" method="POST" class="d-inline">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Body -->
        <main class="content-body">
            <!-- Flash Messages -->
            <?php foreach ($flashMessages as $flash): ?>
                <div class="alert alert-<?= View::e($flash['type']) ?> alert-dismissible fade show alert-auto-dismiss mb-3" role="alert">
                    <?= View::e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/chartjs/chart.umd.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
