

CREATE TABLE IF NOT EXISTS mentor_applications (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    application_message VARCHAR(1000) NULL,
    requested_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at         DATETIME NULL,
    reviewed_by         INT UNSIGNED NULL,
    admin_note          VARCHAR(255) NULL,
    pending_user_id     INT UNSIGNED AS (IF(status = 'pending', user_id, NULL)) PERSISTENT
                        COMMENT 'user_id while pending, else NULL (one pending application per user)',

    PRIMARY KEY (id),
    UNIQUE KEY uq_mentor_applications_pending (pending_user_id),
    KEY idx_mentor_applications_queue (status, requested_at),
    KEY idx_mentor_applications_user (user_id, requested_at),

    CONSTRAINT fk_mentor_applications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_mentor_applications_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_mentor_applications_reviewed
        CHECK (status = 'pending' OR reviewed_at IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
        AND COLUMN_TYPE LIKE '%''mentor''%') = 1,
    'UPDATE users SET role = ''dual'', updated_at = updated_at WHERE role = ''mentor''',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
        AND COLUMN_TYPE LIKE '%''mentor''%') = 1
    AND (SELECT COUNT(*) FROM users WHERE CAST(role AS CHAR) = 'mentor') = 0,
    'ALTER TABLE users MODIFY role ENUM(''learner'',''dual'') NOT NULL DEFAULT ''learner'' COMMENT ''learner = learner only; dual = learner + mentor (approved mentor application)''',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'chk_users_role') = 0
    AND (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
        AND COLUMN_TYPE = 'enum(''learner'',''dual'')') = 1,
    'ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN (''learner'', ''dual''))',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;


SELECT
    (SELECT COLUMN_TYPE FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role') AS role_column,
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mentor_applications')             AS mentor_applications_table,
    (SELECT COUNT(*) FROM users WHERE role = 'learner')                                    AS learners,
    (SELECT COUNT(*) FROM users WHERE role = 'dual')                                       AS learners_and_mentors,
    (SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
        AND CONSTRAINT_NAME = 'chk_users_role')                                            AS role_check_present;
