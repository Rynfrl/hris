<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;

class AuthMiddleware
{
    public function handle(array $params = []): mixed
    {
        if (!Auth::check()) {
            Flash::warning('Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
            Url::redirect('/login');
        }

        $role = Auth::role();
        // Disallow EMPLOYEE from accessing Expense management module
        if ($role === 'EMPLOYEE') {
            http_response_code(403);
            return View::render('errors/403', [
                'title' => 'Akses Ditolak',
                'message' => 'Karyawan tidak memiliki akses pengajuan pengeluaran mandiri. Seluruh laporan pengeluaran dikelola terpusat oleh Admin Expense.',
            ], 'layouts/error');
        }

        return true;
    }
}
