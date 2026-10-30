<?php

$pageTitle = 'Issues and delays';
$pageHelp = 'Report problems that slow a program and keep their resolution history visible.';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
$issueSql = 'SELECT i.*, p.title, s.name stage_name, e.full_name reporter
	FROM issues i
	JOIN programs p ON p.id = i.program_id
	LEFT JOIN workflow_stages s ON s.id = i.stage_id
	LEFT JOIN employees e ON e.id = i.reporter_id';
$issueParams = [];
if (user()['role'] === 'JOURNALIST') {
	$issueSql .= ' WHERE p.journalist_id = (SELECT employee_id FROM users WHERE id = ?)';
    $issueParams[] = user()['id'];
} elseif (user()['role'] !== 'ADMIN') {
	$issueSql .= ' WHERE i.reporter_id = (SELECT employee_id FROM users WHERE id = ?)';
	$issueParams[] = user()['id'];
}
$issueSql .= ' ORDER BY i.created_at DESC';
$issueQuery = db()->prepare($issueSql);
$issueQuery->execute($issueParams);
$issues = $issueQuery->fetchAll();
$issueSummary = ['open' => 0, 'active' => 0, 'resolved' => 0, 'total' => count($issues)];
foreach ($issues as $issueSummaryRow) {
	if ($issueSummaryRow['status'] === 'OPEN') {
		$issueSummary['open']++;
	} elseif (in_array($issueSummaryRow['status'], ['ASSIGNED', 'IN_PROGRESS'], true)) {
		$issueSummary['active']++;
	} elseif (in_array($issueSummaryRow['status'], ['RESOLVED', 'CLOSED'], true)) {
		$issueSummary['resolved']++;
	}
}
$programSql = 'SELECT id, title FROM programs';
$programParams = [];
if (user()['role'] === 'JOURNALIST') {
	$programSql .= ' WHERE journalist_id = (SELECT employee_id FROM users WHERE id = ?)';
    $programParams[] = user()['id'];
} elseif (user()['role'] !== 'ADMIN') {
	$programSql .= ' WHERE current_employee_id = (SELECT employee_id FROM users WHERE id = ?)';
	$programParams[] = user()['id'];
}
$programSql .= ' ORDER BY title';
$programQuery = db()->prepare($programSql);
$programQuery->execute($programParams);
$programs = $programQuery->fetchAll();
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<div class="issue-page-stack">
<section class="panel issue-management-summary">
<div class="panel-header">
<div>
<h2><?= user()['role'] === 'ADMIN' ? 'Problem management' : 'Report a problem' ?></h2>
<p class="muted"><?= user()['role'] === 'ADMIN' ? 'Review, assign, resolve, and close problems reported by employees.' : 'Add a clear problem description so the responsible team can act.' ?></p>
</div>
</div><?php if($error):
?>
<div class="alert error"><?= e($error)
?>
</div><?php 
endif;
?>
<?php if (user()['role'] !== 'ADMIN'): ?><form method="post" action="api/issue_create.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token())
?>
">
<label>Program<select name="program_id" required>
<option value="">Select program</option><?php 
foreach($programs as $p):
?>
<option value="<?= $p['id']
?>
"><?= e($p['title'])
?>
</option><?php 
endforeach;
?>
</select>
</label>
<label>Issue type<input name="issue_type" required placeholder="Technical problem">
</label>
<label>Priority<select name="priority">
<option>MEDIUM</option>
<option>LOW</option>
<option>HIGH</option>
<option>CRITICAL</option>
</select>
</label>
<label>Description<textarea name="description" rows="5" required>
</textarea>
</label>
<button class="button primary">Report issue</button>
</form>
<?php else: ?><div class="issue-summary-grid"><div><span><?= e(__('open_status')) ?></span><strong><?= $issueSummary['open'] ?></strong><small><?= e(__('needs_assignment')) ?></small></div><div><span><?= e(__('in_progress')) ?></span><strong><?= $issueSummary['active'] ?></strong><small><?= e(__('being_handled')) ?></small></div><div><span><?= e(__('resolved_closed')) ?></span><strong><?= $issueSummary['resolved'] ?></strong><small><?= e(__('completed_reports')) ?></small></div></div><?php endif; ?>
</section>
<section class="panel issue-register-panel">
<div class="panel-header">
<div>
<h2><?= e(__('issue_register')) ?></h2>
<p class="muted">Update the status after the team has investigated the problem.</p>
</div>
</div>
<div class="table-wrap">
<table><caption class="visually-hidden">Problem reports and administrator actions</caption>
<thead>
<tr>
<th scope="col">Issue</th>
<th scope="col">Program and stage</th>
<th scope="col">Reported by</th>
<th scope="col">Type</th>
<th scope="col">Priority</th>
<th scope="col">Status and action</th>
</tr>
</thead>
<tbody><?php 
foreach($issues as $issue):
?>
<tr data-issue-row="<?= (int) $issue['id'] ?>">
<td data-label="Issue"><?= e($issue['issue_code'])
?>
<br>
<small><?= e($issue['created_at'])
?>
</small>
</td>
<td data-label="Program and stage"><?= e($issue['title'])
?>
<br>
<small><?= e($issue['stage_name']?:'')
?>
</small>
</td>
<td data-label="Reported by"><?= e($issue['reporter'] ?: 'Employee') ?></td>
<td data-label="Type"><?= e($issue['issue_type'])
?>
</td>
<td data-label="Priority"><?= e($issue['priority'])
?>
</td>
<td data-label="Status and action" class="issue-actions-cell">
<span class="badge <?= e($issue['status'])
?>
"><?= e($issue['status'])
?>
</span>
<button class="button secondary" type="button" data-issue-edit data-issue-id="<?= (int) $issue['id'] ?>" data-issue-code="<?= e($issue['issue_code']) ?>" data-issue-status="<?= e($issue['status']) ?>" data-issue-resolution="<?= e($issue['resolution'] ?? '') ?>">Edit</button>
<form method="post" action="api/issue_update.php" class="inline-form">
<input type="hidden" name="csrf" value="<?= e(csrf_token())
?>
">
<input type="hidden" name="id" value="<?= $issue['id']
?>
">
<select name="status">
<option <?= $issue['status']==='OPEN'?'selected':''
?>
>OPEN</option>
<option <?= $issue['status']==='ASSIGNED'?'selected':''
?>
>ASSIGNED</option>
<option <?= $issue['status']==='IN_PROGRESS'?'selected':''
?>
>IN_PROGRESS</option>
<option <?= $issue['status']==='RESOLVED'?'selected':''
?>
>RESOLVED</option>
<option <?= $issue['status']==='CLOSED'?'selected':''
?>
>CLOSED</option>
</select>
<input name="resolution" placeholder="Resolution note">
<button class="button secondary">Save</button>
</form>
</td>
</tr><?php 
endforeach;
?>
</tbody>
</table>
</div>
</section>
</div><?php 
require_once __DIR__.'/includes/footer.php';
?>
