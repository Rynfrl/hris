<?php

declare(strict_types=1);

use App\Core\Flash;
use App\Core\View;

$flashMessages = Flash::getMessages();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Masuk') ?> — Expenses</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #EFF6FF 0%, #F8FAFC 100%);
        }
        .auth-card {
            max-width: 420px;
            width: 100%;
            padding: 2.5rem;
            background: #FFFFFF;
            border-radius: 1rem;
            border: 1px solid var(--gt-border);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-light text-primary mb-2">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
        </div>
        <h4 class="fw-bold mb-1">Expenses</h4>
        <p class="text-muted small">Sistem Pengeluaran &amp; Reimbursement ERP</p>
    </div>

    <?php foreach ($flashMessages as $flash): ?>
        <div class="alert alert-<?= View::e($flash['type']) ?> alert-dismissible fade show mb-3" role="alert">
            <?= View::e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>

    <?= $content ?>
</div>

<script src="/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
