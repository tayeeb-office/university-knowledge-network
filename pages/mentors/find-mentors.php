<?php
/**
 * Find Mentors — main center content only. Routed via
 * index.php?page=find-mentors (see index.php's $routes map). The header,
 * left sidebar, contextual right sidebar ($rightSidebarContext = 'mentors',
 * set by index.php's $sidebarContextByPage map — Top Rated Mentors/
 * Available Today/Popular Mentor Skills, see includes/right-sidebar.php)
 * and footer come from the shell — not from here.
 *
 * Search + skill/department/rating/availability filtering is entirely
 * client-side (assets/js/pages/mentors.js) against this page's own
 * #mentorSearchInput — a completely separate element/state from
 * includes/header.php's global search, same separation already
 * established by pages/skills/skills.php.
 *
 * Mentor Cards reuse components/mentor-card.php as-is; "Request Session"
 * relies on that component's data-request-* attributes and
 * assets/js/core/modal.js's shared contextual-modal listener (see both
 * files) rather than a second modal or a page-specific one.
 *
 * Frontend-only mock data throughout — no real search API, no
 * recommendation/matching algorithm, no database.
 */
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/empty-state.php';

$mentors = [
    ['name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'primarySkill' => 'Python', 'otherSkills' => ['Data Analysis', 'Database Design'], 'rating' => 4.9, 'sessions' => 127, 'points' => 520, 'availability' => 'Available This Week', 'profileHref' => ukn_route_href('mentor-profile') . '&id=2'],
    ['name' => 'Hasan Mahmud', 'initials' => 'HM', 'department' => 'Electrical Engineering', 'primarySkill' => 'Arduino', 'otherSkills' => ['Embedded Systems', 'C++'], 'rating' => 4.7, 'sessions' => 52, 'points' => 365, 'availability' => 'Available Today', 'profileHref' => ukn_route_href('mentor-profile') . '&id=3'],
    ['name' => 'Sara Khan', 'initials' => 'SK', 'department' => 'Business Administration', 'primarySkill' => 'Public Speaking', 'otherSkills' => ['Presentation', 'Communication'], 'rating' => 4.8, 'sessions' => 84, 'points' => 410, 'availability' => 'Weekend', 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Ayesha Rahman', 'initials' => 'AR', 'department' => 'Computer Science', 'primarySkill' => 'React', 'otherSkills' => ['JavaScript', 'UI/UX Design'], 'rating' => 4.7, 'sessions' => 71, 'points' => 368, 'availability' => 'Available This Week', 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Farhan Kabir', 'initials' => 'FK', 'department' => 'Computer Science', 'primarySkill' => 'MySQL', 'otherSkills' => ['Database Design', 'Backend Development'], 'rating' => 4.9, 'sessions' => 103, 'points' => 462, 'availability' => 'Available Today', 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Tanvir Hossain', 'initials' => 'TH', 'department' => 'Electrical Engineering', 'primarySkill' => 'Data Analysis', 'otherSkills' => ['Python', 'Statistics'], 'rating' => 4.7, 'sessions' => 41, 'points' => 388, 'availability' => 'Available This Week', 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Nusrat Jahan', 'initials' => 'NJ', 'department' => 'English', 'primarySkill' => 'Academic Writing', 'otherSkills' => ['Research Methods', 'Essay Structure'], 'rating' => 4.6, 'sessions' => 28, 'points' => 240, 'availability' => 'Weekend', 'profileHref' => ukn_route_href('mentor-profile')],
    ['name' => 'Kamal Hossain', 'initials' => 'KH', 'department' => 'Economics', 'primarySkill' => 'Digital Marketing', 'otherSkills' => ['Market Research', 'Content Strategy'], 'rating' => 4.8, 'sessions' => 35, 'points' => 290, 'availability' => 'Weekend', 'profileHref' => ukn_route_href('mentor-profile')],
];

usort($mentors, static fn (array $a, array $b): int => $b['rating'] <=> $a['rating']); // matches the "Sorted by rating" label below

$skillFilters = ['All Skills', 'Python', 'MySQL', 'React', 'Data Analysis', 'UI/UX Design', 'Public Speaking', 'Database Design', 'Arduino'];
$departmentFilters = ['All Departments', 'Computer Science', 'Electrical Engineering', 'Business Administration', 'English', 'Economics'];
$ratingFilters = ['Any Rating' => '', '4.0+' => '4.0', '4.5+' => '4.5', '4.8+' => '4.8'];
$availabilityFilters = ['Any Availability', 'Available Today', 'Available This Week', 'Weekend'];
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

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 ukn-body-sm">
  <span data-mentor-count><?= count($mentors) ?> mentors found</span>
  <span class="d-flex align-items-center gap-3">
    Sorted by rating
    <button type="button" class="btn-ghost" data-mentor-clear-filters>Clear Filters</button>
  </span>
</div>

<div class="row g-3" data-mentor-grid>
  <?php foreach ($mentors as $mentor): ?>
    <div
      class="col-md-6"
      data-mentor-item
      data-mentor-search="<?= htmlspecialchars(strtolower($mentor['name'] . ' ' . $mentor['department'] . ' ' . $mentor['primarySkill'] . ' ' . implode(' ', $mentor['otherSkills']))) ?>"
      data-mentor-skills="<?= htmlspecialchars(strtolower(implode('|', array_merge([$mentor['primarySkill']], $mentor['otherSkills'])))) ?>"
      data-mentor-department="<?= htmlspecialchars($mentor['department']) ?>"
      data-mentor-rating="<?= htmlspecialchars((string) $mentor['rating']) ?>"
      data-mentor-availability="<?= htmlspecialchars($mentor['availability']) ?>"
    >
      <?php ukn_mentor_card($mentor); ?>
    </div>
  <?php endforeach; ?>
</div>

<div hidden data-mentor-empty>
  <?php ukn_empty_state([
      'icon' => 'person_search',
      'title' => 'No mentors found.',
      'message' => 'Try changing your skill, availability or rating filters.',
      'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-mentor-clear-filters'],
      'dashed' => true,
  ]); ?>
</div>
