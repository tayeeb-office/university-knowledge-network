<?php
/**
 * User model — every read and write against the `users` table.
 *
 *     require_once __DIR__ . '/../models/User.php';
 *
 *     $users = new User();                 // opens its own connection
 *     $users = new User($existingPdo);     // or reuses one you already have
 *
 * SCOPE
 * -----
 * Data access only. This class never starts a session, never redirects,
 * never echoes, and never emits HTML — callers decide what to do with
 * what it returns. That is what makes it reusable from a web endpoint,
 * from admin pages, and from a CLI script alike.
 *
 * CONNECTION
 * ----------
 * Pass a PDO into the constructor wherever you already have one.
 * getDatabaseConnection() in backend/config/database.php builds a NEW
 * connection on every call, so letting each model open its own would
 * mean several connections per request. Registration in particular must
 * pass one shared handle, because create() and createUserSettings()
 * belong in a single transaction and a transaction cannot span two
 * connections.
 *
 * TRANSACTIONS
 * ------------
 * No method here begins, commits or rolls back a transaction. That is
 * deliberate: an inner beginTransaction() would silently break an outer
 * one the caller had already opened. Registration owns the transaction:
 *
 *     $pdo = getDatabaseConnection();
 *     $users = new User($pdo);
 *     $pdo->beginTransaction();
 *     try {
 *         $id = $users->create($data);
 *         $users->createUserSettings($id);
 *         $pdo->commit();
 *     } catch (Throwable $e) {
 *         $pdo->rollBack();
 *         throw $e;
 *     }
 *
 * ERROR HANDLING
 * --------------
 * Genuine database failures surface as PDOException and are left to
 * propagate — swallowing them would hide real bugs behind a "user not
 * found". The one case that IS translated is a duplicate-key collision
 * on insert, which is an expected outcome of registration (two people
 * can submit the same email a millisecond apart, after emailExists()
 * said it was free). That becomes a RuntimeException naming the field,
 * so the caller can show a useful message instead of a stack trace.
 *
 * NOTE: no closing `?>` tag, matching the rest of backend/.
 */

require_once __DIR__ . '/../config/database.php';

class User
{
    /**
     * Columns the login path needs. INCLUDES password_hash, because
     * password_verify() cannot work without it.
     */
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

    /**
     * Columns the profile/display path needs. Deliberately EXCLUDES
     * password_hash — a hash that is never selected can never be leaked
     * into a template, a var_dump, a log line or a JSON response by
     * accident. findByEmail() is the only way to obtain it, and only
     * authentication should ever call that.
     */
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

    /** Required keys for create(). Every one is NOT NULL with no default. */
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

    /**
     * Find one user by email, including the password hash.
     *
     * This is the LOGIN lookup. Matching is case-insensitive twice over:
     * the value is lowercased here, and the column's own collation
     * (utf8mb4_unicode_ci) is case-insensitive anyway.
     *
     * Returns the row as an associative array, or null when no such user
     * exists. Note that this does NOT filter on status — a suspended
     * account must still be found, so the login handler can tell the
     * difference between "wrong password" and "your account is
     * suspended" rather than reporting both as a failed login.
     *
     * @return array<string, mixed>|null
     */
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

    /**
     * Fetch a user profile by id, WITHOUT the password hash.
     *
     * Used for rendering a profile, the header, the sidebar — anywhere
     * the app needs to know who someone is rather than authenticate them.
     *
     * @return array<string, mixed>|null
     */
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

    /**
     * Is this email already registered?
     *
     * Selects only `id`, never the whole row — this is a yes/no question
     * and there is no reason to pull a password hash across to answer it.
     *
     * This is a convenience check for a friendly form error, NOT the
     * guarantee of uniqueness. The guarantee is uq_users_email in the
     * schema; create() handles the collision if one slips through the
     * gap between this check and the insert.
     */
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

    /**
     * Is this university ID already registered?
     *
     * Same contract as emailExists() — backed by uq_users_university_id.
     */
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

    /**
     * Insert a new user and return its id.
     *
     * Expected $data keys:
     *   full_name      (required)
     *   initials       (required — derive from full_name; the column is
     *                   NOT NULL with no default, so an insert without it
     *                   fails outright)
     *   email          (required)
     *   university_id  (required)
     *   password_hash  (required — already hashed; this method never
     *                   hashes anything, so a plain password passed here
     *                   would be stored in plain text)
     *   department_id  (optional, nullable)
     *
     * Everything else takes its schema default: role 'learner',
     * status 'active', is_admin 0, all point totals 0, created_at NOW().
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException when a required key is missing or blank
     * @throws RuntimeException         on a duplicate email / university ID
     * @throws PDOException             on any other database failure
     */
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

        // department_id is nullable, so an absent or empty value must bind
        // as a real SQL NULL — binding '' would fail the foreign key.
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

    /**
     * Stamp last_active_at = NOW() for this user.
     *
     * Called after a successful login, and worth calling on ordinary
     * requests later if you want the "last active" column in
     * admin/users.php to mean anything.
     *
     * @return bool true when a row was actually updated (false means no
     *              such user id, which is worth noticing)
     */
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

    /**
     * Give a newly created user their default settings row.
     *
     * Every column in user_settings except user_id has a schema default,
     * so this inserts the key alone and lets the database fill the rest —
     * one place defining the defaults instead of two that can drift.
     *
     * user_settings.user_id is the primary key, so this is written to be
     * idempotent: calling it twice for the same user is a no-op rather
     * than a duplicate-key error, which keeps a retried registration from
     * failing on its second attempt.
     *
     * ON DUPLICATE KEY UPDATE is used rather than INSERT IGNORE on
     * purpose. INSERT IGNORE downgrades EVERY error to a warning — a
     * foreign-key violation from a user id that does not exist would be
     * silently swallowed and reported back here as "already existed".
     * This form absorbs only the primary-key collision it is meant to.
     *
     * @return bool true if a row was created, false if one already existed
     * @throws PDOException if the user id does not exist (FK violation)
     */
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

    /**
     * Trim and lowercase an email so the same address always looks the
     * same going in and coming out.
     */
    private function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Turn a unique-constraint violation into something a caller can act
     * on, naming the field that actually collided.
     *
     * SQLSTATE 23000 / MySQL error 1062 is "duplicate entry". The index
     * name in the driver message tells us which constraint fired.
     * Anything else is returned untouched so it keeps propagating as the
     * real error it is.
     */
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
