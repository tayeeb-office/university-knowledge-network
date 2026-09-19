<?php
/**
 * Skill Details — main center content only. Routed via
 * index.php?page=skill-details[&id=..] (see index.php's $routes map). The
 * header, left sidebar, contextual right sidebar ($rightSidebarContext =
 * 'skills', set by index.php's $sidebarContextByPage map — same Popular/
 * Trending Skills/Categories/Most Requested modules as the rest of the
 * Skills system, see includes/right-sidebar.php) and footer come from the
 * shell — not from here, and are not duplicated here.
 *
 * $_GET['id'] only ever indexes into the small $skills lookup below — it
 * is never concatenated into a query or file path, so there is no
 * injection/traversal surface. An unrecognized id falls back to id 1
 * (Python), matching this project's routing example.
 *
 * The primary "Add to Learning" / "Add to Teaching" action reuses the
 * exact same [data-skill-toggle]/[data-state]/[data-skill-toggle-label]
 * markup contract as components/skill-card.php's directory variant, so
 * assets/js/pages/skills.js's one generic toggle handler drives this too
 * — no second toggle implementation. Frontend-only mock data throughout —
 * no real search/recommendation/matching logic, no database.
 */
require_once __DIR__ . '/../../components/mentor-card.php';
require_once __DIR__ . '/../../components/post-card.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';

/** Matches pages/skills/skills.php exactly, so "already added" agrees everywhere. */
$myLearningSkills = ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];
$myTeachingSkills = ['Python', 'Database Design', 'Data Analysis'];

/**
 * A small, reused cast of mentors (same people/stats already established
 * on the Dashboards and Mentor Profile pages) rather than inventing a new
 * name per skill.
 */
$mentorPool = [
    'Rahim Ahmed'    => ['initials' => 'RA', 'department' => 'Computer Science', 'skill' => 'Python', 'rating' => 4.9, 'points' => 520, 'sessions' => 127, 'profileHref' => ukn_route_href('mentor-profile') . '&id=2'],
    'Hasan Mahmud'   => ['initials' => 'HM', 'department' => 'Electrical Engineering', 'skill' => 'Arduino', 'rating' => 4.7, 'points' => 365, 'sessions' => 52, 'profileHref' => ukn_route_href('mentor-profile') . '&id=3'],
    'Sara Khan'      => ['initials' => 'SK', 'department' => 'Business Administration', 'skill' => 'Public Speaking', 'rating' => 4.8, 'points' => 410, 'sessions' => 47, 'profileHref' => ukn_route_href('mentor-profile')],
    'Tanvir Hossain' => ['initials' => 'TH', 'department' => 'Electrical Engineering', 'skill' => 'Data Analysis', 'rating' => 4.7, 'points' => 388, 'sessions' => 41, 'profileHref' => ukn_route_href('mentor-profile')],
];

$categoryMentors = [
    'Programming'   => ['Rahim Ahmed', 'Tanvir Hossain'],
    'Data'          => ['Tanvir Hossain', 'Rahim Ahmed'],
    'Design'        => ['Rahim Ahmed', 'Sara Khan'],
    'Communication' => ['Sara Khan', 'Rahim Ahmed'],
    'Business'      => ['Sara Khan', 'Tanvir Hossain'],
    'Engineering'   => ['Hasan Mahmud', 'Tanvir Hossain'],
    'Academic'      => ['Sara Khan', 'Rahim Ahmed'],
];

$categoryTopics = [
    'Programming'   => ['Data Structures', 'APIs', 'Version Control', 'Algorithms'],
    'Data'          => ['Statistics', 'Data Visualization', 'Data Cleaning'],
    'Design'        => ['User Research', 'Prototyping', 'Accessibility'],
    'Communication' => ['Presentation', 'Body Language', 'Storytelling'],
    'Business'      => ['Market Research', 'Content Strategy', 'Analytics'],
    'Engineering'   => ['Circuit Design', 'Sensors', 'Robotics'],
    'Academic'      => ['Research Methods', 'Citation Styles', 'Essay Structure'],
];

