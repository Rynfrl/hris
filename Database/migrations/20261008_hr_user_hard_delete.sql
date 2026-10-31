-- Apply once to an existing hris database before using permanent user deletion.
-- Business records are retained; user attribution is set to NULL when a user is deleted.
-- The users.deleted_at column is intentionally kept and remains NULL for hard-deleted users.

ALTER TABLE `goods_receipts`
  DROP FOREIGN KEY `fk_grn_user`;

ALTER TABLE `purchase_requisitions`
  DROP FOREIGN KEY `fk_pr_approver`,
  DROP FOREIGN KEY `fk_pr_requester`;

ALTER TABLE `purchase_returns`
  DROP FOREIGN KEY `fk_pret_user`;

ALTER TABLE `purchase_transactions`
  DROP FOREIGN KEY `fk_pt_user`;

ALTER TABLE `vendor_invoices`
  DROP FOREIGN KEY `fk_vi_approved_by`,
  DROP FOREIGN KEY `fk_vi_entered_by`;

ALTER TABLE `employees`
  MODIFY `id_user` int UNSIGNED DEFAULT NULL;

ALTER TABLE `goods_receipts`
  MODIFY `received_by` int UNSIGNED DEFAULT NULL;

ALTER TABLE `purchase_requisitions`
  MODIFY `requester_id` int UNSIGNED DEFAULT NULL;

ALTER TABLE `purchase_returns`
  MODIFY `created_by` int UNSIGNED DEFAULT NULL;

ALTER TABLE `purchase_transactions`
  MODIFY `user_id` int UNSIGNED DEFAULT NULL;

ALTER TABLE `vendor_invoices`
  MODIFY `entered_by` int UNSIGNED DEFAULT NULL,
  MODIFY `approved_by` int UNSIGNED DEFAULT NULL;

ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `fk_grn_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `purchase_requisitions`
  ADD CONSTRAINT `fk_pr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pr_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `fk_pret_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `purchase_transactions`
  ADD CONSTRAINT `fk_pt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `vendor_invoices`
  ADD CONSTRAINT `fk_vi_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vi_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
