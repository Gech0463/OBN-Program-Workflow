<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
verify_csrf($_POST['csrf'] ?? null);
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('UPDATE notifications SET read_at=NOW() WHERE id=? AND (user_id=? OR user_id IS NULL)');
$stmt->execute([$id, user()['id']]);
header('Content-Type: application/json');
echo json_encode(['success' => true]);
