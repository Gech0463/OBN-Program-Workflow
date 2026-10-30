<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../app/auth/auth_check.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, [], 'Method not allowed.', 405);
$data = request_data();
verify_csrf($data['csrf'] ?? null);
$id = (int)($data['id'] ?? 0);
$status = (string)($data['status'] ?? 'ACTIONED');
if ($id < 1 || !in_array($status, ['ACTIONED', 'DISMISSED'], true)) json_response(false, [], 'Invalid reset action.', 422);
$stmt = db()->prepare('UPDATE password_reset_requests SET status = ?, actioned_by = ?, actioned_at = NOW() WHERE id = ? AND status = \'PENDING\'');
$stmt->execute([$status, user()['id'], $id]);
json_response(true, ['updated' => $stmt->rowCount()]);
