<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load(dirname(__DIR__) . '/.env');

echo "=== Expenses Migration Runner ===\n";

try {
    $pdo = Database::pdo();
    echo "[OK] Connected to database: " . $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n";

    // 1. Ensure schema_migrations table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            migration_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(190) NOT NULL UNIQUE,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // 2. Read migrations directory
    $migrationsDir = dirname(__DIR__) . '/database/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    foreach ($files as $filePath) {
        $migrationName = basename($filePath);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM schema_migrations WHERE migration_name = :name");
        $stmt->execute(['name' => $migrationName]);
        if ((int) $stmt->fetchColumn() > 0) {
            echo "[SKIP] Migration {$migrationName} already applied.\n";
            continue;
        }

        echo "[RUN] Applying migration: {$migrationName}...\n";

        // Precheck for 001_gt_expense_integrity.sql
        if ($migrationName === '001_gt_expense_integrity.sql') {
            $chk1 = $pdo->query("
                SELECT expense_id, COUNT(*) AS n 
                FROM expense_report_items 
                GROUP BY expense_id 
                HAVING COUNT(*) > 1
            ")->fetchAll();

            $chk2 = $pdo->query("
                SELECT report_id, COUNT(*) AS n 
                FROM reimbursements 
                GROUP BY report_id 
                HAVING COUNT(*) > 1
            ")->fetchAll();

            if (!empty($chk1) || !empty($chk2)) {
                throw new RuntimeException("Precheck failed for {$migrationName}: duplicate expense or reimbursement records found!");
            }
            echo "  [PRECHECK] Passed: No duplicates found.\n";
        }

        // Execute migration SQL
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new RuntimeException("Cannot read migration file: {$filePath}");
        }

        // MySQL multiple statements execution
        $pdo->exec($sql);

        // Record in schema_migrations
        $recordStmt = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (:name)");
        $recordStmt->execute(['name' => $migrationName]);

        echo "[DONE] Migration {$migrationName} applied successfully!\n";
    }

    echo "=== All migrations completed successfully! ===\n";
} catch (Throwable $e) {
    echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