$skills = [
    1 => ['name' => 'Python', 'category' => 'Programming', 'mentors' => 124, 'learners' => 340, 'sessionsHeld' => 1248, 'discussions' => 86,
        'description' => 'A versatile programming language used for software development, automation, data analysis and machine learning.',
        'about' => 'Python is the most taught skill on the network. Mentors cover everything from first syntax to pandas, scripting and the machine-learning coursework sequence, and most sessions run 45–60 minutes in the Student Union study rooms or online.',
        'relatedTopics' => ['Data Analysis', 'Automation', 'Backend Development', 'Algorithms', 'APIs'],
        'discussionPosts' => [
            ['id' => 2, 'author' => 'Rahim Ahmed', 'initials' => 'RA', 'role' => 'Mentor', 'department' => 'Computer Science', 'time' => '35 min ago',
                'title' => 'A Simple Way to Start Learning Python for Data Analysis',
                'excerpt' => 'Skip the theory-heavy courses at first. Start with pandas on a dataset you actually care about — it clicks a lot faster than notebooks full of print statements.',
                'tags' => ['Python', 'Data Analysis'], 'score' => 48, 'comments' => 12],
            ['id' => 201, 'author' => 'Imran Chowdhury', 'initials' => 'IC', 'role' => 'Learner', 'department' => 'English', 'time' => '1 day ago',
                'title' => 'Python List Comprehension Confusion',
                'excerpt' => "I understand the basic syntax but nested comprehensions with a condition still take me a full minute to read. Any mental model that made this click for you?",
                'tags' => ['Python'], 'score' => 21, 'comments' => 9],
            ['id' => 202, 'author' => 'Sara Khan', 'initials' => 'SK', 'role' => 'Learner', 'department' => 'Business Administration', 'time' => '2 days ago',
                'title' => 'Which Library Should I Learn for Data Analysis?',
                'excerpt' => 'Trying to decide between going deep on pandas first or splitting time with numpy and matplotlib from the start. What order actually worked for you?',
                'tags' => ['Python', 'Data Analysis'], 'score' => 17, 'comments' => 6],
        ],
    ],
    2 => ['name' => 'MySQL', 'category' => 'Data', 'mentors' => 82, 'learners' => 214, 'sessionsHeld' => 640, 'discussions' => 42,
        'description' => 'A widely used relational database system for storing and querying structured data.',
        'about' => 'Sessions typically start from writing and querying tables, then move into joins, indexes and the normalization rules that show up most in coursework and interviews.'],
    3 => ['name' => 'React', 'category' => 'Programming', 'mentors' => 76, 'learners' => 196, 'sessionsHeld' => 590, 'discussions' => 51,
        'description' => 'A JavaScript library for building interactive user interfaces and single-page applications.',
        'about' => 'Most mentors assume basic JavaScript and focus on components, state and the mistakes that trip up a first real project — prop drilling, effect dependencies and re-render loops.'],
    4 => ['name' => 'UI/UX Design', 'category' => 'Design', 'mentors' => 68, 'learners' => 173, 'sessionsHeld' => 480, 'discussions' => 37,
        'description' => 'Designing interfaces and experiences that are functional, accessible and easy to use.',
        'about' => 'Sessions mix short critique of your own screens with the fundamentals — layout, hierarchy, contrast and how to justify a design decision, not just make one.'],
    5 => ['name' => 'Data Analysis', 'category' => 'Data', 'mentors' => 91, 'learners' => 256, 'sessionsHeld' => 710, 'discussions' => 63,
        'description' => 'Turning raw data into insight using spreadsheets, Python and statistical thinking.',
        'about' => 'Mentors work through a real dataset with you — cleaning it, asking useful questions of it, and presenting what you found, rather than teaching statistics in the abstract.'],
    6 => ['name' => 'Public Speaking', 'category' => 'Communication', 'mentors' => 54, 'learners' => 161, 'sessionsHeld' => 390, 'discussions' => 28,
        'description' => 'Structuring and delivering talks and presentations with confidence.',
        'about' => 'Sessions are mostly practice: structuring a short talk, handling nerves, and getting specific feedback on pacing and filler words rather than general advice.'],
    7 => ['name' => 'Database Design', 'category' => 'Data', 'mentors' => 63, 'learners' => 147, 'sessionsHeld' => 460, 'discussions' => 33,
        'description' => 'Modeling data relationships and normalizing schemas for reliable, efficient systems.',
        'about' => 'Covers modeling entities and relationships, normalization, and the trade-offs between a clean schema and a fast query — the part coursework often skips.'],
    8 => ['name' => 'Arduino', 'category' => 'Engineering', 'mentors' => 47, 'learners' => 128, 'sessionsHeld' => 310, 'discussions' => 22,
        'description' => 'Building and programming microcontroller projects, from sensors to simple robotics.',
        'about' => 'Hands-on sessions with real boards and components — wiring, debouncing, sensors and the debugging habits that save the most time on a first project.'],
    9 => ['name' => 'Academic Writing', 'category' => 'Academic', 'mentors' => 39, 'learners' => 104, 'sessionsHeld' => 260, 'discussions' => 19,
        'description' => 'Structuring essays, reports and citations for university-level coursework.',
        'about' => 'Mentors help structure arguments, tighten paragraphs and get citations right for the specific style your department expects.'],
    10 => ['name' => 'Digital Marketing', 'category' => 'Business', 'mentors' => 44, 'learners' => 121, 'sessionsHeld' => 300, 'discussions' => 24,
        'description' => 'Reaching an audience through social media, content and basic campaign analytics.',
        'about' => 'Sessions cover the basics of reaching an audience — content planning, social platforms and reading enough analytics to know if something worked.'],
];

