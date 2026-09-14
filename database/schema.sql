-- EMChat Media — database schema (MySQL 5.7+ / MariaDB 10.3+)
-- Charset utf8mb4 throughout. Safe to run on Namecheap shared MySQL.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS users (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(30)  NOT NULL,
    display_name    VARCHAR(60)  NOT NULL,
    email           VARCHAR(190) NOT NULL,
    bio             VARCHAR(280) NOT NULL DEFAULT '',
    location        VARCHAR(80)  NOT NULL DEFAULT '',
    website         VARCHAR(190) NOT NULL DEFAULT '',
    avatar_path     VARCHAR(190) NULL,
    is_private      TINYINT(1)   NOT NULL DEFAULT 0,
    discoverable    TINYINT(1)   NOT NULL DEFAULT 1,  -- appears in search/sitemap
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    email_verified_at DATETIME   NULL,
    last_seen_at    DATETIME     NULL,
    suspended_at    DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_tokens (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(190) NOT NULL,
    token_hash   CHAR(64)     NOT NULL,
    purpose      VARCHAR(20)  NOT NULL DEFAULT 'login',
    expires_at   DATETIME     NOT NULL,
    consumed_at  DATETIME     NULL,
    created_ip   VARBINARY(16) NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_tokens_hash (token_hash),
    KEY idx_login_tokens_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NOT NULL,
    body         VARCHAR(2000) NOT NULL DEFAULT '',
    visibility   ENUM('public','followers','private') NOT NULL DEFAULT 'public',
    reply_to_id  BIGINT UNSIGNED NULL,
    like_count   INT UNSIGNED NOT NULL DEFAULT 0,
    reply_count  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at    DATETIME NULL,
    deleted_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_posts_user (user_id, id),
    KEY idx_posts_visibility (visibility, id),
    KEY idx_posts_reply (reply_to_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_media (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id    BIGINT UNSIGNED NOT NULL,
    path       VARCHAR(190) NOT NULL,
    width      INT UNSIGNED NOT NULL DEFAULT 0,
    height     INT UNSIGNED NOT NULL DEFAULT 0,
    alt        VARCHAR(280) NOT NULL DEFAULT '',
    position   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_post_media_post (post_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS follows (
    follower_id  BIGINT UNSIGNED NOT NULL,
    followee_id  BIGINT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted') NOT NULL DEFAULT 'accepted',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, followee_id),
    KEY idx_follows_followee (followee_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS likes (
    user_id    BIGINT UNSIGNED NOT NULL,
    post_id    BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    KEY idx_likes_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id    BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    parent_id  BIGINT UNSIGNED NULL,
    body       VARCHAR(1000) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_comments_post (post_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blocks (
    blocker_id BIGINT UNSIGNED NOT NULL,
    blocked_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (blocker_id, blocked_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mutes (
    muter_id  BIGINT UNSIGNED NOT NULL,
    muted_id  BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (muter_id, muted_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,   -- recipient
    actor_id    BIGINT UNSIGNED NOT NULL,
    type        VARCHAR(20) NOT NULL,       -- like, follow, follow_request, comment, mention, message
    subject_id  BIGINT UNSIGNED NULL,       -- post id / comment id / conversation id
    read_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversations (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    is_group   TINYINT(1) NOT NULL DEFAULT 0,
    title      VARCHAR(80) NOT NULL DEFAULT '',
    photo_path VARCHAR(190) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversation_participants (
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    role            ENUM('member','admin') NOT NULL DEFAULT 'member',
    state           VARCHAR(12) NOT NULL DEFAULT 'active',  -- 'active' | 'request'
    added_by        BIGINT UNSIGNED NULL,
    joined_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_read_at    DATETIME NULL,
    cleared_at      DATETIME NULL,   -- "Delete chat": hide messages before this
    PRIMARY KEY (conversation_id, user_id),
    KEY idx_cp_user (user_id),
    KEY idx_cp_user_state (user_id, state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id       BIGINT UNSIGNED NOT NULL,
    kind            VARCHAR(16) NOT NULL DEFAULT 'text',
    reply_to_id     BIGINT UNSIGNED NULL,
    body            VARCHAR(4000) NOT NULL DEFAULT '',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at       DATETIME NULL,
    deleted_at      DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_messages_conv (conversation_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_hides (
    message_id BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    handled_by    BIGINT UNSIGNED NULL,
    action        VARCHAR(30) NOT NULL DEFAULT '',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_report (reporter_id, subject_type, subject_id),
    KEY idx_reports_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket     VARCHAR(120) NOT NULL,
    hits       INT UNSIGNED NOT NULL DEFAULT 0,
    window_end DATETIME NOT NULL,
    PRIMARY KEY (bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
