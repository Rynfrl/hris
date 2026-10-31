<?php

declare(strict_types=1);

use App\Core\View;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Error') ?> — Expenses</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--gt-bg);
        }
        .error-card {
            max-width: 500px;
            width: 100%;
            padding: 2.5rem;
            background: #FFFFFF;
            border-radius: 1rem;
            border: 1px solid var(--gt-border);
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
<div class="error-card">
    <?= $content ?>
</div>
</body>
</html>
