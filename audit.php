<?php
$pageTitle = 'Audit logs';
$pageHelp = 'Review important workflow, issue, account, and administrator actions.';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_admin();
require_once __DIR__ . '/includes/header.php';
$logs = db()->query('SELECT a.*, u.username, p.title program_title FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN programs p ON a.entity_type = \'PROGRAM\' AND p.id = CAST(a.entity_id AS UNSIGNED) ORDER BY a.created_at DESC LIMIT 200')->fetchAll();
?>
<section class="panel">
    <div class="panel-header"><div><h2>Audit logs</h2><p class="muted">Recent actions are recorded with the user, timestamp, program, and change details.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Program</th><th>Details</th></tr></thead><tbody>
    <?php foreach ($logs as $log): ?>
        <tr><td><?= e($log['created_at']) ?></td><td><?= e($log['username'] ?: 'System') ?></td><td><?= e($log['action']) ?></td><td><?= e($log['entity_type'] . ' ' . $log['entity_id']) ?></td><td><?= e($log['program_title'] ?: '-') ?></td><td><small><?= e($log['new_value'] ?: $log['old_value'] ?: '-') ?></small></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
