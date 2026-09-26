-- Simplified account roles + mentor applications.
--
-- Account model after this patch:
--   users.role = 'learner'  learner capability only (every new registration)
--   users.role = 'dual'     learner + mentor capability (granted by an approved application)
--   users.is_admin = 1      admin authorization, independent of role (unchanged)
-- The permanent mentor-only role 'mentor' is removed.
--
-- Idempotent, in this order:
--   1. create mentor_applications if it does not exist;
--   2. convert every role = 'mentor' account to 'dual' (keeps learner access, adds nothing
--      else: status, is_admin, verification, profile, skills, sessions, ratings and even
--      updated_at stay as they were);
--   3. shrink the role ENUM to ('learner','dual') — only while it still contains 'mentor'
--      and no 'mentor' row is left;
--   4. add CHECK chk_users_role (role IN ('learner','dual')) once the ENUM is shrunk, so even a
--      non-strict session cannot store the ENUM's empty error value for an invalid role.
-- No application rows are created for accounts that are already 'dual'.

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

-- 2. Mentor-only accounts become learner + mentor. Only runs while the ENUM still has 'mentor'.
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

-- 3. Remove 'mentor' from the ENUM once no account uses it.
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

-- 4. Enforce the role values in the database itself (not only through the strict application
--    connection). Added once, after step 3, when every row already holds 'learner' or 'dual'.
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
