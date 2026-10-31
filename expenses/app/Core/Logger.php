<?php

declare(strict_types=1);

namespace App\Core;

class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $logPath = $appConfig['logs_path'] ?? dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logPath)) {
            @mkdir($logPath, 0755, true);
        }

        $file = $logPath . '/app.log';
        $time = date('Y-m-d H:i:s');

        // Sanitize context: never write passwords or sensitive tokens
        $filteredContext = [];
        foreach ($context as $key => $val) {
            if (preg_match('/(password|token|secret|credit_card|csrf)/i', (string) $key)) {
                $filteredContext[$key] = '***REDACTED***';
            } elseif (is_scalar($val) || $val === null) {
                $filteredContext[$key] = $val;
            } else {
                $filteredContext[$key] = json_encode($val);
            }
        }

        $contextStr = !empty($filteredContext) ? ' ' . json_encode($filteredContext, JSON_UNESCAPED_SLASHES) : '';
        $line = sprintf("[%s] [%s] %s%s\n", $time, strtoupper($level), $message, $contextStr);

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }
}
