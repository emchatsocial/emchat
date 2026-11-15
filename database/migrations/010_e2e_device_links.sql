-- Linking a new device (like WhatsApp's linked devices): the new browser asks,
-- one of the user's existing browsers approves and sends the user's keys,
-- encrypted to a one-time key of the new browser. The server only relays
-- ciphertext and cannot read it.

CREATE TABLE IF NOT EXISTS e2e_links (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    requester_pub  VARCHAR(255) NOT NULL,         -- one-time ECDH P-256 key of the new device (base64 SPKI)
    device         VARCHAR(80)  NOT NULL DEFAULT '',
    status         VARCHAR(12)  NOT NULL DEFAULT 'pending',  -- pending | approved | denied | used
    approver_pub   VARCHAR(255) NULL,
    payload        MEDIUMTEXT   NULL,             -- keys encrypted for the new device
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_e2e_links_user (user_id, status, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
