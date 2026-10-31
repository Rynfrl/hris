<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;
use Throwable;

class Database
{
    private static ?PDO $instance = null;

    public static function connect(?array $config = null): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            $candidateDb = ($config['database'] === 'erp_database') ? 'hris' : 'erp_database';
            try {
                $fallbackDsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $candidateDb,
                    $config['charset'] ?? 'utf8mb4'
                );
                $pdo = new PDO($fallbackDsn, $config['username'], $config['password'], $config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (\Throwable) {
                throw $e;
            }
        }

        self::$instance = $pdo;
        return self::$instance;
    }

    public static function pdo(): PDO
    {
        return self::connect();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
