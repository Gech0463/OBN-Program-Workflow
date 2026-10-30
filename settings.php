<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/app/auth/auth_check.php';
require_login();
$stmt = db()->prepare('SELECT e.* FROM employees e INNER JOIN users u ON u.employee_id = e.id WHERE u.id = ?');
$stmt->execute([user()['id']]);
$profile = $stmt->fetch() ?: [];
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$pageTitle = 'Settings';
$pageHelp = 'Update your profile and interface preferences.';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel profile-settings"><div class="panel-header"><div><h2>Edit profile</h2><p class="muted">These details are visible to the workflow team.</p></div></div>
<form class="form-grid" method="post" action="api/profile/update.php" enctype="multipart/form-data" data-profile-form><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="alert error" data-profile-error hidden></div>
<label for="settings-name">Full name<input id="settings-name" name="full_name" required value="<?= e($profile['full_name'] ?? '') ?>"><span class="field-error" data-error-for="full_name"></span></label>
<label for="settings-phone">Phone number<input id="settings-phone" name="phone" required value="<?= e($profile['phone'] ?? '') ?>"><span class="field-error" data-error-for="phone"></span></label>
<label for="settings-position">Position / job title<input id="settings-position" name="position" required value="<?= e($profile['position'] ?? '') ?>"><span class="field-error" data-error-for="position"></span></label>
<label for="settings-department">Department<select id="settings-department" name="department_id" required><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (int)($profile['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></label>
<label for="settings-avatar">Profile photo<input id="settings-avatar" type="file" name="avatar" accept="image/jpeg,image/png" data-avatar-input><img class="avatar-preview" data-avatar-preview src="<?= e($profile['avatar_path'] ?? '') ?>" alt="Avatar preview" <?= empty($profile['avatar_path']) ? 'hidden' : '' ?>></label>
<label for="settings-bio">Short bio<textarea id="settings-bio" name="bio" maxlength="200" rows="3" data-bio-input><?= e($profile['bio'] ?? '') ?></textarea><small><span data-bio-count>0</span>/200</small><span class="field-error" data-error-for="bio"></span></label>
<label for="settings-language">Preferred interface language<select id="settings-language" name="preferred_language"><option value="en" <?= ($profile['preferred_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option><option value="om" <?= ($profile['preferred_language'] ?? '') === 'om' ? 'selected' : '' ?>>Afaan Oromo</option><option value="am" <?= ($profile['preferred_language'] ?? '') === 'am' ? 'selected' : '' ?>>Amharic</option></select></label>
<label for="settings-theme">Theme<select id="settings-theme" name="theme_preference"><option value="light" <?= ($profile['theme_preference'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option><option value="dark" <?= ($profile['theme_preference'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option></select></label>
<button class="button primary" type="submit" data-profile-submit>Save changes</button><p class="form-message" data-profile-message aria-live="polite"></p></form></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
