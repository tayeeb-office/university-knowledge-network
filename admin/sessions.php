<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'sessions';
$adminPageTitle = 'Sessions';
$adminPageSub = 'Monitor mentoring sessions, requests and session status across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/moderation.js'];

$statusLabels = ['pending' => 'Pending', 'accepted' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'ukn-status-neutral', 'accepted' => 'ukn-status-accent', 'completed' => 'ukn-status-accent', 'cancelled' => 'ukn-status-neutral', 'rejected' => 'ukn-status-neutral'];
// Matches the filter <select>'s option values exactly (accepted -> "upcoming"; every other
// status's filter value is already identical to its raw DB enum value).
$statusFilterValues = ['pending' => 'pending', 'accepted' => 'upcoming', 'completed' => 'completed', 'cancelled' => 'cancelled', 'rejected' => 'rejected'];

$sessions = [];
$skillOptions = [];
$statCounts = ['total' => 0, 'pending' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0];
$sessionsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query(
        "SELECT ms.id, ms.reference_code, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                ms.status, ms.request_message, ms.cancel_reason,
                l.id AS learner_id, l.full_name AS learner_name, dl.name AS learner_department,
                m.id AS mentor_id, m.full_name AS mentor_name, dm.name AS mentor_department,
                sk.name AS skill, sr.overall AS rating
         FROM mentoring_sessions ms
         JOIN users l ON l.id = ms.learner_id
         JOIN users m ON m.id = ms.mentor_id
         JOIN skills sk ON sk.id = ms.skill_id
         LEFT JOIN departments dl ON dl.id = l.department_id
         LEFT JOIN departments dm ON dm.id = m.department_id
         LEFT JOIN session_ratings sr ON sr.session_id = ms.id
         ORDER BY ms.requested_at DESC"
    );
    $rows = $stmt->fetchAll();

    $skillSet = [];
    foreach ($rows as $row) {
        $statCounts['total']++;
        if (isset($statCounts[$row['status']])) {
            $statCounts[$row['status']]++;
        }
        $skillSet[$row['skill']] = true;

        $message = $row['status'] === 'cancelled' ? $row['cancel_reason'] : $row['request_message'];

        $sessions[] = [
            'id' => $row['reference_code'],
            'learner' => [
                'id' => (int) $row['learner_id'],
                'name' => $row['learner_name'],
                'department' => (string) ($row['learner_department'] ?? ''),
            ],
            'mentor' => [
                'id' => (int) $row['mentor_id'],
                'name' => $row['mentor_name'],
                'department' => (string) ($row['mentor_department'] ?? ''),
            ],
            'skill' => $row['skill'],
            'date' => date('F j, Y', strtotime($row['scheduled_date'])),
            'dateSort' => $row['scheduled_date'],
            'time' => date('g:i A', strtotime($row['scheduled_time'])),
            'duration' => $row['duration_minutes'] . ' minutes',
            'status' => $row['status'],
            'message' => $message,
            'rating' => $row['status'] === 'completed' && $row['rating'] !== null ? (float) $row['rating'] : null,
        ];
    }
    $skillOptions = array_keys($skillSet);
    sort($skillOptions);
} catch (Throwable $e) {
    error_log('[UKN admin/sessions] ' . $e->getMessage());
    $sessionsDbError = true;
    $sessions = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($sessionsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load sessions.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-4" data-session-summary>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="">
    <?php ukn_stat_card(['label' => 'Total Sessions', 'value' => number_format($statCounts['total']), 'icon' => 'event']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="pending">
    <?php ukn_stat_card(['label' => 'Pending', 'value' => number_format($statCounts['pending']), 'icon' => 'hourglass_empty']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="upcoming">
    <?php ukn_stat_card(['label' => 'Upcoming', 'value' => number_format($statCounts['accepted']), 'icon' => 'event_available']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="completed">
    <?php ukn_stat_card(['label' => 'Completed', 'value' => number_format($statCounts['completed']), 'icon' => 'check_circle']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="cancelled">
    <?php ukn_stat_card(['label' => 'Cancelled', 'value' => number_format($statCounts['cancelled']), 'icon' => 'cancel']); ?>
  </button>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="sessionSearchInput" class="ukn-visually-hidden">Search by learner, mentor, skill or session ID</label>
        <input type="search" id="sessionSearchInput" class="form-control" placeholder="Search by learner, mentor, skill or session ID…" data-session-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="sessionStatusFilter">Filter by status</label>
      <select id="sessionStatusFilter" class="form-select form-select-sm" data-session-filter="status">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="upcoming">Upcoming</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="rejected">Rejected</option>
      </select>
      <label class="ukn-visually-hidden" for="sessionSkillFilter">Filter by skill</label>
      <select id="sessionSkillFilter" class="form-select form-select-sm" data-session-filter="skill">
        <option value="">All Skills</option>
        <?php foreach ($skillOptions as $skill): ?>
          <option value="<?= htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="ukn-visually-hidden" for="sessionDateFilter">Filter by date</label>
      <select id="sessionDateFilter" class="form-select form-select-sm" data-session-filter="date">
        <option value="">All Dates</option>
        <option value="upcoming">Upcoming Only</option>
        <option value="past">Past Only</option>
      </select>
      <label class="ukn-visually-hidden" for="sessionSort">Sort sessions</label>
      <select id="sessionSort" class="form-select form-select-sm" data-session-sort>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="upcoming-first">Upcoming First</option>
        <option value="recently-completed">Recently Completed</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-session-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>
<p class="ukn-body-sm ukn-text-muted" data-session-result-count role="status"><?= count($sessions) ?> sessions found</p>
<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-session-table>
      <thead>
        <tr>
          <th scope="col">Session ID</th>
          <th scope="col">Learner</th>
          <th scope="col">Mentor</th>
          <th scope="col">Skill</th>
          <th scope="col">Date &amp; Time</th>
          <th scope="col">Duration</th>
          <th scope="col">Status</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sessions as $s):
            $learner = $s['learner'];
            $mentor = $s['mentor'];
            $canCancel = in_array($s['status'], ['pending', 'accepted'], true);
            $cancelLabel = $s['status'] === 'pending' ? 'Cancel Request' : 'Cancel Session';
        ?>
          <tr
            data-session-row
            data-session-id="<?= htmlspecialchars($s['id']) ?>"
            data-session-search="<?= htmlspecialchars(strtolower($s['id'] . ' ' . $learner['name'] . ' ' . $mentor['name'] . ' ' . $s['skill'])) ?>"
            data-session-status="<?= htmlspecialchars($statusFilterValues[$s['status']] ?? $s['status']) ?>"
            data-session-skill="<?= htmlspecialchars(strtolower($s['skill'])) ?>"
            data-session-date-sort="<?= htmlspecialchars($s['dateSort']) ?>"
            data-session-learner-id="<?= $learner['id'] ?>"
            data-session-learner-name="<?= htmlspecialchars($learner['name']) ?>"
            data-session-learner-department="<?= htmlspecialchars($learner['department']) ?>"
            data-session-mentor-id="<?= $mentor['id'] ?>"
            data-session-mentor-name="<?= htmlspecialchars($mentor['name']) ?>"
            data-session-mentor-department="<?= htmlspecialchars($mentor['department']) ?>"
            data-session-skill-label="<?= htmlspecialchars($s['skill']) ?>"
            data-session-date="<?= htmlspecialchars($s['date']) ?>"
            data-session-time="<?= htmlspecialchars($s['time']) ?>"
            data-session-duration="<?= htmlspecialchars($s['duration']) ?>"
            data-session-message="<?= htmlspecialchars($s['message'] ?? '') ?>"
            data-session-rating="<?= htmlspecialchars($s['rating'] !== null ? number_format($s['rating'], 1) : '') ?>"
          >
            <td data-label="Session ID" class="fw-bold"><?= htmlspecialchars($s['id']) ?></td>
            <td data-label="Learner">
              <a href="user-details.php?id=<?= $learner['id'] ?>" aria-label="View <?= htmlspecialchars($learner['name']) ?> in Admin"><?= htmlspecialchars($learner['name']) ?></a>
              <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($learner['department']) ?></div>
            </td>
            <td data-label="Mentor">
              <a href="user-details.php?id=<?= $mentor['id'] ?>" aria-label="View <?= htmlspecialchars($mentor['name']) ?> in Admin"><?= htmlspecialchars($mentor['name']) ?></a>
              <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($mentor['department']) ?></div>
            </td>
            <td data-label="Skill"><?= htmlspecialchars($s['skill']) ?></td>
            <td data-label="Date &amp; Time"><?= htmlspecialchars($s['date']) ?><div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($s['time']) ?></div></td>
            <td data-label="Duration"><?= htmlspecialchars($s['duration']) ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$s['status']] ?>" data-session-status-badge><?= htmlspecialchars($statusLabels[$s['status']]) ?></span></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#sessionDetailModal" data-session-view>View</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Session actions for <?= htmlspecialchars($s['id']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-session-cancel
                        <?= $canCancel ? '' : 'hidden' ?>
                        data-delete-title="Cancel Session?"
                        data-delete-message="Cancel <?= htmlspecialchars($s['id']) ?> between <?= htmlspecialchars($learner['name']) ?> and <?= htmlspecialchars($mentor['name']) ?>? This is a demo action. No participants will be notified and no backend data will be changed."
                        data-delete-confirm-label="<?= htmlspecialchars($cancelLabel) ?>"
                        data-success-message="Session cancelled in demo mode."
                      ><?= htmlspecialchars($cancelLabel) ?></button>
                    </li>
                    <?php if (!$canCancel): ?>
                      <li><span class="dropdown-item-text ukn-body-sm ukn-text-muted">No further action available.</span></li>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body"<?= $sessions ? ' hidden' : '' ?> data-session-empty>
    <?php ukn_empty_state([
        'icon' => 'event_busy',
        'title' => 'No sessions found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-session-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body ukn-admin-pagination" data-session-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-session-pagination-summary></span>
    <div class="d-flex gap-1" data-session-pagination-pages></div>
  </div>
</div>
<div class="modal fade" id="sessionDetailModal" tabindex="-1" aria-labelledby="sessionDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="sessionDetailModalLabel">Session Details</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="fw-bold" data-session-detail="id">—</span>
          <span class="ukn-status ukn-status-accent" data-session-detail="status">—</span>
        </div>
        <div class="ukn-admin-row">
          <span class="ukn-body-sm ukn-text-muted">Learner</span>
          <span class="ms-auto text-end"><a href="#" data-session-detail-link="learner">—</a><span class="d-block ukn-body-sm ukn-text-muted" data-session-detail="learnerDepartment"></span></span>
        </div>
        <div class="ukn-admin-row">
          <span class="ukn-body-sm ukn-text-muted">Mentor</span>
          <span class="ms-auto text-end"><a href="#" data-session-detail-link="mentor">—</a><span class="d-block ukn-body-sm ukn-text-muted" data-session-detail="mentorDepartment"></span></span>
        </div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Skill</span><span class="fw-bold ms-auto" data-session-detail="skill">—</span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Date</span><span class="fw-bold ms-auto" data-session-detail="date">—</span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Time</span><span class="fw-bold ms-auto" data-session-detail="time">—</span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Duration</span><span class="fw-bold ms-auto" data-session-detail="duration">—</span></div>
        <div class="ukn-admin-row" hidden data-session-detail-rating-row>
          <span class="ukn-body-sm ukn-text-muted">Rating</span><span class="fw-bold ms-auto" data-session-detail="rating">—</span>
        </div>
        <div class="mt-3" hidden data-session-detail-message-row>
          <div class="ukn-eyebrow mb-1">Request Message</div>
          <p class="ukn-body-sm mb-0" data-session-detail="message"></p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
