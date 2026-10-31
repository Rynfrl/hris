<?php

declare(strict_types=1);

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\ReportController;
use App\Controllers\ApprovalController;
use App\Controllers\FinanceController;
use App\Controllers\ReimbursementController;
use App\Controllers\SettingController;
use App\Controllers\AnalyticsController;

return function (Router $router): void {
    // Guest / Auth routes
    $router->get('/', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'processLogin'], [GuestMiddleware::class, CsrfMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('/logout', [AuthController::class, 'logout']);

    // Dashboard
    $router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

    // Expenses
    $router->get('/expenses', [ExpenseController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/expenses/create', [ExpenseController::class, 'create'], [AuthMiddleware::class]);
    $router->post('/expenses', [ExpenseController::class, 'store'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/expenses/{id}', [ExpenseController::class, 'show'], [AuthMiddleware::class]);
    $router->get('/expenses/{id}/edit', [ExpenseController::class, 'edit'], [AuthMiddleware::class]);
    $router->post('/expenses/{id}/update', [ExpenseController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/expenses/{id}/delete', [ExpenseController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/expenses/{id}/receipt', [ExpenseController::class, 'uploadReceipt'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/expenses/{id}/receipt', [ExpenseController::class, 'downloadReceipt'], [AuthMiddleware::class]);
    $router->post('/expenses/{id}/receipt/remove', [ExpenseController::class, 'removeReceipt'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Reports
    $router->get('/reports', [ReportController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/reports/create', [ReportController::class, 'create'], [AuthMiddleware::class]);
    $router->post('/reports', [ReportController::class, 'store'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/reports/{id}', [ReportController::class, 'show'], [AuthMiddleware::class]);
    $router->get('/reports/{id}/edit', [ReportController::class, 'edit'], [AuthMiddleware::class]);
    $router->post('/reports/{id}/update', [ReportController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/items', [ReportController::class, 'addItem'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/items/{expenseId}/remove', [ReportController::class, 'removeItem'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/submit', [ReportController::class, 'submit'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/revise', [ReportController::class, 'revise'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/comments', [ReportController::class, 'addComment'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Approvals
    $router->get('/approvals', [ApprovalController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/reports/{id}/approve', [ApprovalController::class, 'approve'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reports/{id}/refuse', [ApprovalController::class, 'refuse'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Finance / Posting
    $router->get('/finance/posting', [FinanceController::class, 'postingQueue'], [AuthMiddleware::class]);
    $router->post('/reports/{id}/post', [FinanceController::class, 'postReport'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Reimbursements
    $router->get('/reimbursements', [ReimbursementController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/reports/{id}/reimbursement', [ReimbursementController::class, 'createQueue'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reimbursements/{id}/processing', [ReimbursementController::class, 'setProcessing'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reimbursements/{id}/paid', [ReimbursementController::class, 'markPaid'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/reimbursements/{id}/failed', [ReimbursementController::class, 'markFailed'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->get('/reimbursements/history', [ReimbursementController::class, 'history'], [AuthMiddleware::class]);

    // Settings / Master Data
    $router->get('/settings/users', [SettingController::class, 'users'], [AuthMiddleware::class]);
    $router->post('/settings/users', [SettingController::class, 'createUser'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/settings/users/{id}/update', [SettingController::class, 'updateUser'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/settings/categories', [SettingController::class, 'categories'], [AuthMiddleware::class]);
    $router->post('/settings/categories', [SettingController::class, 'createCategory'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/settings/categories/{id}/update', [SettingController::class, 'updateCategory'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/settings/departments', [SettingController::class, 'departments'], [AuthMiddleware::class]);
    $router->post('/settings/departments', [SettingController::class, 'createDepartment'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/settings/departments/{id}/update', [SettingController::class, 'updateDepartment'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $router->get('/settings/audit-logs', [SettingController::class, 'auditLogs'], [AuthMiddleware::class]);

    // Analytics & Exports
    $router->get('/analytics', [AnalyticsController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/exports/expenses.csv', [AnalyticsController::class, 'exportExpensesCsv'], [AuthMiddleware::class]);
    $router->get('/exports/reports.csv', [AnalyticsController::class, 'exportReportsCsv'], [AuthMiddleware::class]);
};
