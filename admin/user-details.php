<?php
/**
 * Admin User Details — the "View" destination from admin/users.php (and
 * admin/dashboard.php's Recent Users table). Reuses the exact same
 * admin/includes/users-data.php mock dataset users.php reads, so the two
 * pages can never disagree about the same id.
 *
 * ?id= is only ever used to look up a key in that in-memory mock array —
 * never as a file path, never in an include(). An id that isn't in the
 * dataset (missing, non-numeric, or simply unknown) renders a clean
 * "User Not Found" state instead of a PHP warning or a silently-wrong
 * fallback user. "Users" stays the active sidebar item even here, since
 * this page is reached FROM Users, not from its own nav entry.
 *
 * No password/hash/token of any kind is ever read or displayed. Suspend/
 * Restore reuses the same shared modals/delete-confirmation-modal.php +
 * assets/js/admin/users.js mock state change users.php's table uses —
 * not a second confirmation mechanism.
 */
require_once __DIR__ . '/includes/users-data.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'users';
$users = ukn_admin_mock_users();

$requestedId = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : null;
$user = ($requestedId !== null && isset($users[$requestedId])) ? $users[$requestedId] : null;

$adminPageTitle = $user ? $user['name'] : 'User Not Found';
$adminPageSub = $user ? 'Admin management view for this user.' : '';
$adminPageStyles = ['../assets/css/admin/tables.css'];
$adminPageScripts = $user ? ['../assets/js/admin/users.js'] : [];

require __DIR__ . '/includes/header.php';

if (!$user) {
    ?>
    <div class="ukn-state ukn-state--dashed">
      <span class="ms" aria-hidden="true">person_off</span>
      <div class="ukn-state__title">User Not Found</div>
      <p class="ukn-state__text">This user id doesn't match any account in the current mock dataset.</p>
      <a href="users.php" class="btn btn-primary btn-sm">Back to Users</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    return;
}

