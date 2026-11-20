
CREATE TABLE IF NOT EXISTS login_attempts (
    throttle_key       CHAR(64)     NOT NULL,
    attempts           INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at  DATETIME     NOT NULL,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (throttle_key),
    KEY idx_login_attempts_window (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT COUNT(*) AS login_attempts_table_present
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_attempts';
