-- 003: message edit / delete / reply
ALTER TABLE messages
  ADD COLUMN reply_to_id BIGINT UNSIGNED NULL AFTER sender_id,
  ADD COLUMN edited_at   DATETIME NULL AFTER created_at,
  ADD COLUMN deleted_at  DATETIME NULL AFTER edited_at;

CREATE TABLE IF NOT EXISTS message_hides (
    message_id BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
