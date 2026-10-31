<?php
/**
 * guard.php
 * Included at the very top of every protected page (before any output).
 * Starts the HRIS session, redirects to the HRIS login if unauthenticated, and
 * optionally enforces a role whitelist for pages restricted to specific
 * roles (e.g. master-products.php, activity-log.php).
 *
 * Usage:
 *   require __DIR__ . '/guard.php';
 *   // optional: pur_page_require_role(['Admin']);
 */

require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/api/auth.php';

pur_start_session();

if (!pur_is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$currentUser = pur_current_user();
$csrfToken = pur_csrf_token();
$currentMenu = pur_role_menu($currentUser['role']);

/** Call after guard.php to restrict an entire page to specific roles. */
function pur_page_require_role(array $roles): void
{
    global $currentUser;
    if (!in_array($currentUser['role'], $roles, true)) {
        http_response_code(403);
        ?><!doctype html>
        <html lang="id"><head><meta charset="utf-8"><title>Akses Ditolak</title></head>
        <body style="font-family: sans-serif; padding: 3rem; text-align: center;">
            <h1>403 - Akses Ditolak</h1>
            <p>Halaman ini tidak tersedia untuk peran <strong><?= htmlspecialchars($currentUser['role']) ?></strong>.</p>
            <p><a href="./dashboard.php">Kembali ke Dashboard</a></p>
        </body></html><?php
        exit;
    }
}
