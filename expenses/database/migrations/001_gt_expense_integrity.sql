-- Migration 001: GT Expense integrity and report comments.
-- MySQL 8.0+. Track this migration in schema_migrations.
--
-- PRECHECK BEFORE APPLYING:
-- 1. Verify no expense_id appears in more than one expense_report_items row:
--    SELECT expense_id, COUNT(*) AS n FROM expense_report_items
--    GROUP BY expense_id HAVING COUNT(*) > 1;
-- 2. Verify no report_id has multiple reimbursement rows:
--    SELECT report_id, COUNT(*) AS n FROM reimbursements
--    GROUP BY report_id HAVING COUNT(*) > 1;
-- Resolve any returned rows before adding unique indexes. Back up first.
-- Conditional index creation allows recovery if a previous run stopped after DDL.

SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE expense_report_items ADD UNIQUE KEY uq_expense_report_items_expense (expense_id)',
        'SELECT "uq_expense_report_items_expense already exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'expense_report_items'
      AND index_name = 'uq_expense_report_items_expense'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

SET @gt_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE reimbursements ADD UNIQUE KEY uq_reimbursements_report (report_id)',
        'SELECT "uq_reimbursements_report already exists"'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reimbursements'
      AND index_name = 'uq_reimbursements_report'
);
PREPARE gt_stmt FROM @gt_sql;
EXECUTE gt_stmt;
DEALLOCATE PREPARE gt_stmt;

CREATE TABLE IF NOT EXISTS expense_comments (
    comment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    comment_body TEXT NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_comments_report_created (report_id, created_at),
    CONSTRAINT fk_expense_comments_report
        FOREIGN KEY (report_id) REFERENCES expense_reports(report_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_expense_comments_author
        FOREIGN KEY (author_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
