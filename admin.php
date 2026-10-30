<?php

$pageTitle = 'Administration';
$pageHelp = 'Manage the people, rooms, categories, and workflow rules used by OBN operations.';
require_once __DIR__ . '/app/auth/auth_check.php';
require_admin();
require_once __DIR__ . '/includes/functions.php';
$message = $_SESSION['admin_message'] ?? null;
$error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_message'], $_SESSION['admin_error']);
$departments = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$types = db()->query('SELECT * FROM program_types WHERE active=1 ORDER BY name')->fetchAll();
$rooms = db()->query(
'SELECT r.*, d.name department_name
	 FROM rooms r
	 LEFT JOIN departments d ON d.id = r.department_id
	 WHERE r.active=1
	 ORDER BY r.name'
)->fetchAll();
$stages = db()->query(
'SELECT * FROM workflow_stages WHERE active=1 ORDER BY sequence_no'
)->fetchAll();
$employees = db()->query(
'SELECT id, full_name FROM employees WHERE active=1 ORDER BY full_name'
)->fetchAll();
$users = db()->query(
'SELECT u.username, u.role, u.active, e.full_name
	 FROM users u
	 LEFT JOIN employees e ON e.id = u.employee_id
	 ORDER BY u.username'
)->fetchAll();
$resetRequests = db()->query("SELECT r.id, r.requested_at, u.username, e.full_name FROM password_reset_requests r INNER JOIN users u ON u.id = r.user_id LEFT JOIN employees e ON e.id = u.employee_id WHERE r.status = 'PENDING' ORDER BY r.requested_at DESC")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-alerts"><?php 
if($message):
?>
<div class="alert success"><?= e($message)
?>
</div><?php 
endif;
?><?php 
if($error):
?>
<div class="alert error"><?= e($error)
?>
</div><?php 
endif;
?>
</section>
<div class="admin-tabs">
    <button type="button" class="tab-button active" onclick="openTab(event, 'tab-org')"><i class="fa-solid fa-sitemap"></i> Organization</button>
    <button type="button" class="tab-button" onclick="openTab(event, 'tab-rooms')"><i class="fa-solid fa-door-open"></i> Rooms</button>
    <button type="button" class="tab-button" onclick="openTab(event, 'tab-workflow')"><i class="fa-solid fa-route"></i> Workflow Stages</button>
    <button type="button" class="tab-button" onclick="openTab(event, 'tab-users')"><i class="fa-solid fa-users-gear"></i> Users & Access</button>
</div>

<div id="tab-org" class="admin-tab-content" style="display: block;">
<div class="admin-grid">
<section class="panel">
<h2><?= e(__('add_department')) ?></h2>
<form method="post" action="api/admin_save.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="department">
<label>Department name<input name="name" required></label>
<button class="button primary"><?= e(__('add_department')) ?></button>
</form>
<hr>
<h2><?= e(__('departments')) ?></h2><?php foreach($departments as $row): ?>
<div class="admin-row">
<span><?= e($row['name']) ?></span>
<form method="post" action="api/admin_save.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="department">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<button class="link-danger"><?= e(__('remove')) ?></button>
</form>
</div><?php endforeach; ?>
</section>
<section class="panel">
<h2><?= e(__('add_program_type')) ?></h2>
<form method="post" action="api/admin_save.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="type">
<label>Type name<input name="name" required></label>
<button class="button primary">Add type</button>
</form>
<hr>
<h2><?= e(__('program_types')) ?></h2><?php foreach($types as $row): ?>
<div class="admin-row">
<span><?= e($row['name']) ?></span>
<form method="post" action="api/admin_save.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="type">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<button class="link-danger"><?= e(__('remove')) ?></button>
</form>
</div><?php endforeach; ?>
</section>
</div>
</div>
<div id="tab-rooms" class="admin-tab-content" style="display: none;">
<section class="panel">
<div class="panel-header">
<div>
<h2><?= e(__('add_room')) ?></h2>
<p class="muted">Rooms show where a program is physically being handled.</p>
</div>
</div>
<form method="post" action="api/admin_save.php" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="room">
<label>Room code<input name="code" placeholder="EDIT-03" required></label>
<label>Room name<input name="name" required></label>
<label>Room type<select name="room_type" required>
<option value="">Select type</option>
<option value="EDITING">EDITING</option>
<option value="STUDIO">STUDIO</option>
<option value="AUDIO">AUDIO</option>
<option value="GRAPHICS">GRAPHICS</option>
<option value="MASTER_CONTROL">MASTER CONTROL</option>
<option value="SERVER_ROOM">SERVER ROOM</option>
</select></label>
<label>Building<input name="building"></label>
<label>Floor<input name="floor"></label>
<label>Capacity<input type="number" name="capacity" min="0"></label>
<label>Department<select name="department_id">
<option value="">Select department</option><?php foreach($departments as $department): ?>
<option value="<?= $department['id'] ?>"><?= e($department['name']) ?></option><?php endforeach; ?>
</select></label>
<label>Description<textarea name="description" rows="2"></textarea></label>
<button class="button primary"><?= e(__('add_room')) ?></button>
</form>
<div class="table-wrap">
<table>
<thead><tr><th>Room</th><th>Type</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
<tbody><?php foreach($rooms as $room): ?>
<tr>
<td><?= e($room['name']) ?><br><small><?= e($room['code']) ?></small></td>
<td><?= e($room['room_type']) ?></td>
<td><?= e($room['department_name']?:'-') ?></td>
<td><?= e($room['status']) ?></td>
<td>
<form method="post" action="api/admin_save.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="room">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $room['id'] ?>">
<button class="link-danger"><?= e(__('remove')) ?></button>
</form>
</td>
</tr><?php endforeach; ?>
</tbody>
</table>
</div>
</section>
</div>
<div id="tab-workflow" class="admin-tab-content" style="display: none;">
<section class="panel">
<h2>Add workflow stage</h2>
<form method="post" action="api/admin_save.php" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="stage">
<label>Stage key<select name="stage_key" required>
<option value="">Select stage key</option>
<option value="JOURNALIST_SUBMISSION">JOURNALIST_SUBMISSION</option>
<option value="INGEST">INGEST</option>
<option value="PREVIEW">PREVIEW</option>
<option value="EDITING">EDITING</option>
<option value="GRAPHICS">GRAPHICS</option>
<option value="AUDIO">AUDIO</option>
<option value="QC">QC</option>
<option value="MASTER_CONTROL">MASTER_CONTROL</option>
<option value="UPLINK">UPLINK</option>
<option value="ARCHIVE">ARCHIVE</option>
</select></label>
<label>Stage name<input name="name" required></label>
<label>Order number<input type="number" name="sequence_no" min="1" required></label>
<label>Target minutes<input type="number" name="expected_minutes" min="1" value="30" required></label>
<button class="button primary" style="grid-column: 1 / -1;"><?= e(__('add_stage')) ?></button>
</form>
<h2>Workflow order</h2>
<div class="workflow-pipeline">
<?php foreach($stages as $stage): ?>
    <div class="pipeline-node">
        <div class="node-badge"><?= (int)$stage['sequence_no'] ?></div>
        <div class="node-content">
            <strong><?= e($stage['name']) ?></strong>
            <small><i class="fa-regular fa-clock"></i> <?= (int)$stage['expected_minutes'] ?> min</small>
        </div>
        <form method="post" action="api/admin_save.php" class="node-actions">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="entity" value="stage">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $stage['id'] ?>">
            <button class="node-delete" title="Remove stage"><i class="fa-solid fa-xmark"></i></button>
        </form>
    </div>
    <?php if ($stage !== end($stages)): ?>
        <div class="pipeline-connector"><i class="fa-solid fa-arrow-right"></i></div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
