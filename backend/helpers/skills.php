<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

if (!defined('UKN_SKILL_TYPES')) {
    define('UKN_SKILL_TYPES', ['learning', 'teaching']);
}
if (!defined('UKN_PROFICIENCY_LEVELS')) {
    // Matches the user_skills.proficiency ENUM.
    define('UKN_PROFICIENCY_LEVELS', ['Beginner', 'Intermediate', 'Advanced']);
}
if (!function_exists('uknCurrentUserSkills')) {
    /**
     * The logged-in user's skills as ['learning' => [id => name], 'teaching' => [id => name]]
     * (empty lists for guests). One query per request, shared by every page/include.
     */
    function uknCurrentUserSkills(): array
    {
        static $skills = null;
        if ($skills !== null) {
            return $skills;
        }
        $skills = ['learning' => [], 'teaching' => []];
        $user = getCurrentUser();
        if ($user === null) {
            return $skills;
        }
        try {
            $stmt = getDatabaseConnection()->prepare(
                'SELECT us.skill_type, s.id, s.name FROM user_skills us
                 JOIN skills s ON s.id = us.skill_id
                 WHERE us.user_id = ? ORDER BY s.name'
            );
            $stmt->execute([(int) $user['id']]);
            foreach ($stmt->fetchAll() as $row) {
                $skills[$row['skill_type']][(int) $row['id']] = $row['name'];
            }
        } catch (Throwable $e) {
            error_log('[UKN skills] ' . $e->getMessage());
        }
        return $skills;
    }
}
if (!function_exists('uknFindActiveSkillName')) {
    /** Name of an active skill by id, or null if it does not exist / is inactive. */
    function uknFindActiveSkillName(PDO $pdo, int $skillId): ?string
    {
        $stmt = $pdo->prepare("SELECT name FROM skills WHERE id = ? AND status = 'active'");
        $stmt->execute([$skillId]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string) $name;
    }
}
if (!function_exists('uknFindActiveSkillIdByName')) {
    function uknFindActiveSkillIdByName(PDO $pdo, string $name): ?int
    {
        $stmt = $pdo->prepare("SELECT id FROM skills WHERE name = ? AND status = 'active'");
        $stmt->execute([trim($name)]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}
