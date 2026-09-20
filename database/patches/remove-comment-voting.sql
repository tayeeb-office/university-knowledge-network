
SELECT
    DATABASE() AS patching_database,
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comment_votes')  AS comment_votes_table_found,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments'
        AND COLUMN_NAME = 'vote_score')                                  AS comments_vote_score_found;


DROP TABLE IF EXISTS comment_votes;


SET @ukn_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'comments'
      AND COLUMN_NAME  = 'vote_score'
);

SET @ukn_sql := IF(
    @ukn_col_exists > 0,
    'ALTER TABLE comments DROP COLUMN vote_score',
    'DO 0'
);

PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;


SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comment_votes')  AS comment_votes_table_remaining,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments'
        AND COLUMN_NAME = 'vote_score')                                  AS comments_vote_score_remaining,
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'post_votes')     AS post_votes_table_intact,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'
        AND COLUMN_NAME = 'vote_score')                                  AS posts_vote_score_intact;
