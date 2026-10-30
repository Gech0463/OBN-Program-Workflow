<?php
$pageTitle = 'Program details';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT p.*, s.name stage_name, s.sequence_no, r.name room_name,
            e.full_name responsible, pt.name type_name, d.name department_name
     FROM programs p
     LEFT JOIN workflow_stages s ON s.id = p.current_stage_id
     LEFT JOIN rooms r ON r.id = p.current_room_id
     LEFT JOIN employees e ON e.id = p.current_employee_id
     LEFT JOIN program_types pt ON pt.id = p.program_type_id
     LEFT JOIN departments d ON d.id = p.department_id
     WHERE p.id = ?'
);
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
$history = db()->prepare(
    'SELECT h.*, s.name stage_name, r.name room_name, e.full_name employee_name
     FROM program_stage_history h
     JOIN workflow_stages s ON s.id = h.stage_id
     LEFT JOIN rooms r ON r.id = h.room_id
     LEFT JOIN employees e ON e.id = h.employee_id
     WHERE h.program_id = ?
     ORDER BY h.actual_start'
);
$history->execute([$id]);
?>
<section class="panel">
<div class="panel-header">
<div>
<p class="eyebrow"><?= e($program['program_code'])
?>
</p>
<h2><?= e($program['title'])
?>
</h2>
<p class="muted"><?= e($program['description'])
?>
</p>
</div>
<span class="badge <?= e($program['current_status'])
?>
"><?= e(str_replace('_',' ',$program['current_status']))
?>
</span>
</div>
<div class="stats-grid">
<div class="stat">
<span>Current location</span>
<strong><?= e($program['room_name']?:'Unassigned')
?>
</strong>
<small><?= e($program['stage_name']?:'Initiation')
?>
</small>
</div>
<div class="stat">
<span>Responsible</span>
<strong><?= e($program['responsible']?:'Unassigned')
?>
</strong>
<small><?= e($program['department_name'])
?>
</small>
</div>
<div class="stat">
<span>Progress</span>
<strong><?= (int)$program['progress']
?>
%</strong>
<div class="progress">
<span style="width:<?= (int)$program['progress']
?>
%">
</span>
</div>
</div>
<div class="stat">
<span>Broadcast</span>
<strong><?= $program['planned_broadcast']?e(date('M d',strtotime($program['planned_broadcast']))):'-'
?>
</strong>
<small><?= $program['planned_broadcast']?e(date('H:i',strtotime($program['planned_broadcast']))):''
?>
</small>
</div>
</div>
</section>
<section class="panel">
<div class="panel-header">
<h2>Stage history</h2>
<?php if ($program['current_status'] !== 'BLOCKED' && !in_array($program['current_status'], ['COMPLETED', 'CANCELLED'], true)): ?><a class="button secondary" href="transition.php?id=<?= $id
?>
">Advance stage</a><?php endif; ?>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Stage</th>
<th>Status</th>
<th>Room</th>
<th>Owner</th>
<th>Started</th>
<th>Ended</th>
<th>Duration</th>
<th>Delay</th>
</tr>
</thead>
<tbody><?php 
foreach($history as $row):
?>
<tr>
<td><?= e($row['stage_name'])
?>
</td>
<td>
<span class="badge <?= e($row['status'])
?>
"><?= e($row['status'])
?>
</span>
</td>
<td><?= e($row['room_name'])
?>
</td>
<td><?= e($row['employee_name'])
?>
</td>
<td><?= e(date('M d H:i',strtotime($row['actual_start'])))
?>
</td>
<td><?= $row['actual_end']?e(date('M d H:i',strtotime($row['actual_end']))):'Active'
?>
</td>
<td><?= $row['actual_minutes']?minutes_label((int)$row['actual_minutes']):'-'
?>
</td>
<td><?= (int)$row['delay_minutes']
?>
m</td>
</tr><?php 
endforeach;
?>
</tbody>
</table>
</div>
</section><?php 
require_once __DIR__.'/includes/footer.php';
?>
