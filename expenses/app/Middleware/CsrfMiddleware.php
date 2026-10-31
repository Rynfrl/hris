<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\View;

class CsrfMiddleware
{
    public function handle(array $params = []): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Csrf::validate($token)) {
                http_response_code(419);
                echo View::render('errors/419', ['title' => 'Sesi Telah Berakhir'], 'layouts/error');
                exit;
            }
        }
        return true;
    }
}
