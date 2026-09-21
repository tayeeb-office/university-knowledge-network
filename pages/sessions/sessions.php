<?php
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';

// TODO(auth): replace with the real session user id; mirrors index.php's own hardcoded
// demo identity (Nabila Rahman, user id 1) until real sessions exist.
if (!defined('UKN_DEMO_USER_ID')) {
    define('UKN_DEMO_USER_ID', 1);
}

$sessionView = $sessionView ?? 'sessions';
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$sessionsDbError = false;

$mapSessionRow = static function (array $row, string $counterpartyRoute): array {
    $timestamp = strtotime($row['scheduled_date'] . ' ' . $row['scheduled_time']);
    return [
        'id' => $row['id'],
        'counterparty' => $row['counterparty'],
        'counterpartyInitials' => $row['counterpartyInitials'],
        'counterpartyHref' => ukn_route_href($counterpartyRoute) . '&id=' . $row['counterparty_id'],
        'skill' => $row['skill'],
        'day' => date('d', $timestamp),
        'month' => date('M', $timestamp),
        'time' => date('g:i A', $timestamp),
        'duration' => $row['duration_minutes'] . ' min',
        'status' => $row['status'],
        'message' => $row['request_message'],
        'detailsHref' => ukn_route_href('session-details') . '&id=' . $row['id'],
    ];
};

if ($sessionView === 'requests'):
    $requests = [];
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            "SELECT ms.id, ms.status, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                    ms.request_message, l.id AS counterparty_id, l.full_name AS counterparty,
                    l.initials AS counterpartyInitials, sk.name AS skill
             FROM mentoring_sessions ms
             JOIN users l ON l.id = ms.learner_id
             JOIN skills sk ON sk.id = ms.skill_id
             WHERE ms.mentor_id = ? AND ms.status IN ('pending', 'accepted', 'rejected')
             ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC"
        );
        $stmt->execute([UKN_DEMO_USER_ID]);
        $requests = array_map(
            static fn (array $row) => $mapSessionRow($row, 'learner-profile'),
            $stmt->fetchAll()
        );
    } catch (Throwable $e) {
        error_log('[UKN sessions/requests] ' . $e->getMessage());
        $sessionsDbError = true;
    }
    $tabs = [
        ['id' => 'pending', 'label' => 'Pending'],
        ['id' => 'accepted', 'label' => 'Accepted'],
        ['id' => 'rejected', 'label' => 'Rejected'],
        ['id' => 'all', 'label' => 'All'],
    ];
    $defaultTab = 'pending';
    ?>
    <div class="ukn-page-header">
      <div>
        <h1>Learner Requests</h1>
        <p class="ukn-page-header__sub">Review and respond to students who want to learn from you.</p>
      </div>
    </div>
    <?php if ($sessionsDbError): ?>
      <?php ukn_error_state([
          'title' => 'Unable to load learner requests.',
          'message' => 'Something went wrong while loading this page. Please try again shortly.',
      ]); ?>
    <?php else: ?>
    <div class="nav nav-tabs mb-3" role="tablist" data-session-tablist>
      <?php foreach ($tabs as $tab): $count = $tab['id'] === 'all' ? count($requests) : count(array_filter($requests, static fn ($r) => $r['status'] === $tab['id'])); ?>
        <button
          type="button"
          class="nav-link<?= $tab['id'] === $defaultTab ? ' active' : '' ?>"
          role="tab"
          aria-selected="<?= $tab['id'] === $defaultTab ? 'true' : 'false' ?>"
          data-session-tab="<?= htmlspecialchars($tab['id']) ?>"
        ><?= htmlspecialchars($tab['label']) ?> <span class="ukn-tab-count" data-session-tab-count><?= $count ?></span></button>
      <?php endforeach; ?>
    </div>
    <div data-session-list>
      <?php foreach ($requests as $request): ukn_session_card($request); endforeach; ?>
    </div>
    <div<?= $requests ? ' hidden' : '' ?> data-session-empty>
      <?php ukn_empty_state([
          'icon' => 'inbox',
          'title' => 'No pending learner requests.',
          'message' => 'New session requests will appear here.',
      ]); ?>
    </div>
    <?php endif; ?>
