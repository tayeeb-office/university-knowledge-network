<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/config/database.php";

try {
    getDatabaseConnection();
    echo "Database Connected Successfully\n";
} catch (Throwable $e) {
    echo "Database connection failed (see the PHP error log).\n";
    exit(1);
}
