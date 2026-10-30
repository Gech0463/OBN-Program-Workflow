<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Invalid request method.']);
}

require_login();

$id = (int)($_POST['id'] ?? 0);
$csrf = (string)($_POST['csrf'] ?? '');

if (!verify_csrf($csrf)) {
    json_response(['success' => false, 'error' => 'Invalid CSRF token.']);
}

$employeeStmt = db()->prepare('SELECT employee_id FROM users WHERE id = ?');
$employeeStmt->execute([user()['id']]);
$employeeId = (int)$employeeStmt->fetchColumn();

if (!$employeeId) {
    json_response(['success' => false, 'error' => 'You must be linked to an employee profile.']);
}

$programStmt = db()->prepare('SELECT p.*, s.stage_key FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE p.id = ?');
$programStmt->execute([$id]);
$program = $programStmt->fetch();

if (!$program) {
    json_response(['success' => false, 'error' => 'Program not found.']);
}

$operatorStageKeys = workflow_stage_keys_for_role((string)user()['role']);
if (!in_array($program['stage_key'], $operatorStageKeys, true)) {
    json_response(['success' => false, 'error' => 'This program is not at your workflow stage.']);
}

if ($program['current_employee_id'] && $program['current_employee_id'] != $employeeId) {
    json_response(['success' => false, 'error' => 'This program is already being handled by someone else.']);
}

try {
    db()->beginTransaction();

    $update = db()->prepare("UPDATE programs SET current_status = 'IN_PROGRESS', current_employee_id = ?, updated_at = NOW() WHERE id = ?");
    $update->execute([$employeeId, $id]);

    $historyUpdate = db()->prepare("UPDATE program_stage_history SET employee_id = ?, updated_at = NOW() WHERE program_id = ? AND stage_id = ? AND actual_end IS NULL");
    $historyUpdate->execute([$employeeId, $id, $program['current_stage_id']]);

    db()->commit();
    json_response(['success' => true, 'data' => ['message' => 'Program accepted successfully.']]);
} catch (Exception $e) {
    db()->rollBack();
    json_response(['success' => false, 'error' => 'A database error occurred.']);
}

