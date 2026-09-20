<?php
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentorView = $activeRole === 'mentor';
$sessions = [
    101 => ['refId' => 'UKN-1048', 'status' => 'pending', 'timeline' => 'Requested 2 days ago',
        'learner' => ['name' => 'Mahi Noor', 'department' => 'Computer Science', 'href' => ukn_route_href('learner-profile')],
        'mentor' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'initials' => 'NR', 'rating' => 4.6, 'sessions' => 18, 'points' => 412, 'skills' => 'Python, Data Analysis', 'href' => ukn_route_href('mentor-profile')],
        'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1',
        'date' => 'September 18, 2026', 'time' => '7:00 PM', 'duration' => '60 minutes',
        'message' => 'I need help understanding Python data analysis fundamentals and working with pandas.'],
    104 => ['refId' => 'UKN-1052', 'status' => 'accepted', 'timeline' => 'Requested 4 days ago · Accepted 1 day ago',
        'learner' => ['name' => 'Tanvir Hossain', 'department' => 'Electrical Engineering', 'href' => ukn_route_href('learner-profile')],
        'mentor' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'initials' => 'NR', 'rating' => 4.6, 'sessions' => 18, 'points' => 412, 'skills' => 'Python, Data Analysis', 'href' => ukn_route_href('mentor-profile')],
        'skill' => 'Data Analysis', 'skillHref' => ukn_route_href('skill-details') . '&id=5',
        'date' => 'September 17, 2026', 'time' => '5:00 PM', 'duration' => '45 minutes',
        'message' => 'Could we focus on merging dataframes for the coursework assignment?'],
    105 => ['refId' => 'UKN-1055', 'status' => 'rejected', 'timeline' => 'Requested 5 days ago · Rejected 3 days ago',
        'learner' => ['name' => 'Sara Khan', 'department' => 'Business Administration', 'href' => ukn_route_href('learner-profile') . '&id=2'],
        'mentor' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'initials' => 'NR', 'rating' => 4.6, 'sessions' => 18, 'points' => 412, 'skills' => 'Python, Data Analysis', 'href' => ukn_route_href('mentor-profile')],
        'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1',
        'date' => 'September 15, 2026', 'time' => '6:00 PM', 'duration' => '60 minutes', 'message' => null],
    201 => ['refId' => 'UKN-1040', 'status' => 'upcoming', 'timeline' => 'Requested 5 days ago · Accepted 4 days ago',
        'learner' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'href' => ukn_route_href('learner-profile')],
        'mentor' => ['name' => 'Rahim Ahmed', 'department' => 'Computer Science', 'initials' => 'RA', 'rating' => 4.9, 'sessions' => 127, 'points' => 520, 'skills' => 'Python, Data Analysis, Database Design', 'href' => ukn_route_href('mentor-profile') . '&id=2'],
        'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1',
        'date' => 'September 18, 2026', 'time' => '7:00 PM', 'duration' => '60 minutes',
        'message' => 'I can read basic pandas code but I get lost merging two dataframes for the assignment. Could we work through one real example together?'],
    203 => ['refId' => 'UKN-1035', 'status' => 'completed', 'ratingStatus' => 'unrated', 'timeline' => 'Completed on September 8, 2026',
        'learner' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'href' => ukn_route_href('learner-profile')],
        'mentor' => ['name' => 'Rahim Ahmed', 'department' => 'Computer Science', 'initials' => 'RA', 'rating' => 4.9, 'sessions' => 127, 'points' => 520, 'skills' => 'Python, Data Analysis, Database Design', 'href' => ukn_route_href('mentor-profile') . '&id=2'],
        'skill' => 'Python', 'skillHref' => ukn_route_href('skill-details') . '&id=1',
        'date' => 'September 8, 2026', 'time' => '6:00 PM', 'duration' => '60 minutes', 'message' => null],
    205 => ['refId' => 'UKN-1028', 'status' => 'cancelled', 'timeline' => 'Cancelled 2 days ago',
        'learner' => ['name' => 'Nabila Rahman', 'department' => 'Computer Science', 'href' => ukn_route_href('learner-profile')],
        'mentor' => ['name' => 'Hasan Mahmud', 'department' => 'Electrical Engineering', 'initials' => 'HM', 'rating' => 4.7, 'sessions' => 52, 'points' => 365, 'skills' => 'Arduino, Embedded Systems', 'href' => ukn_route_href('mentor-profile') . '&id=3'],
        'skill' => 'Arduino', 'skillHref' => ukn_route_href('skill-details') . '&id=8',
        'date' => 'August 28, 2026', 'time' => '3:00 PM', 'duration' => '45 minutes',
        'message' => 'Cancelled by learner — schedule clash with lab.'],
];

$requestedId = isset($_GET['id']) && is_string($_GET['id']) && isset($sessions[(int) $_GET['id']]) ? (int) $_GET['id'] : 201;
$session = $sessions[$requestedId];
$session += ['ratingStatus' => null, 'ratingValue' => null];
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
$meta = $statusMeta[$session['status']] ?? $statusMeta['upcoming'];
$facts = [
    ['label' => 'Session', 'value' => '#' . $session['refId']],
    ['label' => 'Learner', 'value' => $session['learner']['name'] . ' · ' . $session['learner']['department'], 'href' => $session['learner']['href']],
    ['label' => 'Mentor', 'value' => $session['mentor']['name'] . ' · ' . $session['mentor']['department'], 'href' => $session['mentor']['href']],
    ['label' => 'Skill', 'value' => $session['skill'], 'href' => $session['skillHref']],
    ['label' => 'Date & Time', 'value' => $session['date'] . ' · ' . $session['time']],
    ['label' => 'Duration', 'value' => $session['duration']],
];
?>
<div class="ukn-page-header">
  <div>
    <a href="<?= htmlspecialchars($backHref) ?>" class="ukn-body-sm d-inline-flex align-items-center gap-1 mb-2">
      <span class="ms" aria-hidden="true">arrow_back</span><?= htmlspecialchars($backLabel) ?>
    </a>
    <h1>Session Details</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3" data-session-id="<?= $requestedId ?>" data-session-status="<?= htmlspecialchars($session['status']) ?>" data-session-details-href="">
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
          <?php if ($session['status'] === 'pending' && $isMentorView): ?>
            <button type="button" class="btn btn-primary btn-sm" data-session-accept>Accept Request</button>
            <button
              type="button" class="btn btn-outline-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
              data-delete-title="Reject this request?"
              data-delete-message="The learner will be notified that this request was not accepted."
              data-delete-confirm-label="Reject" data-success-message="Session request rejected."
              data-reject-session
            >Reject Request</button>
          <?php elseif ($session['status'] === 'upcoming'): ?>
            <?php if ($isMentorView): ?>
              <button type="button" class="btn btn-primary btn-sm" data-session-mark-complete>Mark Session Complete</button>
            <?php endif; ?>
            <button
              type="button" class="btn btn-outline-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
              data-delete-title="Cancel Session?"
              data-delete-message="Are you sure you want to cancel this session?"
              data-delete-confirm-label="Cancel Session" data-success-message="Session cancelled."
              data-cancel-session
            >Cancel Session</button>
          <?php elseif ($session['status'] === 'completed' && !$isMentorView): ?>
            <?php if ($session['ratingStatus'] === 'unrated'): ?>
              <button
                type="button" class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#ratingModal"
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