<?php
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentorView = $activeRole === 'mentor';

$requestedId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
$session = false;
$sessionDetailsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $selectBase = "SELECT ms.id, ms.reference_code, ms.status, ms.scheduled_date, ms.scheduled_time,
            ms.duration_minutes, ms.request_message, ms.cancel_reason,
            ms.requested_at, ms.responded_at, ms.completed_at, ms.updated_at,
            sk.id AS skill_id, sk.name AS skill,
            ul.id AS learner_id, ul.full_name AS learner_name, ul.initials AS learner_initials, dl.name AS learner_department,
            um.id AS mentor_id, um.full_name AS mentor_name, um.initials AS mentor_initials, dm.name AS mentor_department,
            um.avg_rating AS mentor_rating, um.sessions_as_mentor AS mentor_sessions, um.mentor_points,
            sr.overall AS rating_value
        FROM mentoring_sessions ms
        JOIN skills sk ON sk.id = ms.skill_id
        JOIN users ul ON ul.id = ms.learner_id
        LEFT JOIN departments dl ON dl.id = ul.department_id
        JOIN users um ON um.id = ms.mentor_id
        LEFT JOIN departments dm ON dm.id = um.department_id
        LEFT JOIN session_ratings sr ON sr.session_id = ms.id
        WHERE (ms.learner_id = ? OR ms.mentor_id = ?) ";

    // Access control: only a participant in the session (learner or mentor) may view it,
    // not just anyone who guesses an id (see DATABASE_READ_INTEGRATION_PLAN.md §2.5).
    $stmt = $pdo->prepare($selectBase . "AND ms.id = ?");
    $stmt->execute([UKN_CURRENT_USER_ID, UKN_CURRENT_USER_ID, $requestedId]);
    // A missing id, a non-existent id and someone else's session all show "Session not found".
    $session = $stmt->fetch();

    if ($session !== false) {
        // Actions depend on the viewer's side in *this* session, and are offered only while
        // their active role matches that side (the endpoints enforce the same rule).
        $viewerSide = (int) $session['mentor_id'] === UKN_CURRENT_USER_ID ? 'mentor' : 'learner';
        $canActOnSession = $viewerSide === $activeRole;
        $isMentorView = $viewerSide === 'mentor';
        $displayStatus = $session['status'] === 'accepted' ? 'upcoming' : $session['status'];

        $skillsStmt = $pdo->prepare(
            "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id = ? AND us.skill_type = 'teaching' ORDER BY s.name"
        );
        $skillsStmt->execute([$session['mentor_id']]);
        $mentorSkills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

        switch ($displayStatus) {
            case 'pending':
                $timeline = 'Requested ' . ukn_time_ago($session['requested_at']);
                break;
            case 'upcoming':
                $timeline = 'Requested ' . ukn_time_ago($session['requested_at'])
                    . ($session['responded_at'] ? ' · Accepted ' . ukn_time_ago($session['responded_at']) : '');
                break;
            case 'rejected':
                $timeline = 'Requested ' . ukn_time_ago($session['requested_at'])
                    . ($session['responded_at'] ? ' · Rejected ' . ukn_time_ago($session['responded_at']) : '');
                break;
            case 'completed':
                $timeline = $session['completed_at']
                    ? 'Completed on ' . date('F j, Y', strtotime($session['completed_at']))
                    : 'Completed';
                break;
            case 'cancelled':
                $timeline = 'Cancelled ' . ukn_time_ago($session['updated_at']);
                break;
            default:
                $timeline = '';
        }

        $timestamp = strtotime($session['scheduled_date'] . ' ' . $session['scheduled_time']);
        $realId = (int) $session['id'];
        $session = [
            'id' => $realId,
            'refId' => $session['reference_code'],
            'status' => $displayStatus,
            'timeline' => $timeline,
            'learner' => [
                'name' => $session['learner_name'],
                'department' => (string) ($session['learner_department'] ?? ''),
                'href' => ukn_route_href('learner-profile') . '&id=' . $session['learner_id'],
            ],
            'mentor' => [
                'name' => $session['mentor_name'],
                'department' => (string) ($session['mentor_department'] ?? ''),
                'initials' => $session['mentor_initials'],
                'rating' => $session['mentor_rating'],
                'sessions' => (int) $session['mentor_sessions'],
                'points' => (int) $session['mentor_points'],
                'skills' => implode(', ', $mentorSkills),
                'href' => ukn_route_href('mentor-profile') . '&id=' . $session['mentor_id'],
            ],
            'skill' => $session['skill'],
            'skillHref' => ukn_route_href('skill-details') . '&id=' . $session['skill_id'],
            'date' => date('F j, Y', $timestamp),
            'time' => date('g:i A', $timestamp),
            'duration' => $session['duration_minutes'] . ' minutes',
            'message' => $displayStatus === 'cancelled' ? $session['cancel_reason'] : $session['request_message'],
            'ratingStatus' => $displayStatus === 'completed'
                ? ($session['rating_value'] !== null ? 'rated' : 'unrated')
                : null,
            'ratingValue' => $session['rating_value'] !== null ? (float) $session['rating_value'] : null,
        ];
    }
} catch (Throwable $e) {
    error_log('[UKN session-details] ' . $e->getMessage());
    $sessionDetailsDbError = true;
}

