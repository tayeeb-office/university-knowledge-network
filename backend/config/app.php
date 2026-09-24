<?php
// Environment-driven app settings. Set these with Apache `SetEnv` (or the server environment);
// nothing secret is stored here. Defaults are safe for local development.
//
//   UKN_APP_URL        Absolute base URL used in emailed links, e.g. https://ukn.example.edu
//                      (no trailing slash). REQUIRED in production so links are never built
//                      from the request's Host header.
//   UKN_MAIL_TRANSPORT 'log'  (default) writes each email to storage/mail/ instead of sending it
//                      'mail' sends with PHP mail(), using the SMTP/sendmail settings in php.ini
//   UKN_MAIL_FROM      From address for outgoing mail.

if (!defined('UKN_APP_URL')) {
    define('UKN_APP_URL', rtrim((string) getenv('UKN_APP_URL'), '/'));
}
if (!defined('UKN_MAIL_TRANSPORT')) {
    define('UKN_MAIL_TRANSPORT', getenv('UKN_MAIL_TRANSPORT') === 'mail' ? 'mail' : 'log');
}
if (!defined('UKN_MAIL_FROM')) {
    define('UKN_MAIL_FROM', (string) (getenv('UKN_MAIL_FROM') ?: 'no-reply@localhost'));
}
if (!defined('UKN_MAIL_LOG_DIR')) {
    define('UKN_MAIL_LOG_DIR', dirname(__DIR__, 2) . '/storage/mail');
}
if (!defined('UKN_VERIFICATION_TTL_HOURS')) {
    define('UKN_VERIFICATION_TTL_HOURS', 24);
}
