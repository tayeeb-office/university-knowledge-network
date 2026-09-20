<?php
require_once __DIR__ . '/../../components/session-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
$sessionView = $sessionView ?? 'sessions';
$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
if ($sessionView === 'requests'):
    $requests = [
        ['id' => 101, 'counterparty' => 'Mahi Noor', 'counterpartyInitials' => 'MN', 'counterpartyHref' => ukn_route_href('learner-profile'), 'skill' => 'Python', 'day' => '18', 'month' => 'Sep', 'time' => '7:00 PM', 'duration' => '60 min', 'status' => 'pending', 'message' => 'I need help understanding Python data analysis fundamentals and working with pandas.', 'detailsHref' => ukn_route_href('session-details') . '&id=101&from=requests'],
        ['id' => 102, 'counterparty' => 'Imran Chowdhury', 'counterpartyInitials' => 'IC', 'counterpartyHref' => ukn_route_href('learner-profile') . '&id=1', 'skill' => 'Presentation Skills', 'day' => '20', 'month' => 'Sep', 'time' => '6:30 PM', 'duration' => '45 min', 'status' => 'pending', 'detailsHref' => ukn_route_href('session-details') . '&id=102&from=requests'],
        ['id' => 103, 'counterparty' => 'Ayesha Rahman', 'counterpartyInitials' => 'AR', 'counterpartyHref' => ukn_route_href('learner-profile'), 'skill' => 'Database Design', 'day' => '21', 'month' => 'Sep', 'time' => '8:00 PM', 'duration' => '60 min', 'status' => 'pending', 'detailsHref' => ukn_route_href('session-details') . '&id=103&from=requests'],
        ['id' => 104, 'counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'counterpartyHref' => ukn_route_href('learner-profile'), 'skill' => 'Data Analysis', 'day' => '17', 'month' => 'Sep', 'time' => '5:00 PM', 'duration' => '45 min', 'status' => 'accepted', 'message' => 'Could we focus on merging dataframes for the coursework assignment?', 'detailsHref' => ukn_route_href('session-details') . '&id=104&from=requests'],
        ['id' => 105, 'counterparty' => 'Sara Khan', 'counterpartyInitials' => 'SK', 'counterpartyHref' => ukn_route_href('learner-profile') . '&id=2', 'skill' => 'Python', 'day' => '15', 'month' => 'Sep', 'time' => '6:00 PM', 'duration' => '60 min', 'status' => 'rejected', 'detailsHref' => ukn_route_href('session-details') . '&id=105&from=requests'],
    ];
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
    <div hidden data-session-empty>
      <?php ukn_empty_state([
          'icon' => 'inbox',
          'title' => 'No pending learner requests.',
          'message' => 'New session requests will appear here.',
      ]); ?>
    </div>
<?php else:
    $isMentor = $activeRole === 'mentor';
    if ($isMentor) {
        $sessions = [
            ['id' => 301, 'counterparty' => 'Imran Chowdhury', 'counterpartyInitials' => 'IC', 'counterpartyHref' => ukn_route_href('learner-profile') . '&id=1', 'skill' => 'Python', 'day' => '19', 'month' => 'Sep', 'time' => '6:00 PM', 'duration' => '60 min', 'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details') . '&id=301&from=sessions'],
            ['id' => 302, 'counterparty' => 'Imran Chowdhury', 'counterpartyInitials' => 'IC', 'counterpartyHref' => ukn_route_href('learner-profile') . '&id=1', 'skill' => 'Python', 'day' => '05', 'month' => 'Sep', 'time' => '6:00 PM', 'duration' => '60 min', 'status' => 'completed', 'detailsHref' => ukn_route_href('session-details') . '&id=302&from=sessions'],
            ['id' => 303, 'counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'counterpartyHref' => ukn_route_href('learner-profile'), 'skill' => 'Data Analysis', 'day' => '01', 'month' => 'Sep', 'time' => '5:00 PM', 'duration' => '45 min', 'status' => 'completed', 'detailsHref' => ukn_route_href('session-details') . '&id=303&from=sessions'],
            ['id' => 304, 'counterparty' => 'Imran Chowdhury', 'counterpartyInitials' => 'IC', 'counterpartyHref' => ukn_route_href('learner-profile') . '&id=1', 'skill' => 'Python', 'day' => '25', 'month' => 'Aug', 'time' => '7:00 PM', 'duration' => '60 min', 'status' => 'cancelled', 'message' => 'Cancelled by learner — exam conflict.', 'detailsHref' => ukn_route_href('session-details') . '&id=304&from=sessions'],
        ];
    } else {
        $sessions = [
            ['id' => 201, 'counterparty' => 'Rahim Ahmed', 'counterpartyInitials' => 'RA', 'counterpartyHref' => ukn_route_href('mentor-profile') . '&id=2', 'skill' => 'Python', 'day' => '18', 'month' => 'Sep', 'time' => '7:00 PM', 'duration' => '60 min', 'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details') . '&id=201&from=sessions'],
            ['id' => 202, 'counterparty' => 'Sara Khan', 'counterpartyInitials' => 'SK', 'counterpartyHref' => ukn_route_href('mentor-profile'), 'skill' => 'Public Speaking', 'day' => '21', 'month' => 'Sep', 'time' => '11:00 AM', 'duration' => '45 min', 'status' => 'upcoming', 'detailsHref' => ukn_route_href('session-details') . '&id=202&from=sessions'],
            ['id' => 203, 'counterparty' => 'Rahim Ahmed', 'counterpartyInitials' => 'RA', 'counterpartyHref' => ukn_route_href('mentor-profile') . '&id=2', 'skill' => 'Python', 'day' => '08', 'month' => 'Sep', 'time' => '6:00 PM', 'duration' => '60 min', 'status' => 'completed', 'ratingStatus' => 'unrated', 'detailsHref' => ukn_route_href('session-details') . '&id=203&from=sessions'],
            ['id' => 204, 'counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'counterpartyHref' => ukn_route_href('mentor-profile'), 'skill' => 'Data Analysis', 'day' => '02', 'month' => 'Sep', 'time' => '4:00 PM', 'duration' => '60 min', 'status' => 'completed', 'ratingStatus' => 'rated', 'ratingValue' => 5.0, 'detailsHref' => ukn_route_href('session-details') . '&id=204&from=sessions'],
            ['id' => 205, 'counterparty' => 'Hasan Mahmud', 'counterpartyInitials' => 'HM', 'counterpartyHref' => ukn_route_href('mentor-profile') . '&id=3', 'skill' => 'Arduino', 'day' => '28', 'month' => 'Aug', 'time' => '3:00 PM', 'duration' => '45 min', 'status' => 'cancelled', 'message' => 'Cancelled by learner — schedule clash with lab.', 'detailsHref' => ukn_route_href('session-details') . '&id=205&from=sessions'],
        ];
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

    <div hidden data-session-empty>
      <?php ukn_empty_state([
          'icon' => 'event',
          'title' => 'No sessions here yet.',
          'message' => 'Sessions you book or accept will show up here.',
      ]); ?>
    </div>
<?php endif; ?>