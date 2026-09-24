-- Phase J (J1): login throttling state, shared by all PHP sessions and requests.
--
-- Why a new table: throttling must also count attempts against emails that have no account
-- (otherwise the limiter itself would reveal which emails exist), so it cannot live on
-- `users`; and per-account counters on `users` would turn lockout into a denial-of-service
-- tool. Each row is one throttle key: SHA-256 of "scope|value" (email, email+IP or IP), so no
-- raw email address or IP is stored. No foreign keys; rows expire with their window.
-- Additive and idempotent: safe to run again, touches no existing table or row.

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
