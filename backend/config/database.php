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

        error_log("[UKN] Database connection failed: " . $e->getMessage());
        throw new RuntimeException('Database connection failed.');
    }
}