$roleLabels = ['learner' => 'Learner', 'mentor' => 'Mentor', 'dual' => 'Learner + Mentor'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral', 'suspended' => 'ukn-status-neutral'];
$isSuspended = $user['status'] === 'suspended';

function ukn_admin_user_sessions(int $id, array $user): array
{
    $known = [
        1 => [
            ['participant' => 'Rahim Ahmed', 'skill' => 'Python', 'date' => 'Sep 18, 2026', 'role' => 'Learner', 'status' => 'Upcoming'],
            ['participant' => 'Rahim Ahmed', 'skill' => 'Python', 'date' => 'Sep 8, 2026', 'role' => 'Learner', 'status' => 'Completed'],
        ],
        2 => [
            ['participant' => 'Nabila Rahman', 'skill' => 'Python', 'date' => 'Sep 8, 2026', 'role' => 'Mentor', 'status' => 'Completed'],
            ['participant' => 'Tanvir Hossain', 'skill' => 'Data Analysis', 'date' => 'Sep 4, 2026', 'role' => 'Mentor', 'status' => 'Completed'],
        ],
        4 => [
            ['participant' => 'Mahi Noor', 'skill' => 'Embedded Systems', 'date' => 'Aug 18, 2026', 'role' => 'Mentor', 'status' => 'Completed'],
            ['participant' => 'Nabila Rahman', 'skill' => 'Arduino', 'date' => 'Aug 28, 2026', 'role' => 'Mentor', 'status' => 'Cancelled'],
        ],
    ];
    if (isset($known[$id])) {
        return $known[$id];
    }
    $isMentorSession = !empty($user['mentor']);
    $skill = $isMentorSession ? ($user['mentor']['skills'][0] ?? 'General') : ($user['learner']['skills'][0] ?? 'General');
    return [
        ['participant' => $isMentorSession ? 'A learner' : 'A mentor', 'skill' => $skill, 'date' => 'Sep 5, 2026', 'role' => $isMentorSession ? 'Mentor' : 'Learner', 'status' => 'Completed'],
    ];
}

function ukn_admin_user_activity(array $user): array
{
    $activity = [];
    if (!empty($user['learner'])) {
        $activity[] = ['icon' => 'event_available', 'text' => 'Completed a ' . $user['learner']['skills'][0] . ' learning session.', 'time' => '3 days ago'];
        if (count($user['learner']['skills']) > 1) {
            $activity[] = ['icon' => 'add_circle', 'text' => 'Added ' . end($user['learner']['skills']) . ' to Learning Skills.', 'time' => '2 weeks ago'];
        }
    }
    if (!empty($user['mentor'])) {
        $activity[] = ['icon' => 'event_available', 'text' => 'Completed a ' . $user['mentor']['skills'][0] . ' mentoring session.', 'time' => '4 days ago'];
        $activity[] = ['icon' => 'star', 'text' => 'Received a ' . $user['mentor']['rating'] . '-star mentoring rating.', 'time' => '1 week ago'];
    }
    return $activity;
}

$sessionsPreview = ukn_admin_user_sessions($requestedId, $user);
$activity = ukn_admin_user_activity($user);
?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap" data-user-row data-user-id="<?= $requestedId ?>" data-user-status="<?= htmlspecialchars($user['status']) ?>">
      <span class="ukn-avatar ukn-avatar-lg flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($user['initials']) ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h2 class="ukn-h3 mb-0"><?= htmlspecialchars($user['name']) ?></h2>
          <span class="ukn-role-chip"><?= htmlspecialchars($roleLabels[$user['role']]) ?></span>
          <span class="ukn-status <?= $statusClass[$user['status']] ?>" data-user-status-badge><?= htmlspecialchars($statusLabels[$user['status']]) ?></span>
        </div>
        <div class="ukn-body-sm ukn-text-muted mt-1"><?= htmlspecialchars($user['department']) ?> &middot; <?= htmlspecialchars($user['universityId']) ?></div>
        <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="d-flex flex-column gap-2 flex-shrink-0">
        <a href="users.php" class="btn btn-outline-secondary btn-sm">Back to Users</a>
        <button
          type="button"
          class="btn btn-outline-danger btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#deleteConfirmationModal"
          data-suspend-user
          <?= $isSuspended ? 'hidden' : '' ?>
          data-delete-title="Suspend <?= htmlspecialchars($user['name']) ?>?"
          data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
          data-delete-confirm-label="Suspend User"
          data-success-message="User suspended in demo mode."
        >Suspend User</button>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#deleteConfirmationModal"
          data-restore-user
          <?= $isSuspended ? '' : 'hidden' ?>
          data-delete-title="Restore <?= htmlspecialchars($user['name']) ?>?"
          data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
          data-delete-confirm-label="Restore User"
          data-success-message="User restored in demo mode."
        >Restore User</button>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><h3 class="ukn-h4 mb-0">Account Overview</h3></div>
      <div class="card-body">
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Full Name</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['name']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Email</span><span class="fw-bold ms-auto ukn-truncate"><?= htmlspecialchars($user['email']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">University ID</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['universityId']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Department</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['department']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Joined</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['joined']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Last Active</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['lastActive']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Roles</span><span class="fw-bold ms-auto"><?= htmlspecialchars($roleLabels[$user['role']]) ?></span></div>
        <?php if ($isSuspended && !empty($user['suspendReason'])): ?>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Suspend Reason</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['suspendReason']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if (!empty($user['learner'])): ?>
      <div class="card mb-3">
        <div class="card-header"><h3 class="ukn-h4 mb-0">Learner Activity</h3></div>
        <div class="card-body">
          <div class="ukn-admin-stat-grid mb-3">
            <?php
            ukn_stat_card(['label' => 'Learning Points', 'value' => (string) $user['learner']['points'], 'icon' => 'military_tech']);
            ukn_stat_card(['label' => 'Completed Sessions', 'value' => (string) $user['learner']['sessions'], 'icon' => 'event_available']);
            ukn_stat_card(['label' => 'Active Goals', 'value' => (string) $user['learner']['goals'], 'icon' => 'flag']);
            ukn_stat_card(['label' => 'Community Posts', 'value' => (string) $user['learner']['posts'], 'icon' => 'article']);
            ?>
          </div>
          <div class="ukn-eyebrow mb-2">Learning Skills</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($user['learner']['skills'] as $skill): ?>
              <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($user['mentor'])): ?>
      <div class="card mb-3">
        <div class="card-header"><h3 class="ukn-h4 mb-0">Mentor Activity</h3></div>
        <div class="card-body">
          <div class="ukn-admin-stat-grid mb-3">
            <?php
            ukn_stat_card(['label' => 'Mentor Points', 'value' => (string) $user['mentor']['points'], 'icon' => 'military_tech']);
            ukn_stat_card(['label' => 'Rating', 'value' => number_format($user['mentor']['rating'], 1), 'icon' => 'star']);
            ukn_stat_card(['label' => 'Completed Sessions', 'value' => (string) $user['mentor']['sessions'], 'icon' => 'event_available']);
            ukn_stat_card(['label' => 'Learners Helped', 'value' => (string) $user['mentor']['learnersHelped'], 'icon' => 'group']);
            ?>
          </div>
          <div class="ukn-eyebrow mb-2">Teaching Skills</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($user['mentor']['skills'] as $skill): ?>
              <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="ukn-h4 mb-0">Recent Sessions</h3>
        <a href="sessions.php" class="ukn-body-sm">View All Sessions</a>
      </div>
      <div class="table-responsive">
        <table class="table ukn-admin-table">
          <thead>
            <tr><th scope="col">Other Participant</th><th scope="col">Skill</th><th scope="col">Date</th><th scope="col">Role in Session</th><th scope="col">Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sessionsPreview as $session): ?>
              <tr>
                <td data-label="Other Participant"><?= htmlspecialchars($session['participant']) ?></td>
                <td data-label="Skill"><?= htmlspecialchars($session['skill']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($session['date']) ?></td>
                <td data-label="Role in Session"><?= htmlspecialchars($session['role']) ?></td>
                <td data-label="Status"><span class="ukn-status ukn-status-accent"><?= htmlspecialchars($session['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="ukn-h4 mb-0">Recent Activity</h3></div>
      <div class="card-body">
        <?php if ($activity): ?>
          <?php foreach ($activity as $entry): ?>
            <div class="ukn-admin-row">
              <span class="ms ukn-text-accent flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($entry['icon']) ?></span>
              <span class="flex-fill ukn-min-w-0"><?= htmlspecialchars($entry['text']) ?></span>
              <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= htmlspecialchars($entry['time']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="ukn-body-sm ukn-text-muted mb-0">No recent activity for this user yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
