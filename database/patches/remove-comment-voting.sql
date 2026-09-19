-- =====================================================================
--  PATCH — remove comment voting from an ALREADY-IMPORTED database
--  University Knowledge Network (UKN)
-- =====================================================================
--
--  WHY THIS EXISTS
--  ---------------
--  database/schema.sql has been corrected so that comments have no
--  voting. That change only affects a FRESH import. This patch applies
--  the same change to a database that was already created from the older
--  schema.
--
--  WHAT IT REMOVES
--  ---------------
--    1. the `comment_votes` table (with its PK, index, both foreign keys
--       and CHECK constraint — all dropped with the table)
--    2. the `comments.vote_score` column
--
--  WHAT IT MUST NEVER TOUCH
--  ------------------------
--    post_votes            -- POST voting table            KEEP
--    posts.vote_score      -- POST vote count              KEEP
--    idx_posts_popular     -- index over posts.vote_score  KEEP
--    chk_post_votes_value  -- POST vote CHECK              KEEP
--    fk_post_votes_*       -- POST vote foreign keys       KEEP
--  Nothing below references any of them. Voting for POSTS is unchanged.
--
--  SAFETY
--  ------
--  * Idempotent — safe to run twice. Running it on an already-patched
--    database reports "already absent" instead of erroring.
--  * MySQL 8 has no `ALTER TABLE ... DROP COLUMN IF EXISTS` (that is a
--    MariaDB extension), so the column drop is guarded through
--    INFORMATION_SCHEMA + a prepared statement. That form works on BOTH
--    MySQL 8 and MariaDB, which matters because XAMPP ships either.
--  * Scoped to DATABASE() — it only ever touches the schema you are
--    currently connected to.
--  * No data other than comment votes is read, written or deleted.
--
--  DATA LOSS NOTE
--  --------------
--  Any rows in `comment_votes` and any values in `comments.vote_score`
--  are destroyed. That is the intent — comment votes are no longer part
--  of the product. Nothing else is affected. Take a backup first if you
--  want to be able to roll back (see the report for the export step).
--
--  Run order: apply once, against the UKN database, via phpMyAdmin's SQL
--  tab. See the accompanying report for click-by-click steps.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 0. Show what we are about to change (informational).
-- ---------------------------------------------------------------------
SELECT
    DATABASE() AS patching_database,
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comment_votes')  AS comment_votes_table_found,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments'
        AND COLUMN_NAME = 'vote_score')                                  AS comments_vote_score_found;


-- ---------------------------------------------------------------------
-- 1. Drop the comment_votes table.
--
--    IF EXISTS makes this idempotent. Dropping the table also drops its
--    PRIMARY KEY (user_id, comment_id), idx_comment_votes_comment,
--    fk_comment_votes_user, fk_comment_votes_comment and
--    chk_comment_votes_value — no separate statements are needed.
--
--    comment_votes is the CHILD in both of its foreign keys, so nothing
--    points at it and it can be dropped directly. FOREIGN_KEY_CHECKS is
--    deliberately NOT disabled — there is no need, and leaving it on
--    keeps the rest of the database protected while this runs.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS comment_votes;


-- ---------------------------------------------------------------------
-- 2. Drop comments.vote_score, only if it is still there.
-- ---------------------------------------------------------------------
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
    'DO 0'   -- no-op statement: the column is already gone
);

PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;


-- ---------------------------------------------------------------------
-- 3. Verify. Expected result after a successful run:
--
--      comment_votes_table_remaining  = 0
--      comments_vote_score_remaining  = 0
--      post_votes_table_intact        = 1   <-- must stay 1
--      posts_vote_score_intact        = 1   <-- must stay 1
--
--    If either of the last two is 0, STOP — post voting was damaged and
--    the database should be restored from backup.
-- ---------------------------------------------------------------------
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
