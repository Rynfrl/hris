<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $coreAliases = [
        'Url' => 'App\\Core\\Url',
        'View' => 'App\\Core\\View',
        'Csrf' => 'App\\Core\\Csrf',
        'Auth' => 'App\\Core\\Auth',
        'Flash' => 'App\\Core\\Flash',
        'Database' => 'App\\Core\\Database',
    ];
    if (isset($coreAliases[$class])) {
        $target = $coreAliases[$class];
        if (class_exists($target)) {
            class_alias($target, $class);
            return;
        }
    }

    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
