<?php
// Phase H (Steps 45–47): input validation for departments, skill categories and skills.
require_once __DIR__ . '/admin.php';

if (!function_exists('uknAdminDepartmentInput')) {
    /**
     * name (1–100 chars, departments.name), code (letters/digits/hyphen, starts with a letter,
     * 1–10 chars, stored upper-case), status (active|inactive). Returns [data, error].
     */
    function uknAdminDepartmentInput(): array
    {
        $name = uknAdminText('name', 100);
        if ($name === null) {
            return [null, 'Department name is required (up to 100 characters).'];
        }
        $code = uknAdminText('code', 10);
        $code = $code === null ? null : strtoupper($code);
        if ($code === null || !preg_match('/^[A-Z][A-Z0-9-]{0,9}$/', $code)) {
            return [null, 'Department code is required: up to 10 letters, digits or hyphens, starting with a letter.'];
        }
        $status = uknAdminEnum('status', ['active', 'inactive']);
        if ($status === null) {
            return [null, 'Choose a valid status.'];
        }
        return [['name' => $name, 'code' => $code, 'status' => $status], null];
    }
}
if (!function_exists('uknAdminDepartmentConflict')) {
    /** Duplicate name or code (case-insensitive, as the unique indexes are), ignoring $exceptId. */
    function uknAdminDepartmentConflict(PDO $pdo, array $data, int $exceptId = 0): ?string
    {
        $stmt = $pdo->prepare('SELECT name = ? AS same_name, code = ? AS same_code FROM departments
                               WHERE (name = ? OR code = ?) AND id <> ?');
        $stmt->execute([$data['name'], $data['code'], $data['name'], $data['code'], $exceptId]);
        foreach ($stmt->fetchAll() as $row) {
            if ((int) $row['same_name'] === 1) {
                return 'A department with this name already exists.';
            }
            if ((int) $row['same_code'] === 1) {
                return 'A department with this code already exists.';
            }
        }
        return null;
    }
}
if (!function_exists('uknAdminCategoryInput')) {
    /** Step 46: name (1–60, skill_categories.name), description (1–255), status. Returns [data, error]. */
    function uknAdminCategoryInput(): array
    {
        $name = uknAdminText('name', 60);
        if ($name === null) {
            return [null, 'Category name is required (up to 60 characters).'];
        }
        $description = uknAdminText('description', 255);
        if ($description === null) {
            return [null, 'Description is required (up to 255 characters).'];
        }
        $status = uknAdminEnum('status', ['active', 'inactive']);
        if ($status === null) {
            return [null, 'Choose a valid status.'];
        }
        return [['name' => $name, 'description' => $description, 'status' => $status], null];
    }
}
if (!function_exists('uknAdminSkillInput')) {
    /**
     * Step 47: name (1–80, skills.name; no '|' or ',' because skill pickers use them as
     * separators), category_id (positive id), description (1–255), status. Returns [data, error].
     */
    function uknAdminSkillInput(): array
    {
        $name = uknAdminText('name', 80);
        if ($name === null || strpbrk($name, '|,') !== false) {
            return [null, 'Skill name is required (up to 80 characters, without "|" or ",").'];
        }
        $categoryId = uknPostId('category_id');
        if ($categoryId === null) {
            return [null, 'Choose a category.'];
        }
        $description = uknAdminText('description', 255);
        if ($description === null) {
            return [null, 'Description is required (up to 255 characters).'];
        }
        $status = uknAdminEnum('status', ['active', 'inactive']);
        if ($status === null) {
            return [null, 'Choose a valid status.'];
        }
        return [['name' => $name, 'category_id' => $categoryId, 'description' => $description, 'status' => $status], null];
    }
}
if (!function_exists('uknAdminSkillSlug')) {
    /** URL slug for skills.slug (unique): lower-case a-z0-9 words joined by '-', suffixed -2, -3… on collision. */
    function uknAdminSkillSlug(PDO $pdo, string $name, int $exceptId = 0): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        $base = substr($base === '' ? 'skill' : $base, 0, 70);
        $check = $pdo->prepare('SELECT 1 FROM skills WHERE slug = ? AND id <> ?');
        for ($n = 1; ; $n++) {
            $slug = $n === 1 ? $base : $base . '-' . $n;
            $check->execute([$slug, $exceptId]);
            if ($check->fetchColumn() === false) {
                return $slug;
            }
        }
    }
}
if (!function_exists('uknAdminSkillReferences')) {
    /** Rows that still point at a skill (anything here blocks a permanent delete). */
    function uknAdminSkillReferences(PDO $pdo, int $skillId): array
    {
        $stmt = $pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM user_skills WHERE skill_id = :a) AS member_skills,
                    (SELECT COUNT(*) FROM post_skills WHERE skill_id = :b) AS post_tags,
                    (SELECT COUNT(*) FROM learning_goals WHERE skill_id = :c) AS goals,
                    (SELECT COUNT(*) FROM mentoring_sessions WHERE skill_id = :d) AS sessions,
                    (SELECT COUNT(*) FROM session_ratings WHERE skill_id = :e) AS ratings,
                    (SELECT COUNT(*) FROM skill_relations WHERE source_skill_id = :f OR target_skill_id = :g) AS relations'
        );
        $stmt->execute(['a' => $skillId, 'b' => $skillId, 'c' => $skillId, 'd' => $skillId, 'e' => $skillId, 'f' => $skillId, 'g' => $skillId]);
        return array_map('intval', $stmt->fetch());
    }
}
