<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
if (user()['role'] === 'ADMIN') {
    http_response_code(403);
    exit('Administrators manage reported problems instead of submitting them.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('issues.php');
    }
verify_csrf($_POST['csrf'] ?? null);
$pdo = db();
$programId = (int) $_POST['program_id'];
$code = 'ISS-' . date('YmdHis');
$stmt = $pdo->prepare('SELECT current_stage_id,current_room_id,current_employee_id,current_status,title FROM programs WHERE id=?');
$stmt->execute([$programId]);
$program = $stmt->fetch();
if (!$program) {
    $_SESSION['error'] = 'Program not found.';
    redirect('issues.php');
    }
if (in_array($program['current_status'], ['COMPLETED', 'CANCELLED'], true)) {
    $_SESSION['error'] = 'A completed or cancelled program cannot receive a new problem report.';
    redirect('issues.php');
}
$employeeId = db()->prepare('SELECT employee_id FROM users WHERE id=? AND active=1');
$employeeId->execute([user()['id']]);
$employeeId = (int)($employeeId->fetchColumn() ?: 0);
if (user()['role'] === 'JOURNALIST') {
    $programOwner = $pdo->prepare('SELECT journalist_id FROM programs WHERE id = ?');
    $programOwner->execute([$programId]);
    if ((int) $programOwner->fetchColumn() !== $employeeId) {
        http_response_code(403);
        exit('Forbidden');
    }
} elseif ((int) $program['current_employee_id'] !== $employeeId) {
    http_response_code(403);
    exit('You may report problems only for your assigned current program.');
}
$pdo->prepare(
'INSERT INTO issues(issue_code,program_id,stage_id,room_id,reporter_id,issue_type,priority,description)
	 VALUES (?,?,?,?,?,?,?,?)'
)->execute([
$code,
$programId,
$program['current_stage_id'],
$program['current_room_id'],
$employeeId,
trim($_POST['issue_type']),
$_POST['priority'],
trim($_POST['description']),
]);
$pdo->prepare("UPDATE programs SET current_status = 'BLOCKED', updated_at = NOW() WHERE id = ? AND current_status NOT IN ('COMPLETED', 'CANCELLED')")->execute([$programId]);
notify_role('ADMIN', $programId, 'issue_reported', 'New issue reported for program: ' . $program['title'], user()['id']);
$pdo->prepare(
'INSERT INTO audit_logs(user_id,action,entity_type,entity_id,new_value,ip_address)
	 VALUES (?,?,?,?,?,?)'
)->execute([
user()['id'],
'Reported issue',
'PROGRAM',
(string) $programId,
json_encode(['issue_code' => $code]),
$_SERVER['REMOTE_ADDR'] ?? null,
]);
redirect('issues.php');