$backTarget = (isset($_GET['from']) && $_GET['from'] === 'requests') ? 'requests' : 'sessions';
$backHref = $backTarget === 'requests' ? ukn_route_href('learner-requests') : ukn_route_href('sessions');
$backLabel = $backTarget === 'requests' ? 'Back to Learner Requests' : 'Back to Sessions';
$statusMeta = [
    'pending'   => ['label' => 'Pending',   'class' => 'ukn-status-accent'],
    'accepted'  => ['label' => 'Accepted',  'class' => 'ukn-status-success'],
    'upcoming'  => ['label' => 'Upcoming',  'class' => 'ukn-status-accent'],
    'completed' => ['label' => 'Completed', 'class' => 'ukn-status-success'],
    'rejected'  => ['label' => 'Rejected',  'class' => 'ukn-status-danger'],
    'cancelled' => ['label' => 'Cancelled', 'class' => 'ukn-status-danger'],
];
if ($session !== false) {
    $meta = $statusMeta[$session['status']] ?? $statusMeta['upcoming'];
    $facts = [
        ['label' => 'Session', 'value' => '#' . $session['refId']],
        ['label' => 'Learner', 'value' => $session['learner']['name'] . ' · ' . $session['learner']['department'], 'href' => $session['learner']['href']],
        ['label' => 'Mentor', 'value' => $session['mentor']['name'] . ' · ' . $session['mentor']['department'], 'href' => $session['mentor']['href']],
        ['label' => 'Skill', 'value' => $session['skill'], 'href' => $session['skillHref']],
        ['label' => 'Date & Time', 'value' => $session['date'] . ' · ' . $session['time']],
        ['label' => 'Duration', 'value' => $session['duration']],
    ];
}
?>
<div class="ukn-page-header">
  <div>
    <a href="<?= htmlspecialchars($backHref) ?>" class="ukn-body-sm d-inline-flex align-items-center gap-1 mb-2">
      <span class="ms" aria-hidden="true">arrow_back</span><?= htmlspecialchars($backLabel) ?>
    </a>
    <h1>Session Details</h1>
  </div>
</div>
<?php if ($sessionDetailsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load this session.',
      'message' => 'Something went wrong while loading this session. Please try again shortly.',
  ]); ?>
<?php elseif ($session === false): ?>
  <?php ukn_empty_state([
      'icon' => 'event_busy',
      'title' => 'Session not found.',
      'message' => 'This session may not exist or you may not have access to it.',
      'action' => ['label' => 'Back to Sessions', 'href' => htmlspecialchars(ukn_route_href('sessions'))],
  ]); ?>
