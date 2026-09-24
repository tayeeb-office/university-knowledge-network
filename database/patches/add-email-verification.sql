-- Adds email verification to users (Phase A authentication).
--
-- Additive and idempotent: each column/index is only added if it is missing, no existing
-- row or constraint is changed. Existing users keep email_verified_at = NULL (unverified).
--
--   email_verified_at        NULL until the user clicks their verification link
--   verification_token_hash  SHA-256 hex of the emailed token (the raw token is never stored);
--                            cleared on successful verification so each token is single-use
--   verification_expires_at  token expiry

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at') = 0,
    'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER password_hash',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_token_hash') = 0,
    'ALTER TABLE users ADD COLUMN verification_token_hash CHAR(64) NULL AFTER email_verified_at',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_expires_at') = 0,
    'ALTER TABLE users ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_token_hash',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_verification_token') = 0,
    'ALTER TABLE users ADD UNIQUE KEY uq_users_verification_token (verification_token_hash)',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;


SELECT
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        AND COLUMN_NAME IN ('email_verified_at', 'verification_token_hash', 'verification_expires_at')) AS verification_columns_present,
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        AND INDEX_NAME = 'uq_users_verification_token')                                                 AS verification_index_present;
