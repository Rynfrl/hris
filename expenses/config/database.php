<?php

declare(strict_types=1);

use App\Core\Env;

// Shared HRIS database config as source of truth / fallback
$parentConfigFile = dirname(__DIR__, 2) . '/config/database.php';
$parentConfig = file_exists($parentConfigFile) ? require $parentConfigFile : [];

$host = Env::get('DB_HOST', $parentConfig['host'] ?? '127.0.0.1');
$port = (int) Env::get('DB_PORT', (string) ($parentConfig['port'] ?? 3306));
$database = Env::get('DB_DATABASE', $parentConfig['name'] ?? 'hris');
$username = Env::get('DB_USERNAME', $parentConfig['user'] ?? 'root');
$password = (string) Env::get('DB_PASSWORD', $parentConfig['pass'] ?? '');
$charset = $parentConfig['charset'] ?? 'utf8mb4';

return [
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset' => $charset,
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

