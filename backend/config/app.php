<?php

if (!defined('UKN_APP_URL')) {
    define('UKN_APP_URL', rtrim((string) getenv('UKN_APP_URL'), '/'));
}
if (!defined('UKN_MAIL_TRANSPORT')) {
    $uknMailTransport = getenv('UKN_MAIL_TRANSPORT');
    define('UKN_MAIL_TRANSPORT', in_array($uknMailTransport, ['mail', 'smtp'], true) ? $uknMailTransport : 'log');
    unset($uknMailTransport);
}
if (!defined('UKN_MAIL_FROM')) {
    define('UKN_MAIL_FROM', (string) (getenv('UKN_MAIL_FROM') ?: 'no-reply@localhost'));
}
if (!defined('UKN_MAIL_FROM_NAME')) {
    define('UKN_MAIL_FROM_NAME', (string) (getenv('UKN_MAIL_FROM_NAME') ?: 'University Knowledge Network'));
}
if (!defined('UKN_MAIL_HOST')) {
    define('UKN_MAIL_HOST', (string) getenv('UKN_MAIL_HOST'));
}
if (!defined('UKN_MAIL_PORT')) {
    define('UKN_MAIL_PORT', (int) (getenv('UKN_MAIL_PORT') ?: 587));
}
if (!defined('UKN_MAIL_USERNAME')) {
    define('UKN_MAIL_USERNAME', (string) getenv('UKN_MAIL_USERNAME'));
}
if (!defined('UKN_MAIL_LOG_DIR')) {
    define('UKN_MAIL_LOG_DIR', dirname(__DIR__, 2) . '/storage/mail');
}
if (!defined('UKN_VERIFICATION_TTL_HOURS')) {
    define('UKN_VERIFICATION_TTL_HOURS', 24);
}
