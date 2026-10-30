<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
$pageTitle = user()['role'] === 'JOURNALIST'
    ? 'My programs'
    : 'Operations dashboard';
$pageHelp = user()['role'] === 'JOURNALIST'
    ? 'See your programs, current stage, progress, and next step.'
    : 'A live view of today\'s programs, current locations, delays, and workflow progress.';
require_once __DIR__.'/includes/header.php';
if (user()['role'] === 'JOURNALIST'):
	$owner = db()->prepare('SELECT employee_id FROM users WHERE id = ?');
	$owner->execute([user()['id']]);
	$employeeId = (int)($owner->fetchColumn() ?: 0);
	$stmt = db()->prepare('SELECT p.*, s.name stage_name, s.sequence_no, s.icon stage_icon, r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id LEFT JOIN rooms r ON r.id = p.current_room_id WHERE p.journalist_id = ? ORDER BY p.updated_at DESC');
	$stmt->execute([$employeeId]);
	$myPrograms = $stmt->fetchAll();
	$workflowStages = db()->query('SELECT * FROM workflow_stages WHERE active = 1 ORDER BY sequence_no')->fetchAll();
	$activeCount = count(array_filter($myPrograms, static fn(array $program): bool => !in_array($program['current_status'], ['COMPLETED', 'CANCELLED'], true)));
	$completedCount = count(array_filter($myPrograms, static fn(array $program): bool => $program['current_status'] === 'COMPLETED'));
	$delayedCount = count(array_filter($myPrograms, static fn(array $program): bool => $program['current_status'] === 'DELAYED'));
	$recentPrograms = array_slice($myPrograms, 0, 4);
?>

<section class="journalist-welcome">
<div>
<p class="eyebrow">OBN PROGRAM WORKFLOW</p>
<h2>Welcome, <?= e(user()['name'])
?>
</h2>
<p class="muted">Follow each program from the journal to uplink.</p>
</div>
<div class="quick-actions">
<a class="button primary" href="program_new.php">Register a program</a>
<a class="button secondary" href="tracking.php">Where is my program?</a>
<a class="button secondary" href="issues.php">Report a problem</a>
</div>
</section>
<section class="stats-grid journalist-stats">
<div class="stat">
<span>My programs</span>
<strong><?= count($myPrograms)
?>
</strong>
<small class="muted">All registered programs</small>
</div>
<div class="stat">
<span>In workflow</span>
<strong><?= $activeCount
?>
</strong>
<small class="muted">Currently moving</small>
</div>
<div class="stat">
<span>Completed</span>
<strong><?= $completedCount
?>
</strong>
<small class="muted">Reached the end</small>
</div>
<div class="stat">
<span>Needs attention</span>
<strong><?= $delayedCount
?>
</strong>
<small class="muted"><?= e(__('delayed_programs')) ?></small>
</div>
</section>
<section class="panel journalist-flow">
<div class="panel-header">
<div>
<h2>Program workflow</h2>
<p class="muted">Every program follows this sequence from submission to uplink.</p>
</div>
 </div>
