<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Url;
use App\Core\Router;

$passed = 0;
$failed = 0;

function assertCondition(string $desc, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $desc\n";
        $passed++;
    } else {
        echo "[FAIL] $desc\n";
        $failed++;
    }
}

echo "=== Testing Sidebar Link Active Functionality ===\n\n";

// Scenario 1: App hosted under /expenses (http://hris.test/expenses/...)
$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';

// Test 1.1: On Dashboard (/expenses/dashboard)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/dashboard';
assertCondition("Base path detected as /expenses", Url::basePath() === '/expenses');
assertCondition("Current path detected as /dashboard", Url::currentPath() === '/dashboard');
assertCondition("Dashboard is active on /expenses/dashboard", Url::isActive('/dashboard') === true);
assertCondition("Expenses is NOT active on /expenses/dashboard", Url::isActive('/expenses') === false);
assertCondition("Reports is NOT active on /expenses/dashboard", Url::isActive('/reports') === false);
assertCondition("Approvals is NOT active on /expenses/dashboard", Url::isActive('/approvals') === false);
assertCondition("Finance is NOT active on /expenses/dashboard", Url::isActive('/finance') === false);
assertCondition("Reimbursements is NOT active on /expenses/dashboard", Url::isActive('/reimbursements') === false);
assertCondition("Analytics is NOT active on /expenses/dashboard", Url::isActive('/analytics') === false);
assertCondition("Settings Users is NOT active on /expenses/dashboard", Url::isActive('/settings/users') === false);

// Test 1.2: Root of expenses module (/expenses or /expenses/)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses';
assertCondition("Dashboard is active on /expenses root", Url::isActive('/dashboard') === true);
assertCondition("Expenses is NOT active on /expenses root", Url::isActive('/expenses') === false);

Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/';
assertCondition("Dashboard is active on /expenses/ root", Url::isActive('/dashboard') === true);
assertCondition("Expenses is NOT active on /expenses/ root", Url::isActive('/expenses') === false);

// Test 1.3: On Expenses list (/expenses/expenses)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/expenses';
assertCondition("Expenses is active on /expenses/expenses", Url::isActive('/expenses') === true);
assertCondition("Dashboard is NOT active on /expenses/expenses", Url::isActive('/dashboard') === false);

// Test 1.4: On Expense Create (/expenses/expenses/create)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/expenses/create';
assertCondition("Expenses is active on /expenses/expenses/create", Url::isActive('/expenses') === true);
assertCondition("Dashboard is NOT active on /expenses/expenses/create", Url::isActive('/dashboard') === false);

// Test 1.5: On Expense Detail (/expenses/expenses/42)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/expenses/42';
assertCondition("Expenses is active on /expenses/expenses/42", Url::isActive('/expenses') === true);

// Test 1.6: On Reports list (/expenses/reports)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/reports';
assertCondition("Reports is active on /expenses/reports", Url::isActive('/reports') === true);
assertCondition("Expenses is NOT active on /expenses/reports", Url::isActive('/expenses') === false);
assertCondition("Dashboard is NOT active on /expenses/reports", Url::isActive('/dashboard') === false);

// Test 1.7: On Reports Detail (/expenses/reports/15)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/reports/15';
assertCondition("Reports is active on /expenses/reports/15", Url::isActive('/reports') === true);
assertCondition("Expenses is NOT active on /expenses/reports/15", Url::isActive('/expenses') === false);

// Test 1.8: On Approvals (/expenses/approvals)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/approvals';
assertCondition("Approvals is active on /expenses/approvals", Url::isActive('/approvals') === true);
assertCondition("Dashboard is NOT active on /expenses/approvals", Url::isActive('/dashboard') === false);
assertCondition("Expenses is NOT active on /expenses/approvals", Url::isActive('/expenses') === false);

// Test 1.9: On Finance Posting (/expenses/finance/posting)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/finance/posting';
assertCondition("Finance is active on /expenses/finance/posting", Url::isActive('/finance') === true);
assertCondition("Dashboard is NOT active on /expenses/finance/posting", Url::isActive('/dashboard') === false);
assertCondition("Expenses is NOT active on /expenses/finance/posting", Url::isActive('/expenses') === false);

// Test 1.10: On Reimbursements (/expenses/reimbursements)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/reimbursements';
assertCondition("Reimbursements is active on /expenses/reimbursements", Url::isActive('/reimbursements') === true);
assertCondition("Dashboard is NOT active on /expenses/reimbursements", Url::isActive('/dashboard') === false);

// Test 1.11: On Reimbursements History (/expenses/reimbursements/history)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/reimbursements/history';
assertCondition("Reimbursements is active on /expenses/reimbursements/history", Url::isActive('/reimbursements') === true);

// Test 1.12: On Analytics (/expenses/analytics)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/analytics';
assertCondition("Analytics is active on /expenses/analytics", Url::isActive('/analytics') === true);
assertCondition("Dashboard is NOT active on /expenses/analytics", Url::isActive('/dashboard') === false);

