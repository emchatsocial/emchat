ALTER TABLE users
  ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER discoverable,
  ADD COLUMN suspended_at DATETIME NULL AFTER last_seen_at;

CREATE INDEX idx_users_role ON users (role);

ALTER TABLE reports
  ADD COLUMN handled_by BIGINT UNSIGNED NULL AFTER handled_at,
  ADD COLUMN action VARCHAR(30) NOT NULL DEFAULT '' AFTER handled_by;