<?php else: ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3" data-session-id="<?= $session['id'] ?>" data-session-status="<?= htmlspecialchars($session['status']) ?>" data-session-details-href="">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
          <span class="ukn-status <?= $meta['class'] ?>" data-session-status-badge><?= $meta['label'] ?></span>
          <span class="ukn-body-sm"><?= htmlspecialchars($session['timeline']) ?></span>
        </div>

        <?php foreach ($facts as $fact): ?>
          <div class="row ukn-session-fact">
            <div class="col-sm-4 ukn-eyebrow"><?= htmlspecialchars($fact['label']) ?></div>
            <div class="col-sm-8">
              <?php if (!empty($fact['href'])): ?>
                <a href="<?= htmlspecialchars($fact['href']) ?>"><?= htmlspecialchars($fact['value']) ?></a>
              <?php else: ?>
                <?= htmlspecialchars($fact['value']) ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if ($session['message']): ?>
          <div class="mt-3 p-3 ukn-bg-surface-2 ukn-rounded-md">
            <div class="ukn-eyebrow mb-2"><?= $session['status'] === 'cancelled' ? 'Cancellation Note' : 'Request Message' ?></div>
            <p class="ukn-body mb-0"><?= htmlspecialchars($session['message']) ?></p>
          </div>
        <?php endif; ?>
        <div class="d-flex gap-2 flex-wrap mt-3" data-session-actions>
          <?php if ($session['status'] === 'pending' && $isMentorView && $canActOnSession): ?>
            <?php ukn_session_action_form($session['id'], 'accept', null, 'Accept Request'); ?>
            <button
              type="button" class="btn btn-outline-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
              data-delete-title="Reject this request?"
              data-delete-message="The learner will see that this request was not accepted."
              data-delete-confirm-label="Reject"
              data-delete-form="sessionReject-<?= $session['id'] ?>"
            >Reject Request</button>
            <?php ukn_session_action_form($session['id'], 'reject', 'sessionReject-' . $session['id']); ?>
          <?php elseif ($session['status'] === 'pending' && !$isMentorView && $canActOnSession): ?>
            <button
              type="button" class="btn btn-outline-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
              data-delete-title="Cancel this request?"
              data-delete-message="The mentor will no longer see this request."
              data-delete-confirm-label="Cancel Request"
              data-delete-form="sessionCancel-<?= $session['id'] ?>"
            >Cancel Request</button>
            <?php ukn_session_action_form($session['id'], 'cancel', 'sessionCancel-' . $session['id']); ?>
          <?php elseif ($session['status'] === 'upcoming' && $canActOnSession): ?>
            <?php if ($isMentorView): ?>
              <?php ukn_session_action_form($session['id'], 'complete', null, 'Mark Session Complete'); ?>
            <?php endif; ?>
            <button
              type="button" class="btn btn-outline-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
              data-delete-title="Cancel Session?"
              data-delete-message="Are you sure you want to cancel this session? Cancelling within 6 hours of the start costs 10 points."
              data-delete-confirm-label="Cancel Session"
              data-delete-form="sessionCancel-<?= $session['id'] ?>"
            >Cancel Session</button>
            <?php ukn_session_action_form($session['id'], 'cancel', 'sessionCancel-' . $session['id']); ?>
          <?php elseif ($session['status'] === 'completed' && !$isMentorView): ?>
            <?php if ($session['ratingStatus'] === 'unrated' && $canActOnSession): ?>
              <button
                type="button" class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#ratingModal"
                data-rating-session-id="<?= (int) $session['id'] ?>"
                data-rating-mentor="<?= htmlspecialchars($session['mentor']['name']) ?>"
                data-rating-skill="<?= htmlspecialchars($session['skill']) ?>"
                data-rating-date="<?= htmlspecialchars($session['date']) ?>"
              >Rate Mentor</button>
            <?php elseif ($session['ratingStatus'] === 'rated'): ?>
              <span class="ukn-status ukn-status-success">Rated <?= htmlspecialchars((string) $session['ratingValue']) ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div class="ukn-eyebrow mb-3">Mentor</div>
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="ukn-avatar ukn-avatar-lg flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($session['mentor']['initials']) ?></span>
          <div class="ukn-min-w-0">
            <div class="fw-bold ukn-truncate"><?= htmlspecialchars($session['mentor']['name']) ?></div>
            <div class="ukn-body-sm">★ <?= htmlspecialchars((string) $session['mentor']['rating']) ?></div>
          </div>
        </div>
        <div class="ukn-body-sm pt-3 ukn-border-top">
          Teaches <?= htmlspecialchars($session['mentor']['skills']) ?><br>
          <?= (int) $session['mentor']['sessions'] ?> completed sessions<br>
          <?= (int) $session['mentor']['points'] ?> mentor points
        </div>
        <a href="<?= htmlspecialchars($session['mentor']['href']) ?>" class="btn btn-outline-secondary btn-sm w-100 mt-3">View Profile</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>