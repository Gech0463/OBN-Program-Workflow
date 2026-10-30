<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('programs.php');
verify_csrf($_POST['csrf'] ?? null);
$wantsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
$respond = static function (bool $success, string $message, ?string $redirect = null, int $status = 200) use ($wantsJson): never {
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'error' => $success ? null : $message, 'data' => $success ? ['redirect' => $redirect] : null]);
        exit;
    }
    $_SESSION[$success ? 'welcome_toast' : 'error'] = $message;
    redirect($redirect ?? 'programs.php');
};
$pdo = db();
$programId = (int) ($_POST['program_id'] ?? 0);
$nextStageId = (int) ($_POST['next_stage_id'] ?? 0);
$roomId = (int) ($_POST['room_id'] ?? 0);
$employeeId = (int) ($_POST['employee_id'] ?? 0);
$isOverride = user()['role'] === 'ADMIN' && ($_POST['override'] ?? '') === '1';
$overrideReason = trim((string) ($_POST['override_reason'] ?? ''));
$pdo->beginTransaction();
try {
    $query = $pdo->prepare('SELECT p.*, s.sequence_no, s.stage_key, s.name stage_name, s.expected_minutes FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE p.id = ? FOR UPDATE');
    $query->execute([$programId]);
    $program = $query->fetch();
    if (!$program) {
        throw new RuntimeException('Program was not found.');
        }
    if (in_array($program['current_status'], ['BLOCKED', 'COMPLETED', 'CANCELLED'], true)) {
        throw new RuntimeException('This program cannot be advanced while it is blocked or finished.');
    }
    if (user()['role'] !== 'ADMIN') {
        $currentRole = workflow_stage_role((string) $program['stage_key']);
        if ($currentRole === '' || user()['role'] !== $currentRole) {
            throw new RuntimeException('You are not authorized to complete the current workflow stage.');
        }
    }
    if ($isOverride && $overrideReason === '') {
        throw new RuntimeException('An administrator override requires a reason.');
    }
    $query = $pdo->prepare('SELECT * FROM workflow_stages WHERE id = ? AND active = 1');
    $query->execute([$nextStageId]);
    $stage = $query->fetch();
    if (!$stage) {
        throw new RuntimeException('This stage move is not allowed. Use the next configured stage.');
        }
    $nextRole = workflow_stage_role((string) $stage['stage_key']);
    $employeeCheck = $pdo->prepare('SELECT u.role FROM users u INNER JOIN employees e ON e.id=u.employee_id WHERE e.id=? AND e.active=1 AND u.active=1 LIMIT 1');
    $employeeCheck->execute([$employeeId]);
    if ($employeeCheck->fetchColumn() !== $nextRole) {
        throw new RuntimeException('The program must be assigned to an active employee responsible for the next stage.');
    }
    $expectedNext = (int) ($program['sequence_no'] ?? 0) + 1;
    if (!$isOverride && (int) $stage['sequence_no'] !== $expectedNext) {
        throw new RuntimeException('This stage move is not allowed. Use the next configured stage.');
        }
    $startTime = $program['current_started_at'] ?: $program['updated_at'] ?: date('Y-m-d H:i:s');
    $started = new DateTimeImmutable($startTime);
    $actualMinutes = max(0, (int) round((time() - $started->getTimestamp()) / 60));
    $delayMinutes = max(0, $actualMinutes - (int) ($program['expected_minutes'] ?? 0));
    $oldStageId = $program['current_stage_id'] ? (int) $program['current_stage_id'] : null;
    $oldRoomId = $program['current_room_id'] ? (int) $program['current_room_id'] : null;
    $oldEmployeeId = $program['current_employee_id'] ? (int) $program['current_employee_id'] : null;
    $history = $pdo->prepare('UPDATE program_stage_history SET actual_end = NOW(), actual_minutes = ?, delay_minutes = ?, status = ? WHERE program_id = ? AND actual_end IS NULL');
    $history->execute([$actualMinutes, $delayMinutes, $delayMinutes > 0 ? 'DELAYED' : 'COMPLETED', $programId]);
    if ($delayMinutes > 0) {
        $pdo->prepare('INSERT INTO delays(program_id,stage_id,employee_id,reason,minutes) VALUES (?,?,?,?,?)')
        ->execute([$programId, $oldStageId, $oldEmployeeId, 'Exceeded expected duration', $delayMinutes]);
        $pdo->prepare('INSERT INTO delay_records(program_id,stage_id,reason,minutes,recorded_by) VALUES (?,?,?,?,?)')
        ->execute([$programId, $oldStageId, 'Exceeded expected duration', $delayMinutes, user()['id']]);
        }
    $isComplete = $stage['stage_key'] === 'UPLINK';
    $progress = $isComplete ? 100 : min(99, (int) round(((int) $stage['sequence_no'] - 1) / 9 * 100));
    $status = $isComplete ? 'COMPLETED' : 'WAITING';
    $update = $pdo->prepare('UPDATE programs SET previous_stage_id = ?, previous_room_id = ?, previous_employee_id = ?, current_stage_id = ?, current_room_id = ?, current_employee_id = ?, current_status = ?, current_started_at = NOW(), progress = ?, completed_at = IF(?, NOW(), NULL), updated_at = NOW() WHERE id = ?');
    $update->execute([
    $oldStageId,
    $oldRoomId,
    $oldEmployeeId,
    $stage['id'],
    $roomId,
    $employeeId,
    $status,
    $progress,
    $isComplete ? 1 : 0,
    $programId,
    ]);
    $pdo->prepare('INSERT INTO program_stage_history(program_id,stage_id,room_id,employee_id,status,planned_start,actual_start,expected_minutes,notes) VALUES (?,?,?,?,?,NOW(),NOW(),?,?)')->execute([
    $programId,
    $stage['id'],
    $roomId,
    $employeeId,
    $isComplete ? 'COMPLETED' : 'WAITING',
    $stage['expected_minutes'],
    trim($_POST['notes'] ?? '')
    ]);
    $pdo->prepare(
    'INSERT INTO program_movements(
            program_id,from_stage_id,from_room_id,to_stage_id,to_room_id,
            moved_by,previous_duration_minutes,notes
        ) VALUES (?,?,?,?,?,?,?,?)'
    )->execute([
    $programId,
    $oldStageId,
    $oldRoomId,
    $stage['id'],
    $roomId,
    user()['id'],
    $actualMinutes,
    trim($_POST['notes'] ?? ''),
    ]);
    $message = $isComplete ? 'Program completed: '.$program['title'] : 'Program moved to '.$stage['name'].': '.$program['title'];
    notify_role('ADMIN', $programId, $isComplete ? 'program_completed' : ($delayMinutes ? 'program_delayed' : 'program_moved'), $message, user()['id']);
    $ownerStmt = $pdo->prepare("SELECT u.id FROM users u INNER JOIN programs p ON p.journalist_id = u.employee_id WHERE p.id = ? AND u.role = 'JOURNALIST' AND u.active = 1");
    $ownerStmt->execute([$programId]);
    notify_user((int)($ownerStmt->fetchColumn() ?: 0), $programId, $isComplete ? 'program_completed' : ($delayMinutes ? 'program_delayed' : 'program_moved'), $message);
    $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,old_value,new_value,ip_address) VALUES (?,?,?,?,?,?,?)')->execute([
    user()['id'],
    $isOverride ? 'Administrator workflow override' : 'Moved program to next stage',
    'PROGRAM',
    (string)$programId,
    json_encode(['stage'=>$program['stage_name'], 'room'=>$oldRoomId]),
    json_encode(['stage'=>$stage['name'], 'room'=>$roomId, 'override'=>$isOverride, 'reason'=>$overrideReason]),
    $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    $pdo->commit();
    calculate_performance($programId);
    $respond(true, $message, 'program.php?id='.$programId);
    }
catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $respond(false, 'The program could not be moved. Please check the workflow details and try again.', 'program.php?id='.$programId, 422);
    }
