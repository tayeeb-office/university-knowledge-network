
ALTER TABLE learner_recommendations
    ADD KEY IF NOT EXISTS idx_learner_recommendations_recommender (recommender_id, learner_id),
    DROP INDEX IF EXISTS uq_learner_recommendations_pair;

SELECT
    (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learner_recommendations'
        AND INDEX_NAME = 'uq_learner_recommendations_pair')                        AS unique_pair_key_left,
    (SELECT COUNT(*) FROM learner_recommendations)                                   AS recommendation_rows;
