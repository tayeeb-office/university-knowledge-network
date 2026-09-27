-- Learner recommendations: allow the same member to recommend the same learner more than once.
--
-- Drops the UNIQUE key uq_learner_recommendations_pair (recommender_id, learner_id), so each
-- submission is its own row (own id, text and created_at). That key was also the index behind
-- the foreign key fk_learner_recommendations_recommender, so a plain (non-unique) index on the
-- same columns replaces it in the same statement. The primary key, both foreign keys, the
-- not-self and non-blank CHECKs and idx_learner_recommendations_learner are unchanged.
--
-- Idempotent and non-destructive: no row is changed or removed; running it again does nothing.

ALTER TABLE learner_recommendations
    ADD KEY IF NOT EXISTS idx_learner_recommendations_recommender (recommender_id, learner_id),
    DROP INDEX IF EXISTS uq_learner_recommendations_pair;

SELECT
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learner_recommendations'
        AND INDEX_NAME = 'uq_learner_recommendations_pair')                        AS unique_pair_key_left,
    (SELECT COUNT(*) FROM learner_recommendations)                                   AS recommendation_rows;
