-- Migration 002: Add bank_name column to expenses table.
-- MySQL 8.0+. Safe and idempotent migration using dynamic SQL.
-- Tracked in schema_migrations.

SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expenses ADD COLUMN bank_name VARCHAR(100) NULL AFTER payment_method',
        'SELECT "bank_name already exists"'
    )
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'expenses'
      AND column_name = 'bank_name'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;
