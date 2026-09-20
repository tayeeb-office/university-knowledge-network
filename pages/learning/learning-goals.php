<?php
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
$skillOptions = ['Python', 'MySQL', 'Data Analysis', 'Database Design', 'Public Speaking', 'React', 'UI/UX Design'];
$activeGoals = [
    ['id' => 1, 'title' => 'Learn Python for Data Analysis', 'skill' => 'Python', 'progress' => 65, 'targetDate' => 'December 15, 2026', 'targetDateRaw' => '2026-12-15', 'status' => 'in-progress'],
    ['id' => 2, 'title' => 'Improve Database Design Skills', 'skill' => 'Database Design', 'progress' => 40, 'targetDate' => 'November 30, 2026', 'targetDateRaw' => '2026-11-30', 'status' => 'in-progress'],
    ['id' => 3, 'title' => 'Become Confident in Public Speaking', 'skill' => 'Public Speaking', 'progress' => 70, 'targetDate' => 'January 20, 2027', 'targetDateRaw' => '2027-01-20', 'status' => 'in-progress'],
];
$completedGoals = [
    ['id' => 4, 'title' => 'Learn SQL Fundamentals', 'skill' => 'MySQL', 'progress' => 100, 'targetDate' => 'August 28, 2026', 'targetDateRaw' => '2026-08-28', 'status' => 'completed'],
    ['id' => 5, 'title' => 'Build My First React Project', 'skill' => 'React', 'progress' => 100, 'targetDate' => 'July 12, 2026', 'targetDateRaw' => '2026-07-12', 'status' => 'completed'],
    ['id' => 6, 'title' => 'Complete a Data Analysis Mini-Project', 'skill' => 'Data Analysis', 'progress' => 100, 'targetDate' => 'June 20, 2026', 'targetDateRaw' => '2026-06-20', 'status' => 'completed'],
    ['id' => 7, 'title' => 'Design a Simple UI Mockup', 'skill' => 'UI/UX Design', 'progress' => 100, 'targetDate' => 'May 15, 2026', 'targetDateRaw' => '2026-05-15', 'status' => 'completed'],
    ['id' => 8, 'title' => 'Finish Database Normalization Basics', 'skill' => 'Database Design', 'progress' => 100, 'targetDate' => 'April 2, 2026', 'targetDateRaw' => '2026-04-02', 'status' => 'completed'],
];
$activeCount = count($activeGoals);
$completedCount = count($completedGoals);
$averageProgress = $activeCount ? (int) round(array_sum(array_column($activeGoals, 'progress')) / $activeCount) : 0;
$summaryStats = [
    ['label' => 'Active Goals', 'value' => (string) $activeCount, 'icon' => 'flag'],
    ['label' => 'Completed Goals', 'value' => (string) $completedCount, 'icon' => 'check_circle'],
    ['label' => 'Average Progress', 'value' => $averageProgress . '%', 'icon' => 'trending_up'],
    ['label' => 'Target Skills', 'value' => '4', 'icon' => 'workspaces'],
];
?>
<div class="ukn-page-header">
  <div>
    <h1>Learning Goals</h1>
    <p class="ukn-page-header__sub">Set goals and track your progress as you develop new skills.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-goal-create>
    <span class="ms" aria-hidden="true">add</span>Create Goal
  </button>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($summaryStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>
<div class="ukn-tabs-pill mb-4" data-goal-filters role="group" aria-label="Filter learning goals">
  <button type="button" class="ukn-tab-pill is-active" data-goal-filter="active" aria-pressed="true">
    In Progress (<span data-goal-count="active"><?= $activeCount ?></span>)
  </button>
  <button type="button" class="ukn-tab-pill" data-goal-filter="completed" aria-pressed="false">
    Completed (<span data-goal-count="completed"><?= $completedCount ?></span>)
  </button>
</div>
<div data-goal-section="active">
  <div data-goal-list>
    <?php foreach ($activeGoals as $goal): ukn_goal_card($goal + ['showDelete' => true]); endforeach; ?>
  </div>
  <div<?= $activeGoals ? ' hidden' : '' ?> data-goal-list-empty>
    <?php ukn_empty_state([
        'icon' => 'flag',
        'title' => 'No learning goals yet.',
        'message' => 'Create your first goal to start tracking your progress.',
        'action' => ['label' => 'Create Goal', 'href' => '#', 'attrs' => 'data-goal-create'],
    ]); ?>
  </div>
</div>
<div data-goal-section="completed" hidden>
  <div data-goal-list>
    <?php foreach ($completedGoals as $goal): ukn_goal_card($goal + ['showDelete' => true]); endforeach; ?>
  </div>
  <div<?= $completedGoals ? ' hidden' : '' ?> data-goal-list-empty>
    <?php ukn_empty_state([
        'icon' => 'check_circle',
        'title' => 'No completed goals yet.',
        'message' => 'Goals you mark complete will show up here.',
    ]); ?>
  </div>
</div>
<div class="modal fade" id="goalFormModal" tabindex="-1" aria-labelledby="goalFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form data-goal-form novalidate>
        <input type="hidden" data-goal-form-id>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="goalFormModalLabel" data-goal-form-title>Create Goal</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="ukn-form-group">
            <label for="goalFormTitle" class="form-label">Goal Title <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <input type="text" class="form-control" id="goalFormTitle" name="title" placeholder="e.g. Learn Python for Data Analysis" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="title" hidden>
              <span class="ms" aria-hidden="true">error</span>Please enter a goal title.
            </div>
          </div>
          <div class="ukn-form-row">
            <div class="ukn-form-group">
              <label for="goalFormSkill" class="form-label">Related Skill <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <select class="form-select" id="goalFormSkill" name="skill" data-validate="required">
                <option value="" selected disabled>Choose a skill</option>
                <?php foreach ($skillOptions as $option): ?>
                  <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="ukn-field-message is-invalid" data-error-for="skill" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a related skill.
              </div>
            </div>
            <div class="ukn-form-group">
              <label for="goalFormDate" class="form-label">Target Date <span class="ukn-text-danger" aria-hidden="true">*</span></label>
              <input type="date" class="form-control" id="goalFormDate" name="targetDate" data-validate="required">
              <div class="ukn-field-message is-invalid" data-error-for="targetDate" hidden>
                <span class="ms" aria-hidden="true">error</span>Choose a target date.
              </div>
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="goalFormDescription" class="form-label">Description (optional)</label>
            <textarea class="form-control" id="goalFormDescription" name="description" placeholder="What does finishing this goal look like?"></textarea>
          </div>
          <div class="ukn-form-group mb-0">
            <label for="goalFormProgress" class="form-label">Progress</label>
            <div class="d-flex align-items-center gap-3">
              <input type="range" class="form-range flex-fill" id="goalFormProgress" name="progress" min="0" max="100" step="5" value="0" data-goal-progress-input>
              <span class="ukn-body-sm flex-shrink-0" data-goal-progress-output>0%</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" data-goal-form-submit>Create Goal</button>
        </div>
      </form>
    </div>
  </div>
</div>