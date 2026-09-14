-- 004: group conversations
ALTER TABLE conversations
  ADD COLUMN is_group   TINYINT(1) NOT NULL DEFAULT 0 AFTER id,
  ADD COLUMN title      VARCHAR(80) NOT NULL DEFAULT '' AFTER is_group,
  ADD COLUMN photo_path VARCHAR(190) NULL AFTER title,
  ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER photo_path;

ALTER TABLE conversation_participants
  ADD COLUMN role      ENUM('member','admin') NOT NULL DEFAULT 'member' AFTER user_id,
  ADD COLUMN added_by  BIGINT UNSIGNED NULL AFTER role,
  ADD COLUMN joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER added_by;

ALTER TABLE messages
  ADD COLUMN kind VARCHAR(16) NOT NULL DEFAULT 'text' AFTER sender_id;
