<?php

$pageTitle = 'Program journal';
$pageHelp = 'Register a program and start its workflow.';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_login();
if (user()['role'] !== 'JOURNALIST') {
    redirect('programs.php');
}

$types = db()->query('SELECT id, name FROM program_types WHERE active = 1 ORDER BY name')->fetchAll();
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$journalists = db()->query(
    "SELECT e.id, e.full_name
     FROM employees e
     INNER JOIN users u ON u.employee_id = e.id
     WHERE e.active = 1 AND u.role = 'JOURNALIST' AND u.active = 1
     ORDER BY e.full_name"
)->fetchAll();

$currentEmployeeId = 0;
if (user()['role'] === 'JOURNALIST') {
    $ownerStmt = db()->prepare('SELECT employee_id FROM users WHERE id = ?');
    $ownerStmt->execute([user()['id']]);
    $currentEmployeeId = (int)($ownerStmt->fetchColumn() ?: 0);
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e(__('new_program_journal')) ?></h2>
            <p class="muted"><?= e(__('program_will_start')) ?></p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="api/program_create.php" class="form-grid" data-program-form>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label>
            <?= e(__('program_name')) ?>
            <input name="title" required placeholder="Morning News">
        </label>

        <label>
            <?= e(__('program_type')) ?>
            <select name="program_type_id" required>
                <option value=""><?= e(__('select_type')) ?></option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= (int)$type['id'] ?>"><?= e($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Department
            <select name="department_id" required>
                <option value=""><?= e(__('select_department')) ?></option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int)$department['id'] ?>"><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (user()['role'] === 'ADMIN'): ?>
            <label>
                <?= e(__('journalist_owner')) ?>
                <select name="journalist_id" required>
                    <option value=""><?= e(__('select_journalist')) ?></option>
                    <?php foreach ($journalists as $journalist): ?>
                        <option value="<?= (int)$journalist['id'] ?>"><?= e($journalist['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <label>
                <?= e(__('journalist_owner')) ?>
                <input value="<?= e(user()['name']) ?>" disabled>
                <input type="hidden" name="journalist_id" value="<?= $currentEmployeeId ?>">
            </label>
        <?php endif; ?>

        <label><?= e(__('presenter')) ?><input name="presenter"></label>
        <label><?= e(__('language')) ?><input name="language" value="Afaan Oromo"></label>
        <label><?= e(__('source')) ?><input name="source" placeholder="Newsroom, field, guest"></label>

        <label>
            Priority
            <select name="priority">
                <option value="NORMAL"><?= e(__('normal')) ?></option>
                <option value="LOW"><?= e(__('low')) ?></option>
                <option value="HIGH"><?= e(__('high')) ?></option>
                <option value="URGENT"><?= e(__('urgent')) ?></option>
            </select>
        </label>

        <label><?= e(__('program_date_start')) ?><input type="datetime-local" name="planned_start" required></label>
        <label><?= e(__('scheduled_broadcast')) ?><input type="datetime-local" name="planned_broadcast"></label>
        <label><?= e(__('expected_duration')) ?><input type="number" name="expected_duration_minutes" value="60" min="1" required></label>
        <label><?= e(__('initial_location')) ?><input value="Program Journal" disabled></label>

        <label class="full-width">
            <?= e(__('description_notes')) ?>
            <textarea name="description" rows="5" placeholder="What is this program about?"></textarea>
        </label>

        <button class="button primary" type="submit"><?= e(__('create_program_journal')) ?></button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
