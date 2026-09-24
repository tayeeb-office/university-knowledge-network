<?php
// Phase J (J9/J10): what the browser may see when something goes wrong.
//
// UKN_ENV=production (set with Apache `SetEnv UKN_ENV production`, like the other UKN_*
// settings in backend/config/app.php):
//   - PHP errors are never displayed, only logged (php.ini error_log);
//   - an uncaught exception or error gets a generic HTTP 500 page; the class, message, file
//     and line go to the log, never to the browser.
// Any other value (default: local XAMPP development) leaves PHP's own display settings alone,
// so warnings stay visible while developing and in the test suites.
// Everything that reaches the browser in normal operation is already a generic message:
// pages and endpoints catch their database errors and log the details.

if (!defined('UKN_ENV')) {
    define('UKN_ENV', getenv('UKN_ENV') === 'production' ? 'production' : 'development');
}
if (UKN_ENV === 'production' && !defined('UKN_ERROR_HANDLING_INSTALLED')) {
    define('UKN_ERROR_HANDLING_INSTALLED', true);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    set_exception_handler(static function (Throwable $e): void {
        error_log('[UKN uncaught] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Something went wrong</title></head>'
            . '<body><h1>Something went wrong</h1><p>Please try again in a moment.</p></body></html>';
    });
}
