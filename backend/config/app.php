<?php
// Environment-driven app settings. Set these with Apache `SetEnv` (or the server environment);
// nothing secret is stored here. Defaults are safe for local development.
//
//   UKN_APP_URL        Absolute base URL used in emailed links, e.g. https://ukn.example.edu
//                      (no trailing slash). REQUIRED in production so links are never built
//                      from the request's Host header.
//   UKN_MAIL_TRANSPORT 'log'  (default) writes each email to storage/mail/ instead of sending it
//                      'mail' sends with PHP mail(), using the SMTP/sendmail settings in php.ini
//                      'smtp' sends through an authenticated SMTP server (e.g. Gmail) with PHPMailer
//   UKN_MAIL_FROM      From address for outgoing mail (for Gmail: the same address as the username).
//   UKN_MAIL_FROM_NAME Display name for the From address.
//
// SMTP transport only (STARTTLS with certificate verification is always used):
//   UKN_MAIL_HOST      SMTP server, e.g. smtp.gmail.com
//   UKN_MAIL_PORT      SMTP port (default 587)
//   UKN_MAIL_USERNAME  SMTP login, e.g. the Gmail address
//   UKN_MAIL_PASSWORD  SMTP password (for Gmail: an App Password). It is read only inside
//                      uknSendMail() (backend/helpers/mail.php) and never defined as a constant.

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
