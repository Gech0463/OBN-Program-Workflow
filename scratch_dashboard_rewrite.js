const fs = require('fs');

let code = fs.readFileSync('dashboard.php', 'utf8');

const replacement = `if (user()['role'] !== 'ADMIN'):
$employeeUser = db()->prepare('SELECT employee_id FROM users WHERE id = ?');
$employeeUser->execute([user()['id']]);
$employeeId = (int) ($employeeUser->fetchColumn() ?: 0);
$operatorStageKeys = workflow_stage_keys_for_role((string) user()['role']);
$operatorStageSql = implode(',', array_fill(0, count($operatorStageKeys), '?'));

// My Work Query
$workQuery = db()->prepare("SELECT p.*, s.name stage_name, r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE p.current_employee_id = ? AND s.stage_key IN ({$operatorStageSql}) AND p.current_status != 'COMPLETED' ORDER BY p.updated_at DESC");
$workQuery->execute(array_merge([$employeeId], $operatorStageKeys));
$myWork = $workQuery->fetchAll();

// Available Pool Query
$poolQuery = db()->prepare("SELECT p.*, s.name stage_name, r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id WHERE (p.current_employee_id IS NULL OR p.current_employee_id != ?) AND p.current_status IN ('WAITING', 'DELAYED') AND s.stage_key IN ({$operatorStageSql}) ORDER BY p.updated_at ASC");
$poolQuery->execute(array_merge([$employeeId], $operatorStageKeys));
$availablePool = $poolQuery->fetchAll();

$completedHistoryQuery = db()->prepare('SELECT COUNT(DISTINCT h.program_id) FROM program_stage_history h WHERE h.employee_id = ? AND h.actual_end IS NOT NULL');
$completedHistoryQuery->execute([$employeeId]);
$completedWorkCount = (int) $completedHistoryQuery->fetchColumn();
?>
<section class="dashboard-intro"><div><p class="eyebrow"><?= e(__('dashboard_title')) ?></p><h2>Welcome, <?= e(user()['name']) ?></h2><p class="muted">Manage programs in your department. Accept incoming tasks, process them, and hand them off.</p></div><div class="quick-actions"><a class="button secondary" href="tracking.php">Find a program</a><a class="button secondary" href="issues.php">Report a problem</a></div></section>
<section class="stats-grid"><div class="stat"><span>Incoming pool</span><strong><?= count($availablePool) ?></strong><small class="muted">Waiting to be accepted</small></div><div class="stat"><span>My current work</span><strong><?= count($myWork) ?></strong><small class="muted">In progress by me</small></div><div class="stat"><span>Completed</span><strong><?= $completedWorkCount ?></strong><small class="muted">Assigned history</small></div></section>

<!-- Incoming Pool -->
<section class="panel"><div class="panel-header"><div><h2>Incoming programs (Waiting)</h2><p class="muted">These programs are at your stage. Accept them to begin your work.</p></div></div><?php if (!$availablePool): ?><div class="empty-state"><h2>No incoming programs</h2><p class="muted">There are currently no programs waiting for your role.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Program</th><th><?= e(__('stage')) ?></th><th>Room</th><th><?= e(__('status')) ?></th><th><?= e(__('action')) ?></th></tr></thead><tbody><?php foreach ($availablePool as $program): ?><tr><td><a href="program.php?id=<?= (int) $program['id'] ?>"><strong><?= e($program['title']) ?></strong></a><br><small class="muted"><?= e($program['program_code']) ?></small></td><td><?= e($program['stage_name'] ?: '-') ?></td><td><?= e($program['room_name'] ?: 'Not assigned') ?></td><td><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_', ' ', $program['current_status'])) ?></span></td><td>
<form method="POST" action="api/program_accept.php" data-accept-form style="display:inline-block;"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $program['id'] ?>"><button type="submit" class="button primary">Accept Program</button></form>
</td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>

<!-- My Work -->
<section class="panel"><div class="panel-header"><div><h2>My active work</h2><p class="muted">Programs you have accepted. Process them to advance the workflow.</p></div></div><?php if (!$myWork): ?><div class="empty-state compact-empty"><strong>No active work</strong><p class="muted">Accept a program from the incoming pool above.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Program</th><th><?= e(__('stage')) ?></th><th>Room</th><th><?= e(__('status')) ?></th><th><?= e(__('action')) ?></th></tr></thead><tbody><?php foreach ($myWork as $program): ?><tr><td><a href="program.php?id=<?= (int) $program['id'] ?>"><strong><?= e($program['title']) ?></strong></a><br><small class="muted"><?= e($program['program_code']) ?></small></td><td><?= e($program['stage_name'] ?: '-') ?></td><td><?= e($program['room_name'] ?: 'Not assigned') ?></td><td><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_', ' ', $program['current_status'])) ?></span></td><td><?php if (!in_array($program['current_status'], ['COMPLETED', 'BLOCKED'], true)): ?><a class="button secondary" href="transition.php?id=<?= (int) $program['id'] ?>">Process next</a><?php else: ?><span class="muted">No action</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php require_once __DIR__.'/includes/footer.php'; exit; endif; ?>`;

const operatorBlockStart = code.indexOf("if (user()['role'] !== 'ADMIN'):");
const operatorBlockEnd = code.indexOf("<?php require_once __DIR__.'/includes/footer.php'; exit; endif; ?>") + "<?php require_once __DIR__.'/includes/footer.php'; exit; endif; ?>".length;

const newCode = code.substring(0, operatorBlockStart) + replacement + code.substring(operatorBlockEnd);

fs.writeFileSync('dashboard.php', newCode, 'utf8');
console.log('Replaced dashboard.php');

