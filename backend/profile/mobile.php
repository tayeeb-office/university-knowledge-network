<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/private-contacts.php';

// Settings: the signed-in user adds or changes their own private mobile number. The row is
// always the session user's (no user id is read from the request). The number itself is never
// echoed in a message or written to a log.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('settings');

$back = static function (): void {
    if (!headers_sent()) {
        header('Location: ' . uknRouteUrl('settings') . '#mobile', true, 302);
    }
    exit;
};
$mobile = uknNormalizeMobile(uknPostString('mobile_number'));
if ($mobile === null) {
    uknFlashToast('danger', 'Enter a valid Bangladesh mobile number, e.g. 01XXXXXXXXX or +8801XXXXXXXXX.');
    $back();
}
try {
    uknSaveOwnMobile(getDatabaseConnection(), (int) getCurrentUser()['id'], $mobile);
} catch (Throwable $e) {
    error_log('[UKN profile/mobile] could not save the mobile number (' . get_class($e) . ')');
    uknFlashToast('danger', 'Your mobile number could not be saved. Please try again.');
    $back();
}
uknFlashToast('success', 'Mobile number saved.');
$back();
