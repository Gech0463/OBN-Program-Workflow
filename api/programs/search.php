<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../app/auth/auth_check.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
$_SESSION['search_requests'] = (int)($_SESSION['search_requests'] ?? 0) + 1;
if ($_SESSION['search_requests'] > 120) json_response(false, [], 'Search rate limit reached.', 429);
$query = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) json_response(true, ['results' => []]);
$term = '%' . $query . '%';
$sql = 'SELECT p.id, p.program_code, p.title, p.current_status, s.name stage_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE (p.program_code LIKE ? OR p.title LIKE ?)';
$params = [$term, $term];
if (user()['role'] === 'JOURNALIST') {
    $sql .= ' AND p.journalist_id = (SELECT employee_id FROM users WHERE id = ?)';
    $params[] = user()['id'];
}
$sql .= ' ORDER BY p.updated_at DESC LIMIT 10';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
if (user()['role'] === 'ADMIN' && count($results) < 10) {
    $staff = db()->prepare('SELECT id, full_name, email FROM employees WHERE active = 1 AND (full_name LIKE ? OR email LIKE ?) ORDER BY full_name LIMIT ?');
    $remaining = 10 - count($results);
    $staff->bindValue(1, $term);
    $staff->bindValue(2, $term);
    $staff->bindValue(3, $remaining, PDO::PARAM_INT);
    $staff->execute();
    foreach ($staff->fetchAll() as $employee) $results[] = ['employee_id' => $employee['id'], 'title' => $employee['full_name'], 'subtitle' => $employee['email'], 'type' => 'staff'];
}
json_response(true, ['results' => $results]);
