-- Migration: 005_reimbursement_failures_and_event_approvals.sql
-- Description: Add audit trail table for failed reimbursement attempts and retry tracking

CREATE TABLE IF NOT EXISTS `expense_reimbursement_failures` (
  `failure_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reimbursement_id` bigint unsigned NOT NULL,
  `report_id` bigint unsigned NOT NULL,
  `previous_status` varchar(50) NOT NULL DEFAULT 'PROCESSING',
  `new_status` varchar(50) NOT NULL DEFAULT 'FAILED',
  `failure_reason` text NOT NULL,
  `failed_by` bigint unsigned NOT NULL,
  `payment_reference` varchar(150) DEFAULT NULL,
  `attempt_number` int unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`failure_id`),
  KEY `idx_reimb_fail_reimb` (`reimbursement_id`),
  KEY `idx_reimb_fail_report` (`report_id`),
  KEY `idx_reimb_fail_user` (`failed_by`),
  CONSTRAINT `fk_reimb_fail_reimb` FOREIGN KEY (`reimbursement_id`) REFERENCES `reimbursements` (`reimbursement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_fail_report` FOREIGN KEY (`report_id`) REFERENCES `expense_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reimb_fail_user` FOREIGN KEY (`failed_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
