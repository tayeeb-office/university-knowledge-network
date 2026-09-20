
SET NAMES utf8mb4;
SET time_zone = '+00:00';


SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS point_transactions;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS saved_posts;
DROP TABLE IF EXISTS post_votes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS post_skills;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS session_ratings;
DROP TABLE IF EXISTS mentoring_sessions;
DROP TABLE IF EXISTS learning_goals;
DROP TABLE IF EXISTS mentor_availability;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skill_relations;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS skill_categories;
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE departments (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)      NOT NULL,
    code        VARCHAR(10)       NOT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_name (name),
    UNIQUE KEY uq_departments_code (code),
    KEY idx_departments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE users (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name           VARCHAR(120) NOT NULL,
    initials            VARCHAR(4)   NOT NULL,
    email               VARCHAR(160) NOT NULL,
    university_id       VARCHAR(30)  NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    department_id       SMALLINT UNSIGNED NULL,

    role                ENUM('learner','mentor','dual') NOT NULL DEFAULT 'learner',
    is_admin            TINYINT(1)   NOT NULL DEFAULT 0,

    year_of_study       ENUM('1st Year','2nd Year','3rd Year','4th Year') NULL,
    headline            VARCHAR(150) NULL COMMENT 'Mentor title, e.g. "Python & Data Analysis Mentor"',
    bio                 TEXT         NULL,
    avatar_path         VARCHAR(255) NULL COMMENT 'Relative path under uploads/profiles/',

    status              ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    suspend_reason      VARCHAR(255) NULL,

    learning_points     INT NOT NULL DEFAULT 0,
    mentor_points       INT NOT NULL DEFAULT 0,
    avg_rating          DECIMAL(2,1) NULL,
    total_reviews       INT UNSIGNED NOT NULL DEFAULT 0,
    sessions_as_learner INT UNSIGNED NOT NULL DEFAULT 0,
    sessions_as_mentor  INT UNSIGNED NOT NULL DEFAULT 0,
    learners_helped     INT UNSIGNED NOT NULL DEFAULT 0,

    last_active_at      DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_university_id (university_id),
    KEY idx_users_department (department_id),
    KEY idx_users_role_status (role, status),
    KEY idx_users_full_name (full_name),
    KEY idx_users_mentor_points (mentor_points),
    KEY idx_users_learning_points (learning_points),

    CONSTRAINT fk_users_department
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_users_avg_rating
        CHECK (avg_rating IS NULL OR (avg_rating >= 1.0 AND avg_rating <= 5.0)),
    CONSTRAINT chk_users_suspend_reason
        CHECK (status <> 'suspended' OR suspend_reason IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_settings (
    user_id                  INT UNSIGNED NOT NULL,

    notify_session_updates   TINYINT(1) NOT NULL DEFAULT 1,
    notify_session_reminders TINYINT(1) NOT NULL DEFAULT 1,
    notify_community_replies TINYINT(1) NOT NULL DEFAULT 1,
    notify_follow_activity   TINYINT(1) NOT NULL DEFAULT 0,
    notify_learner_requests  TINYINT(1) NOT NULL DEFAULT 1,
    notify_rating_received   TINYINT(1) NOT NULL DEFAULT 1,

    profile_visibility       ENUM('public','members','private') NOT NULL DEFAULT 'members',
    theme                    ENUM('light','dark','system')      NOT NULL DEFAULT 'system',

    updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_settings_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE skill_categories (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(60)      NOT NULL,
    description VARCHAR(255)     NOT NULL DEFAULT '',
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_skill_categories_name (name),
    KEY idx_skill_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE skills (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)       NOT NULL,
    slug        VARCHAR(80)       NOT NULL,
    category_id TINYINT UNSIGNED  NOT NULL,
    description VARCHAR(255)      NOT NULL DEFAULT '',
    about       TEXT              NULL COMMENT 'Long copy on pages/skills/skill-details.php',
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_skills_name (name),
    UNIQUE KEY uq_skills_slug (slug),
    KEY idx_skills_category (category_id),
    KEY idx_skills_status (status),

    CONSTRAINT fk_skills_category
        FOREIGN KEY (category_id) REFERENCES skill_categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE skill_relations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_skill_id SMALLINT UNSIGNED NOT NULL,
    target_skill_id SMALLINT UNSIGNED NOT NULL,
    strength        ENUM('Strong','Medium','Related') NOT NULL DEFAULT 'Related',
    reason          VARCHAR(255) NOT NULL DEFAULT '',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_skill_relations_pair (source_skill_id, target_skill_id),
    KEY idx_skill_relations_target (target_skill_id),

    CONSTRAINT fk_skill_relations_source
        FOREIGN KEY (source_skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_skill_relations_target
        FOREIGN KEY (target_skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_skill_relations_not_self
        CHECK (source_skill_id <> target_skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE user_skills (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    skill_id          SMALLINT UNSIGNED NOT NULL,
    skill_type        ENUM('learning','teaching') NOT NULL,

    proficiency       ENUM('Beginner','Intermediate','Advanced') NULL,
    progress          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100, learning only',
    sessions_count    INT UNSIGNED NOT NULL DEFAULT 0,
    avg_rating        DECIMAL(2,1) NULL COMMENT 'teaching only',
    primary_mentor_id INT UNSIGNED NULL COMMENT 'learning only',

    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_user_skills (user_id, skill_id, skill_type),
    KEY idx_user_skills_skill_type (skill_id, skill_type),
    KEY idx_user_skills_mentor (primary_mentor_id),

    CONSTRAINT fk_user_skills_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_primary_mentor
        FOREIGN KEY (primary_mentor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_user_skills_progress
        CHECK (progress BETWEEN 0 AND 100),
    CONSTRAINT chk_user_skills_mentor_only_on_learning
        CHECK (primary_mentor_id IS NULL OR skill_type = 'learning'),
    CONSTRAINT chk_user_skills_rating
        CHECK (avg_rating IS NULL OR (avg_rating >= 1.0 AND avg_rating <= 5.0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE mentor_availability (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    is_enabled  TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_mentor_availability_slot (user_id, day_of_week, start_time),
    KEY idx_mentor_availability_day (day_of_week, is_enabled),

    CONSTRAINT fk_mentor_availability_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_mentor_availability_day
        CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT chk_mentor_availability_range
        CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE learning_goals (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    skill_id     SMALLINT UNSIGNED NULL,
    title        VARCHAR(160) NOT NULL,
    progress     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    target_date  DATE NULL,
    status       ENUM('in-progress','completed') NOT NULL DEFAULT 'in-progress',
    completed_at DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_learning_goals_user_status (user_id, status),
    KEY idx_learning_goals_skill (skill_id),

    CONSTRAINT fk_learning_goals_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_learning_goals_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_learning_goals_progress
        CHECK (progress BETWEEN 0 AND 100),
    CONSTRAINT chk_learning_goals_completed
        CHECK (status <> 'completed' OR progress = 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE mentoring_sessions (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code   VARCHAR(20)  NOT NULL,
    learner_id       INT UNSIGNED NOT NULL,
    mentor_id        INT UNSIGNED NOT NULL,
    skill_id         SMALLINT UNSIGNED NOT NULL,

    scheduled_date   DATE NOT NULL,
    scheduled_time   TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,

    status           ENUM('pending','accepted','completed','rejected','cancelled')
                     NOT NULL DEFAULT 'pending',
    request_message  TEXT NULL,
    cancel_reason    VARCHAR(255) NULL,

    requested_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at     DATETIME NULL,
    completed_at     DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_mentoring_sessions_reference (reference_code),
    KEY idx_sessions_mentor_status (mentor_id, status),
    KEY idx_sessions_learner_status (learner_id, status),
    KEY idx_sessions_date (scheduled_date),
    KEY idx_sessions_skill (skill_id),

    CONSTRAINT fk_sessions_learner
        FOREIGN KEY (learner_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sessions_mentor
        FOREIGN KEY (mentor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sessions_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_sessions_not_self
        CHECK (learner_id <> mentor_id),
    CONSTRAINT chk_sessions_duration
        CHECK (duration_minutes BETWEEN 15 AND 240),
    CONSTRAINT chk_sessions_cancel_reason
        CHECK (status <> 'cancelled' OR cancel_reason IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE session_ratings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id    INT UNSIGNED NOT NULL,
    reviewer_id   INT UNSIGNED NOT NULL COMMENT 'the learner',
    mentor_id     INT UNSIGNED NOT NULL,
    skill_id      SMALLINT UNSIGNED NULL,

    overall       TINYINT UNSIGNED NOT NULL,
    teaching      TINYINT UNSIGNED NULL,
    communication TINYINT UNSIGNED NULL,
    helpfulness   TINYINT UNSIGNED NULL,
    review        TEXT NULL,

    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_session_ratings_session (session_id),
    KEY idx_session_ratings_mentor (mentor_id, created_at),
    KEY idx_session_ratings_skill (skill_id),
    KEY idx_session_ratings_reviewer (reviewer_id),

    CONSTRAINT fk_session_ratings_session
        FOREIGN KEY (session_id) REFERENCES mentoring_sessions (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_ratings_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_ratings_mentor
        FOREIGN KEY (mentor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_session_ratings_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_session_ratings_overall
        CHECK (overall BETWEEN 1 AND 5),
    CONSTRAINT chk_session_ratings_teaching
        CHECK (teaching IS NULL OR teaching BETWEEN 1 AND 5),
    CONSTRAINT chk_session_ratings_communication
        CHECK (communication IS NULL OR communication BETWEEN 1 AND 5),
    CONSTRAINT chk_session_ratings_helpfulness
        CHECK (helpfulness IS NULL OR helpfulness BETWEEN 1 AND 5),
    CONSTRAINT chk_session_ratings_not_self
        CHECK (reviewer_id <> mentor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE posts (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(160) NOT NULL,
    content       TEXT NOT NULL,

    vote_score    INT NOT NULL DEFAULT 0 COMMENT 'SIGNED: downvotes can push this below zero',
    comment_count INT UNSIGNED NOT NULL DEFAULT 0,
    report_count  INT UNSIGNED NOT NULL DEFAULT 0,
    status        ENUM('visible','hidden') NOT NULL DEFAULT 'visible',

    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_posts_author (user_id, created_at),
    KEY idx_posts_feed (status, created_at),
    KEY idx_posts_popular (status, vote_score),
    FULLTEXT KEY ft_posts_search (title, content),

    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE post_skills (
    post_id  INT UNSIGNED NOT NULL,
    skill_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (post_id, skill_id),
    KEY idx_post_skills_skill (skill_id),

    CONSTRAINT fk_post_skills_post
        FOREIGN KEY (post_id) REFERENCES posts (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_post_skills_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE comments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id      INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    parent_id    INT UNSIGNED NULL,
    content      TEXT NOT NULL,

    report_count INT UNSIGNED NOT NULL DEFAULT 0,
    status       ENUM('visible','hidden') NOT NULL DEFAULT 'visible',

    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_comments_post (post_id, created_at),
    KEY idx_comments_parent (parent_id),
    KEY idx_comments_user (user_id),

    CONSTRAINT fk_comments_post
        FOREIGN KEY (post_id) REFERENCES posts (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent
        FOREIGN KEY (parent_id) REFERENCES comments (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE post_votes (
    user_id    INT UNSIGNED NOT NULL,
    post_id    INT UNSIGNED NOT NULL,
    value      TINYINT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, post_id),
    KEY idx_post_votes_post (post_id),

    CONSTRAINT fk_post_votes_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_post_votes_post
        FOREIGN KEY (post_id) REFERENCES posts (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_post_votes_value CHECK (value IN (-1, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE saved_posts (
    user_id    INT UNSIGNED NOT NULL,
    post_id    INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, post_id),
    KEY idx_saved_posts_post (post_id),

    CONSTRAINT fk_saved_posts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_saved_posts_post
        FOREIGN KEY (post_id) REFERENCES posts (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE follows (
    follower_id  INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (follower_id, following_id),
    KEY idx_follows_following (following_id),

    CONSTRAINT fk_follows_follower
        FOREIGN KEY (follower_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_follows_following
        FOREIGN KEY (following_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_follows_not_self CHECK (follower_id <> following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE point_transactions (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id            INT UNSIGNED NOT NULL,
    amount             INT NOT NULL,
    point_type         ENUM('learning','mentor','community') NOT NULL,
    category           ENUM('session','goal','rating','community','penalty') NOT NULL,
    reason             VARCHAR(160) NOT NULL,

    related_session_id INT UNSIGNED NULL,
    related_post_id    INT UNSIGNED NULL,
    related_goal_id    INT UNSIGNED NULL,

    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_points_user_date (user_id, created_at),
    KEY idx_points_user_type (user_id, point_type),
    KEY idx_points_category (category),

    CONSTRAINT fk_points_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_points_session
        FOREIGN KEY (related_session_id) REFERENCES mentoring_sessions (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_points_post
        FOREIGN KEY (related_post_id) REFERENCES posts (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_points_goal
        FOREIGN KEY (related_goal_id) REFERENCES learning_goals (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_points_amount_nonzero CHECK (amount <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE notifications (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    type       ENUM('session','community','rating','system') NOT NULL,
    icon       VARCHAR(40)  NOT NULL DEFAULT 'notifications' COMMENT 'Material Symbol name',
    message    VARCHAR(255) NOT NULL,
    link_url   VARCHAR(255) NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_notifications_inbox (user_id, is_read, created_at),
    KEY idx_notifications_type (user_id, type),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE reports (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_code VARCHAR(20)  NOT NULL,
    reporter_id    INT UNSIGNED NOT NULL,

    target_type    ENUM('post','comment','user') NOT NULL,
    target_id      INT UNSIGNED NOT NULL,

    reason         ENUM('academic-integrity','off-topic','spam',
                        'inappropriate','harassment','other') NOT NULL,
    description    TEXT NULL,

    status         ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
    reviewed_by    INT UNSIGNED NULL,
    reviewed_at    DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_reports_reference (reference_code),
    UNIQUE KEY uq_reports_one_per_reporter (reporter_id, target_type, target_id),
    KEY idx_reports_queue (status, created_at),
    KEY idx_reports_target (target_type, target_id),

    CONSTRAINT fk_reports_reporter
        FOREIGN KEY (reporter_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reports_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT chk_reports_reviewed
        CHECK (status = 'pending' OR reviewed_at IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


