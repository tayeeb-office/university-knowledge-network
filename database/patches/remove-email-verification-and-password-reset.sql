-- Removes email verification and password reset from an existing database.
-- The application no longer uses these columns; every account can log in right after
-- registration (existing unverified accounts included). Safe to run more than once.
-- Usage: mysql -u <db-user> -p ukn_database < database/patches/remove-email-verification-and-password-reset.sql

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_verification_token') > 0,
    'ALTER TABLE users DROP INDEX uq_users_verification_token',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_password_reset_token') > 0,
    'ALTER TABLE users DROP INDEX uq_users_password_reset_token',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_expires_at') > 0,
    'ALTER TABLE users DROP COLUMN password_reset_expires_at',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_reset_token_hash') > 0,
    'ALTER TABLE users DROP COLUMN password_reset_token_hash',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_expires_at') > 0,
    'ALTER TABLE users DROP COLUMN verification_expires_at',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_token_hash') > 0,
    'ALTER TABLE users DROP COLUMN verification_token_hash',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at') > 0,
    'ALTER TABLE users DROP COLUMN email_verified_at',
    'DO 0');
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SELECT COUNT(*) AS leftover_columns
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
  AND COLUMN_NAME IN ('email_verified_at', 'verification_token_hash', 'verification_expires_at',
                      'password_reset_token_hash', 'password_reset_expires_at');
