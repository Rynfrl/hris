<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;

class AuthMiddleware
{
    public function handle(array $params = []): mixed
    {
        if (!Auth::check()) {
            Flash::warning('Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
            Url::redirect('/login');
        }
        return true;
    }
}
