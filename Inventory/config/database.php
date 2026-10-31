<?php

$databaseConfig = require dirname(__DIR__, 2) . '/config/database.php';

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $databaseConfig;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $databaseConfig['host'],
            $databaseConfig['port'],
            $databaseConfig['name'],
            $databaseConfig['charset']
        );

        $pdo = new PDO($dsn, $databaseConfig['user'], $databaseConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function sendJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
