<?php

$pageTitle = 'Programs';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$sort = trim($_GET['sort'] ?? 'updated');
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = paginated_programs(user()['role'], (int) user()['id'], $search, $status, $stage, $sort, $page);
$programs = $result['rows'];
$stages = db()->query('SELECT id,name FROM workflow_stages WHERE active=1 ORDER BY sequence_no')->fetchAll();
$statuses = ['WAITING','IN_PROGRESS','BLOCKED','DELAYED','ON_AIR','COMPLETED','CANCELLED'];
$query = $_GET;
?>

<section class="panel">
<div class="panel-header">
<div>
<h2><?= user()['role'] === 'JOURNALIST' ? 'My programs' : 'Program register'
?>
</h2>
<p class="muted">Search, filter, and sort the workflow register.</p>
</div>
<?php if (user()['role'] === 'JOURNALIST'): ?><a class="button primary" href="program_new.php">New program</a><?php endif; ?>
</div>
<form class="program-filters" method="get">
<input class="search-input" name="q" placeholder="Search programs..." value="<?= e($search) ?>">
<select name="status" aria-label="Filter by status"><option value="">All statuses</option><?php foreach ($statuses as $option): ?><option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $option)) ?></option><?php endforeach; ?></select>
<select name="stage" aria-label="Filter by stage"><option value="">All stages</option><?php foreach ($stages as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $stage === (string) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option><?php endforeach; ?></select>
<select name="sort" aria-label="Sort programs"><option value="updated" <?= $sort === 'updated' ? 'selected' : '' ?>>Recently updated</option><option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title</option><option value="stage" <?= $sort === 'stage' ? 'selected' : '' ?>>Stage</option><option value="broadcast" <?= $sort === 'broadcast' ? 'selected' : '' ?>>Broadcast</option><option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option></select>
<button class="button secondary">Apply</button>
</form>
<div class="table-wrap">
<table id="program-list">
<thead>
<tr>
<th>Program</th>
<th>Type</th>
<th>Stage</th>
<th>Location</th>
<th>Responsible</th>
<th>Broadcast</th>
<th>Status</th>
</tr>
</thead>
<tbody><?php 
foreach ($programs as $program):
?>

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
<td><?= e($program['type_name'])
?>
</td>
<td><?= e($program['stage_name'] ?: 'Program Journal')
?>
</td>
<td><?= e($program['room_name'] ?: 'Not assigned')
?>
</td>
<td><?= e($program['responsible'] ?: 'Unassigned')
?>
</td>
<td><?= $program['planned_broadcast'] ? e(date('M d, H:i', strtotime($program['planned_broadcast']))) : '-'
?>
</td>
<td>
<span class="badge <?= e($program['current_status'])
?>
">
								<?= e(str_replace('_', ' ', $program['current_status']))
?>

							</span>
</td>
</tr><?php 
endforeach;
?>

			</tbody>
</table>
</div>
<?php if ($result['pages'] > 1): ?><nav class="pagination" aria-label="Program pages"><?php for ($number = 1; $number <= $result['pages']; $number++): $query['page'] = $number; ?><a class="button <?= $number === $result['page'] ? 'primary' : 'secondary' ?>" href="?<?= e(http_build_query($query)) ?>" aria-current="<?= $number === $result['page'] ? 'page' : 'false' ?>"><?= $number ?></a><?php endfor; ?></nav><?php endif; ?>
</section><?php 
require_once __DIR__ . '/includes/footer.php';
?>
