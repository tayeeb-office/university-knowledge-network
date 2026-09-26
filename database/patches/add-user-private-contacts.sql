-- Private contact details (mobile number), kept out of the users table.
--
-- One optional row per user. Registration writes it in the same transaction as the account;
-- existing accounts have no row until the owner adds a number in Settings. The number is only
-- shown to its owner (Settings) and to the mentor of that learner's pending/accepted session
-- (Session Details). It is stored as +8801XXXXXXXXX (Bangladesh mobile, E.164); the CHECK
-- repeats the application's validation. Not unique: family/shared numbers are allowed.
--
-- Idempotent: creates the table only if it does not exist; no existing row is changed.

CREATE TABLE IF NOT EXISTS user_private_contacts (
    user_id       INT UNSIGNED NOT NULL,
    mobile_number VARCHAR(20)  NOT NULL COMMENT 'private; +8801XXXXXXXXX',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),

    CONSTRAINT fk_user_private_contacts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT chk_user_private_contacts_mobile
        CHECK (mobile_number REGEXP '^[+]8801[3-9][0-9]{8}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_private_contacts') AS private_contacts_table,
    (SELECT COUNT(*) FROM user_private_contacts)                               AS contact_rows,
    (SELECT COUNT(*) FROM users)                                               AS users;
