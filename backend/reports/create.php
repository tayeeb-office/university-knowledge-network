<?php
require_once __DIR__ . '/../helpers/actions.php';
require_once __DIR__ . '/../helpers/reports.php';

// Report a visible post or comment for admin review (admin/reports.php). The reporter is the
// session user; one report per reporter and target (uq_reports_one_per_reporter). One
// transaction: the report row (reference UKN-R-<id>) and the target's report_count.
uknRequirePostMethod();
requireLogin();
uknRequireActionCsrf('home');

$targetType = uknPostString('target_type');
if (!in_array($targetType, ['post', 'comment'], true)) {
    uknFailAction('Invalid report.', 'home');
}
$targetId = uknPostId('target_id');
if ($targetId === null) {
    uknFailAction('That ' . $targetType . ' is no longer available.', 'home');
}
$reason = uknPostString('reason');
if ($reason === null || !array_key_exists($reason, uknReportReasons())) {
    uknFailAction('Choose a reason for your report.', 'home');
}
if (array_key_exists('description', $_POST) && uknPostString('description') === null) {
    uknFailAction('Invalid report details.', 'home');
}
$description = trim(sanitizeInput(uknPostString('description') ?? ''));
if (!validateLength($description, 0, UKN_REPORT_DESCRIPTION_MAX)) {
    uknFailAction('Report details can be up to ' . UKN_REPORT_DESCRIPTION_MAX . ' characters.', 'home');
}

$outcome = null;
try {
    $pdo = getDatabaseConnection();
    $userId = (int) getCurrentUser()['id'];
    $pdo->beginTransaction();
    // Only content the community can see: a visible post, or a visible comment on a visible
    // post whose parent (for a reply) is visible too.
    $target = $pdo->prepare($targetType === 'post'
        ? "SELECT user_id FROM posts WHERE id = ? AND status = 'visible' FOR UPDATE"
        : "SELECT c.user_id FROM comments c
           JOIN posts p ON p.id = c.post_id AND p.status = 'visible'
           LEFT JOIN comments pc ON pc.id = c.parent_id
           WHERE c.id = ? AND c.status = 'visible' AND (c.parent_id IS NULL OR pc.status = 'visible')
           FOR UPDATE");
    $target->execute([$targetId]);
    $authorId = $target->fetchColumn();
    if ($authorId === false) {
        $outcome = ['danger', 'That ' . $targetType . ' is no longer available.'];
    } elseif ((int) $authorId === $userId) {
        $outcome = ['danger', 'You cannot report your own ' . $targetType . '.'];
    } else {
        $existing = $pdo->prepare('SELECT 1 FROM reports WHERE reporter_id = ? AND target_type = ? AND target_id = ?');
        $existing->execute([$userId, $targetType, $targetId]);
        if ($existing->fetchColumn() !== false) {
            $outcome = ['success', 'You have already reported this ' . $targetType . '.'];
        }
    }
    if ($outcome !== null) {
        $pdo->rollBack();
    } else {
        // reference_code is NOT NULL UNIQUE and derived from the new id: insert a unique
        // placeholder, then set the final code in the same transaction.
        $pdo->prepare(
            'INSERT INTO reports (reference_code, reporter_id, target_type, target_id, reason, description)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute(['TMP-' . bin2hex(random_bytes(8)), $userId, $targetType, $targetId, $reason, $description === '' ? null : $description]);
        $reportId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE reports SET reference_code = ? WHERE id = ?')
            ->execute([sprintf('UKN-R-%04d', $reportId), $reportId]);
        // A report is not an edit of the content: updated_at keeps its value.
        $pdo->prepare(($targetType === 'post' ? 'UPDATE posts' : 'UPDATE comments')
            . ' SET report_count = report_count + 1, updated_at = updated_at WHERE id = ?')
            ->execute([$targetId]);
        $pdo->commit();
        $outcome = ['success', 'Thanks — this ' . $targetType . ' has been reported for review.'];
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // A concurrent duplicate submission loses the race on uq_reports_one_per_reporter.
    if ($e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062) {
        $outcome = ['success', 'You have already reported this ' . $targetType . '.'];
    } else {
        error_log('[UKN reports/create] ' . $e->getMessage());
        $outcome = ['danger', 'Your report could not be sent. Please try again.'];
    }
}
uknFlashToast($outcome[0], $outcome[1]);
uknRedirectBack('home');
