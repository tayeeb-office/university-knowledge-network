<?php
require_once __DIR__ . '/../../helpers/admin-moderation.php';

// Step 51: resolve or dismiss a pending report (status, reviewed_by = the acting admin from
// the session, reviewed_at). Only pending reports can be reviewed, and only this report
// changes — other reports on the same target stay pending. When resolving, the admin may
// also moderate the target in the same transaction: hide the post / comment, or suspend the
// user, using the Step 48–50 rules. If that action cannot be applied (target gone, own
// account, last admin), nothing is saved. A target that is already hidden/suspended counts
// as moderated. The report row keeps target_type/target_id, so it stays traceable even after
// the content is deleted.
$adminId = uknAdminBeginAction('reports.php');
$reportId = uknPostId('report_id');
$decision = uknAdminEnum('decision', ['resolved', 'dismissed']);
$actionRaw = $_POST['action'] ?? null;
if ($reportId === null) {
    uknAdminFail('Report not found.', 'reports.php');
}
if ($decision === null) {
    uknAdminFail('Choose to resolve or dismiss the report.', 'reports.php');
}
if ($actionRaw !== null && $actionRaw !== 'moderate') {
    uknAdminFail('Invalid report action.', 'reports.php');
}
$moderate = $actionRaw === 'moderate';
if ($moderate && $decision !== 'resolved') {
    uknAdminFail('Only a resolved report can moderate its target.', 'reports.php');
}
$reasonLabels = [
    'academic-integrity' => 'Academic integrity concern', 'off-topic' => 'Off-topic', 'spam' => 'Spam',
    'inappropriate' => 'Inappropriate content', 'harassment' => 'Harassment / conduct', 'other' => 'Other',
];

$outcome = uknAdminRunTx(static function (PDO $pdo) use ($reportId, $decision, $moderate, $adminId, $reasonLabels): array {
    $stmt = $pdo->prepare('SELECT reference_code, target_type, target_id, reason, status FROM reports WHERE id = ? FOR UPDATE');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    if ($report === false) {
        return ['result' => 'missing', 'message' => 'Report not found.', 'label' => ''];
    }
    if ($report['status'] !== 'pending') {
        return ['result' => 'refused', 'message' => $report['reference_code'] . ' has already been reviewed.', 'label' => ''];
    }
    $note = '';
    if ($moderate) {
        $targetId = (int) $report['target_id'];
        if ($report['target_type'] === 'post') {
            $action = uknAdminSetPostStatusTx($pdo, $targetId, 'hidden');
            $done = 'post hidden';
        } elseif ($report['target_type'] === 'comment') {
            $action = uknAdminSetCommentStatusTx($pdo, $targetId, 'hidden');
            $done = 'comment hidden';
        } else {
            $reason = 'Report ' . $report['reference_code'] . ': ' . ($reasonLabels[$report['reason']] ?? $report['reason']);
            $action = uknAdminSuspendUserTx($pdo, $adminId, $targetId, $reason);
            $done = 'user suspended';
        }
        if ($action['result'] === 'missing') {
            return ['result' => 'refused', 'message' => 'The reported ' . $report['target_type'] . ' is no longer available. Resolve the report without the extra action.', 'label' => ''];
        }
        if ($action['result'] === 'refused') {
            return ['result' => 'refused', 'message' => $action['message'], 'label' => ''];
        }
        $note = $action['result'] === 'done' ? '; ' . $done : '; target was already moderated';
    }
    $pdo->prepare("UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'")
        ->execute([$decision, $adminId, $reportId]);
    return ['result' => 'done', 'message' => $report['reference_code'] . ' ' . $decision . $note . '.', 'label' => ''];
}, 'admin/reports/review');

if ($outcome === null) {
    uknAdminFail('Could not update the report. Please try again.', 'reports.php');
}
$outcome['result'] === 'done'
    ? uknAdminDone($outcome['message'], 'reports.php')
    : uknAdminFail($outcome['message'], 'reports.php');