</section>
</div>

<div id="tab-users" class="admin-tab-content" style="display: none;">
<section class="panel">
<h2><?= e(__('add_user')) ?></h2>
<form method="post" action="api/admin_save.php" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="entity" value="user">
<label>Username<input name="username" required></label>
<label>Password<input type="password" id="user-password" name="password" minlength="8" required>
<small class="muted">Default generated by role.</small>
</label>
<label>Employee<select name="employee_id">
<option value="">Unlinked account</option><?php foreach($employees as $employee): ?>
<option value="<?= $employee['id'] ?>"><?= e($employee['full_name']) ?></option><?php endforeach; ?>
</select></label>
<label>Role<select name="role" data-role-passwords='{"JOURNALIST":"journalist123","INGEST_OPERATOR":"operator123","PREVIEW_OPERATOR":"operator123","EDITOR":"operator123","GRAPHICS_OPERATOR":"operator123","AUDIO_OPERATOR":"operator123","QC_OPERATOR":"operator123","MASTER_CONTROL_OPERATOR":"operator123","UPLINK_OPERATOR":"operator123"}'>
<option value="JOURNALIST">JOURNALIST</option>
<option value="INGEST_OPERATOR">INGEST OPERATOR</option>
<option value="PREVIEW_OPERATOR">PREVIEW OPERATOR</option>
<option value="EDITOR">EDITOR</option>
<option value="GRAPHICS_OPERATOR">GRAPHICS OPERATOR</option>
<option value="AUDIO_OPERATOR">AUDIO OPERATOR</option>
<option value="QC_OPERATOR">QUALITY CONTROL</option>
<option value="MASTER_CONTROL_OPERATOR">MASTER CONTROL</option>
<option value="UPLINK_OPERATOR">UPLINK OPERATOR</option>
<option value="ADMIN">ADMIN</option>
</select></label>
<button class="button primary" style="grid-column: 1 / -1;"><?= e(__('add_user')) ?></button>
</form>
<h2>Users</h2><?php foreach($users as $account): ?>
<div class="admin-row">
<span><?= e($account['username']) ?></span>
<small><?= e($account['role']) ?></small>
</div><?php endforeach; ?>
</section>
</div>

<script>
function openTab(evt, tabId) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
</script><?php 
if ($resetRequests): ?>
<section class="panel"><div class="panel-header"><div><h2>Password reset requests</h2><p class="muted">Review requests from staff and record the action taken.</p></div></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Requested</th><th>Action</th></tr></thead><tbody><?php foreach ($resetRequests as $request): ?><tr><td><?= e($request['full_name'] ?: $request['username']) ?><br><small><?= e($request['username']) ?></small></td><td><?= e($request['requested_at']) ?></td><td><form method="post" action="api/password-reset/action.php" data-reset-action><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$request['id'] ?>"><button class="button secondary" name="status" value="ACTIONED">Mark actioned</button><button class="link-danger" name="status" value="DISMISSED">Dismiss</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endif;
require_once __DIR__.'/includes/footer.php';
?>
