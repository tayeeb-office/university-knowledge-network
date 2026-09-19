<?php

function getDatabaseConnection()
{
    $host = "localhost";
    $dbname = "ukn_database";
    $username = "root";
    $password = "";

    try {

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password
        );

        // Error handling
        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        // Default fetch mode
        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        return $pdo;

    } catch (PDOException $e) {

        /*
         * The real reason goes to the SERVER LOG only.
         *
         * A PDO connection failure reports things like
         *   SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
         *   SQLSTATE[HY000] [1049] Unknown database 'ukn_database'
         * which name the database user and the schema. That belongs in a
         * log an administrator reads, never in a page a visitor sees.
         */
        error_log("[UKN] Database connection failed: " . $e->getMessage());

        /*
         * Throw rather than die().
         *
         * die() cannot be caught, so every caller's own error handling —
         * including the try/catch and rollBack() in backend/auth/register.php
         * — was simply skipped, and the raw message above was printed
         * straight to the browser instead.
         *
         * The message here is deliberately generic and carries no host,
         * database name, username or driver text.
         *
         * $e is intentionally NOT chained as the previous exception: PHP's
         * default handler prints the whole chain when display_errors is on
         * (as it is by default under XAMPP), which would put the message
         * above back on screen. While debugging locally you can add
         * `, 0, $e` below temporarily — just do not leave it there.
         */
        throw new RuntimeException('Database connection failed.');

    }
}