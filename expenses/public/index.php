<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;
use App\Core\Session;
use App\Core\Router;
use App\Core\View;
use App\Core\Logger;

// Load environment variables
Env::load(dirname(__DIR__) . '/.env');

// Set application timezone
$appConfig = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Jakarta');

// Error & exception handling
$debug = $appConfig['debug'] ?? false;
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

set_exception_handler(function (Throwable $e) use ($debug): void {
    Logger::error('Uncaught Exception: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    if ($debug) {
        echo sprintf(
            '<h1>500 Internal Server Error</h1><p><strong>%s</strong></p><pre>%s:%d</pre><pre>%s</pre>',
            htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'),
            $e->getLine(),
            htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
        );
    } else {
        echo View::render('errors/500', ['title' => 'Terjadi Kesalahan'], 'layouts/error');
    }
});


// SSO bridge: accept authenticated session from HRIS login
if (!empty($_GET['sso_sid']) && preg_match('/^[a-zA-Z0-9,-]+$/', (string)$_GET['sso_sid'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('expenses_session');
        session_id((string)$_GET['sso_sid']);
    }
}

// Start secure session
Session::start();

// Initialize router and dispatch
$router = new Router();
$routesDefinition = require dirname(__DIR__) . '/config/routes.php';
$routesDefinition($router);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$response = $router->dispatch($method, $uri);
if (is_string($response)) {
    echo $response;
}
