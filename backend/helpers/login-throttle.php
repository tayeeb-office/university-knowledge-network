<?php
// Phase J (J1): login throttling, stored in `login_attempts` so it holds across PHP sessions,
// cookies and parallel requests (never in $_SESSION, which an attacker can simply discard).
//
// Every login attempt with a well-formed email is counted *before* the password is checked,
// under three keys (stored only as SHA-256 hashes):
//   pair  = email + client IP   one source guessing one account
//   email = email, from any IP   distributed guessing against one account
//   ip    = client IP            one source spraying many accounts
// An attempt is refused, without checking the password, while any key is over its limit.
// Limits reset when their window ends: throttling is always temporary, never a permanent
// lockout. A correct password clears the email/pair keys and gives the IP key its attempt
// back, so the IP key only counts failures (many users behind one campus NAT stay unaffected).
// The same rules and the same message apply whether or not the email has an account.
require_once __DIR__ . '/../config/database.php';

if (!defined('UKN_LOGIN_WINDOW_MINUTES')) {
    define('UKN_LOGIN_WINDOW_MINUTES', 15);
    define('UKN_LOGIN_LIMITS', ['pair' => 5, 'email' => 20, 'ip' => 50]);
}

if (!function_exists('uknLoginClientIp')) {
    /** The TCP peer address only; X-Forwarded-For and similar headers are client-controlled. */
    function uknLoginClientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
if (!function_exists('uknLoginThrottleKeys')) {
    /** scope => SHA-256 key, in a fixed order (rows are always locked in this order). */
    function uknLoginThrottleKeys(string $email, string $ip): array
    {
        $email = strtolower(trim($email));
        $keys = [
            'pair'  => hash('sha256', 'pair|' . $email . '|' . $ip),
            'email' => hash('sha256', 'email|' . $email),
            'ip'    => hash('sha256', 'ip|' . $ip),
        ];
        asort($keys);
        return $keys;
    }
}
if (!function_exists('uknLoginThrottleAttempt')) {
    /**
     * Counts this attempt under every key (one transaction, rows locked in key order) and
     * returns ['allowed' => bool, 'retry_minutes' => int]. The count each request sees is
     * its own position in the window, so concurrent requests cannot share one free slot.
     */
    function uknLoginThrottleAttempt(PDO $pdo, string $email, string $ip): array
    {
        $window = (int) UKN_LOGIN_WINDOW_MINUTES;
        $keys = uknLoginThrottleKeys($email, $ip);
        $upsert = $pdo->prepare(
            "INSERT INTO login_attempts (throttle_key, attempts, window_started_at) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                 attempts = IF(window_started_at <= NOW() - INTERVAL {$window} MINUTE, 1, attempts + 1),
                 window_started_at = IF(window_started_at <= NOW() - INTERVAL {$window} MINUTE, NOW(), window_started_at)"
        );
        $read = $pdo->prepare(
            "SELECT attempts, GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, NOW(), window_started_at + INTERVAL {$window} MINUTE) / 60)) AS retry_minutes
             FROM login_attempts WHERE throttle_key = ?"
        );
        $allowed = true;
        $retry = 0;
        $pdo->beginTransaction();
        try {
            foreach ($keys as $scope => $key) {
                $upsert->execute([$key]);
                $read->execute([$key]);
                $row = $read->fetch();
                if ((int) $row['attempts'] > UKN_LOGIN_LIMITS[$scope]) {
                    $allowed = false;
                    $retry = max($retry, (int) $row['retry_minutes']);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        return ['allowed' => $allowed, 'retry_minutes' => $retry];
    }
}
if (!function_exists('uknLoginThrottleSucceeded')) {
    /**
     * The password was correct: clear the email and pair keys, hand the IP key back its attempt,
     * and drop rows whose window ended more than a day ago (bounded, indexed cleanup).
     */
    function uknLoginThrottleSucceeded(PDO $pdo, string $email, string $ip): void
    {
        $keys = uknLoginThrottleKeys($email, $ip);
        $pdo->prepare('DELETE FROM login_attempts WHERE throttle_key IN (?, ?)')->execute([$keys['pair'], $keys['email']]);
        $pdo->prepare('UPDATE login_attempts SET attempts = GREATEST(attempts, 1) - 1 WHERE throttle_key = ?')->execute([$keys['ip']]);
        $pdo->exec('DELETE FROM login_attempts WHERE window_started_at < NOW() - INTERVAL 1 DAY LIMIT 100');
    }
}
