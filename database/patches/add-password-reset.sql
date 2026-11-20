
SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_token_hash') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_token_hash CHAR(64) NULL COMMENT ''SHA-256 hex of the emailed reset token; cleared once used'' AFTER verification_expires_at',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_expires_at') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL AFTER password_reset_token_hash',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_password_reset_token') = 0,
    'ALTER TABLE users ADD UNIQUE KEY uq_users_password_reset_token (password_reset_token_hash)',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;


SELECT
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        AND COLUMN_NAME IN ('password_reset_token_hash', 'password_reset_expires_at')) AS password_reset_columns_present,
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        AND INDEX_NAME = 'uq_users_password_reset_token')                              AS password_reset_index_present;
