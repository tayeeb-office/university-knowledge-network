<?php
require_once __DIR__ . '/../config/app.php';

if (!function_exists('uknSendMail')) {
    /**
     * Sends a plain-text email through the transport chosen by UKN_MAIL_TRANSPORT
     * (see backend/config/app.php). Returns false on failure; never throws.
     */
    function uknSendMail(string $to, string $subject, string $body): bool
    {
        $to = trim($to);
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        // Header-injection guard: subject/from must be single-line.
        $subject = str_replace(["\r", "\n"], ' ', $subject);
        $from = str_replace(["\r", "\n"], '', UKN_MAIL_FROM);

        $headers = "From: {$from}\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        if (UKN_MAIL_TRANSPORT === 'mail') {
            $sent = @mail($to, $subject, $body, $headers);
            if (!$sent) {
                error_log('[UKN mail] mail() failed for a verification email.');
            }
            return $sent;
        }

        // 'log' transport (development): write the message to storage/mail/, which is not
        // web-accessible (storage/.htaccess), so tokens are never exposed in HTTP responses.
        if (!is_dir(UKN_MAIL_LOG_DIR) && !@mkdir(UKN_MAIL_LOG_DIR, 0700, true)) {
            error_log('[UKN mail] Mail log directory is not writable.');
            return false;
        }
        $file = UKN_MAIL_LOG_DIR . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
        $message = $headers . "To: {$to}\r\nSubject: {$subject}\r\nDate: " . date(DATE_RFC2822) . "\r\n\r\n" . $body . "\r\n";
        if (@file_put_contents($file, $message, LOCK_EX) === false) {
            error_log('[UKN mail] Could not write mail log file.');
            return false;
        }
        return true;
    }
}
