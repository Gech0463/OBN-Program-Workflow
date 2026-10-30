<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
?>

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
</thead>
<tbody><?php 
foreach (live_programs() as $program):
?>

			<tr>

			<td class="<?= (int) $program['elapsed_minutes'] > (int) $program['expected_minutes'] ? 'delay-cell' : '' ?>">
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
<td><?= e($program['room_name'] ?: 'Not assigned')
?>
</td>
<td><?= e($program['responsible'] ?: 'Unassigned')
?>
</td>
<td>
					<?= minutes_label((int) $program['elapsed_minutes'])
?>
 /
					<?= minutes_label((int) $program['expected_minutes'])
?>

				</td>
				<span class="badge <?= e($program['current_status']) ?><?= (int) $program['elapsed_minutes'] > (int) $program['expected_minutes'] ? ' delay-status' : '' ?>"
?>
%</td>
<td>
<span class="badge <?= e($program['current_status'])
?>
">
						<?= e($program['current_status'])
?>

					</span>
</td>
</tr><?php 
endforeach;
?>

	</tbody>
</table>
