<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Url;

class GuestMiddleware
{
    public function handle(array $params = []): mixed
    {
        if (Auth::check()) {
            Url::redirect('/dashboard');
        }
        return true;
    }
}
