<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load(dirname(__DIR__) . '/.env');

echo "=== Expenses Roles & Permissions Seed Runner ===\n";

try {
    $pdo = Database::pdo();
    $sqlFile = dirname(__DIR__) . '/database/seeds/gt_expense_roles_permissions.sql';

    if (!file_exists($sqlFile)) {
        throw new RuntimeException("Seed file not found: {$sqlFile}");
    }

    echo "[RUN] Executing {$sqlFile}...\n";
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    echo "[OK] Roles and permissions seeded successfully!\n";

    // Verify
    $roles = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll();
    echo "Current Roles:\n";
    foreach ($roles as $r) {
        echo " - [{$r['role_id']}] {$r['role_name']}\n";
    }

    $perms = $pdo->query("SELECT permission_id, permission_key FROM permissions WHERE permission_key LIKE 'expense%'")->fetchAll();
    echo "Expense Permissions:\n";
    foreach ($perms as $p) {
        echo " - [{$p['permission_id']}] {$p['permission_key']}\n";
    }
} catch (Throwable $e) {
    echo "[ERROR] Seed failed: " . $e->getMessage() . "\n";
    exit(1);
}
