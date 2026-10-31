<?php
/**
 * api/auth.php
 * HRIS session authentication + Purchasing RBAC + CSRF.
 *
 * Included by both api/index.php (for API requests) and page-level guards
 * (auth/guard.php) so both share exactly the same session/CSRF logic.
 */

/** Must be called before any output, on every request (API or page). */
function pur_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        require_once __DIR__ . '/../../session.php';
    }

    $lifetime = (int) pur_config('session_lifetime_seconds');
    if (!empty($_SESSION['login']) && !empty($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $lifetime) {
            pur_logout();
        }
    }
    if (!empty($_SESSION['login'])) {
        $_SESSION['last_activity'] = time();
    }
}

function pur_is_logged_in(): bool
{
    return ($_SESSION['login'] ?? false) === true
        && !empty($_SESSION['user_id'])
        && pur_map_erp_role((string) ($_SESSION['role'] ?? '')) !== null;
}

/** Projects the authenticated HRIS user into the shape expected by Purchasing. */
function pur_current_user(): ?array
{
    if (!pur_is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => pur_map_erp_role((string) $_SESSION['role']),
    ];
}

function pur_require_login(): array
{
    $user = pur_current_user();
    if (!$user) {
        throw new ApiException('Sesi Anda telah berakhir. Silakan login kembali.', 401);
    }
    return $user;
}

function pur_require_role(array $roles): array
{
    $user = pur_require_login();
    if (!in_array($user['role'], $roles, true)) {
        throw new ApiException('Anda tidak memiliki hak akses untuk aksi ini.', 403);
    }
    return $user;
}

/**
 * Sidebar visibility map: which menu keys each role sees. This is purely
 * a UX convenience (a hidden link is not a security boundary) - every
 * handler below independently re-checks the role with pur_require_role()
 * before doing anything, which is the actual enforcement point.
 */
function pur_role_menu(string $role): array
{
    $all = ['dashboard', 'pr', 'po', 'grn', 'invoices', 'returns', 'scorecard', 'master_products', 'master_suppliers', 'activity_log'];
    $map = [
        'Admin' => $all,
        'Purchasing Staff' => ['dashboard', 'pr', 'po', 'scorecard', 'master_products', 'master_suppliers'],
        'Warehouse Staff' => ['dashboard', 'po', 'grn', 'returns', 'master_products'],
        'Finance Manager' => ['dashboard', 'pr', 'po', 'invoices', 'scorecard'],
        // Role ERP MANAGER (approver/manajer departemen): hanya menyetujui PR.
        'Manager' => ['dashboard', 'pr', 'po', 'scorecard'],
    ];
    return $map[$role] ?? [];
}

function pur_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------
// CSRF protection for cookie-authenticated, state-changing requests.
// ---------------------------------------------------------------------
function pur_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function pur_verify_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? null;
    if (!$expected || !$sent || !hash_equals($expected, $sent)) {
        throw new ApiException('Token keamanan (CSRF) tidak valid atau kedaluwarsa. Muat ulang halaman dan coba lagi.', 419);
    }
}
