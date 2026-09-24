<?php
// Developer connectivity check. Step 52–56 audit: it used to be reachable from the browser and
// echoed raw exception text; it now runs only from the command line
// (php backend/test-db.php) and never prints internal error details.
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
