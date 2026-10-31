<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Core\Url;
use App\Repositories\UserRepository;

class AuthController
{
    private UserRepository $userRepo;

    public function __construct(?UserRepository $userRepo = null)
    {
        $this->userRepo = $userRepo ?? new UserRepository();
    }

    public function showLogin(): string
    {
        return View::render('auth/login', [
            'title' => 'Masuk ke Sistem',
        ], 'layouts/auth');
    }

    public function processLogin(): void
    {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($identifier === '' || $password === '') {
            Flash::error('Silakan masukkan email/username dan kata sandi.');
            Url::redirect('/login');
        }

        $user = $this->userRepo->findByUsernameOrEmail($identifier);

        if (!$user) {
            $this->userRepo->recordLoginAttempt($identifier, $ip, false);
            Flash::error('Identitas akun atau kata sandi tidak sesuai.');
            Url::redirect('/login');
        }

        // Check if active
        if ((int) $user['is_active'] !== 1) {
            $this->userRepo->recordLoginAttempt($identifier, $ip, false);
            Flash::error('Akun Anda saat ini dinonaktifkan. Silakan hubungi administrator.');
            Url::redirect('/login');
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->userRepo->recordLoginAttempt($identifier, $ip, false);
            Flash::error('Identitas akun atau kata sandi tidak sesuai.');
            Url::redirect('/login');
        }

        // Login success
        $this->userRepo->recordLoginAttempt($identifier, $ip, true);
        Auth::login($user);

        Flash::success('Selamat datang kembali, ' . ($user['employee_name'] ?? $user['username']) . '!');
        Url::redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::info('Anda telah berhasil keluar dari sistem.');

        $base = Url::basePath();
        if ($base !== '') {
            $hrisDir = dirname($base);
            $hrisUrl = ($hrisDir === '/' || $hrisDir === '\\' ? '' : $hrisDir) . '/index.php?logout=1';
            header('Location: ' . $hrisUrl);
            exit;
        }

        Url::redirect('/login');
    }
}
