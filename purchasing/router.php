<?php
/**
 * router.php
 * -----------------------------------------------------------------------
 * Use with PHP's built-in server from the HRIS project root:
 *
 *   php -S localhost:8000 -t . purchasing/router.php
 *
 * then open http://localhost:8000/ and sign in through HRIS.
 *
 * This is NOT needed if you deploy under Apache/XAMPP/Laragon - there,
 * api/.htaccess handles routing and every .php file is served as-is.
 * -----------------------------------------------------------------------
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Purchasing API routes are under /purchasing/api/ when hosted by HRIS.
if (preg_match('#^/purchasing/api(/|$)#', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Let the built-in server serve HRIS and Purchasing PHP/assets normally.
return false;
