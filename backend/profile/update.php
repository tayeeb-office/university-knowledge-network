<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/skills.php';

// Updates the logged-in user's own profile (Edit Profile page). The user is always the
// session user; email and university ID are identity fields and cannot be changed here.
// The page's skill picker edits the skills of the current active role (learning for
// learners, teaching for mentors), synced in the same transaction.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('edit-profile');

const UKN_YEARS_OF_STUDY = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
const UKN_MAX_BIO = 300; // matches the form's maxlength

$fail = static function (array $errors, array $old): void {
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_old'] = $old;
    uknRedirectToRoute('edit-profile');
};

$name = sanitizeInput(uknPostString('name') ?? '');
$department = sanitizeInput(uknPostString('department') ?? '');
$year = uknPostString('year') ?? '';
$bio = sanitizeInput(uknPostString('bio') ?? '');
$skillsRaw = uknPostString('skills');
$old = ['name' => $name, 'department' => $department, 'year' => $year, 'bio' => $bio, 'skills' => $skillsRaw ?? ''];

$errors = [];
if (!validateLength($name, 2, UKN_MAX_FULL_NAME)) {
    $errors['name'] = 'Full name must be between 2 and ' . UKN_MAX_FULL_NAME . ' characters.';
}
if ($year !== '' && !in_array($year, UKN_YEARS_OF_STUDY, true)) {
    $errors['year'] = 'Choose a valid year of study.';
}
if (mb_strlen($bio, 'UTF-8') > UKN_MAX_BIO) {
    $errors['bio'] = 'Bio must be ' . UKN_MAX_BIO . ' characters or fewer.';
}
if (array_key_exists('skills', $_POST) && $skillsRaw === null) {
    $errors['skills'] = 'Invalid skills list.';
}

try {
    $pdo = getDatabaseConnection();
    $departmentId = uknResolveDepartmentId($pdo, $department);
    if ($departmentId === null) {
        $errors['department'] = 'Choose a department.';
    }
    // Skill names from the picker → active skill ids (only when the field was submitted).
    $skillIds = null;
    if ($skillsRaw !== null && !isset($errors['skills'])) {
        $skillIds = [];
        foreach (array_unique(array_filter(array_map('trim', explode('|', $skillsRaw)), 'strlen')) as $skillName) {
            $skillId = uknFindActiveSkillIdByName($pdo, $skillName);
            if ($skillId === null) {
                $errors['skills'] = "\"{$skillName}\" is not a skill in the directory.";
                break;
            }
            $skillIds[] = $skillId;
        }
    }
    if ($errors !== []) {
        $fail($errors, $old);
    }

    $userId = (int) getCurrentUser()['id'];
    $skillType = getCurrentActiveRole() === 'mentor' ? 'teaching' : 'learning';

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'UPDATE users SET full_name = ?, initials = ?, department_id = ?, year_of_study = ?, bio = ?
         WHERE id = ?'
    );
    $stmt->execute([$name, uknDeriveInitials($name), $departmentId, $year === '' ? null : $year, $bio === '' ? null : $bio, $userId]);

    if ($skillIds !== null) {
        $existingStmt = $pdo->prepare('SELECT skill_id FROM user_skills WHERE user_id = ? AND skill_type = ?');
        $existingStmt->execute([$userId, $skillType]);
        $existing = array_map('intval', $existingStmt->fetchAll(PDO::FETCH_COLUMN));

        $delete = $pdo->prepare('DELETE FROM user_skills WHERE user_id = ? AND skill_id = ? AND skill_type = ?');
        foreach (array_diff($existing, $skillIds) as $skillId) {
            $delete->execute([$userId, $skillId, $skillType]);
        }
        $insert = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id, skill_type) VALUES (?, ?, ?)');
        foreach (array_diff($skillIds, $existing) as $skillId) {
            $insert->execute([$userId, $skillId, $skillType]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[UKN profile/update] ' . $e->getMessage());
    $fail(['form' => 'Your profile could not be saved. Please try again.'], $old);
}
uknFlashToast('success', 'Profile updated.');
uknRedirectToRoute('my-profile');
