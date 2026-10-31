<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../../session.php';

startSecureSession();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    $salesPath = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/sales/');
    $hrisPrefix = $salesPath === false ? '' : substr($_SERVER['SCRIPT_NAME'], 0, $salesPath);
    header('Location: ' . $hrisPrefix . '/index.php');
    exit;
}

if (!hr_is_sales_role($_SESSION['role'] ?? '')) {
    http_response_code(403);
    exit('Akses ditolak. Halaman Sales hanya dapat diakses oleh Admin_Sales dan Marketing_Staff.');
}

function salesIsAdmin(): bool
{
    return hr_normalize_role($_SESSION['role'] ?? '') === 'ADMIN_SALES';
}

function salesRequireAdmin(): void
{
    if (!salesIsAdmin()) {
        http_response_code(403);
        exit('Akses ditolak. Fitur ini hanya dapat diakses oleh Admin_Sales.');
    }
}

function currentUser(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}