<div class="stage-track"><?php foreach ($workflowStages as $stage): ?>
<div class="stage">
<div class="stage-dot">
<i class="fa-solid <?= e($stage['icon'] ?? 'fa-circle')
?>
" aria-hidden="true">
</i>
</div>
<strong><?= e($stage['name'])
?>
</strong>
</div><?php endforeach; ?>
</div>
</section>
<section class="panel">
<div class="panel-header">
<div>
<h2>Recent program activity</h2>
<p class="muted">Your latest updates and current workflow state.</p>
</div>
</div>
<?php if (!$recentPrograms): ?>
<div class="empty-state compact-empty">
<strong>No recent activity</strong>
<p class="muted">Register a new program to begin tracking your workflow.</p>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Program</th>
<th>Current stage</th>
<th>Room</th>
<th><?= e(__('status')) ?></th>
<th><?= e(__('action')) ?></th>
</tr>
</thead>
<tbody><?php foreach ($recentPrograms as $program): ?>
<tr>
<td><a href="program.php?id=<?= (int) $program['id'] ?>"><strong><?= e($program['title']) ?></strong></a><br><small class="muted"><?= e($program['program_code']) ?></small></td>
<td><?= e($program['stage_name'] ?: 'Journal') ?></td>
<td><?= e($program['room_name'] ?: 'Not assigned') ?></td>
<td><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_', ' ', $program['current_status'])) ?></span></td>
<td><a class="button secondary" href="program.php?id=<?= (int) $program['id'] ?>">View</a></td>
</tr><?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
<section class="panel">
<div class="panel-header">
<div>
<h2>My programs</h2>
<p class="muted">Select a program to see its complete history and status.</p>
</div>
</div><?php 
if (!$myPrograms):
?>
<div class="empty-state">
<h2>No programs registered</h2>
<p class="muted">Register your first program to start tracking its workflow.</p>
<a class="button primary" href="program_new.php">Register a program</a>
</div><?php 
else:
?>
<div class="journalist-program-grid"><?php 
foreach ($myPrograms as $program):
?>
<article class="journalist-program-card">
<div class="program-card-top">
<div>
<p class="eyebrow"><?= e($program['program_code'])
?>
</p>
<h3><?= e($program['title'])
?>
</h3>
</div>
<span class="badge <?= e($program['current_status'])
?>
"><?= e(str_replace('_', ' ', $program['current_status']))
?>
</span>
</div>
<div class="program-current-stage">
<span>Current stage</span>
<strong><?= e($program['stage_name'] ?: 'Program Journal')
?>
</strong>
<small><?= e($program['room_name'] ?: 'Location not assigned')
?>
</small>
</div>
<div class="progress">
<span style="width:<?= (int)$program['progress']
?>
%">
</span>
</div>
<div class="program-card-meta">
<span><?= (int)$program['progress']
?>
% complete</span>
<span><?= $program['planned_broadcast'] ? e(date('M d, H:i', strtotime($program['planned_broadcast']))) : 'Broadcast not scheduled'
?>
</span>
</div>
<a class="button secondary" href="program.php?id=<?= (int)$program['id']
?>
">View workflow</a>
</article><?php 
endforeach;
?>
</div><?php 
endif;
?>
</section><?php 
require_once __DIR__.'/includes/footer.php';
exit;
endif;
if (user()['role'] !== 'ADMIN'):
$employeeUser = db()->prepare('SELECT employee_id FROM users WHERE id = ?');
$employeeUser->execute([user()['id']]);
$employeeId = (int) ($employeeUser->fetchColumn() ?: 0);
$operatorStageKeys = workflow_stage_keys_for_role((string) user()['role']);
$operatorStageSql = implode(',', array_fill(0, count($operatorStageKeys), '?'));

// My Work Query
$workQuery = db()->prepare("SELECT p.*, s.name stage_name, r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id LEFT JOIN rooms r ON r.id = p.current_room_id WHERE p.current_employee_id = ? AND s.stage_key IN ({$operatorStageSql}) AND p.current_status != 'COMPLETED' ORDER BY p.updated_at DESC");
$workQuery->execute(array_merge([$employeeId], $operatorStageKeys));
$myWork = $workQuery->fetchAll();

// Available Pool Query
$poolQuery = db()->prepare("SELECT p.*, s.name stage_name, r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id = p.current_stage_id LEFT JOIN rooms r ON r.id = p.current_room_id WHERE (p.current_employee_id IS NULL OR p.current_employee_id != ?) AND p.current_status IN ('WAITING', 'DELAYED') AND s.stage_key IN ({$operatorStageSql}) ORDER BY p.updated_at ASC");
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
<?php require_once __DIR__.'/includes/footer.php'; exit; endif; ?>
<?php
$stats=dashboard_stats();
$live=live_programs();
$stages=db()->query('SELECT * FROM workflow_stages WHERE active=1 ORDER BY sequence_no')->fetchAll();
$boardPrograms=db()->query('SELECT p.id,p.title,p.program_code,p.current_status,p.progress,p.current_stage_id,s.name stage_name,s.sequence_no,r.name room_name FROM programs p LEFT JOIN workflow_stages s ON s.id=p.current_stage_id LEFT JOIN rooms r ON r.id=p.current_room_id WHERE p.current_status NOT IN (\'COMPLETED\',\'CANCELLED\') ORDER BY p.updated_at DESC')->fetchAll();
$ingestStageId = (int) db()->query("SELECT id FROM workflow_stages WHERE stage_key = 'INGEST' LIMIT 1")->fetchColumn();
$uplinkStageId = (int) db()->query("SELECT id FROM workflow_stages WHERE stage_key = 'UPLINK' LIMIT 1")->fetchColumn();
$cards=[['Total programs',$stats['total'],'All registered programs','programs.php'],['In progress',$stats['active'],'Waiting or active workflow','programs.php?status=IN_PROGRESS'],['Blocked / delayed',$stats['delayed'],'Needs management attention','programs.php?status=BLOCKED'],['Completed today',$stats['completed_today'],'Finished today','programs.php?status=COMPLETED'],['Waiting for ingest',$stats['ingest'],'Current stage','programs.php?stage='.$ingestStageId],['At uplink',$stats['uplink'],'Final operational stage','programs.php?stage='.$uplinkStageId]];
$problemQuery = db()->query("SELECT i.id, i.issue_code, i.issue_type, i.priority, i.status, i.created_at, p.id program_id, p.title program_title, s.name stage_name, e.full_name reporter FROM issues i INNER JOIN programs p ON p.id = i.program_id LEFT JOIN workflow_stages s ON s.id = i.stage_id LEFT JOIN employees e ON e.id = i.reporter_id WHERE i.status NOT IN ('RESOLVED', 'CLOSED') ORDER BY FIELD(i.priority, 'CRITICAL', 'HIGH', 'MEDIUM', 'LOW'), i.created_at DESC LIMIT 6");
$openProblems = $problemQuery->fetchAll();

