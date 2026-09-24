<?php

function getDatabaseConnection()
{
    // One connection per request: index.php, the page, the sidebars and the current-user
    // lookup all call this, so reuse the first successful connection.
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = "localhost";
    $dbname = "ukn_database";
    $username = "root";
    $password = "";

    try {

        // Step 55: strict mode for this app's connection, so a value that does not fit its column
        // (too long, not valid for the character set, wrong type) raises an error and the
        // surrounding transaction rolls back, instead of being silently truncated or converted.
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = TRIM(BOTH ',' FROM CONCAT(@@SESSION.sql_mode, ',STRICT_TRANS_TABLES'))"]
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        return $pdo;

    } catch (PDOException $e) {

        $pdo = null;
        error_log("[UKN] Database connection failed: " . $e->getMessage());
        throw new RuntimeException('Database connection failed.');
    }
}