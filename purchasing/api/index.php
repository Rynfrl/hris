<?php
/**
 * api/index.php
 * Front controller. Every request under /api/ is routed through here
 * (see ./.htaccess for Apache, or ../router.php for `php -S`).
 *
 * Route matching is done against whatever comes after the LAST "/api/"
 * segment in the request path, so this works whether the project is
 * hosted at the web root or inside a subfolder (e.g. XAMPP's
 * htdocs/purchasing/), without needing any base-path configuration.
 */

declare(strict_types=1);

// Never let raw PHP errors/warnings leak into the JSON response body -
// everything must go through the centralized catch block below, which
// decides what is safe to expose based on api/config.php's 'debug' flag.
ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/handlers/auth.php';
require __DIR__ . '/handlers/dashboard.php';
require __DIR__ . '/handlers/transactions.php';
require __DIR__ . '/handlers/products.php';
require __DIR__ . '/handlers/departments.php';
require __DIR__ . '/handlers/suppliers.php';
require __DIR__ . '/handlers/requisitions.php';
require __DIR__ . '/handlers/goods_receipts.php';
require __DIR__ . '/handlers/vendor_invoices.php';
require __DIR__ . '/handlers/purchase_returns.php';
require __DIR__ . '/handlers/vendor_scorecard.php';
require __DIR__ . '/handlers/activity_log.php';

pur_start_session();
pur_send_cors_headers();
pur_send_security_headers();
pur_enforce_body_size_limit();
pur_enforce_rate_limit();

$method = $_SERVER['REQUEST_METHOD'];
$fullPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$apiPos = strrpos($fullPath, '/api/');
$route = $apiPos !== false ? substr($fullPath, $apiPos + 4) : '/';
$route = '/' . ltrim($route, '/');
$route = rtrim($route, '/');
if ($route === '') {
    $route = '/';
}

// [method, regex, action key]. Auth requirement + CSRF are decided per
// action below (see $publicActions / $mutatingMethods), not per-route,
// so the table itself stays readable.
$routes = [
    ['GET', '#^/auth/me$#', 'auth.me'],
    ['GET', '#^/auth/csrf$#', 'auth.csrf'],

    ['GET', '#^/purchasing/dashboard$#', 'dashboard'],

    ['GET', '#^/purchasing/transactions$#', 'po.list'],
    ['GET', '#^/purchasing/transactions/(?P<id>\d+)$#', 'po.get'],
    ['POST', '#^/purchasing/transactions$#', 'po.create'],
    ['PUT', '#^/purchasing/transactions/(?P<id>\d+)$#', 'po.update'],
    ['POST', '#^/purchasing/transactions/(?P<id>\d+)/cancel$#', 'po.cancel'],

    ['GET', '#^/products$#', 'products.list'],
    ['POST', '#^/products$#', 'products.create'],
    ['PUT', '#^/products/(?P<id>\d+)$#', 'products.update'],
    ['DELETE', '#^/products/(?P<id>\d+)$#', 'products.delete'],

    ['GET', '#^/suppliers$#', 'suppliers.list'],
    ['POST', '#^/suppliers$#', 'suppliers.create'],
    ['PUT', '#^/suppliers/(?P<id>\d+)$#', 'suppliers.update'],
    ['DELETE', '#^/suppliers/(?P<id>\d+)$#', 'suppliers.delete'],

    ['GET', '#^/departments$#', 'departments.list'],

    ['GET', '#^/pr$#', 'pr.list'],
    ['GET', '#^/pr/(?P<id>\d+)$#', 'pr.get'],
    ['POST', '#^/pr$#', 'pr.create'],
    ['PUT', '#^/pr/(?P<id>\d+)$#', 'pr.update'],
    ['POST', '#^/pr/(?P<id>\d+)/submit$#', 'pr.submit'],
    ['POST', '#^/pr/(?P<id>\d+)/approve$#', 'pr.approve'],
    ['POST', '#^/pr/(?P<id>\d+)/reject$#', 'pr.reject'],
    ['POST', '#^/pr/(?P<id>\d+)/convert$#', 'pr.convert'],

    ['GET', '#^/grn$#', 'grn.list'],
    ['GET', '#^/grn/(?P<id>\d+)$#', 'grn.get'],
    ['POST', '#^/grn$#', 'grn.create'],

    ['GET', '#^/invoices$#', 'invoices.list'],
    ['GET', '#^/invoices/(?P<id>\d+)$#', 'invoices.get'],
    ['POST', '#^/invoices$#', 'invoices.create'],
    ['POST', '#^/invoices/(?P<id>\d+)/approve$#', 'invoices.approve'],
    ['POST', '#^/invoices/(?P<id>\d+)/reject$#', 'invoices.reject'],

    ['GET', '#^/returns$#', 'returns.list'],
    ['POST', '#^/returns$#', 'returns.create'],

    ['GET', '#^/vendor-scorecard$#', 'scorecard.get'],

    ['GET', '#^/activity-log$#', 'activitylog.list'],
];

