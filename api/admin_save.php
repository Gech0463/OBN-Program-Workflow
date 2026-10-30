<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
    }
verify_csrf($_POST['csrf'] ?? null);
$entity = $_POST['entity'] ?? '';
$action = $_POST['action'] ?? 'create';
$id = (int) ($_POST['id'] ?? 0);
$pdo = db();
try {
    if ($entity === 'department' && $action === 'create') {
        $pdo->prepare('INSERT INTO departments(name) VALUES (?)')->execute([trim($_POST['name'])]);
        $message = 'Department added.';
        }
    elseif ($entity === 'type' && $action === 'create') {
        $pdo->prepare('INSERT INTO program_types(name) VALUES (?)')->execute([trim($_POST['name'])]);
        $message = 'Program type added.';
        }
    elseif ($entity === 'room' && $action === 'create') {
        $pdo->prepare(
        'INSERT INTO rooms(code,name,building,floor,room_type,department_id,capacity,description,status)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
        trim($_POST['code']),
        trim($_POST['name']),
        trim($_POST['building'] ?? ''),
        trim($_POST['floor'] ?? ''),
        trim($_POST['room_type']),
        ($_POST['department_id'] ?? '') ? (int) $_POST['department_id'] : null,
        (int) ($_POST['capacity'] ?? 0),
        trim($_POST['description'] ?? ''),
        'AVAILABLE',
        ]);
        $message = 'Room added.';
        }
    elseif ($entity === 'stage' && $action === 'create') {
        throw new RuntimeException('The operational workflow stages are fixed and cannot be added from this screen.');
        }
    elseif ($entity === 'user' && $action === 'create') {
        $pdo->beginTransaction();
        $employeeId = ($_POST['employee_id'] ?? '') ? (int) $_POST['employee_id'] : null;
        $hash = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        $allowedRoles = ['JOURNALIST', 'INGEST_OPERATOR', 'PREVIEW_OPERATOR', 'EDITOR', 'GRAPHICS_OPERATOR', 'AUDIO_OPERATOR', 'QC_OPERATOR', 'MASTER_CONTROL_OPERATOR', 'UPLINK_OPERATOR'];
        $role = in_array($_POST['role'] ?? '', $allowedRoles, true) ? $_POST['role'] : 'JOURNALIST';
        if (($_POST['role'] ?? '') === 'ADMIN') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ADMIN' AND active = 1")->fetchColumn();
            if ($adminCount > 0) {
                throw new RuntimeException('Only one active administrator account is allowed.');
            }
            $role = 'ADMIN';
        }
        $pdo->prepare('INSERT INTO users(employee_id,username,password_hash,role) VALUES (?,?,?,?)')
        ->execute([$employeeId, trim($_POST['username']), $hash, $role]);
        $pdo->commit();
        $message = 'User added.';
        }
    elseif ($action === 'delete' && in_array($entity, ['department', 'type', 'room', 'stage'], true)) {
        $table = [
        'department' => 'departments',
        'type' => 'program_types',
        'room' => 'rooms',
        'stage' => 'workflow_stages',
        ][$entity];
        if ($entity === 'department') {
            $pdo->prepare("DELETE FROM {
                $table}
             WHERE id=?")->execute([$id]);
            }
        else {
            $pdo->prepare("UPDATE {
                $table}
             SET active=0 WHERE id=?")->execute([$id]);
            }
        $message = 'Record disabled.';
        }
    else {
        throw new RuntimeException('Unsupported administration action.');
        }
    $_SESSION['admin_message']=$message;
    }
catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        }
    $_SESSION['admin_error'] = 'Could not save this record. Check that the value is unique and valid.';
    }
redirect('admin.php');
