-- End-to-end encrypted direct messages (stage 1: one-to-one chats).
-- Public keys are public; private keys never reach the server unencrypted.
-- e2e_backups.keyring is the user's private keys encrypted in their browser
-- with a key derived from their recovery PIN (PBKDF2-SHA256).

CREATE TABLE IF NOT EXISTS e2e_keys (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    public_key  VARCHAR(255) NOT NULL,          -- base64 SPKI, ECDH P-256
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_e2e_keys_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS e2e_backups (
    user_id     BIGINT UNSIGNED NOT NULL,
    keyring     MEDIUMTEXT NOT NULL,            -- base64url(iv) ":" base64url(AES-GCM ciphertext)
    salt        VARCHAR(64) NOT NULL,
    iterations  INT UNSIGNED NOT NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encrypted bodies are longer than the plaintext they carry.
ALTER TABLE messages MODIFY body TEXT NOT NULL;

-- For reports on encrypted messages: the reporter's browser shares the
-- decrypted text it is reporting, since the server cannot read it.
ALTER TABLE reports ADD COLUMN evidence TEXT NULL AFTER note;
