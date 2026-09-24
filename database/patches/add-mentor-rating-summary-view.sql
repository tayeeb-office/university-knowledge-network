-- Phase K: reusable per-mentor rating summary (SQL VIEW).
--
-- One row per mentor who has at least one rating: the average overall rating (rounded to
-- one decimal, exactly as shown in the app) and the number of reviews, computed from the
-- real `session_ratings` rows. It replaces the identical derived table that the Leaderboard
-- (Top Mentors) and Search (mentor results) queries each defined inline.
--
-- SQL SECURITY INVOKER: the view runs with the privileges of the account that queries it,
-- so it does not depend on the account that happened to create it.
-- Idempotent: CREATE OR REPLACE can be run any number of times; no table or row is changed.

CREATE OR REPLACE SQL SECURITY INVOKER VIEW mentor_rating_summary AS
SELECT sr.mentor_id,
       ROUND(AVG(sr.overall), 1) AS avg_rating,
       COUNT(*)                  AS total_reviews
FROM session_ratings sr
GROUP BY sr.mentor_id;

SELECT COUNT(*) AS mentor_rating_summary_view_present
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mentor_rating_summary';
