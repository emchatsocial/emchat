-- 002: message attachments, reports, edited posts
CREATE TABLE IF NOT EXISTS message_attachments (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id  BIGINT UNSIGNED NOT NULL,
    kind        ENUM('image','video','audio','file') NOT NULL DEFAULT 'file',
    path        VARCHAR(190) NOT NULL,
    name        VARCHAR(190) NOT NULL DEFAULT '',
    mime        VARCHAR(100) NOT NULL DEFAULT '',
    size        INT UNSIGNED NOT NULL DEFAULT 0,
    width       INT UNSIGNED NOT NULL DEFAULT 0,
    height      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_ma_message (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id   BIGINT UNSIGNED NOT NULL,
    subject_type  ENUM('post','user','message') NOT NULL,
    subject_id    BIGINT UNSIGNED NOT NULL,
    reason        VARCHAR(60) NOT NULL DEFAULT '',
    note          VARCHAR(500) NOT NULL DEFAULT '',
    handled_at    DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_report (reporter_id, subject_type, subject_id),
    KEY idx_reports_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE messages MODIFY body VARCHAR(4000) NOT NULL DEFAULT '';
