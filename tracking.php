<?php

$pageTitle = 'Where is my program?';
$pageHelp = 'Search a program to see its current stage, room, owner, and complete journey.';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
$query = trim($_GET['q'] ?? '');
$programs = [];
if ($query) {
    $programs = user()['role'] === 'JOURNALIST'
        ? journalist_programs((int) user()['id'], $query)
        : (user()['role'] === 'ADMIN' ? all_programs($query) : operator_programs((int) user()['id'], $query));
}
$selected = $programs[0] ?? null;
$history = [];
$stages = db()->query('SELECT * FROM workflow_stages WHERE active=1 ORDER BY sequence_no')->fetchAll();
if ($selected) {
    $historyQuery = db()->prepare('SELECT h.*,s.name stage_name,r.name room_name,e.full_name employee_name FROM program_stage_history h JOIN workflow_stages s ON s.id=h.stage_id LEFT JOIN rooms r ON r.id=h.room_id LEFT JOIN employees e ON e.id=h.employee_id WHERE h.program_id=? ORDER BY h.actual_start');
    $historyQuery->execute([$selected['id']]);
    $history = $historyQuery->fetchAll();
    }
?>
<section class="panel tracking-search">
<form method="get" data-tracking-form>
<label>Program ID or program name<input name="q" data-tracking-search value="<?= e($query)
?>
" placeholder="Example: Morning News or OBN-PRG-2026-00001" autofocus>
</label>
<button class="button primary">Find program</button>
</form>
</section><?php 
if($query && !$selected):
?>
<section class="panel empty-state">
<h2>No program found</h2>
<p class="muted">Try the program ID or name again.</p>
</section><?php 
endif;
?><?php 
if($selected):
?>
<section class="panel location-hero">
<div>
<p class="eyebrow"><?= e($selected['program_code'])
?>
</p>
<h2><?= e($selected['title'])
?>
</h2>
<p class="muted">Current location: <strong><?= e($selected['room_name']?:'Not assigned')
?>
</strong>
</p>
</div>
<span class="badge <?= e($selected['current_status'])
?>
"><?= e(str_replace('_',' ',$selected['current_status']))
?>
</span>
<div class="location-grid">
<div>
<span>Current stage</span>
<strong><?= e($selected['stage_name']?:'Journal')
?>
</strong>
</div>
<div>
<span>Responsible staff</span>
<strong><?= e($selected['responsible']?:'Not assigned')
?>
</strong>
</div>
<div>
<span>Started</span>
<strong><?= $selected['current_started_at']?e(date('H:i',strtotime($selected['current_started_at']))):'-'
?>
</strong>
</div>
<div>
<span>Progress</span>
<strong><?= (int)$selected['progress']
?>
%</strong>
</div>
</div>
</section>
<section class="panel">
<h2>Program journey</h2>
<div class="stage-track tracking-track"><?php 
foreach($stages as $stage): $historyRow=null;
foreach($history as $item){
    if((int)$item['stage_id']===(int)$stage['id']){
        $historyRow=$item;
        break;
        }
    }
$isCurrent=(int)$selected['current_stage_id']===(int)$stage['id'];
?>
<div class="stage <?= $historyRow&&$historyRow['actual_end']?'done':($isCurrent?'current':'')
?>
">
<div class="stage-dot">
</div>
<small><?= e($stage['name'])
?>
</small><?php 
if($historyRow):
?>
<em><?= e($historyRow['room_name']?:'')
?>
</em><?php 
endif;
?>
</div><?php 
endforeach;
?>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Stage</th>
<th>Room</th>
<th>Staff</th>
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
<td><?= e($row['room_name']?:'-')
?>
</td>
<td><?= e($row['employee_name']?:'-')
?>
</td>
<td><?= e(date('M d H:i',strtotime($row['actual_start'])))
?>
</td>
<td><?= $row['actual_end']?e(date('M d H:i',strtotime($row['actual_end']))):'Current'
?>
</td>
<td><?= $row['actual_minutes']?minutes_label((int)$row['actual_minutes']):'Running'
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
endif;
?><?php 
require_once __DIR__ . '/includes/footer.php';
?>
