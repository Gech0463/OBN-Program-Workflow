<?php

$pageTitle = 'Reports and analytics';
$pageHelp = 'Review program volume, workflow speed, delays, and stage performance for a selected period.';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_admin();
require_once __DIR__ . '/includes/header.php';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$report = report_summary($from, $to);
$statusRows = db()->query(
'SELECT current_status, COUNT(*) total
	 FROM programs
	 GROUP BY current_status
	 ORDER BY total DESC'
)->fetchAll();
?>
<section class="panel report-filters">
<form method="get">
<label>From<input type="date" name="from" value="<?= e($from)
?>
">
</label>
<label>To<input type="date" name="to" value="<?= e($to)
?>
">
</label>
<button class="button primary">Update report</button>
<button class="button secondary" type="button" data-report-preset="today">Today</button>
<button class="button secondary" type="button" data-report-preset="week">7 days</button>
<button class="button secondary" type="button" data-report-preset="month">This month</button>
<a class="button secondary" href="reports_export.php?from=<?= e($from)
?>
&to=<?= e($to)
?>
">Download CSV</a>
<button class="button secondary" type="button" onclick="window.print()">Print</button>
</form>
</section>
<section class="stats-grid">
<div class="stat">
<span>Total programs</span>
<strong><?= (int)($report['programs']['total']??0)
?>
</strong>
<small class="muted">Selected period</small>
</div>
<div class="stat">
<span>Active</span>
<strong><?= (int)($report['programs']['active']??0)
?>
</strong>
<small class="muted">Currently moving</small>
</div>
<div class="stat">
<span>Completed</span>
<strong><?= (int)($report['programs']['completed']??0)
?>
</strong>
<small class="muted">Finished programs</small>
</div>
<div class="stat">
<span>Delayed</span>
<strong><?= (int)($report['programs']['delayed']??0)
?>
</strong>
<small class="muted">Needs attention</small>
</div>
</section>
<div class="live-grid">
<section class="panel">
<h2>Stage performance</h2>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Stage</th>
<th>Records</th>
<th>Average time</th>
<th>Delay minutes</th>
</tr>
</thead>
<tbody><?php 
foreach($report['stages'] as $stage):
?>
<tr>
<td><?= e($stage['name'])
?>
</td>
<td><?= (int)$stage['movements']
?>
</td>
<td><?= minutes_label((int)$stage['average_minutes'])
?>
</td>
<td><?= minutes_label((int)$stage['delay_minutes'])
?>
</td>
</tr><?php 
endforeach;
?>
</tbody>
</table>
</div>
</section>
<section class="panel">
<h2>Programs by status</h2>
<canvas class="report-chart" data-report-chart='<?= e(json_encode($statusRows)) ?>' height="220" aria-label="Programs by status chart"></canvas>
<div class="report-bars"><?php 
foreach($statusRows as $status):
?>
<div class="report-bar">
<span><?= e(str_replace('_',' ',$status['current_status']))
?>
</span>
<strong><?= (int)$status['total']
?>
</strong>
<i style="width:<?= min(100,(int)$status['total']*20)
?>
%">
</i>
</div><?php 
endforeach;
?>
</div>
</section>
</div><?php 
require_once __DIR__.'/includes/footer.php';
?>
