<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, [], 'Method not allowed.', 405);
}

$data = request_data();
verify_csrf($data['csrf'] ?? null);

$fullName = trim((string)($data['full_name'] ?? ''));
$role = (string)($data['role'] ?? '');
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$confirmation = (string)($data['password_confirmation'] ?? '');
$errors = [];

if (mb_strlen($fullName) < 2) {
    $errors['full_name'] = 'Enter your full name.';
}

$validRoles = [
    'JOURNALIST', 'INGEST_OPERATOR', 'PREVIEW_OPERATOR', 
    'EDITOR', 'GRAPHICS_OPERATOR', 'AUDIO_OPERATOR', 
    'QC_OPERATOR', 'MASTER_CONTROL_OPERATOR', 'UPLINK_OPERATOR'
];

if (!in_array($role, $validRoles, true)) {
    $errors['role'] = 'Select a valid staff role. Administrator accounts cannot be created here.';
}

if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
    $errors['username'] = 'Use 3-80 letters, numbers, dots, underscores, or hyphens.';
}

if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if ($password !== $confirmation) {
    $errors['password_confirmation'] = 'Passwords do not match.';
}

if ($errors) {
    json_response(false, ['fields' => $errors], 'Please correct the highlighted fields.', 422);
}
$pdo = db();
$existing = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$existing->execute([$username]);
if ($existing->fetchColumn()) {
    json_response(false, ['fields' => ['username' => 'That username is already in use.']], 'Choose another username.', 409);
}

$pdo->beginTransaction();
try {
    $employeeCode = 'EMP-' . date('YmdHis') . '-' . random_int(100, 999);
    // Insert with default/null for removed fields
    $employee = $pdo->prepare('INSERT INTO employees(employee_code, full_name, phone, position_id, position, department_id, bio, preferred_language, profile_completed_at, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)');
    $employee->execute([$employeeCode, $fullName, '', null, '', null, '', 'en']);
    $employeeId = (int)$pdo->lastInsertId();

    $account = $pdo->prepare('INSERT INTO users(employee_id, username, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
    $account->execute([$employeeId, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
    $userId = (int)$pdo->lastInsertId();
    $pdo->commit();

    $_SESSION['login_success'] = 'Account created successfully. Log in with your new username and password.';
    json_response(true, ['redirect' => 'login.php']);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, [], 'The account could not be created. Please try again.', 500);
}