<?php else:
    $isMentor = $activeRole === 'mentor';
    $sessions = [];
    try {
        $pdo = getDatabaseConnection();
        if ($isMentor) {
            $stmt = $pdo->prepare(
                "SELECT ms.id, ms.status, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                        ms.request_message, ms.cancel_reason,
                        l.id AS counterparty_id, l.full_name AS counterparty, l.initials AS counterpartyInitials,
                        sk.name AS skill
                 FROM mentoring_sessions ms
                 JOIN users l ON l.id = ms.learner_id
                 JOIN skills sk ON sk.id = ms.skill_id
                 WHERE ms.mentor_id = ? AND ms.status IN ('accepted', 'completed', 'cancelled')
                 ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC"
            );
            $stmt->execute([UKN_DEMO_USER_ID]);
            $sessions = array_map(static function (array $row) use ($mapSessionRow) {
                $row['request_message'] = $row['status'] === 'cancelled' ? $row['cancel_reason'] : $row['request_message'];
                $mapped = $mapSessionRow($row, 'learner-profile');
                $mapped['status'] = $row['status'] === 'accepted' ? 'upcoming' : $row['status'];
                return $mapped;
            }, $stmt->fetchAll());
        } else {
            $stmt = $pdo->prepare(
                "SELECT ms.id, ms.status, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
                        ms.request_message, ms.cancel_reason,
                        m.id AS counterparty_id, m.full_name AS counterparty, m.initials AS counterpartyInitials,
                        sk.name AS skill, sr.overall AS rating_value
                 FROM mentoring_sessions ms
                 JOIN users m ON m.id = ms.mentor_id
                 JOIN skills sk ON sk.id = ms.skill_id
                 LEFT JOIN session_ratings sr ON sr.session_id = ms.id
                 WHERE ms.learner_id = ? AND ms.status IN ('accepted', 'completed', 'cancelled')
                 ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC"
            );
            $stmt->execute([UKN_DEMO_USER_ID]);
            $sessions = array_map(static function (array $row) use ($mapSessionRow) {
                $row['request_message'] = $row['status'] === 'cancelled' ? $row['cancel_reason'] : $row['request_message'];
                $mapped = $mapSessionRow($row, 'mentor-profile');
                $mapped['status'] = $row['status'] === 'accepted' ? 'upcoming' : $row['status'];
                if ($row['status'] === 'completed') {
                    $mapped['ratingStatus'] = $row['rating_value'] !== null ? 'rated' : 'unrated';
                    $mapped['ratingValue'] = $row['rating_value'] !== null ? (float) $row['rating_value'] : null;
                }
                return $mapped;
            }, $stmt->fetchAll());
        }
    } catch (Throwable $e) {
        error_log('[UKN sessions] ' . $e->getMessage());
        $sessionsDbError = true;
    }
    $tabs = [
        ['id' => 'upcoming', 'label' => 'Upcoming'],
        ['id' => 'completed', 'label' => 'Completed'],
        ['id' => 'cancelled', 'label' => 'Cancelled'],
    ];
    $defaultTab = 'upcoming';
    ?>
    <div class="ukn-page-header">
      <div>
        <h1>Sessions</h1>
        <p class="ukn-page-header__sub">Manage your upcoming and completed learning sessions.</p>
      </div>
    </div>
    <?php if ($sessionsDbError): ?>
      <?php ukn_error_state([
          'title' => 'Unable to load your sessions.',
          'message' => 'Something went wrong while loading this page. Please try again shortly.',
      ]); ?>
    <?php else: ?>
    <div class="nav nav-tabs mb-3" role="tablist" data-session-tablist>
      <?php foreach ($tabs as $tab): $count = count(array_filter($sessions, static fn ($s) => $s['status'] === $tab['id'])); ?>
        <button
          type="button"
          class="nav-link<?= $tab['id'] === $defaultTab ? ' active' : '' ?>"
          role="tab"
          aria-selected="<?= $tab['id'] === $defaultTab ? 'true' : 'false' ?>"
          data-session-tab="<?= htmlspecialchars($tab['id']) ?>"
        ><?= htmlspecialchars($tab['label']) ?> <span class="ukn-tab-count" data-session-tab-count><?= $count ?></span></button>
      <?php endforeach; ?>
    </div>
    <div data-session-list>
      <?php foreach ($sessions as $session): ukn_session_card($session); endforeach; ?>
    </div>

    <div<?= $sessions ? ' hidden' : '' ?> data-session-empty>
      <?php ukn_empty_state([
          'icon' => 'event',
          'title' => 'No sessions here yet.',
          'message' => 'Sessions you book or accept will show up here.',
      ]); ?>
    </div>
    <?php endif; ?>
<?php endif; ?>