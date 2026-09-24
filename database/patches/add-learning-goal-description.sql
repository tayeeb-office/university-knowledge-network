-- Adds the optional description the Learning Goals form already collects (Phase C, Step 26).
-- Additive and idempotent; existing goals keep description = NULL.

SET @ukn_sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learning_goals' AND COLUMN_NAME = 'description') = 0,
    'ALTER TABLE learning_goals ADD COLUMN description VARCHAR(500) NULL AFTER title',
    'DO 0'
);
PREPARE ukn_stmt FROM @ukn_sql;
EXECUTE ukn_stmt;
DEALLOCATE PREPARE ukn_stmt;

SELECT COUNT(*) AS description_column_present
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learning_goals' AND COLUMN_NAME = 'description';