// Prepare data for the Native HTML5 Canvas Dashboard
$chartData = [];
foreach ($stages as $stage) {
    $count = 0;
    foreach ($boardPrograms as $program) {
        if ((int)$program['current_stage_id'] === (int)$stage['id']) {
            $count++;
        }
    }
    $chartData[] = [
        'label' => $stage['name'],
        'value' => $count
    ];
}
?>
<section class="dashboard-intro">
<div>
<p class="eyebrow">OBN PROGRAM OPERATIONS</p>
<h2>Good morning, <?= e(user()['name'])
?>
</h2>
<p class="muted">Use this page to see what needs attention now. Select a program for its full history.</p>
</div>
<div class="quick-actions">
<a class="button secondary" href="monitor.php">Monitor operations</a>
<a class="button secondary" href="issues.php">Manage problems</a>
</div>
</section>
<section class="stats-grid"><?php 
foreach($cards as $card):
?>
<a class="stat" data-dashboard-filter="<?= e($card[0]) ?>" href="<?= e($card[3])
?>
">
<span><?= e($card[0])
?>
</span>
<strong><?= e((string)$card[1])
?>
</strong>
<small class="muted"><?= e($card[2])
?>
</small>
</a><?php 
endforeach;
?>
</section>
<section class="panel">
<div class="panel-header">
<div>
<h2>Workflow Load Analysis</h2>
<p class="muted"><?= e(__('active_programs')) ?> currently waiting or in progress at each stage.</p>
</div>
</div>
<div style="width: 100%; height: 260px; position: relative;">
<canvas id="workflowCanvas"></canvas>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('workflowCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Data from PHP
    const rawData = <?= json_encode($chartData) ?>;
    
    // Convert to simple arrays for drawing
    const labels = rawData.map(d => d.label);
    const values = rawData.map(d => parseInt(d.value, 10));
    
    // Responsive drawing function
    function drawChart() {
        // Resize canvas to match parent
        canvas.width = canvas.parentElement.clientWidth;
        canvas.height = canvas.parentElement.clientHeight;
        
        const width = canvas.width;
        const height = canvas.height;
        
        ctx.clearRect(0, 0, width, height);
        
        const padding = { top: 30, right: 20, bottom: 40, left: 40 };
        const chartWidth = width - padding.left - padding.right;
        const chartHeight = height - padding.top - padding.bottom;
        
        let maxVal = Math.max(...values, 5); // at least 5 for scale
        // ensure maxVal is divisible by 5 for cleaner labels
        maxVal = Math.ceil(maxVal / 5) * 5;
        
        // Draw grid and Y axis
        ctx.beginPath();
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        
        const ySteps = 5;
        for (let i = 0; i <= ySteps; i++) {
            const y = padding.top + chartHeight - (i / ySteps) * chartHeight;
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            
            // Y-axis labels
            ctx.fillStyle = '#64748b';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillText(Math.round((maxVal / ySteps) * i), padding.left - 10, y);
        }
        ctx.stroke();
        
        // Draw bars
        const barWidth = Math.min((chartWidth / values.length) * 0.6, 60);
        const spacing = (chartWidth - (barWidth * values.length)) / (values.length + 1);
        
        for (let i = 0; i < values.length; i++) {
            const val = values[i];
            const barHeight = (val / maxVal) * chartHeight;
            const x = padding.left + spacing + (i * (barWidth + spacing));
            const y = padding.top + chartHeight - barHeight;
            
            // Bar gradient
            const grad = ctx.createLinearGradient(0, y, 0, y + barHeight);
            grad.addColorStop(0, '#ea6228'); // orange
            grad.addColorStop(1, '#d15622'); // orange-hover
            
            ctx.fillStyle = grad;
            // Draw bar with slight border radius at top
            ctx.beginPath();
            ctx.roundRect(x, y, barWidth, Math.max(barHeight, 2), [4, 4, 0, 0]);
            ctx.fill();
            
            // X-axis label (truncate if too long)
            ctx.fillStyle = '#64748b';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            let label = labels[i];
            if (chartWidth / values.length < 60 && label.length > 8) {
                 label = label.substring(0, 6) + '..';
            }
            ctx.fillText(label, x + (barWidth / 2), padding.top + chartHeight + 10);
            
            // Value above bar
            if (val > 0) {
                ctx.fillStyle = '#1e293b';
                ctx.fillText(val, x + (barWidth / 2), y - 15);
            }
        }
    }
    
    // Initial draw
    drawChart();
    
    // Smart Upgrade: Make canvas responsive
    window.addEventListener('resize', drawChart);
});
</script>
</section>
<section class="panel admin-problems" aria-labelledby="admin-problems-title">
<div class="panel-header"><div><h2 id="admin-problems-title"><?= e(__('problems_attention')) ?></h2><p class="muted"><?= e(__('problems_attention_desc')) ?></p></div><a class="button secondary" href="issues.php"><?= e(__('manage_problems')) ?></a></div>
<?php if (!$openProblems): ?><div class="empty-state compact-empty"><strong><?= e(__('no_open_problems')) ?></strong><p class="muted"><?= e(__('no_open_problems_desc')) ?></p></div><?php else: ?><div class="problem-list"><?php foreach ($openProblems as $problem): ?><a class="problem-row" href="issues.php"><span class="problem-priority <?= e(strtolower($problem['priority'])) ?>" aria-label="<?= e($problem['priority']) ?> priority"></span><span class="problem-main"><strong><?= e($problem['issue_type']) ?></strong><small><?= e($problem['program_title']) ?> · <?= e($problem['stage_name'] ?: 'Current stage') ?></small></span><span class="problem-meta"><strong><?= e($problem['status']) ?></strong><small><?= e($problem['reporter'] ?: 'Employee') ?></small></span></a><?php endforeach; ?></div><?php endif; ?>
</section>
<section class="panel dashboard-workflow" data-dashboard-board aria-labelledby="workflow-board-title">
<div class="panel-header">
<div><h2 id="workflow-board-title">Priority workflow queue</h2><p class="muted">Monitor each program's current stage, owner, status, and next responsibility.</p></div>
<div class="view-toggle" role="group" aria-label="Workflow view"><button class="button secondary is-active" type="button" data-dashboard-view="table">Table</button><button class="button secondary" type="button" data-dashboard-view="kanban">Kanban</button></div>
</div>
<div class="queue-filters" role="group" aria-label="Filter workflow queue"><span class="queue-filter-label">Show:</span><button class="button secondary is-active" type="button" data-queue-filter="ALL" aria-pressed="true">All</button><button class="button secondary" type="button" data-queue-filter="WAITING" aria-pressed="false">Waiting</button><button class="button secondary" type="button" data-queue-filter="IN_PROGRESS" aria-pressed="false">In progress</button><button class="button secondary" type="button" data-queue-filter="BLOCKED" aria-pressed="false">Blocked</button></div>
<div class="dashboard-table-view" data-dashboard-panel="table">
<div class="table-wrap"><table><thead><tr><th>Program</th><th><?= e(__('stage')) ?></th><th><?= e(__('status')) ?></th><th>Progress</th><th><?= e(__('action')) ?></th></tr></thead><tbody data-dashboard-rows><?php foreach ($boardPrograms as $program): ?><tr data-program-status="<?= e($program['current_status']) ?>"><td><a data-detail-link href="program.php?id=<?= (int) $program['id'] ?>"><strong><?= e($program['title']) ?></strong></a><br><small class="muted"><?= e($program['program_code']) ?></small></td><td><?= e($program['stage_name'] ?: 'Program Journal') ?></td><td><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_',' ',$program['current_status'])) ?></span></td><td><?= (int) $program['progress'] ?>%</td><td><a class="button secondary" href="transition.php?id=<?= (int) $program['id'] ?>" data-transition-link>Advance</a></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<div class="kanban-board" data-dashboard-panel="kanban" hidden><?php foreach ($stages as $stage): ?><div class="kanban-column" data-stage-sequence="<?= (int) $stage['sequence_no'] ?>"><h3><?= e($stage['name']) ?></h3><div class="kanban-cards"><?php foreach ($boardPrograms as $program): if ((int) $program['current_stage_id'] === (int) $stage['id']): ?><article class="kanban-card" draggable="true" data-program-id="<?= (int) $program['id'] ?>" data-sequence="<?= (int) $stage['sequence_no'] ?>" data-program-status="<?= e($program['current_status']) ?>"><a data-detail-link href="program.php?id=<?= (int) $program['id'] ?>"><strong><?= e($program['title']) ?></strong></a><small><?= e($program['program_code']) ?></small><span class="badge <?= e($program['current_status']) ?>"><?= e(str_replace('_',' ',$program['current_status'])) ?></span><a class="button secondary" href="transition.php?id=<?= (int) $program['id'] ?>" data-transition-link>Open transition</a></article><?php endif; endforeach; ?></div></div><?php endforeach; ?></div>
</section>
<section class="panel">
<div class="panel-header">
<div>
<h2>Live program operations</h2>
<p class="muted"><?= e(__('active_programs')) ?> and their current physical or operational location.</p>
</div>
<a class="button secondary" href="monitor.php">Open monitor</a>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Program</th>
<th><?= e(__('stage')) ?></th>
<th>Room</th>
<th>Responsible staff</th>
<th>Elapsed</th>
<th>Progress</th>
<th><?= e(__('status')) ?></th>
<th><?= e(__('action')) ?></th>
</tr>
</thead>
<tbody><?php 
foreach($live as $program):
?>
<tr>
<td>
<strong><?= e($program['title'])
?>
</strong>
<br>
<small class="muted"><?= e($program['program_code'])
?>
</small>
</td>
<td><?= e($program['stage_name'])
?>
</td>
<td><?= e($program['room_name']?:'Not assigned')
?>
</td>
<td><?= e($program['responsible']?:'Not assigned')
?>
</td>
<td><?= minutes_label((int)$program['elapsed_minutes'])
?>
</td>
<td>
<div class="progress">
<span style="width:<?= (int)$program['progress']
?>
%">
</span>
</div><?= (int)$program['progress']
?>
%</td>
<td>
<span class="badge <?= e($program['current_status'])
?>
"><?= e(str_replace('_',' ',$program['current_status']))
?>
</span>
</td>
<td>
<a class="button secondary" href="program.php?id=<?= $program['id']
?>
">View</a>
</td>
</tr><?php 
endforeach;
?>
</tbody>
</table>
</div>
</section>
<div class="live-grid">
<section class="panel">
<div class="panel-header">
<div>
<h2>Program workflow</h2>
<p class="muted">The configured path from journal to uplink.</p>
</div>
<a href="tracking.php">Track a program</a>
</div>
<div class="stage-track"><?php 
$stages=db()->query('SELECT * FROM workflow_stages WHERE active=1 ORDER BY sequence_no')->fetchAll();
foreach($stages as $stage):
?>
<div class="stage">
<div class="stage-dot">
</div>
<small><?= e($stage['name'])
?>
</small>
</div><?php 
endforeach;
?>
</div>
</section>
<section class="panel">
<div class="panel-header">
<div>
<h2>Need a report?</h2>
<p class="muted">Review delays, stage times, and program volume.</p>
</div>
<a class="button primary" href="reports.php">Open reports</a>
</div>
<p class="muted">Performance scores are calculated from real recorded stage durations and delays.</p>
</section>
</div><?php 
require_once __DIR__.'/includes/footer.php';
?>
