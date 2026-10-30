<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, [], 'Method not allowed.', 405);
$data = request_data();
verify_csrf($data['csrf'] ?? null);
$_SESSION['reset_requests'] = (int)($_SESSION['reset_requests'] ?? 0) + 1;
if ($_SESSION['reset_requests'] > 5) json_response(false, [], 'Please wait before requesting another reset.', 429);
$username = trim((string)($data['username'] ?? ''));
if ($username === '') json_response(false, [], 'Username is required.', 422);
$stmt = db()->prepare('SELECT id FROM users WHERE username = ? AND active = 1');
$stmt->execute([$username]);
$userId = (int)($stmt->fetchColumn() ?: 0);
if ($userId) {
    $pending = db()->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'PENDING'");
    $pending->execute([$userId]);
    if (!$pending->fetchColumn()) db()->prepare('INSERT INTO password_reset_requests(user_id) VALUES (?)')->execute([$userId]);
}
json_response(true, [], 'Your request was sent to an administrator.');
