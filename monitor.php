<?php

$pageTitle = 'Live program monitor';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_admin();
require_once __DIR__ . '/includes/header.php';
?>

<section class="panel">
<div class="panel-header">
<div>
<h2>Active workflow locations</h2>
<p class="muted">Refreshes automatically every 15 seconds. Delays are highlighted when elapsed time exceeds the stage target.</p>
</div>
</div>
<div class="table-wrap" id="live-table" data-live="api/live_monitor.php">
<table>
<thead>
<tr>
<th>Program</th>
<th>Current stage</th>
<th>Room</th>
<th>Responsible</th>
<th>Started</th>
<th>Elapsed / expected</th>
<th>Progress</th>
<th>Status</th>
</tr>
<?php foreach (live_programs() as $program): ?>
					<tr>
<td>
<a href="program.php?id=<?= $program['id']
?>
">
<strong><?= e($program['title'])
?>
</strong>
</a>
<br>
<small class="muted"><?= e($program['program_code'])
?>
</small>
</td>
<td><?= e($program['stage_name'])
?>
</td>
<td><?= e($program['room_name'] ?: 'Not assigned')
?>
</td>
<td><?= e($program['responsible'] ?: 'Unassigned')
?>
</td>
<td><?= e(date('H:i', strtotime($program['current_started_at'])))
?>
</td>
<td class="<?= (int)$program['elapsed_minutes'] > (int)$program['expected_minutes'] ? 'delay-cell' : '' ?>"><?= minutes_label((int)$program['elapsed_minutes'])
?>
 / <?= minutes_label((int)$program['expected_minutes'])
?>
</td>
<td><?= (int)$program['progress']
?>
%</td>
<td>
<span class="badge <?= e($program['current_status']) ?><?= (int)$program['elapsed_minutes'] > (int)$program['expected_minutes'] ? ' delay-status' : '' ?>">
								<?= e($program['current_status'])
?>

							</span>
</td>
</tr><?php 
endforeach;
?>

			</tbody>
</table>
</div>
</section><?php 
require_once __DIR__ . '/includes/footer.php';
?>
