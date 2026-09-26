<?php
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
require_once __DIR__ . '/../../components/error-state.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/helpers/format.php';
require_once __DIR__ . '/../../backend/helpers/community.php';

$mentors = [];
$skillFilters = ['All Skills'];
$departmentFilters = ['All Departments'];
$ratingFilters = ['Any Rating' => '', '4.0+' => '4.0', '4.5+' => '4.5', '4.8+' => '4.8'];
$availabilityFilters = ['Any Availability', 'Available Today', 'Available This Week', 'Weekend'];
$mentorsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $mentorStmt = $pdo->query(
        "SELECT DISTINCT u.id, u.full_name AS name, u.initials, u.avatar_path, u.avg_rating AS rating,
                u.mentor_points AS points, u.sessions_as_mentor AS sessions, d.name AS department
         FROM users u
         JOIN user_skills us ON us.user_id = u.id AND us.skill_type = 'teaching'
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.role = 'dual' AND u.status = 'active'
         ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC"
    );
    $mentors = $mentorStmt->fetchAll();

    if ($mentors) {
        $mentorIds = array_column($mentors, 'id');
        $placeholders = implode(',', array_fill(0, count($mentorIds), '?'));

        $skillsStmt = $pdo->prepare(
            "SELECT us.user_id, s.name, us.sessions_count, us.proficiency
             FROM user_skills us JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id IN ($placeholders) AND us.skill_type = 'teaching'
             ORDER BY us.user_id, us.sessions_count DESC, us.proficiency DESC"
        );
        $skillsStmt->execute($mentorIds);
        $skillsByMentor = [];
        foreach ($skillsStmt->fetchAll() as $row) {
            $skillsByMentor[$row['user_id']][] = $row['name'];
        }

        $availabilityStmt = $pdo->prepare(
            "SELECT user_id, day_of_week FROM mentor_availability
             WHERE user_id IN ($placeholders) AND is_enabled = 1"
        );
        $availabilityStmt->execute($mentorIds);
        $daysByMentor = [];
        foreach ($availabilityStmt->fetchAll() as $row) {
            $daysByMentor[$row['user_id']][] = (int) $row['day_of_week'];
        }

        foreach ($mentors as &$mentor) {
            $skillNames = $skillsByMentor[$mentor['id']] ?? [];
            $mentor['primarySkill'] = $skillNames[0] ?? '';
            $mentor['otherSkills'] = array_slice($skillNames, 1);
            $mentor['availability'] = ukn_availability_bucket($daysByMentor[$mentor['id']] ?? []);
            $mentor['profileHref'] = ukn_route_href('mentor-profile') . '&id=' . $mentor['id'];
            $mentor['following'] = uknFollowStateFor((int) $mentor['id']);
        }
        unset($mentor);
    }

    foreach ($pdo->query(
        "SELECT DISTINCT s.name FROM skills s
         JOIN user_skills us ON us.skill_id = s.id
         WHERE us.skill_type = 'teaching' ORDER BY s.name"
    )->fetchAll(PDO::FETCH_COLUMN) as $skillName) {
        $skillFilters[] = $skillName;
    }

    foreach ($pdo->query(
        "SELECT DISTINCT d.name FROM departments d
         JOIN users u ON u.department_id = d.id
         WHERE u.role = 'dual' AND u.status = 'active'
         ORDER BY d.name"
    )->fetchAll(PDO::FETCH_COLUMN) as $deptName) {
        $departmentFilters[] = $deptName;
    }
} catch (Throwable $e) {
    error_log('[UKN find-mentors] ' . $e->getMessage());
    $mentorsDbError = true;
}
?>
<div class="ukn-page-header">
  <div>
    <h1>Find Mentors</h1>
    <p class="ukn-page-header__sub">Discover mentors based on the skills you want to learn.</p>
  </div>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center gap-2" data-mentor-filters>
      <div class="ukn-search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="mentorSearchInput" class="ukn-visually-hidden">Search mentors by name or skill</label>
        <input type="search" id="mentorSearchInput" class="form-control" placeholder="Search mentors by name or skill...">
      </div>
      <select class="form-select form-select-sm w-auto" id="mentorSkillFilter" aria-label="Filter by skill">
        <?php foreach ($skillFilters as $skill): ?>
          <option value="<?= $skill === 'All Skills' ? '' : htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm w-auto" id="mentorDepartmentFilter" aria-label="Filter by department">
        <?php foreach ($departmentFilters as $dept): ?>
          <option value="<?= $dept === 'All Departments' ? '' : htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm w-auto" id="mentorRatingFilter" aria-label="Filter by minimum rating">
        <?php foreach ($ratingFilters as $label => $value): ?>
          <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm w-auto" id="mentorAvailabilityFilter" aria-label="Filter by availability">
        <?php foreach ($availabilityFilters as $avail): ?>
          <option value="<?= $avail === 'Any Availability' ? '' : htmlspecialchars($avail) ?>"><?= htmlspecialchars($avail) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-dark btn-sm" data-mentor-apply>Apply</button>
    </div>
  </div>
</div>
<?php if ($mentorsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load mentors.',
      'message' => 'Something went wrong while loading the mentor directory. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 ukn-body-sm">
  <span data-mentor-count><?= count($mentors) ?> mentors found</span>
  <span class="d-flex align-items-center gap-3">
    Sorted by rating
    <button type="button" class="btn-ghost" data-mentor-clear-filters>Clear Filters</button>
  </span>
</div>
<div class="row g-3" data-mentor-grid>
  <?php foreach ($mentors as $mentor):
      $department = (string) ($mentor['department'] ?? '');
  ?>
    <div
      class="col-md-6"
      data-mentor-item
      data-mentor-search="<?= htmlspecialchars(strtolower($mentor['name'] . ' ' . $department . ' ' . $mentor['primarySkill'] . ' ' . implode(' ', $mentor['otherSkills']))) ?>"
      data-mentor-skills="<?= htmlspecialchars(strtolower(implode('|', array_filter(array_merge([$mentor['primarySkill']], $mentor['otherSkills']))))) ?>"
      data-mentor-department="<?= htmlspecialchars($department) ?>"
      data-mentor-rating="<?= htmlspecialchars($mentor['rating'] !== null ? (string) $mentor['rating'] : '') ?>"
      data-mentor-availability="<?= htmlspecialchars($mentor['availability']) ?>"
    >
      <?php ukn_mentor_card($mentor); ?>
    </div>
  <?php endforeach; ?>
</div>
<div<?= $mentors ? ' hidden' : '' ?> data-mentor-empty>
  <?php ukn_empty_state([
      'icon' => 'person_search',
      'title' => 'No mentors found.',
      'message' => 'Try changing your skill, availability or rating filters.',
      'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-mentor-clear-filters'],
      'dashed' => true,
  ]); ?>
</div>
<?php endif; ?>