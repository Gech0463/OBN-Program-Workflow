<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, s.name stage_name, r.name room_name, e.full_name responsible, pt.name type_name, d.name department_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id LEFT JOIN employees e ON e.id=p.current_employee_id LEFT JOIN program_types pt ON pt.id=p.program_type_id LEFT JOIN departments d ON d.id=p.department_id WHERE p.id=?');
$stmt->execute([$id]);
$program = $stmt->fetch();
if (!$program) {
    http_response_code(404);
    exit('Program not found');
}
if (!user_can_access_program($id)) {
    http_response_code(403);
    exit('Forbidden');
}
?>
<div class="panel-header"><div><p class="eyebrow"><?= e($program['program_code']) ?></p><h2><?= e($program['title']) ?></h2><p class="muted"><?= e($program['description'] ?? '') ?></p></div><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_', ' ', $program['current_status'])) ?></span></div>
<div class="detail-grid"><div><span>Current stage</span><strong><?= e($program['stage_name'] ?: 'Program Journal') ?></strong></div><div><span>Location</span><strong><?= e($program['room_name'] ?: 'Not assigned') ?></strong></div><div><span>Responsible</span><strong><?= e($program['responsible'] ?: 'Unassigned') ?></strong></div><div><span>Progress</span><strong><?= (int) $program['progress'] ?>%</strong></div></div>
<div class="drawer-actions"><a class="button secondary" href="program.php?id=<?= (int) $program['id'] ?>">Full workflow</a><?php if (user()['role'] === 'ADMIN' && $program['current_status'] !== 'COMPLETED'): ?><a class="button primary" data-transition-link href="transition.php?id=<?= (int) $program['id'] ?>">Open transition</a><?php endif; ?></div>
