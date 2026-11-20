
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
