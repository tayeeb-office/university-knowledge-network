<?php
require_once __DIR__ . '/../../components/mentor-card.php';
$recommendedMentors = [
    ['name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'primarySkill' => 'Python', 'otherSkills' => ['Data Analysis', 'Database Design'], 'rating' => 4.9, 'sessions' => 127, 'points' => 520, 'availability' => 'Available This Week', 'profileHref' => ukn_route_href('mentor-profile') . '&id=2', 'match' => 98, 'matchLabel' => 'Best Match'],
    ['name' => 'Farhan Kabir', 'initials' => 'FK', 'department' => 'Computer Science', 'primarySkill' => 'MySQL', 'otherSkills' => ['Database Design', 'Backend Development'], 'rating' => 4.9, 'sessions' => 103, 'points' => 462, 'availability' => 'Available Today', 'profileHref' => ukn_route_href('mentor-profile'), 'match' => 94, 'matchLabel' => 'Excellent Match'],
    ['name' => 'Ayesha Rahman', 'initials' => 'AR', 'department' => 'Computer Science', 'primarySkill' => 'React', 'otherSkills' => ['JavaScript', 'UI/UX Design'], 'rating' => 4.7, 'sessions' => 71, 'points' => 368, 'availability' => 'Available This Week', 'profileHref' => ukn_route_href('mentor-profile'), 'match' => 91, 'matchLabel' => 'Strong Match'],
    ['name' => 'Hasan Mahmud', 'initials' => 'HM', 'department' => 'Electrical Engineering', 'primarySkill' => 'Arduino', 'otherSkills' => ['Embedded Systems', 'C++'], 'rating' => 4.7, 'sessions' => 52, 'points' => 365, 'availability' => 'Available Today', 'profileHref' => ukn_route_href('mentor-profile') . '&id=3', 'match' => 86, 'matchLabel' => 'Good Match'],
];
$topMentor = array_shift($recommendedMentors);
?>
<div class="ukn-page-header">
  <div>
    <h1>Recommended Mentors</h1>
    <p class="ukn-page-header__sub">Mentors matched to your current learning skills and availability.</p>
  </div>
</div>
<div class="d-flex flex-wrap gap-2 mb-4">
  <span class="ukn-status ukn-status-accent">Best Match</span>
  <span class="ukn-status ukn-status-neutral">Good Match</span>
  <span class="ukn-tag-neutral">Other Matches</span>
</div>
<div class="row g-3">
  <div class="col-12"><?php ukn_mentor_card($topMentor, ['variant' => 'recommendation']); ?></div>
  <?php foreach ($recommendedMentors as $mentor): ?>
    <div class="col-md-6"><?php ukn_mentor_card($mentor, ['variant' => 'recommendation']); ?></div>
  <?php endforeach; ?>
</div>