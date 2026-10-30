<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('issues.php');
    }
verify_csrf($_POST['csrf'] ?? null);
$id = (int) ($_POST['id'] ?? 0);
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$status = $_POST['status'] ?? 'OPEN';
$allowed = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
if (!in_array($status, $allowed, true)) {
    $_SESSION['error'] = 'Invalid issue status.';
    redirect('issues.php');
    }
$stmt = db()->prepare('SELECT i.program_id, p.journalist_id FROM issues i INNER JOIN programs p ON p.id = i.program_id WHERE i.id=?');
$stmt->execute([$id]);
$issue = $stmt->fetch();
if (!$issue) {
    $_SESSION['error'] = 'Issue not found.';
    redirect('issues.php');
    }
db()->prepare(
'UPDATE issues
	 SET status=?, resolution=?, resolved_at=IF(? IN (\'RESOLVED\',\'CLOSED\'),NOW(),NULL)
	 WHERE id=?'
)->execute([$status, trim($_POST['resolution'] ?? ''), $status, $id]);
$openIssues = db()->prepare("SELECT COUNT(*) FROM issues WHERE program_id = ? AND status NOT IN ('RESOLVED', 'CLOSED')");
$openIssues->execute([(int) $issue['program_id']]);
$programStatus = (int) $openIssues->fetchColumn() > 0 ? 'BLOCKED' : 'WAITING';
db()->prepare("UPDATE programs SET current_status = ? WHERE id = ? AND current_status NOT IN ('COMPLETED', 'CANCELLED')")->execute([$programStatus, (int) $issue['program_id']]);
db()->prepare(
'INSERT INTO audit_logs(user_id,action,entity_type,entity_id,new_value,ip_address)
	 VALUES (?,?,?,?,?,?)'
)->execute([
user()['id'],
'Updated issue status',
'ISSUE',
(string) $id,
json_encode(['status' => $status]),
$_SERVER['REMOTE_ADDR'] ?? null,
]);
notify_role('ADMIN', (int)$issue['program_id'], 'issue_updated', 'Issue status updated to ' . $status, user()['id']);
$ownerStmt = db()->prepare("SELECT id FROM users WHERE employee_id = ? AND role = 'JOURNALIST' AND active = 1");
$ownerStmt->execute([(int)$issue['journalist_id']]);
notify_user((int)($ownerStmt->fetchColumn() ?: 0), (int)$issue['program_id'], 'issue_updated', 'Issue status updated to ' . $status);
if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => ['id' => $id, 'status' => $status, 'resolution' => trim($_POST['resolution'] ?? '')]]);
    exit;
}
redirect('issues.php');
