<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
if (user()['role'] !== 'JOURNALIST') {
    http_response_code(403);
    exit('Only journalists can create programs.');
}
$isJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('programs.php');
verify_csrf($_POST['csrf'] ?? null);
$title = trim($_POST['title'] ?? '');
$plannedStart = trim($_POST['planned_start'] ?? '');
if ($title === '' || $plannedStart === '') {
    $_SESSION['error'] = 'Program title and planned start are required.';
    if ($isJson) json_response(false, [], $_SESSION['error'], 422);
    redirect('program_new.php');
    }
$pdo = db();
$pdo->beginTransaction();
try {
    $code = 'OBN-PRG-' . date('Y') . '-' . str_pad((string) (((int) $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn()) + 1), 5, '0', STR_PAD_LEFT);
    $initialStageId = (int) $pdo->query("SELECT id FROM workflow_stages WHERE stage_key = 'JOURNALIST' AND active = 1 ORDER BY sequence_no LIMIT 1")->fetchColumn();
    if (!$initialStageId) {
        throw new RuntimeException('The journalist submission workflow stage is not configured.');
    }
    $journalistId = !empty($_POST['journalist_id']) ? (int) $_POST['journalist_id'] : null;
    if (user()['role'] === 'JOURNALIST') {
        $ownerStmt = $pdo->prepare("SELECT employee_id FROM users WHERE id = ? AND role = 'JOURNALIST'");
        $ownerStmt->execute([user()['id']]);
        $journalistId = (int) ($ownerStmt->fetchColumn() ?: 0) ?: null;
    }
    $stmt = $pdo->prepare(
    'INSERT INTO programs(
            program_code,title,description,language,source,department_id,
            program_type_id,journalist_id,presenter,priority,planned_start,
            planned_broadcast,expected_duration_minutes,current_stage_id,
            current_employee_id,current_status,current_started_at,created_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?,NOW(),?)'
    );
    $stmt->execute([
    $code,
    $title,
    trim($_POST['description'] ?? ''),
    trim($_POST['language'] ?? 'Afaan Oromo'),
    trim($_POST['source'] ?? ''),
    (int)($_POST['department_id'] ?? 0),
    (int)($_POST['program_type_id'] ?? 0),
    $journalistId,
    trim($_POST['presenter'] ?? ''),
    $_POST['priority'] ?? 'NORMAL',
    str_replace('T', ' ', $plannedStart),
    !empty($_POST['planned_broadcast']) ? str_replace('T', ' ', $_POST['planned_broadcast']) : null,
    (int)($_POST['expected_duration_minutes'] ?? 60),
    $initialStageId,
    $journalistId,
    'WAITING',
    user()['id']
    ]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare(
    'INSERT INTO program_journal(
            program_id,program_date,scheduled_time,presenter,source,
            initial_location,notes,created_by
        ) VALUES (?,?,?,?,?,?,?,?)'
    )->execute([
    $id,
    date('Y-m-d', strtotime($plannedStart)),
    !empty($_POST['planned_broadcast']) ? date('H:i:s', strtotime($_POST['planned_broadcast'])) : null,
    trim($_POST['presenter'] ?? ''),
    trim($_POST['source'] ?? ''),
    trim($_POST['initial_location'] ?? 'Journal'),
    trim($_POST['description'] ?? ''),
    user()['id']
    ]);
    $pdo->prepare(
    'INSERT INTO program_stage_history(
            program_id,stage_id,employee_id,status,actual_start,expected_minutes,notes
        ) VALUES (?,?,?,?,NOW(),?,?)'
    )->execute([
    $id,
    $initialStageId,
    $journalistId,
    'WAITING',
    0,
    'Program created; awaiting journalist submission',
    ]);
    notify_role('ADMIN', $id, 'new_program', 'New program registered: ' . $title, user()['id']);
    if ($journalistId !== null) {
        $ownerUser = $pdo->prepare("SELECT id FROM users WHERE employee_id = ? AND role = 'JOURNALIST'");
        $ownerUser->execute([$journalistId]);
        notify_user((int) ($ownerUser->fetchColumn() ?: 0), $id, 'new_program', 'Your program was registered: ' . $title);
    }
    $pdo->prepare(
    'INSERT INTO audit_logs(user_id,action,entity_type,entity_id,new_value,ip_address)
         VALUES (?,?,?,?,?,?)'
    )->execute([
    user()['id'],
    'Created program journal',
    'PROGRAM',
    (string) $id,
    json_encode(['code' => $code, 'title' => $title]),
    $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $pdo->commit();
    if ($isJson) json_response(true, ['redirect' => 'program.php?id=' . $id, 'code' => $code, 'title' => $title]);
    redirect('program.php?id=' . $id);
    }
catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        }
    $_SESSION['error'] = 'Could not create the program journal.';
    if ($isJson) json_response(false, [], $_SESSION['error'], 500);
    redirect('program_new.php');
    }