// Endpoints reachable WITHOUT a logged-in session.
$publicActions = ['auth.csrf'];

try {
    $pdo = pur_get_pdo();
    $matched = null;
    $params = [];
    $pathExistsForOtherMethod = false;

    foreach ($routes as [$routeMethod, $pattern, $action]) {
        if (preg_match($pattern, $route, $m)) {
            if ($routeMethod === $method) {
                $matched = $action;
                $params = $m;
                break;
            }
            $pathExistsForOtherMethod = true;
        }
    }

    if ($matched === null) {
        $status = $pathExistsForOtherMethod ? 405 : 404;
        pur_json_error($status, "Endpoint tidak ditemukan: $method $route");
    }

    // CSRF check for every state-changing request (all except GET), on
    // every endpoint including login (a pre-login CSRF token is minted
    // the first time any page loads, once the session cookie exists).
    if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        pur_verify_csrf();
    }

    if (!in_array($matched, $publicActions, true)) {
        pur_require_login();
    }

    $id = isset($params['id']) ? (int) $params['id'] : null;

    switch ($matched) {
        case 'auth.me': h_auth_me(); break;
        case 'auth.csrf': h_auth_csrf(); break;

        case 'dashboard': h_dashboard_get($pdo); break;

        case 'po.list': h_transactions_list($pdo); break;
        case 'po.get': h_transactions_get($pdo, $id); break;
        case 'po.create': h_transactions_create($pdo); break;
        case 'po.update': h_transactions_update($pdo, $id); break;
        case 'po.cancel': h_transactions_cancel($pdo, $id); break;

        case 'products.list': h_products_list($pdo); break;
        case 'products.create': h_products_create($pdo); break;
        case 'products.update': h_products_update($pdo, $id); break;
        case 'products.delete': h_products_delete($pdo, $id); break;

        case 'suppliers.list': h_suppliers_list($pdo); break;
        case 'suppliers.create': h_suppliers_create($pdo); break;
        case 'suppliers.update': h_suppliers_update($pdo, $id); break;
        case 'suppliers.delete': h_suppliers_delete($pdo, $id); break;

        case 'departments.list': h_departments_list($pdo); break;

        case 'pr.list': h_pr_list($pdo); break;
        case 'pr.get': h_pr_get($pdo, $id); break;
        case 'pr.create': h_pr_create($pdo); break;
        case 'pr.update': h_pr_update($pdo, $id); break;
        case 'pr.submit': h_pr_submit($pdo, $id); break;
        case 'pr.approve': h_pr_approve($pdo, $id); break;
        case 'pr.reject': h_pr_reject($pdo, $id); break;
        case 'pr.convert': h_pr_convert($pdo, $id); break;

        case 'grn.list': h_grn_list($pdo); break;
        case 'grn.get': h_grn_get($pdo, $id); break;
        case 'grn.create': h_grn_create($pdo); break;

        case 'invoices.list': h_invoices_list($pdo); break;
        case 'invoices.get': h_invoices_get($pdo, $id); break;
        case 'invoices.create': h_invoices_create($pdo); break;
        case 'invoices.approve': h_invoices_approve($pdo, $id); break;
        case 'invoices.reject': h_invoices_reject($pdo, $id); break;

        case 'returns.list': h_returns_list($pdo); break;
        case 'returns.create': h_returns_create($pdo); break;

        case 'scorecard.get': h_vendor_scorecard($pdo); break;

        case 'activitylog.list': h_activity_log_list($pdo); break;
    }
} catch (ApiException $e) {
    pur_json_error($e->status, $e->getMessage(), $e->fieldErrors);
} catch (Throwable $e) {
    error_log('[purchasing-api] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    $msg = 'Terjadi kesalahan pada server. Detail teknis dicatat di log server (error_log PHP).';
    if (pur_config('debug')) {
        $msg .= ' Detail: ' . $e->getMessage();
    }
    pur_json_error(500, $msg);
}
