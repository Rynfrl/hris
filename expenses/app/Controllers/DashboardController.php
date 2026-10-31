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
        $metrics = $this->dashboardService->getMetrics($user);

        return View::render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'metrics' => $metrics,
        ]);
    }
}
