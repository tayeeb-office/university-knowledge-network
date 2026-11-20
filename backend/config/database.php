<?php

function getDatabaseConnection()
{
    
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = "localhost";
    $dbname = "ukn_database";
    $username = "root";
    $password = "";

    try {

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