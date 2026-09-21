<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

// Display order Monday -> Sunday; day_of_week follows the 0 = Sunday .. 6 = Saturday
// convention used throughout this project (see DATABASE_READ_INTEGRATION_PLAN.md §2.3).
$dayDefs = [
    ['day' => 'Monday', 'key' => 'mon', 'dow' => 1],
    ['day' => 'Tuesday', 'key' => 'tue', 'dow' => 2],
    ['day' => 'Wednesday', 'key' => 'wed', 'dow' => 3],
    ['day' => 'Thursday', 'key' => 'thu', 'dow' => 4],
    ['day' => 'Friday', 'key' => 'fri', 'dow' => 5],
    ['day' => 'Saturday', 'key' => 'sat', 'dow' => 6],
    ['day' => 'Sunday', 'key' => 'sun', 'dow' => 0],
];
$week = [];
$enabledDayCount = 0;
$totalHours = 0;
$bookedHours = 0;
$openHours = 0;
$pendingRequestCount = 0;
$upcomingSessions = [];
$availabilityDbError = false;

try {
    $pdo = getDatabaseConnection();

    $slotsStmt = $pdo->prepare(
        "SELECT day_of_week, start_time, end_time FROM mentor_availability
         WHERE user_id = ? AND is_enabled = 1
         ORDER BY day_of_week, start_time"
    );
    $slotsStmt->execute([UKN_DEMO_USER_ID]);
    $slotsByDay = [];
    $toMinutes = static fn (string $t): int => (int) explode(':', $t)[0] * 60 + (int) explode(':', $t)[1];
    $totalMinutes = 0;
    foreach ($slotsStmt->fetchAll() as $row) {
        $start = substr($row['start_time'], 0, 5);
        $end = substr($row['end_time'], 0, 5);
        $slotsByDay[(int) $row['day_of_week']][] = ['start' => $start, 'end' => $end];
        $totalMinutes += $toMinutes($end) - $toMinutes($start);
    }
    $totalHours = (int) round($totalMinutes / 60);

    foreach ($dayDefs as $def) {
        $slots = $slotsByDay[$def['dow']] ?? [];
        if ($slots) {
            $enabledDayCount++;
        }
        $week[] = ['day' => $def['day'], 'key' => $def['key'], 'enabled' => (bool) $slots, 'slots' => $slots];
    }

    $today = date('Y-m-d');
    $weekAhead = date('Y-m-d', strtotime('+7 days'));
    $bookedMinutesStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(duration_minutes), 0) FROM mentoring_sessions
         WHERE mentor_id = ? AND status = 'accepted' AND scheduled_date BETWEEN ? AND ?"
    );
    $bookedMinutesStmt->execute([UKN_DEMO_USER_ID, $today, $weekAhead]);
    $bookedHours = (int) round(((int) $bookedMinutesStmt->fetchColumn()) / 60);
    $openHours = max(0, $totalHours - $bookedHours);

    $pendingStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending'"
    );
    $pendingStmt->execute([UKN_DEMO_USER_ID]);
    $pendingRequestCount = (int) $pendingStmt->fetchColumn();

    $upcomingStmt = $pdo->prepare(
        "SELECT ms.id, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                l.full_name AS counterparty, l.initials AS counterpartyInitials, sk.name AS skill
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN skills sk ON sk.id = ms.skill_id
         WHERE ms.mentor_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
         ORDER BY ms.scheduled_date, ms.scheduled_time"
    );
    $upcomingStmt->execute([UKN_DEMO_USER_ID]);
    $upcomingSessions = array_map(static function (array $row): array {
        $timestamp = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
        return [
            'counterparty' => $row['counterparty'],
            'counterpartyInitials' => $row['counterpartyInitials'],
            'skill' => $row['skill'],
            'day' => date('j', $timestamp),
            'month' => date('M', $timestamp),
            'time' => date('D g:ia', $timestamp),
            'duration' => $row['duration_minutes'] . ' min',
            'status' => 'upcoming',
            'detailsHref' => ukn_route_href('session-details') . '&id=' . $row['id'],
        ];
    }, $upcomingStmt->fetchAll());
} catch (Throwable $e) {
    error_log('[UKN availability] ' . $e->getMessage());
    $availabilityDbError = true;
    $week = [];
    $upcomingSessions = [];
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Availability</h1>
    <p class="ukn-page-header__sub">Set the days and times when you're available for mentoring sessions.</p>
  </div>
</div>
<?php if ($availabilityDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load your availability.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<form data-availability-form novalidate>
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="ukn-h4 mb-0">Weekly Schedule</h2>
          </div>
          <?php foreach ($week as $day): ?>
            <div class="ukn-availability-day" data-availability-day="<?= htmlspecialchars($day['key']) ?>">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
                <div class="d-flex align-items-center gap-3">
                  <span class="fw-bold ukn-availability-day__label"><?= htmlspecialchars($day['day']) ?></span>
                  <div class="form-check form-switch mb-0">
                    <input
                      class="form-check-input" type="checkbox" role="switch"
                      id="avail-<?= $day['key'] ?>-toggle" data-availability-toggle
                      <?= $day['enabled'] ? 'checked' : '' ?>
                    >
                    <label class="form-check-label ukn-body-sm" for="avail-<?= $day['key'] ?>-toggle" data-availability-toggle-label>
                      <?= $day['enabled'] ? 'Available' : 'Unavailable' ?>
                    </label>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" aria-label="Add a time slot for <?= htmlspecialchars($day['day']) ?>" data-availability-add-slot<?= $day['enabled'] ? '' : ' hidden' ?>>
                  <span class="ms" aria-hidden="true">add</span>Add Time Slot
                </button>
              </div>
              <div data-availability-slots<?= $day['enabled'] ? '' : ' hidden' ?>>
                <?php foreach ($day['slots'] as $i => $slot): $startId = "avail-{$day['key']}-start-{$i}"; $endId = "avail-{$day['key']}-end-{$i}"; ?>
                  <div class="d-flex align-items-start gap-2 flex-wrap mb-2" data-availability-slot>
                    <div>
                      <label class="ukn-visually-hidden" for="<?= $startId ?>"><?= htmlspecialchars($day['day']) ?> slot start time</label>
                      <input type="time" class="form-control form-control-sm ukn-time-input" id="<?= $startId ?>" value="<?= htmlspecialchars($slot['start']) ?>" data-slot-start>
                    </div>
                    <span class="ukn-body-sm mt-1">to</span>
                    <div>
                      <label class="ukn-visually-hidden" for="<?= $endId ?>"><?= htmlspecialchars($day['day']) ?> slot end time</label>
                      <input type="time" class="form-control form-control-sm ukn-time-input" id="<?= $endId ?>" value="<?= htmlspecialchars($slot['end']) ?>" data-slot-end>
                    </div>
                    <button type="button" class="btn-icon btn-icon-sm" aria-label="Remove this time slot" data-availability-remove-slot>
                      <span class="ms" aria-hidden="true">close</span>
                    </button>
                    <div class="ukn-field-message is-invalid mb-0 w-100" data-slot-error hidden>
                      <span class="ms" aria-hidden="true">error</span>End time must be later than start time.
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <p class="ukn-body-sm mb-0" data-availability-unavailable-label<?= $day['enabled'] ? ' hidden' : '' ?>>Unavailable</p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="ukn-eyebrow mb-2">Current Availability</div>
          <div class="ukn-display"><?= $totalHours ?> hours</div>
          <p class="ukn-body-sm mt-1 mb-3">offered across <?= $enabledDayCount ?> days this week</p>
          <div class="ukn-body-sm pt-3 ukn-border-top">
            <div class="ukn-row-between mb-2"><span>Booked</span><span class="fw-bold"><?= $bookedHours ?> hours</span></div>
            <div class="ukn-row-between mb-2"><span>Open</span><span class="fw-bold"><?= $openHours ?> hours</span></div>
            <div class="ukn-row-between">
              <span>Requests waiting</span>
              <a href="<?= htmlspecialchars(ukn_route_href('learner-requests')) ?>" class="fw-bold ukn-text-accent"><?= $pendingRequestCount ?> &middot; View Requests</a>
            </div>
          </div>
          <div class="form-check form-switch mt-3 mb-0 pt-3 ukn-border-top">
            <input class="form-check-input" type="checkbox" role="switch" id="availAcceptingRequests" data-availability-request-status checked>
            <label class="form-check-label ukn-body-sm" for="availAcceptingRequests" data-availability-request-status-label>Accepting Requests</label>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 mb-4">
    <button type="submit" class="btn btn-primary btn-sm" data-availability-save disabled>Save Availability</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" data-availability-reset>Reset Changes</button>
  </div>
</form>
<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Upcoming Sessions</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('sessions')) ?>" class="ukn-body-sm">View Sessions</a>
  </div>
  <?php foreach ($upcomingSessions as $session): ukn_session_card($session); endforeach; ?>
</div>
<?php endif; ?>