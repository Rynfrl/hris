<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /**
     * @var array<string, array<int, array{path: string, pattern: string, handler: callable|array, params: array<string>, middleware: array<string>}>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        // Extract parameter names from {param}
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        $paramNames = $matches[1] ?? [];

        // Build regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'params' => $paramNames,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $basePath = Url::basePath();
        $parsedUrl = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = $parsedUrl;

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        // Clean any repeated /expenses prefix (e.g. /expenses/expenses/reports -> /reports)
        $normalizedPath = $path;
        while (str_starts_with($normalizedPath, '/expenses')) {
            $normalizedPath = substr($normalizedPath, strlen('/expenses'));
            $normalizedPath = '/' . trim($normalizedPath, '/');
        }
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        Url::setCurrentPath($normalizedPath);

        $candidates = array_unique(array_filter([$normalizedPath, $path, $parsedUrl]));
        if ($normalizedPath !== '/' && !str_starts_with($normalizedPath, '/expenses')) {
            $candidates[] = '/expenses' . $normalizedPath;
        }

        $method = strtoupper($method);
        $lookupMethod = ($method === 'HEAD') ? 'GET' : $method;

        // Check matching route in requested method
        if (isset($this->routes[$lookupMethod])) {
            foreach ($candidates as $testPath) {
                foreach ($this->routes[$lookupMethod] as $route) {
                    if (preg_match($route['pattern'], $testPath, $matches)) {
                        Url::setCurrentPath($testPath);
                        array_shift($matches); // remove full match
                        $params = [];
                        foreach ($route['params'] as $idx => $name) {
                            $params[$name] = isset($matches[$idx]) ? urldecode($matches[$idx]) : null;
                        }

                        // Execute middlewares
                        foreach ($route['middleware'] as $middlewareClass) {
                            $middleware = new $middlewareClass();
                            $result = $middleware->handle($params);
                            if ($result !== null && $result !== true) {
                                return $result;
                            }
                        }

                        return $this->invokeHandler($route['handler'], $params);
                    }
                }
            }
        }

        // Check if route exists under different method (405)
        foreach ($this->routes as $otherMethod => $otherRoutes) {
            if ($otherMethod === $method) {
                continue;
            }
            foreach ($candidates as $testPath) {
                foreach ($otherRoutes as $route) {
                    if (preg_match($route['pattern'], $testPath)) {
                        http_response_code(405);
                        return View::render('errors/405', ['title' => 'Method Not Allowed'], 'layouts/error');
                    }
                }
            }
        }

        // Not found (404)
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Halaman Tidak Ditemukan'], 'layouts/error');
    }

    private function invokeHandler(callable|array $handler, array $params): mixed
    {
        $args = array_values($params);
        if (is_array($handler)) {
            [$classOrObject, $method] = $handler;
            $controller = is_object($classOrObject) ? $classOrObject : new $classOrObject();
            return call_user_func_array([$controller, $method], $args);
        }

        return call_user_func_array($handler, $args);
    }
}
