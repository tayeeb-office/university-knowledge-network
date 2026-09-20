<?php
require_once __DIR__ . '/../config/database.php';
class User
{
    private const AUTH_COLUMNS = '
        id,
        full_name,
        initials,
        email,
        university_id,
        password_hash,
        role,
        is_admin,
        status,
        department_id,
        learning_points,
        mentor_points
    ';
    private const PROFILE_COLUMNS = '
        id,
        full_name,
        initials,
        email,
        university_id,
        role,
        is_admin,
        status,
        department_id,
        year_of_study,
        headline,
        bio,
        avatar_path,
        learning_points,
        mentor_points,
        avg_rating,
        total_reviews,
        sessions_as_learner,
        sessions_as_mentor,
        learners_helped,
        last_active_at,
        created_at
    ';
    private const REQUIRED_ON_CREATE = [
        'full_name',
        'initials',
        'email',
        'university_id',
        'password_hash',
    ];
    private PDO $pdo;
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getDatabaseConnection();
    }
    public function findByEmail(string $email): ?array
    {
        $email = $this->normaliseEmail($email);
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT ' . self::AUTH_COLUMNS . ' FROM users WHERE email = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT ' . self::PROFILE_COLUMNS . ' FROM users WHERE id = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
    public function emailExists(string $email): bool
    {
        $email = $this->normaliseEmail($email);
        if ($email === '') {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }
    public function universityIdExists(string $universityId): bool
    {
        $universityId = trim($universityId);
        if ($universityId === '') {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE university_id = ? LIMIT 1');
        $stmt->execute([$universityId]);
        return $stmt->fetchColumn() !== false;
    }
    public function create(array $data): int
    {
        foreach (self::REQUIRED_ON_CREATE as $key) {
            if (!isset($data[$key]) || trim((string) $data[$key]) === '') {
                throw new InvalidArgumentException(
                    "User::create() requires a non-empty '{$key}'."
                );
            }
        }
        $sql = 'INSERT INTO users
                    (full_name, initials, email, university_id, password_hash, department_id)
                VALUES
                    (:full_name, :initials, :email, :university_id, :password_hash, :department_id)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':full_name', trim((string) $data['full_name']), PDO::PARAM_STR);
        $stmt->bindValue(':initials', strtoupper(trim((string) $data['initials'])), PDO::PARAM_STR);
        $stmt->bindValue(':email', $this->normaliseEmail((string) $data['email']), PDO::PARAM_STR);
        $stmt->bindValue(':university_id', trim((string) $data['university_id']), PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', (string) $data['password_hash'], PDO::PARAM_STR);


        $departmentId = $data['department_id'] ?? null;
        if ($departmentId === null || $departmentId === '') {
            $stmt->bindValue(':department_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':department_id', (int) $departmentId, PDO::PARAM_INT);
        }
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            throw $this->translateDuplicateKey($e);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastActive(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare('UPDATE users SET last_active_at = NOW() WHERE id = ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
    public function createUserSettings(int $userId): bool
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'User::createUserSettings() requires a positive user id.'
            );
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_settings (user_id) VALUES (?)
             ON DUPLICATE KEY UPDATE user_id = user_id'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
    }
    private function translateDuplicateKey(PDOException $e): Throwable
    {
        $isDuplicate = $e->getCode() === '23000'
            && isset($e->errorInfo[1])
            && (int) $e->errorInfo[1] === 1062;
        if (!$isDuplicate) {
            return $e;
        }
        $message = $e->getMessage();

        if (stripos($message, 'uq_users_email') !== false) {
            return new RuntimeException('That email address is already registered.', 0, $e);
        }
        if (stripos($message, 'uq_users_university_id') !== false) {
            return new RuntimeException('That University ID is already registered.', 0, $e);
        }
        return new RuntimeException('That account already exists.', 0, $e);
    }
}