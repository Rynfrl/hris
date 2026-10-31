<?php
/**
 * api/db.php
 * Single PDO connection factory, shared by every request.
 */

function pur_get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $config = pur_config('db');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Surface a clear, actionable message instead of a raw PDO stack
        // trace - this is almost always a first-run config/setup problem.
        // The technical detail is only included in 'debug' mode (see
        // api/config.php) so a production deployment never leaks it.
        $message = 'Tidak bisa terhubung ke database. Periksa config/database.php (host/nama db/user/password) ' .
            'dan pastikan MySQL/MariaDB sudah berjalan serta database "' . $config['name'] . '" sudah dibuat ' .
            '(siapkan database hris terlebih dahulu menggunakan Database/hris.sql).';
        if (pur_config('debug')) {
            $message .= ' Detail: ' . $e->getMessage();
        }
        pur_json_error(500, $message);
    }

    return $pdo;
}
