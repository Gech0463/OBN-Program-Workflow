<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$positions = db()->query('SELECT id, name FROM job_positions ORDER BY name')->fetchAll();
$error = $_SESSION['login_error'] ?? null;
$success = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_error'], $_SESSION['login_success']);
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>OBN Program Workflow</title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__.'/assets/css/app.css') ?>">
    <link rel="stylesheet" href="assets/css/enhancements.css?v=<?= filemtime(__DIR__.'/assets/css/enhancements.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="login-page" style="--obn-login-banner:url('assets/images/obn-banner.png')">
<main class="login-shell">
    <div class="lang-dropdown" style="position: absolute; top: 16px; right: 16px; box-shadow:0 2px 4px rgba(0,0,0,0.1); border-radius:20px;">
        <?php $currentLang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en'; ?>
        <button class="lang-btn" type="button">
            <?php if($currentLang === 'en'): ?>
                <img src="https://flagcdn.com/w20/gb.png" alt="English" style="width: 20px; border-radius: 2px;"> EN
            <?php else: ?>
                <img src="https://flagcdn.com/w20/et.png" alt="Afaan Oromo" style="width: 20px; border-radius: 2px;"> OM
            <?php endif; ?>
            <i class="fa-solid fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
        </button>
        <div class="lang-menu">
            <a href="api/set_language.php?lang=en" class="lang-option" style="text-decoration:none;">
                <img src="https://flagcdn.com/w20/gb.png" alt="English" style="width: 20px; border-radius: 2px;"> English
            </a>
            <a href="api/set_language.php?lang=om" class="lang-option" style="text-decoration:none;">
                <img src="https://flagcdn.com/w20/et.png" alt="Afaan Oromo" style="width: 20px; border-radius: 2px;"> Afaan Oromo
            </a>
        </div>
    </div>
    <section class="login-brand">
        <img class="login-logo" src="assets/images/obn-logo.png" alt="OBN logo">
        <div class="brand-divider"></div>
        <h1>OBN Program Workflow</h1>
    </section>
    <form class="login-card" method="post" action="api/auth/login.php" data-login-form>
        <h2><?= e(__('login_title')) ?></h2>
        <p class="muted"><?= e(__('login_subtitle')) ?></p>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="alert error" data-login-error hidden></div>
        <div class="login-role-selection" style="display: flex; gap: 16px; margin-bottom: 24px;">
            <label class="role-card" style="flex: 1; border: 2px solid #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; cursor: pointer; transition: all 0.2s;">
                <input type="radio" name="role_group" value="ADMIN" style="display: none;">
                <i class="fa-solid fa-user-shield" style="font-size: 24px; color: #1e293b; margin-bottom: 8px; display: block;"></i>
                <strong style="color: #1e293b;"><?= e(__('administrator')) ?></strong>
            </label>
            <label class="role-card" style="flex: 1; border: 2px solid #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; cursor: pointer; transition: all 0.2s;">
                <input type="radio" name="role_group" value="STAFF" checked style="display: none;">
                <i class="fa-solid fa-users" style="font-size: 24px; color: #175e63; margin-bottom: 8px; display: block;"></i>
                <strong style="color: #1e293b;"><?= e(__('staff')) ?></strong>
            </label>
        </div>
        <label for="login-username"><?= e(__('username')) ?><input id="login-username" name="username" required autocomplete="username" autofocus placeholder="<?= e(__('username')) ?>" data-login-username></label>
        <label for="login-password"><?= e(__('password')) ?><span class="password-field"><input id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="<?= e(__('password')) ?>"><button type="button" class="password-toggle" data-password-toggle aria-label="Show password"><?= e(__('show')) ?></button></span></label>
        <a class="login-forgot" id="password-reset-trigger" href="#password-reset" data-password-reset><?= e(__('forgot_password')) ?></a>
        <div class="login-actions"><button class="button primary login-submit" type="submit" data-login-submit><?= e(__('login_button')) ?></button><button class="button secondary" type="button" data-signup-open><?= e(__('signup_button')) ?></button></div>
    </form>