// Test 1.13: On Settings Users (/expenses/settings/users)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/settings/users';
assertCondition("Settings Users is active on /expenses/settings/users", Url::isActive('/settings/users') === true);
assertCondition("Settings Categories is NOT active on /expenses/settings/users", Url::isActive('/settings/categories') === false);
assertCondition("Settings Departments is NOT active on /expenses/settings/users", Url::isActive('/settings/departments') === false);
assertCondition("Settings Audit Logs is NOT active on /expenses/settings/users", Url::isActive('/settings/audit-logs') === false);

// Test 1.14: On Settings Categories (/expenses/settings/categories)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/settings/categories';
assertCondition("Settings Categories is active on /expenses/settings/categories", Url::isActive('/settings/categories') === true);
assertCondition("Settings Users is NOT active on /expenses/settings/categories", Url::isActive('/settings/users') === false);

// Test 1.15: On Settings Departments (/expenses/settings/departments)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/settings/departments';
assertCondition("Settings Departments is active on /expenses/settings/departments", Url::isActive('/settings/departments') === true);

// Test 1.16: On Settings Audit Logs (/expenses/settings/audit-logs)
Url::reset();
$_SERVER['REQUEST_URI'] = '/expenses/settings/audit-logs';
assertCondition("Settings Audit Logs is active on /expenses/settings/audit-logs", Url::isActive('/settings/audit-logs') === true);

// Scenario 2: App hosted under /hris/expenses
$_SERVER['SCRIPT_NAME'] = '/hris/expenses/public/index.php';
Url::reset();
$_SERVER['REQUEST_URI'] = '/hris/expenses/dashboard';
assertCondition("Base path detected as /hris/expenses", Url::basePath() === '/hris/expenses');
assertCondition("Current path detected as /dashboard on /hris/expenses/dashboard", Url::currentPath() === '/dashboard');
assertCondition("Dashboard is active on /hris/expenses/dashboard", Url::isActive('/dashboard') === true);
assertCondition("Expenses is NOT active on /hris/expenses/dashboard", Url::isActive('/expenses') === false);

// Scenario 3: Router dispatch integration
Url::reset();
$router = new Router();
$router->get('/dashboard', function() { return 'dashboard_content'; });
$router->get('/expenses', function() { return 'expenses_content'; });

$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';
$_SERVER['REQUEST_URI'] = '/expenses/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$router->dispatch('GET', '/expenses/dashboard');
assertCondition("Router dispatch sets current path to /dashboard", Url::currentPath() === '/dashboard');
assertCondition("Dashboard active after Router dispatch", Url::isActive('/dashboard') === true);
assertCondition("Expenses NOT active after Router dispatch to dashboard", Url::isActive('/expenses') === false);

// Scenario 4: Full Layout View::render HTML output test
use App\Core\Auth;
use App\Core\View;
use App\Core\Session;

Session::start();
$_SESSION['user'] = [
    'id' => 1,
    'employee_id' => 1,
    'employee_name' => 'admin_gte',
    'username' => 'admin_gte',
    'role_name' => 'ADMIN',
    'role' => 'ADMIN',
    'permissions' => ['*']
];
$_SERVER['SCRIPT_NAME'] = '/expenses/public/index.php';
$_SERVER['REQUEST_URI'] = '/expenses/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
Url::reset();

$html = View::render('dashboard/index', [
    'title' => 'Dashboard',
    'user' => Auth::user(),
    'metrics' => [
        'users_count' => 12,
        'categories_count' => 5,
        'expenses_count' => 9,
        'total_paid' => 570000,
        'monthly_trend' => [],
        'category_distribution' => [],
    ]
], 'layouts/main');

assertCondition("Rendered HTML has active class on /expenses/dashboard link", str_contains($html, 'href="/expenses/dashboard" class="sidebar-link active"'));
assertCondition("Rendered HTML does NOT have active class on /expenses link", !str_contains($html, 'href="/expenses" class="sidebar-link active"'));
assertCondition("Rendered HTML has regular sidebar-link on /expenses", str_contains($html, 'href="/expenses" class="sidebar-link "'));

// Test when on expenses page
$_SERVER['REQUEST_URI'] = '/expenses/expenses';
Url::reset();
$htmlExpenses = View::render('expenses/index', [
    'title' => 'Pengeluaran Saya',
    'expenses' => [],
    'total' => 0,
    'page' => 1,
    'totalPages' => 1,
    'filters' => [],
    'categories' => [],
    'user' => Auth::user(),
], 'layouts/main');

assertCondition("Rendered HTML has active class on /expenses link when on /expenses/expenses", str_contains($htmlExpenses, 'href="/expenses" class="sidebar-link active"'));
assertCondition("Rendered HTML does NOT have active class on /expenses/dashboard link when on /expenses/expenses", !str_contains($htmlExpenses, 'href="/expenses/dashboard" class="sidebar-link active"'));

echo "\nSummary: $passed passed, $failed failed.\n";
if ($failed > 0) {
    exit(1);
}
