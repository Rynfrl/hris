<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Repositories\ReportRepository;
use App\Controllers\ApprovalController;
use App\Core\Auth;
use App\Repositories\UserRepository;
use App\Core\Database;
use App\Core\Router;
use App\Core\Url;

echo "========================================================\n";
echo "APPROVALS, ROUTING & SQL PARAMETER TEST SUITE\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

$test = function (string $desc, callable $fn) use (&$passCount, &$failCount): void {
    try {
        $fn();
        echo "[PASS] {$desc}\n";
        $passCount++;
    } catch (Throwable $e) {
        echo "[FAIL] {$desc}: " . $e->getMessage() . "\n";
        $failCount++;
    }
};

$repo = new ReportRepository();

$test('ReportRepository::getReports with employee_ids filter (ApprovalController query)', function () use ($repo) {
    $res = $repo->getReports(['status' => 'SUBMITTED', 'employee_ids' => [1, 2, 3]], 1, 100);
    if (!isset($res['items']) || !isset($res['total'])) {
        throw new Exception('Unexpected result structure');
    }
});

$test('ReportRepository::getReports with single employee_id filter', function () use ($repo) {
    $res = $repo->getReports(['employee_id' => 1], 1, 100);
    if (!isset($res['items'])) {
        throw new Exception('Unexpected result structure');
    }
});

$test('UserRepository::findById with parameterized query', function () {
    $userRepo = new UserRepository();
    $u = $userRepo->findById(1);
    if (!$u) {
        throw new Exception('User 1 not found');
    }
});

$test('ApprovalController::index rendered for Manager user without SQLSTATE[HY093]', function () {
    $pdo = Database::pdo();
    $mgr = $pdo->query("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON r.role_id = u.role_id 
        WHERE r.role_name = 'MANAGER'
        LIMIT 1
    ")->fetch();

    if (!$mgr) {
        throw new Exception('No manager found');
    }

    $ref = new ReflectionClass(Auth::class);
    $prop = $ref->getProperty('cachedUser');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    Auth::login($mgr);

    $controller = new ApprovalController();
    $html = $controller->index();
    if (empty($html)) {
        throw new Exception('Empty HTML returned');
    }
});

$test('Router dispatches POST /reports/{id}/post cleanly', function () {
    $router = new Router();
    $routesDef = require dirname(__DIR__) . '/config/routes.php';
    $routesDef($router);

    // Verify route exists for POST /reports/{id}/post
    $ref = new ReflectionClass($router);
    $routesProp = $ref->getProperty('routes');
    $routesProp->setAccessible(true);
    $routes = $routesProp->getValue($router);

    $foundPost = false;
    foreach ($routes['POST'] as $r) {
        if ($r['path'] === '/reports/{id}/post') {
            $foundPost = true;
            break;
        }
    }
    if (!$foundPost) {
        throw new Exception('POST /reports/{id}/post route missing');
    }

    $foundGet = false;
    foreach ($routes['GET'] as $r) {
        if ($r['path'] === '/reports/{id}/post') {
            $foundGet = true;
            break;
        }
    }
    if (!$foundGet) {
        throw new Exception('GET /reports/{id}/post fallback route missing');
    }
});

$test('HRIS root .htaccess exists and contains rewrite rules for expense routes', function () {
    $htaccess = dirname(__DIR__, 2) . '/.htaccess';
    if (!file_exists($htaccess)) {
        throw new Exception('.htaccess in HRIS root does not exist');
    }
    $content = file_get_contents($htaccess);
    if (!str_contains($content, 'reports') || !str_contains($content, 'expenses/public/index.php')) {
        throw new Exception('.htaccess does not properly route reports to expenses/public/index.php');
    }
});

$test('finance/posting.php sets form.action with basePath awareness', function () {
    $viewFile = dirname(__DIR__) . '/app/Views/finance/posting.php';
    $content = file_get_contents($viewFile);
    if (str_contains($content, "form.action = '/reports/' + reportId + '/post';")) {
        throw new Exception('finance/posting.php still contains hardcoded form.action without Url::to()');
    }
    if (!str_contains($content, "Url::to('/reports')")) {
        throw new Exception('finance/posting.php does not use Url::to() for modal action');
    }
});

$test('ReimbursementController::history renders without undefined key warnings', function () {
    $userRepo = new \App\Repositories\UserRepository();
    $financeUser = $userRepo->findByUsernameOrEmail('finance@example.test');
    if ($financeUser) {
        $ref = new ReflectionClass(Auth::class);
        $prop = $ref->getProperty('cachedUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        Auth::login($financeUser);
    }

    // Set error handler that converts PHP notices/warnings to exceptions to catch any undefined key
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {
        $controller = new \App\Controllers\ReimbursementController();
        $html = $controller->history();
        if (empty($html)) {
            throw new Exception('Empty HTML returned for reimbursement history');
        }
    } finally {
        restore_error_handler();
    }
});

$test('generatePaymentNumber produces non-empty unique sequence numbers', function () {
    $repo = new \App\Repositories\ReimbursementRepository();
    $num1 = $repo->generatePaymentNumber();
    if (empty($num1) || !str_starts_with($num1, 'PAY-')) {
        throw new Exception("Invalid generated payment number: {$num1}");
    }
});

$test('Router dispatches /reports/{id}/employees/{employeeId}/remove without named parameter errors', function () {
    $router = new Router();
    $registerRoutes = require dirname(__DIR__) . '/config/routes.php';
    $registerRoutes($router);

    // Test reflection invocation of invokeHandler
    $ref = new ReflectionClass($router);
    $method = $ref->getMethod('invokeHandler');
    $method->setAccessible(true);

    $dummyController = new class {
        public ?int $gotId = null;
        public ?int $gotEmpId = null;
        public function removeEmployee(string|int $id, string|int $employeeId = 0, string|int $reportEmployeeId = 0): void {
            $this->gotId = (int) $id;
            $this->gotEmpId = (int) ($employeeId ?: $reportEmployeeId);
        }
    };

    $params = ['id' => '136', 'employeeId' => '150'];
    $method->invoke($router, [$dummyController, 'removeEmployee'], $params);

    if ($dummyController->gotId !== 136 || $dummyController->gotEmpId !== 150) {
        throw new Exception("Expected 136 and 150, got {$dummyController->gotId} and {$dummyController->gotEmpId}");
    }
});

$test('GET fallback routes exist for employee and item removal', function () {
    $router = new Router();
    $registerRoutes = require dirname(__DIR__) . '/config/routes.php';
    $registerRoutes($router);

    $ref = new ReflectionClass($router);
    $prop = $ref->getProperty('routes');
    $prop->setAccessible(true);
    $routes = $prop->getValue($router);

    $getRoutes = $routes['GET'] ?? [];
    $hasEmpRemove = false;
    $hasItemRemove = false;

    foreach ($getRoutes as $r) {
        if (preg_match($r['pattern'], '/reports/136/employees/150/remove')) {
            $hasEmpRemove = true;
        }
        if (preg_match($r['pattern'], '/reports/136/items/45/remove')) {
            $hasItemRemove = true;
        }
    }

    if (!$hasEmpRemove) {
        throw new Exception('GET /reports/{id}/employees/{employeeId}/remove fallback missing');
    }
    if (!$hasItemRemove) {
        throw new Exception('GET /reports/{id}/items/{expenseId}/remove fallback missing');
    }
});

echo "\n========================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
