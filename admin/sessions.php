<?php
/**
 * Admin Sessions Management — inspection/moderation view over mentoring
 * sessions. Participant ids match admin/includes/users-data.php exactly
 * (Nabila Rahman=1, Rahim Ahmed=2, Imran Chowdhury=3, Hasan Mahmud=4,
 * Ayesha Rahman=5, Sara Khan=6, Farhan Kabir=7, Nusrat Jahan=8, Mahi
 * Noor=9, Tanvir Hossain=10, ...), so every participant link routes to
 * the exact same Admin User Details record Prompt 25 already built.
 *
 * There is no admin/session-details.php — "View" opens one reusable
 * detail modal (#sessionDetailModal) populated from the clicked row's
 * own data-* attributes, the same "read from whichever element
 * triggered it" convention every other shared Admin/user-facing modal in
 * this project already uses. Cancel reuses the one shared
 * modals/delete-confirmation-modal.php exactly like Suspend/Restore
 * (admin/users.php) and Deactivate/Activate (admin/departments.php,
 * skill-categories.php, skills.php) — no second confirmation mechanism.
 *
 * The summary cards intentionally reuse admin/dashboard.php's own
 * already-established network-wide totals (3,482 / 36 / 128 / 3,142 /
 * 176) for cross-page consistency, while the interactive table below
 * demonstrates search/filter/sort/pagination/cancel against a smaller,
 * honestly-labeled mock sample — the same resolution admin/users.php
 * already established for its own summary-vs-table numbers.
 *
 * Allowed mock status transitions are deliberately narrow: Pending or
 * Upcoming -> Cancelled only. Completed/Cancelled/Rejected sessions have
 * no status-changing action at all — no arbitrary status editor, no
 * point/rating mutation, no participant notification of any kind.
 */
require_once __DIR__ . '/includes/users-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'sessions';
$adminPageTitle = 'Sessions';
$adminPageSub = 'Monitor mentoring sessions, requests and session status across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/moderation.js'];

$users = ukn_admin_mock_users();
function ukn_admin_participant(array $users, int $id): array
{
    $u = $users[$id];
    return ['id' => $id, 'name' => $u['name'], 'initials' => $u['initials'], 'department' => $u['department']];
}

