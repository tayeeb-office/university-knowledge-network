
CREATE OR REPLACE SQL SECURITY INVOKER VIEW mentor_rating_summary AS
SELECT sr.mentor_id,
       ROUND(AVG(sr.overall), 1) AS avg_rating,
       COUNT(*)                  AS total_reviews
FROM session_ratings sr
GROUP BY sr.mentor_id;

SELECT COUNT(*) AS mentor_rating_summary_view_present
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mentor_rating_summary';
