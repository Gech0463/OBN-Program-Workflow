<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_login(true);
$stmt = db()->prepare('SELECT e.*, d.name department_name FROM employees e INNER JOIN users u ON u.employee_id = e.id LEFT JOIN departments d ON d.id = e.department_id WHERE u.id = ?');
$stmt->execute([user()['id']]);
$profile = $stmt->fetch() ?: [];
if (!empty($profile['profile_completed_at'])) redirect('dashboard.php');
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$pageTitle = t('profile_setup');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/enhancements.css"></head>
<body class="login-page profile-setup-page"><main class="profile-setup-shell"><section class="login-brand"><img class="login-logo" src="assets/images/obn-logo.png" alt="OBN logo"><h1><?= e($pageTitle) ?></h1><p class="muted">Complete these details once before entering the workflow.</p></section>
<form class="login-card profile-form" method="post" action="api/profile/complete.php" enctype="multipart/form-data" data-profile-form novalidate><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="alert error" data-profile-error hidden></div>
<label for="profile-name">Full name<input id="profile-name" name="full_name" required value="<?= e($profile['full_name'] ?? user()['name']) ?>"><span class="field-error" data-error-for="full_name"></span></label>
<label for="profile-phone">Phone number<input id="profile-phone" name="phone" required placeholder="09XXXXXXXX or +2519XXXXXXXX" value="<?= e($profile['phone'] ?? '') ?>"><span class="field-error" data-error-for="phone"></span></label>
<label for="profile-position">Position / job title<input id="profile-position" name="position" required value="<?= e($profile['position'] ?? '') ?>"><span class="field-error" data-error-for="position"></span></label>
<label for="profile-department">Department<select id="profile-department" name="department_id" required><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (int)($profile['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select><span class="field-error" data-error-for="department_id"></span></label>
<label for="profile-avatar">Profile photo<input id="profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png" data-avatar-input><img class="avatar-preview" data-avatar-preview alt="Avatar preview" hidden></label>
<label for="profile-bio">Short bio<textarea id="profile-bio" name="bio" maxlength="200" rows="3" data-bio-input><?= e($profile['bio'] ?? '') ?></textarea><small><span data-bio-count>0</span>/200</small><span class="field-error" data-error-for="bio"></span></label>
<label for="profile-language">Preferred interface language<select id="profile-language" name="preferred_language"><option value="en">English</option><option value="om">Afaan Oromo</option><option value="am">Amharic</option></select></label>
<button class="button primary" type="submit" data-profile-submit><?= e(t('save_profile')) ?></button><p class="form-message" data-profile-message aria-live="polite"></p></form></main><script src="assets/js/app.js"></script></body></html>
