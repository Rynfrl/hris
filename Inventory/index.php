<?php

require_once dirname(__DIR__) . '/session.php';

$role = strtoupper(trim((string)($_SESSION['role'] ?? '')));
if (($_SESSION['login'] ?? false) !== true
    || !in_array($role, ['ADMIN_WAREHOUSE', 'WAREHOUSE_STAFF'], true)) {
    header('Location: ../index.php');
    exit();
}

header('Location: pages/dashboard.html');
exit();
