<?php

declare(strict_types=1);

namespace App\Core;

class Url
{
    private static ?string $basePath = null;
    private static ?string $currentPath = null;

    public static function reset(): void
    {
        self::$basePath = null;
        self::$currentPath = null;
    }

    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = rtrim(dirname($scriptName), '/');
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            $scriptDir = '';
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // 1. Direct match with script directory (e.g. /hris/expenses/public or /expenses/public)
        if ($scriptDir !== '' && str_starts_with($uriPath, $scriptDir)) {
            self::$basePath = $scriptDir;
            return self::$basePath;
        }

        // 2. If scriptDir ends with /public, but user requested without /public (e.g. via .htaccess rewrite)
        if (str_ends_with($scriptDir, '/public')) {
            $parentDir = substr($scriptDir, 0, -7);
            if ($parentDir !== '' && str_starts_with($uriPath, $parentDir)) {
                self::$basePath = $parentDir;
                return self::$basePath;
            }
        }

        // 3. Fallback checks on uriPath
        if (str_starts_with($uriPath, '/hris/expenses/public')) {
            self::$basePath = '/hris/expenses/public';
        } elseif (str_starts_with($uriPath, '/hris/expenses')) {
            self::$basePath = '/hris/expenses';
        } elseif (str_starts_with($uriPath, '/expenses/public')) {
            self::$basePath = '/expenses/public';
        } elseif (str_starts_with($uriPath, '/expenses')) {
            self::$basePath = '/expenses';
        } else {
            self::$basePath = '';
        }

        return self::$basePath;
    }

    public static function setCurrentPath(?string $path): void
    {
        if ($path === null) {
            self::$currentPath = null;
            return;
        }

        $clean = '/' . trim($path, '/');
        self::$currentPath = $clean === '//' ? '/' : $clean;
    }

    public static function currentPath(): string
    {
        if (self::$currentPath !== null) {
            return self::$currentPath;
        }

        $basePath = self::basePath();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public static function isActive(string $route, bool $exact = false): bool
    {
        $current = self::currentPath();
        $route = '/' . trim($route, '/');

        if ($exact || $route === '/') {
            return $current === $route;
        }

        if ($route === '/dashboard') {
            return $current === '/dashboard' || $current === '/';
        }

        return $current === $route || str_starts_with($current, $route . '/');
    }

    public static function to(string $path = ''): string
    {
        $base = self::basePath();
        $clean = '/' . ltrim($path, '/');
        if ($clean === '/' && $base !== '') {
            return $base . '/';
        }
        return $base . $clean;
    }

    public static function asset(string $path): string
    {
        $base = self::basePath();
        $clean = '/' . ltrim($path, '/');
        return $base . $clean;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::to($path));
        exit;
    }
}
