<?php
/**
 * Availability — main center content only. Routed via
 * index.php?page=availability (see index.php's $routes map). The header,
 * left sidebar and footer come from the shell — not from here. This
 * route isn't in index.php's $sidebarContextByPage map, so it renders
 * full-width with no app-level right sidebar (matching docs/ui/'s own
 * layout, which gives this screen its own internal two-column grid —
 * weekly schedule + a compact "current availability" summary panel —
 * rather than using the app shell's contextual sidebar for that).
 *
 * Mentor-oriented, but frontend-only: opening this route in Learner mode
 * does not corrupt role state or break the shell (no real authorization
 * exists yet) — it just shows the same mentor-facing content regardless
 * of the currently active role.
 *
 * Weekly availability is plain mock PHP data (day/enabled/slots), matching
 * this prompt's own suggested "backend-ready" shape. All interactivity —
 * day toggle, add/remove slot, time validation, dirty-tracking the Save
 * button, Save/Reset — lives in assets/js/pages/learning.js. No real
 * persistence, booking-conflict detection or scheduling logic anywhere.
 */
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/session-card.php';

$week = [
    ['day' => 'Monday', 'key' => 'mon', 'enabled' => false, 'slots' => []],
    ['day' => 'Tuesday', 'key' => 'tue', 'enabled' => true, 'slots' => [['start' => '18:00', 'end' => '20:00']]],
    ['day' => 'Wednesday', 'key' => 'wed', 'enabled' => true, 'slots' => [['start' => '19:00', 'end' => '21:00']]],
    ['day' => 'Thursday', 'key' => 'thu', 'enabled' => false, 'slots' => []],
    ['day' => 'Friday', 'key' => 'fri', 'enabled' => true, 'slots' => [['start' => '18:00', 'end' => '20:00']]],
    ['day' => 'Saturday', 'key' => 'sat', 'enabled' => true, 'slots' => [['start' => '16:00', 'end' => '20:00']]],
    ['day' => 'Sunday', 'key' => 'sun', 'enabled' => true, 'slots' => [['start' => '17:00', 'end' => '20:00']]],
];

$toMinutes = static fn (string $t): int => (int) (explode(':', $t)[0]) * 60 + (int) (explode(':', $t)[1]);

$enabledDayCount = 0;
$totalMinutes = 0;
foreach ($week as $day) {
    if ($day['enabled']) {
        $enabledDayCount++;
    }
    foreach ($day['slots'] as $slot) {
        $totalMinutes += $toMinutes($slot['end']) - $toMinutes($slot['start']);
    }
}
$totalHours = (int) round($totalMinutes / 60);
$bookedHours = 8; // mock — no real session-booking model backs this
$openHours = max(0, $totalHours - $bookedHours);
$pendingRequestCount = 5;

$upcomingSessions = [
    ['counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'skill' => 'Python', 'day' => '19', 'month' => 'Sep', 'time' => 'Sat 6:00pm', 'duration' => '60 min', 'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details')],
    ['counterparty' => 'Sara Khan', 'counterpartyInitials' => 'SK', 'skill' => 'Database Design', 'day' => '23', 'month' => 'Sep', 'time' => 'Wed 7:00pm', 'duration' => '45 min', 'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details')],
];
?>
<div class="ukn-page-header">
  <div>
    <h1>Availability</h1>
    <p class="ukn-page-header__sub">Set the days and times when you're available for mentoring sessions.</p>
  </div>
</div>

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
