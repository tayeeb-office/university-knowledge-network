-- Learner recommendations: a member writes a short recommendation FOR a learner, shown on that
-- learner's public profile (Recommendations tab). Not related to mentor matching
-- (pages/mentors/recommendations.php), which computes suggestions and stores nothing.
--
-- A member may recommend the same learner more than once; each submission is its own row. The
-- recommender is always the logged-in user; who may receive one (an active learner / learner +
-- mentor account) is checked by the application, which also repeats the length and not-self
-- rules. A database created with the earlier one-per-pair UNIQUE key is upgraded by
-- allow-multiple-learner-recommendations.sql.
--
-- Idempotent: creates the table only if it does not exist; no existing row is changed.

CREATE TABLE IF NOT EXISTS learner_recommendations (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    recommender_id INT UNSIGNED  NOT NULL,
    learner_id     INT UNSIGNED  NOT NULL,
    content        VARCHAR(1000) NOT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_learner_recommendations_recommender (recommender_id, learner_id),
    KEY idx_learner_recommendations_learner (learner_id, created_at),

    CONSTRAINT fk_learner_recommendations_recommender
        FOREIGN KEY (recommender_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_learner_recommendations_learner
        FOREIGN KEY (learner_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_learner_recommendations_not_self CHECK (recommender_id <> learner_id),
    CONSTRAINT chk_learner_recommendations_content CHECK (CHAR_LENGTH(TRIM(content)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learner_recommendations') AS learner_recommendations_table,
    (SELECT COUNT(*) FROM learner_recommendations)                                AS recommendation_rows;