$requestedId = isset($_GET['id']) && is_string($_GET['id']) && isset($skills[(int) $_GET['id']]) ? (int) $_GET['id'] : 1;
$skill = $skills[$requestedId];
$skill += ['relatedTopics' => $categoryTopics[$skill['category']] ?? [], 'discussionPosts' => []];

$kind = $isMentor ? 'teaching' : 'learning';
$kindLabel = $isMentor ? 'Teaching' : 'Learning';
$mySkillNames = $isMentor ? $myTeachingSkills : $myLearningSkills;
$isAdded = in_array($skill['name'], $mySkillNames, true);

$topMentorNames = $categoryMentors[$skill['category']] ?? ['Rahim Ahmed', 'Sara Khan'];
$topMentors = array_map(static function (string $name) use ($mentorPool, $skill) {
    $mentor = $mentorPool[$name];
    return [
        'name' => $name, 'initials' => $mentor['initials'], 'department' => $mentor['department'],
        'primarySkill' => $skill['name'], 'rating' => $mentor['rating'], 'points' => $mentor['points'],
        'sessions' => $mentor['sessions'], 'profileHref' => $mentor['profileHref'],
    ];
}, $topMentorNames);
?>
<div class="card mb-4">
  <div class="card-body" data-skill-name="<?= htmlspecialchars($skill['name']) ?>">
    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
      <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($skill['name']) ?></h1>
      <span class="ukn-eyebrow"><?= htmlspecialchars($skill['category']) ?></span>
    </div>
    <p class="ukn-body mt-2 mb-0"><?= htmlspecialchars($skill['description']) ?></p>

    <div class="d-flex align-items-center gap-4 flex-wrap mt-3 pt-3 ukn-border-top">
      <div>
        <div class="ukn-eyebrow">Mentors</div>
        <div class="ukn-display"><?= (int) $skill['mentors'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Learners</div>
        <div class="ukn-display"><?= (int) $skill['learners'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Sessions Held</div>
        <div class="ukn-display"><?= (int) $skill['sessionsHeld'] ?></div>
      </div>
      <div>
        <div class="ukn-eyebrow">Discussions</div>
        <div class="ukn-display"><?= (int) $skill['discussions'] ?></div>
      </div>
      <button
        type="button"
        class="btn btn-sm ms-auto <?= $isAdded ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
        data-skill-toggle="<?= $kind ?>"
        data-state="<?= $isAdded ? 'added' : 'add' ?>"
      >
        <span class="ms" aria-hidden="true"><?= $isAdded ? 'check' : 'add' ?></span>
        <span data-skill-toggle-label><?= $isAdded ? $kindLabel : ('Add to ' . $kindLabel) ?></span>
      </button>
    </div>
  </div>
</div>

<?php if ($skill['about']): ?>
<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">About This Skill</h2>
    <p class="ukn-body mb-0"><?= htmlspecialchars($skill['about']) ?></p>
    <?php if ($skill['relatedTopics']): ?>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <?php foreach ($skill['relatedTopics'] as $topic): ?>
          <span class="ukn-tag-neutral"><?= htmlspecialchars($topic) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Top Mentors for <?= htmlspecialchars($skill['name']) ?></h2>
    <a href="<?= htmlspecialchars(ukn_route_href('find-mentors') . '&skill=' . urlencode(strtolower($skill['name']))) ?>" class="ukn-body-sm">View All Mentors</a>
  </div>
  <div class="row g-3">
    <?php foreach ($topMentors as $mentor): ?>
      <div class="col-md-6"><?php ukn_mentor_card($mentor); ?></div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($skill['discussionPosts']): ?>
<div>
  <h2 class="ukn-h4 mb-3">Related Community Discussions</h2>
  <?php foreach ($skill['discussionPosts'] as $post):
      $post += ['isOwner' => false, 'href' => 'index.php?page=post-details&id=' . $post['id']];
      ukn_post_card($post);
  endforeach; ?>
</div>
<?php endif; ?>
