<?php
require_once __DIR__ . '/../config/app.php';

if (!function_exists('uknSendMailSmtp')) {
    /**
     * 'smtp' transport: sends through the SMTP server in UKN_MAIL_HOST (e.g. Gmail) with PHPMailer,
     * STARTTLS and certificate verification. Returns false on failure; never throws. Only a
     * redacted, one-line reason is logged: never the password, the recipient or the message body
     * (which contains the verification token).
     */
    function uknSendMailSmtp(string $to, string $subject, string $body, string $from): bool
    {
        $password = (string) getenv('UKN_MAIL_PASSWORD');
        if (UKN_MAIL_HOST === '' || UKN_MAIL_USERNAME === '' || $password === '') {
            error_log('[UKN mail] SMTP transport is not configured (UKN_MAIL_HOST / UKN_MAIL_USERNAME / UKN_MAIL_PASSWORD).');
            return false;
        }

        require_once __DIR__ . '/PHPMailer/Exception.php';
        require_once __DIR__ . '/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->SMTPDebug  = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            $mail->Host       = UKN_MAIL_HOST;
            $mail->Port       = UKN_MAIL_PORT;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth   = true;
            $mail->Username   = UKN_MAIL_USERNAME;
            $mail->Password   = $password;
            $mail->Timeout    = 15;
            $mail->CharSet    = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            $mail->XMailer    = ' '; // no library/version header
            $mail->setFrom($from, str_replace(["\r", "\n"], ' ', UKN_MAIL_FROM_NAME));
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML(false);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $reason = str_replace([$password, UKN_MAIL_USERNAME, $to], '[redacted]', $e->getMessage());
            $reason = preg_replace('/[^\s@<>"]+@[^\s@<>"]+/', '[redacted]', $reason);
            error_log('[UKN mail] SMTP send failed: ' . str_replace(["\r", "\n"], ' ', $reason));
            return false;
        } finally {
            $mail->smtpClose();
        }
    }
}

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

        if (UKN_MAIL_TRANSPORT === 'smtp') {
            return uknSendMailSmtp($to, $subject, $body, $from);
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
