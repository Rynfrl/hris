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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Expenses') ?> — Expenses</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/dashboard" class="sidebar-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
            <span>Expenses</span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu Utama</div>
            <a href="/dashboard" class="sidebar-link <?= Url::isActive('/dashboard') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>Dashboard</span>
            </a>

            <a href="/expenses" class="sidebar-link <?= Url::isActive('/expenses') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                <span><?= $role === 'MANAGER' ? 'Pengeluaran Tim' : 'Pengeluaran Saya' ?></span>
            </a>

            <a href="/reports" class="sidebar-link <?= Url::isActive('/reports') ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span><?= $role === 'MANAGER' ? 'Laporan Tim' : 'Laporan Saya' ?></span>
            </a>

            <?php if ($role === 'MANAGER' || Auth::hasPermission('expense.approve')): ?>
                <div class="nav-section-title">Manajer</div>
                <a href="/approvals" class="sidebar-link <?= Url::isActive('/approvals') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>Persetujuan Tim</span>
                </a>
            <?php endif; ?>

            <?php if ($role === 'FINANCE' || Auth::hasPermission('expense.post') || Auth::hasPermission('expense.reimburse')): ?>
                <div class="nav-section-title">Keuangan</div>
                <?php if ($role === 'FINANCE' || Auth::hasPermission('expense.post')): ?>
                    <a href="/finance/posting" class="sidebar-link <?= Url::isActive('/finance') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        <span>Pembukuan (Posting)</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'FINANCE' || Auth::hasPermission('expense.reimburse')): ?>
                    <a href="/reimbursements" class="sidebar-link <?= Url::isActive('/reimbursements') ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Reimbursement</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="nav-section-title">Laporan</div>
            <a href="/analytics" class="sidebar-link <?= (Url::isActive('/analytics') || Url::isActive('/exports')) ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Analitik & Ekspor</span>
            </a>

            <?php if ($role === 'ADMIN' || Auth::hasPermission('settings.manage') || Auth::hasPermission('users.manage')): ?>
                <div class="nav-section-title">Pengaturan</div>
                <a href="/settings/users" class="sidebar-link <?= Url::isActive('/settings/users') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Pengguna</span>
                </a>
                <a href="/settings/categories" class="sidebar-link <?= Url::isActive('/settings/categories') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>Kategori</span>
                </a>
                <a href="/settings/departments" class="sidebar-link <?= Url::isActive('/settings/departments') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                    <span>Departemen</span>
                </a>
                <a href="/settings/audit-logs" class="sidebar-link <?= Url::isActive('/settings/audit-logs') ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Audit Log</span>
                </a>
            <?php endif; ?>
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
                <form action="/logout" method="POST" class="d-inline">
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
