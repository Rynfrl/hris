<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => 'Expenses',
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => (bool) Env::get('APP_DEBUG', false),
    'url' => Env::get('APP_URL', 'http://127.0.0.1:8000'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Jakarta'),
    'session_name' => Env::get('SESSION_NAME', 'expenses_session'),
    'upload_max_bytes' => (int) Env::get('UPLOAD_MAX_BYTES', 5242880), // 5 MB
    'storage_path' => dirname(__DIR__) . '/storage',
    'receipts_path' => dirname(__DIR__) . '/storage/receipts',
    'logs_path' => dirname(__DIR__) . '/storage/logs',
];
