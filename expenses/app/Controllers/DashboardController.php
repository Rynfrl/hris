<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\DashboardService;

class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct(?DashboardService $dashboardService = null)
    {
        $this->dashboardService = $dashboardService ?? new DashboardService();
    }

    public function index(): string
    {
        $user = Auth::user();
        $role = $user['role_name'] ?? 'EMPLOYEE';

        // Employee has no access to operational expense menus
        if ($role === 'EMPLOYEE' || (!in_array($role, ['ADMIN', 'Admin_HR', 'ADMIN_EXPENSE', 'MANAGER', 'FINANCE'], true) && !in_array('expenses.manage', $user['permissions'] ?? []))) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Akses Ditolak'], 'layouts/error');
        }

        $metrics = $this->dashboardService->getMetrics($user);

        return View::render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'metrics' => $metrics,
        ]);
    }
}
