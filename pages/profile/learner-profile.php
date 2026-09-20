<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/post-card.php';
$learners = [
    1 => [
        'name' => 'Imran Chowdhury', 'initials' => 'IC', 'department' => 'English', 'year' => '2nd Year',
        'bio' => "Second-year English student working on academic presentation skills for seminars and case competitions. Learning by doing, not just reading about it.",
        'skills' => ['Public Speaking', 'Presentation', 'Academic Writing'],
        'stats' => [
            ['label' => 'Learning Points', 'value' => '318', 'icon' => 'military_tech'],
            ['label' => 'Completed Sessions', 'value' => '14', 'icon' => 'event_available'],
            ['label' => 'Skills Learning', 'value' => '3', 'icon' => 'workspaces'],
            ['label' => 'Goals Completed', 'value' => '5', 'icon' => 'flag'],
        ],
        'goals' => [
            ['title' => 'Improve Academic Presentation Skills', 'skill' => 'Presentation', 'progress' => 70, 'targetDate' => 'January 2027'],
        ],
        'post' => [
            'id' => 5, 'href' => 'index.php?page=post-details&id=5',
            'title' => 'How Do You Improve Academic Presentation Skills?',
            'excerpt' => 'My seminar presentations feel flat even when the research is solid. Looking for practical habits, not just "practice more" advice.',
            'tags' => ['Presentation', 'Communication'], 'score' => 19, 'comments' => 14, 'time' => '3 hours ago',
        ],
    ],
    2 => [
        'name' => 'Sara Khan', 'initials' => 'SK', 'department' => 'Business Administration', 'year' => '3rd Year',
        'bio' => "Business student picking up enough UI/UX and Python to prototype my own capstone project without waiting on a developer.",
        'skills' => ['UI/UX Design', 'Python', 'Public Speaking'],
        'stats' => [
            ['label' => 'Learning Points', 'value' => '366', 'icon' => 'military_tech'],
            ['label' => 'Completed Sessions', 'value' => '16', 'icon' => 'event_available'],
            ['label' => 'Skills Learning', 'value' => '3', 'icon' => 'workspaces'],
            ['label' => 'Goals Completed', 'value' => '4', 'icon' => 'flag'],
        ],
        'goals' => [
            ['title' => 'Build a UI/UX Portfolio Project', 'skill' => 'UI/UX Design', 'progress' => 50, 'targetDate' => 'February 2027'],
        ],
        'post' => [
            'id' => 7, 'href' => 'index.php?page=post-details&id=7',
            'title' => 'Best Resources for Learning UI/UX Design as a Beginner',
            'excerpt' => 'Business student trying to pick up enough UI/UX to prototype my own capstone project. Free resources preferred over paid courses for now.',
            'tags' => ['UI/UX Design'], 'score' => 22, 'comments' => 5, 'time' => '7 hours ago',
        ],
    ],
];
$requestedId = isset($_GET['id']) && is_string($_GET['id']) && isset($learners[(int) $_GET['id']]) ? (int) $_GET['id'] : 1;
$learner = $learners[$requestedId];
?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <span class="ukn-avatar ukn-avatar-xl flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($learner['initials']) ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($learner['name']) ?></h1>
          <span class="ukn-role-chip">Learner</span>
        </div>
        <div class="ukn-body-sm mt-1"><?= htmlspecialchars($learner['department']) ?> &middot; <?= htmlspecialchars($learner['year']) ?></div>
        <p class="ukn-body-sm mt-2 mb-0"><?= htmlspecialchars($learner['bio']) ?></p>
      </div>
      <div class="flex-shrink-0">
        <button
          type="button"
          class="btn btn-primary btn-sm"
          data-follow-toggle
          data-following="false"
          aria-pressed="false"
        ><span data-follow-label>Follow</span></button>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($learner['stats'] as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">Learning Skills</h2>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($learner['skills'] as $skill): ?>
        <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php if ($learner['goals']): ?>
<div class="mb-4">
  <h2 class="ukn-h4 mb-3">Learning Goals</h2>
  <?php foreach ($learner['goals'] as $goal): $goal['showActions'] = false; ukn_goal_card($goal); endforeach; ?>
</div>
<?php endif; ?>
<?php if ($learner['post']): ?>
<div>
  <h2 class="ukn-h4 mb-3">Recent Posts</h2>
  <?php ukn_post_card($learner['post'] + [
      'author' => $learner['name'], 'initials' => $learner['initials'], 'role' => 'Learner',
      'department' => $learner['department'], 'isOwner' => false,
  ]); ?>
</div>
<?php endif; ?>