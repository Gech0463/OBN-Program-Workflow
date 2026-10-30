<?php

$pageTitle = 'Advance workflow';
$fragment = isset($_GET['fragment']) && $_GET['fragment'] === '1';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);
$program = db()->prepare(
'SELECT p.*, s.sequence_no, s.stage_key, s.name stage_name
	 FROM programs p
	 LEFT JOIN workflow_stages s ON s.id = p.current_stage_id
	 WHERE p.id=?'
);
$program->execute([$id]);
$program = $program->fetch();
if (!$program) {
    exit('Program not found');
    }
require_workflow_actor((string) $program['stage_key']);
$stages = user()['role'] === 'ADMIN'
	? db()->query('SELECT * FROM workflow_stages WHERE active = 1 ORDER BY sequence_no')
	: db()->prepare('SELECT * FROM workflow_stages WHERE sequence_no > ? AND active = 1 ORDER BY sequence_no');
if (user()['role'] !== 'ADMIN') {
	$stages->execute([(int) $program['sequence_no']]);
}
$availableStages = $stages->fetchAll();
$next = $availableStages[0] ?? null;
$nextStageKey = (string) ($next['stage_key'] ?? '');
$preferredRoomTypes = workflow_stage_room_types($nextStageKey);
$roomOrderSql = $preferredRoomTypes
	? 'CASE WHEN room_type IN (' . implode(',', array_fill(0, count($preferredRoomTypes), '?')) . ') THEN 0 ELSE 1 END, name'
	: 'name';
$roomParams = $preferredRoomTypes;
if ($roomParams) {
	$rooms = db()->prepare(
		"SELECT id,name FROM rooms
		 WHERE status IN ('AVAILABLE','IN_USE','OCCUPIED')
		 ORDER BY {$roomOrderSql}"
	);
	$rooms->execute($roomParams);
} else {
	$rooms = db()->query(
		"SELECT id,name FROM rooms
		 WHERE status IN ('AVAILABLE','IN_USE','OCCUPIED')
		 ORDER BY {$roomOrderSql}"
	);
}
$rooms = $rooms->fetchAll();
$nextRole = workflow_stage_role((string) ($next['stage_key'] ?? ''));
$employeeQuery = 'SELECT e.id,e.full_name FROM employees e INNER JOIN users u ON u.employee_id=e.id WHERE e.active=1 AND u.active=1 AND u.role=? ORDER BY e.full_name';
$employeesStmt = db()->prepare($employeeQuery);
$employeesStmt->execute([$nextRole]);
$employees = $employeesStmt->fetchAll();
$roleLabels = [
	'JOURNALIST' => 'Journalist',
	'INGEST_OPERATOR' => 'Ingest operator',
	'PREVIEW_OPERATOR' => 'Preview operator',
	'EDITOR' => 'Editor',
	'GRAPHICS_OPERATOR' => 'Graphics operator',
	'AUDIO_OPERATOR' => 'Audio operator',
	'QC_OPERATOR' => 'Quality control operator',
	'MASTER_CONTROL_OPERATOR' => 'Master control operator',
	'UPLINK_OPERATOR' => 'Uplink operator',
];
$nextRoleLabel = $roleLabels[$nextRole] ?? $nextRole;
if (!$fragment) {
	require_once __DIR__ . '/includes/header.php';
}
?>

<section class="panel">
<h2><?= e($program['title'])
?>
</h2>
<p class="muted">
		Current stage: <?= e($program['stage_name'])
?>
.
		The workflow only permits the next configured stage.
	</p><?php 
if ($next):
?>

		<form method="post" action="api/transition.php" data-transition-form>
<input type="hidden" name="csrf" value="<?= e(csrf_token())
?>
">
<input type="hidden" name="program_id" value="<?= $id
?>
">
		<?php if (user()['role'] === 'ADMIN'): ?><input type="hidden" name="override" value="1">
		<label>Target stage<select name="next_stage_id" required><?php foreach ($availableStages as $availableStage): ?><option value="<?= (int) $availableStage['id'] ?>"><?= e($availableStage['name']) ?></option><?php endforeach; ?></select></label>
		<label>Override reason<textarea name="override_reason" rows="3" required></textarea></label><?php else: ?><input type="hidden" name="next_stage_id" value="<?= $next['id'] ?>"><?php endif; ?>
<label>
				Next stage
				<input value="<?= e($next['name'])
?>
" disabled>
</label>
<label>
				Room
				<select name="room_id" required>
					<option value="">Select room</option>
					<?php foreach ($rooms as $room): ?>
						<option value="<?= (int) $room['id'] ?>"><?= e($room['name']) ?></option>
					<?php endforeach; ?>
				</select>
</label>
<label>
				Assign next responsible employee (<?= e($nextRoleLabel) ?>)
				<select name="employee_id" required <?= !$employees ? 'disabled' : '' ?> aria-describedby="employee-help">
					<?php if ($employees): ?>
						<option value="">Select an active <?= e($nextRoleLabel) ?></option>
						<?php foreach ($employees as $employee): ?>
							<option value="<?= (int) $employee['id'] ?>"><?= e($employee['full_name']) ?></option>
						<?php endforeach; ?>
					<?php else: ?>
						<option value="">No eligible employee is available</option>
					<?php endif; ?>
				</select>
				<small id="employee-help" class="muted">
					<?php if ($employees): ?>Select the active employee who will receive this program for the next stage.
					<?php else: ?>No active user account is linked to an employee with the <?= e($nextRoleLabel) ?> role. An administrator must create or activate that account before this program can move forward.
					<?php endif; ?>
				</small>
</label>
<label>
				Notes
				<textarea name="notes" rows="4">
</textarea>
</label>
<button class="button primary">Confirm transition</button>
</form><?php 
else:
?>

		<div class="alert">
			This program has reached the final configured stage.
		</div><?php 
endif;
?>

</section><?php 
if (!$fragment) {
	require_once __DIR__ . '/includes/footer.php';
}
?>
