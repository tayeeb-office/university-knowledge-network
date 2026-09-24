<?php
require_once __DIR__ . '/../helpers/actions.php';

// Saves the current mentor's weekly schedule from the Availability page. The page edits the
// whole week at once, so the submitted week replaces the mentor's rows in one transaction.
//
// Expected fields: enabled[<day>]=1, slot_start[<day>][]=HH:MM, slot_end[<day>][]=HH:MM
// with <day> in mon..sun. day_of_week follows the project convention 0 = Sunday .. 6 = Saturday.
uknRequirePostMethod();
requireMentor();
uknRequireActionCsrf('availability');

const UKN_AVAILABILITY_DAYS = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
const UKN_AVAILABILITY_MAX_SLOTS = 4; // same limit as the page's "Add Time Slot" control

$enabled = $_POST['enabled'] ?? [];
$starts = $_POST['slot_start'] ?? [];
$ends = $_POST['slot_end'] ?? [];
if (!is_array($enabled) || !is_array($starts) || !is_array($ends)) {
    uknFailAction('Invalid availability data.', 'availability');
}
foreach (array_merge(array_keys($enabled), array_keys($starts), array_keys($ends)) as $dayKey) {
    if (!is_string($dayKey) || !array_key_exists($dayKey, UKN_AVAILABILITY_DAYS)) {
        uknFailAction('Invalid availability data.', 'availability');
    }
}

$parseTime = static function ($value): ?string {
    if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::00)?$/', $value, $m)) {
        return null;
    }
    return $m[1] . ':' . $m[2];
};
$rows = [];
foreach (UKN_AVAILABILITY_DAYS as $dayKey => $dow) {
    if (($enabled[$dayKey] ?? null) !== '1') {
        continue; // day switched off: no slots stored
    }
    $dayStarts = $starts[$dayKey] ?? [];
    $dayEnds = $ends[$dayKey] ?? [];
    if (!is_array($dayStarts) || !is_array($dayEnds) || count($dayStarts) !== count($dayEnds)) {
        uknFailAction('Invalid availability data.', 'availability');
    }
    if (count($dayStarts) > UKN_AVAILABILITY_MAX_SLOTS) {
        uknFailAction('Add at most ' . UKN_AVAILABILITY_MAX_SLOTS . ' time slots per day.', 'availability');
    }
    $slots = [];
    foreach (array_values($dayStarts) as $i => $rawStart) {
        $start = $parseTime($rawStart);
        $end = $parseTime(array_values($dayEnds)[$i]);
        if ($start === null || $end === null) {
            uknFailAction('Enter a start and end time for every time slot.', 'availability');
        }
        if ($end <= $start) {
            uknFailAction('End time must be later than start time.', 'availability');
        }
        $slots[] = [$start, $end];
    }
    usort($slots, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));
    for ($i = 1; $i < count($slots); $i++) {
        if ($slots[$i][0] < $slots[$i - 1][1]) {
            uknFailAction('Time slots on the same day cannot overlap.', 'availability');
        }
    }
    foreach ($slots as [$start, $end]) {
        $rows[] = [$dow, $start . ':00', $end . ':00'];
    }
}

try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM mentor_availability WHERE user_id = ?')->execute([$userId]);
    $insert = $pdo->prepare(
        'INSERT INTO mentor_availability (user_id, day_of_week, start_time, end_time, is_enabled) VALUES (?, ?, ?, ?, 1)'
    );
    foreach ($rows as [$dow, $start, $end]) {
        $insert->execute([$userId, $dow, $start, $end]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN availability/save] ' . $e->getMessage());
    uknFailAction('Your availability could not be saved. Please try again.', 'availability');
}
uknFlashToast('success', 'Availability saved.');
uknRedirectBack('availability');