$sessions = [
    ['id' => 'UKN-S-1048', 'learner' => 1, 'mentor' => 2, 'skill' => 'Python', 'date' => 'September 18, 2026', 'dateSort' => '2026-09-18', 'time' => '7:00 PM', 'duration' => '60 minutes', 'status' => 'upcoming', 'message' => 'I need help understanding Python data analysis fundamentals and working with pandas.'],
    ['id' => 'UKN-S-1049', 'learner' => 5, 'mentor' => 2, 'skill' => 'Database Design', 'date' => 'September 21, 2026', 'dateSort' => '2026-09-21', 'time' => '8:00 PM', 'duration' => '60 minutes', 'status' => 'pending', 'message' => 'Could we go over normalizing a schema with foreign keys?'],
    ['id' => 'UKN-S-1050', 'learner' => 3, 'mentor' => 6, 'skill' => 'Public Speaking', 'date' => 'September 20, 2026', 'dateSort' => '2026-09-20', 'time' => '6:30 PM', 'duration' => '45 minutes', 'status' => 'upcoming', 'message' => null],
    ['id' => 'UKN-S-1039', 'learner' => 1, 'mentor' => 2, 'skill' => 'Python', 'date' => 'September 8, 2026', 'dateSort' => '2026-09-08', 'time' => '7:00 PM', 'duration' => '60 minutes', 'status' => 'completed', 'rating' => 5.0, 'message' => null],
    ['id' => 'UKN-S-1035', 'learner' => 5, 'mentor' => 7, 'skill' => 'MySQL', 'date' => 'September 5, 2026', 'dateSort' => '2026-09-05', 'time' => '8:00 PM', 'duration' => '60 minutes', 'status' => 'completed', 'rating' => null, 'message' => null],
    ['id' => 'UKN-S-1028', 'learner' => 10, 'mentor' => 4, 'skill' => 'Arduino', 'date' => 'August 28, 2026', 'dateSort' => '2026-08-28', 'time' => '3:00 PM', 'duration' => '45 minutes', 'status' => 'cancelled', 'message' => 'Cancelled by learner — schedule clash with lab.'],
    ['id' => 'UKN-S-1055', 'learner' => 13, 'mentor' => 6, 'skill' => 'Public Speaking', 'date' => 'September 15, 2026', 'dateSort' => '2026-09-15', 'time' => '6:00 PM', 'duration' => '60 minutes', 'status' => 'rejected', 'message' => null],
    ['id' => 'UKN-S-1051', 'learner' => 9, 'mentor' => 2, 'skill' => 'Python', 'date' => 'September 22, 2026', 'dateSort' => '2026-09-22', 'time' => '5:00 PM', 'duration' => '45 minutes', 'status' => 'pending', 'message' => null],
    ['id' => 'UKN-S-1052', 'learner' => 1, 'mentor' => 7, 'skill' => 'MySQL', 'date' => 'September 25, 2026', 'dateSort' => '2026-09-25', 'time' => '7:30 PM', 'duration' => '60 minutes', 'status' => 'upcoming', 'message' => null],
    ['id' => 'UKN-S-1053', 'learner' => 11, 'mentor' => 8, 'skill' => 'Academic Writing', 'date' => 'September 19, 2026', 'dateSort' => '2026-09-19', 'time' => '5:30 PM', 'duration' => '30 minutes', 'status' => 'pending', 'message' => null],
    ['id' => 'UKN-S-1029', 'learner' => 12, 'mentor' => 4, 'skill' => 'Arduino', 'date' => 'August 20, 2026', 'dateSort' => '2026-08-20', 'time' => '4:00 PM', 'duration' => '45 minutes', 'status' => 'completed', 'rating' => 4.6, 'message' => null],
    ['id' => 'UKN-S-1030', 'learner' => 10, 'mentor' => 2, 'skill' => 'Data Analysis', 'date' => 'August 15, 2026', 'dateSort' => '2026-08-15', 'time' => '6:00 PM', 'duration' => '60 minutes', 'status' => 'completed', 'rating' => 4.5, 'message' => null],
    ['id' => 'UKN-S-1040', 'learner' => 1, 'mentor' => 2, 'skill' => 'Data Analysis', 'date' => 'September 1, 2026', 'dateSort' => '2026-09-01', 'time' => '7:00 PM', 'duration' => '60 minutes', 'status' => 'completed', 'rating' => 4.8, 'message' => null],
    ['id' => 'UKN-S-1041', 'learner' => 14, 'mentor' => 6, 'skill' => 'Digital Marketing', 'date' => 'September 10, 2026', 'dateSort' => '2026-09-10', 'time' => '6:00 PM', 'duration' => '30 minutes', 'status' => 'completed', 'rating' => 4.0, 'message' => null],
    ['id' => 'UKN-S-1042', 'learner' => 3, 'mentor' => 8, 'skill' => 'Academic Writing', 'date' => 'September 23, 2026', 'dateSort' => '2026-09-23', 'time' => '5:00 PM', 'duration' => '45 minutes', 'status' => 'upcoming', 'message' => null],
    ['id' => 'UKN-S-1060', 'learner' => 15, 'mentor' => 4, 'skill' => 'Arduino', 'date' => 'August 10, 2026', 'dateSort' => '2026-08-10', 'time' => '2:00 PM', 'duration' => '30 minutes', 'status' => 'cancelled', 'message' => 'Cancelled by mentor — unavailable.'],
];

$statusLabels = ['pending' => 'Pending', 'upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'];
$statusClass = ['pending' => 'ukn-status-neutral', 'upcoming' => 'ukn-status-accent', 'completed' => 'ukn-status-accent', 'cancelled' => 'ukn-status-neutral', 'rejected' => 'ukn-status-neutral'];

$skillOptions = [];
foreach ($sessions as $s) {
    $skillOptions[$s['skill']] = true;
}
$skillOptions = array_keys($skillOptions);
sort($skillOptions);

require __DIR__ . '/includes/header.php';
?>
<div class="ukn-admin-stat-grid mb-4" data-session-summary>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="">
    <?php ukn_stat_card(['label' => 'Total Sessions', 'value' => '3,482', 'icon' => 'event']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="pending">
    <?php ukn_stat_card(['label' => 'Pending', 'value' => '36', 'icon' => 'hourglass_empty']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="upcoming">
    <?php ukn_stat_card(['label' => 'Upcoming', 'value' => '128', 'icon' => 'event_available']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="completed">
    <?php ukn_stat_card(['label' => 'Completed', 'value' => '3,142', 'icon' => 'check_circle']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-session-summary-filter="cancelled">
    <?php ukn_stat_card(['label' => 'Cancelled', 'value' => '176', 'icon' => 'cancel']); ?>
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
            $learner = ukn_admin_participant($users, $s['learner']);
            $mentor = ukn_admin_participant($users, $s['mentor']);
            $canCancel = in_array($s['status'], ['pending', 'upcoming'], true);
            $cancelLabel = $s['status'] === 'pending' ? 'Cancel Request' : 'Cancel Session';
        ?>
          <tr
            data-session-row
            data-session-id="<?= htmlspecialchars($s['id']) ?>"
            data-session-search="<?= htmlspecialchars(strtolower($s['id'] . ' ' . $learner['name'] . ' ' . $mentor['name'] . ' ' . $s['skill'])) ?>"
            data-session-status="<?= htmlspecialchars($s['status']) ?>"
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
            data-session-rating="<?= htmlspecialchars(isset($s['rating']) ? number_format($s['rating'], 1) : '') ?>"
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

  <div class="card-body" hidden data-session-empty>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
