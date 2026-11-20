<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Repositories\ReportRepository;
use App\Controllers\ApprovalController;
use App\Core\Auth;
use App\Repositories\UserRepository;
use App\Core\Database;

echo "========================================================\n";
echo "MANAGER APPROVALS & SQL PARAMETER INTEGRITY TEST SUITE\n";
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

echo "\n========================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
