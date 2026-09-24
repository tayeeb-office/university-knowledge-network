<?php
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/skills.php';

if (!function_exists('uknValidateGoalInput')) {
    /**
     * Validates the Learning Goals form (title, skill, targetDate, description, progress).
     * Returns ['errors' => string[], 'data' => [...]] with the skill resolved to an active id.
     */
    function uknValidateGoalInput(PDO $pdo): array
    {
        $errors = [];
        // Array values (e.g. progress[]=5) are rejected outright rather than treated as
        // missing, so optional/defaulted fields can't be silently replaced by defaults.
        foreach (['title', 'skill', 'targetDate', 'description', 'progress'] as $field) {
            if (array_key_exists($field, $_POST) && uknPostString($field) === null) {
                return ['errors' => ['Invalid goal data.'], 'data' => []];
            }
        }
        $title = sanitizeInput(uknPostString('title') ?? '');
        if (!validateLength($title, 1, 160)) {
            $errors[] = 'Enter a goal title (up to 160 characters).';
        }
        $skillName = uknPostString('skill');
        $skillId = $skillName !== null ? uknFindActiveSkillIdByName($pdo, $skillName) : null;
        if ($skillId === null) {
            $errors[] = 'Choose a related skill from the list.';
        }
        $targetDate = uknPostString('targetDate') ?? '';
        $dateParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $targetDate, $m) ? [(int) $m[1], (int) $m[2], (int) $m[3]] : null;
        if ($dateParts === null || !checkdate($dateParts[1], $dateParts[2], $dateParts[0]) || $dateParts[0] < 2000 || $dateParts[0] > 2100) {
            $errors[] = 'Choose a valid target date.';
        }
        $description = sanitizeInput(uknPostString('description') ?? '');
        if (!validateLength($description, 0, 500)) {
            $errors[] = 'Keep the description under 500 characters.';
        }
        $progressRaw = uknPostString('progress') ?? '0';
        $progress = ctype_digit($progressRaw) ? (int) $progressRaw : -1;
        if ($progress < 0 || $progress > 100) {
            $errors[] = 'Progress must be between 0 and 100.';
        }
        return [
            'errors' => $errors,
            'data' => [
                'title' => $title,
                'skill_id' => $skillId,
                'target_date' => $targetDate,
                'description' => $description === '' ? null : $description,
                'progress' => $progress,
            ],
        ];
    }
}