</main>
<div class="modal" data-reset-modal data-modal hidden><div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="reset-title"><button type="button" class="drawer-close" data-reset-close data-modal-close aria-label="Close">&times;</button><h2 id="reset-title">Request password reset</h2><p class="muted">An administrator will review your request.</p><form method="post" action="api/password-reset/request.php" data-reset-form><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label for="reset-username"><?= e(__('username')) ?><input id="reset-username" name="username" required></label><button class="button primary">Send request</button><p class="form-message" data-reset-message aria-live="polite"></p></form></div></div>
<div class="modal" data-signup-modal data-modal hidden><div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="signup-title"><button type="button" class="drawer-close" data-modal-close aria-label="Close">&times;</button><h2 id="signup-title"><?= e(__('create_account')) ?></h2><p class="muted">Complete your profile, then log in with your username and password.</p><form method="post" action="api/auth/register.php" data-signup-form novalidate><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="alert error" data-signup-error hidden></div><label for="signup-name"><?= e(__('full_name')) ?><input id="signup-name" name="full_name" required autocomplete="name"><span class="field-error" data-signup-field="full_name"></span></label><label for="signup-role"><?= e(__('role')) ?><select id="signup-role" name="role" required><option value="">Select your profession</option><option value="JOURNALIST">Journalist</option><option value="INGEST_OPERATOR">Ingest Operator</option><option value="PREVIEW_OPERATOR">Preview Operator</option><option value="EDITOR">Editor</option><option value="GRAPHICS_OPERATOR">Graphics Operator</option><option value="AUDIO_OPERATOR">Audio Operator</option><option value="QC_OPERATOR">Quality Control</option><option value="MASTER_CONTROL_OPERATOR">Master Control</option><option value="UPLINK_OPERATOR">Uplink Operator</option></select><small class="muted">Administrators are managed separately.</small><span class="field-error" data-signup-field="role"></span></label><label for="signup-username"><?= e(__('username')) ?><input id="signup-username" name="username" required autocomplete="username"><span class="field-error" data-signup-field="username"></span></label><label for="signup-password"><?= e(__('password')) ?><input id="signup-password" type="password" name="password" required autocomplete="new-password"><span class="field-error" data-signup-field="password"></span></label><label for="signup-confirmation"><?= e(__('confirm_password')) ?><input id="signup-confirmation" type="password" name="password_confirmation" required autocomplete="new-password"><span class="field-error" data-signup-field="password_confirmation"></span></label><div class="login-actions" style="margin-top: 1rem;"><button class="button primary" type="submit" data-signup-submit><?= e(__('create_account')) ?></button><button class="button secondary" type="button" data-signup-login><?= e(__('login_title')) ?>?</button></div></form></div></div>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleCards = document.querySelectorAll('.role-card');
    const radios = document.querySelectorAll('input[name="role_group"]');
    const body = document.body;
    
    if (radios.length > 0) {
        function updateRoles() {
            let selected = 'STAFF';
            radios.forEach(r => {
                if (r.checked) selected = r.value;
                r.parentElement.style.borderColor = r.checked ? (r.value === 'ADMIN' ? '#1e293b' : '#175e63') : '#cbd5e1';
                r.parentElement.style.backgroundColor = r.checked ? (r.value === 'ADMIN' ? '#f1f5f9' : '#f0fdfa') : '#fff';
            });
            
            if (selected === 'ADMIN') {
                body.style.backgroundColor = '#1e293b';
                body.style.backgroundImage = 'none';
            } else {
                body.style.backgroundColor = '#175e63'; 
                body.style.backgroundImage = 'none';
            }
        }
        
        radios.forEach(r => r.addEventListener('change', updateRoles));
        updateRoles(); // Initialize
    }
});
</script>
</body>
</html>
