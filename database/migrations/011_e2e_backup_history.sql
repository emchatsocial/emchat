-- Keep every encrypted backup instead of replacing it, so a PIN the user
-- used earlier (before starting fresh on a device) can still restore the
-- keys for their older messages.
ALTER TABLE e2e_backups
  DROP PRIMARY KEY,
  ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  ADD KEY idx_e2e_backups_user (user_id, id);
