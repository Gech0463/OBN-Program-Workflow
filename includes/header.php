<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
$currentPage = basename($_SERVER['PHP_SELF']);
$unreadCount = unread_notifications_count((int) user()['id']);
$theme = 'light';
$themeStmt = db()->prepare('SELECT e.theme_preference FROM employees e INNER JOIN users u ON u.employee_id = e.id WHERE u.id = ?');
$themeStmt->execute([user()['id']]);
$theme = $themeStmt->fetchColumn() ?: 'light';
$profileStmt = db()->prepare('SELECT e.full_name, e.avatar_path FROM employees e INNER JOIN users u ON u.employee_id = e.id WHERE u.id = ?');
$profileStmt->execute([user()['id']]);
$profile = $profileStmt->fetch() ?: [];
$profileName = $profile['full_name'] ?: user()['name'];
$profileInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $profileName), 0, 2));
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($pageTitle ?? 'OBN Workflow') ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(__DIR__.'/../assets/css/app.css') ?>">
    <link rel="stylesheet" href="assets/css/enhancements.css?v=<?= filemtime(__DIR__.'/../assets/css/enhancements.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body data-theme="<?= e($theme) ?>" data-user-role="<?= e(user()['role'] ?? '') ?>">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="dashboard.php"><img class="sidebar-logo" src="assets/images/obn-logo.png" alt="OBN logo"><span class="sidebar-brand-name">OBN Program Workflow</span></a>
        <nav aria-label="Main navigation">
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><span><?= e(__('dashboard_title')) ?></span></a>
            <?php if (user()['role'] === 'JOURNALIST'): ?>
                <a class="<?= in_array($currentPage, ['programs.php','program.php','transition.php'], true) ? 'active' : '' ?>" href="programs.php"><span>My programs</span></a>
                <a class="<?= $currentPage === 'program_new.php' ? 'active' : '' ?>" href="program_new.php"><span>Register program</span></a>
                <a class="<?= $currentPage === 'tracking.php' ? 'active' : '' ?>" href="tracking.php"><span>Where is my program?</span></a>
                <a class="<?= $currentPage === 'issues.php' ? 'active' : '' ?>" href="issues.php"><span><?= e(__('issues')) ?></span></a>
                <a class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php"><span><?= e(__('settings')) ?></span></a>
            <?php elseif (user()['role'] === 'ADMIN'): ?>
                <a class="<?= in_array($currentPage, ['programs.php','program.php','transition.php'], true) ? 'active' : '' ?>" href="programs.php"><span><?= e(__('programs')) ?></span></a>
                <a class="<?= $currentPage === 'monitor.php' ? 'active' : '' ?>" href="monitor.php"><span><?= e(__('live_monitor')) ?></span></a>
                <a class="<?= $currentPage === 'issues.php' ? 'active' : '' ?>" href="issues.php"><span><?= e(__('issues')) ?></span></a>
                <a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php"><span><?= e(__('reports')) ?></span></a>
                <a class="<?= $currentPage === 'audit.php' ? 'active' : '' ?>" href="audit.php"><span><?= e(__('audit_logs')) ?></span></a>
                <a class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>" href="admin.php"><span><?= e(__('users')) ?></span></a>
                <a class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php"><span><?= e(__('settings')) ?></span></a>
            <?php else: ?>
                <a class="<?= $currentPage === 'tracking.php' ? 'active' : '' ?>" href="tracking.php"><span>Program tracking</span></a>
                <a class="<?= $currentPage === 'issues.php' ? 'active' : '' ?>" href="issues.php"><span><?= e(__('issues')) ?></span></a>
                <a class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php"><span>Profile</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom"><span><?= e(user()['name']) ?></span><small><?= e(role_label(user()['role'])) ?></small><a href="logout.php"><?= e(__('sign_out')) ?></a></div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false" data-menu-toggle>&#9776;</button>
            <div class="topbar-heading">
                <p class="eyebrow">OBN PROGRAM WORKFLOW</p>
                <h1><?= e($pageTitle ?? 'Overview') ?></h1>
                <p class="page-help"><?= e($pageHelp ?? 'Monitor programs, responsibilities, and progress from one place.') ?></p>
            </div>
            <div class="topbar-actions">
                <div class="global-search"><input id="global-search" type="search" placeholder="<?= e(__('search_programs')) ?>" data-global-search autocomplete="off"><div class="search-results" data-search-results hidden></div></div>
            <div class="lang-dropdown" style="margin-right: 16px;">
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
                <button class="notification-button<?= $unreadCount ? ' has-unread' : '' ?>" type="button" aria-label="<?= $unreadCount ? e($unreadCount . ' unread notifications') : 'No unread notifications' ?>" aria-expanded="false" data-notifications>
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    <?php if ($unreadCount): ?><b><?= $unreadCount ?></b><?php endif; ?>
                </button>
                <a class="topbar-user" href="settings.php" aria-label="Open profile for <?= e($profileName) ?>">
                    <?php if (!empty($profile['avatar_path'])): ?><img src="<?= e($profile['avatar_path']) ?>" alt=""><?php else: ?><span class="topbar-avatar" aria-hidden="true"><?= e($profileInitials ?: 'OB') ?></span><?php endif; ?>
                    <span class="topbar-user-copy"><strong><?= e($profileName) ?></strong><small><?= e(role_label(user()['role'])) ?></small></span>
                    <span class="status-dot"></span>
                </a>
            </div>
        </header>
        <aside class="notification-drawer" data-notification-drawer aria-label="Notifications">
            <div class="panel-header">
                <div>
                    <p class="eyebrow"><?= e(__('updates')) ?></p>
                    <h2><?= e(__('notifications_title')) ?></h2>
                </div>
                <button type="button" class="drawer-close" aria-label="Close notifications" data-notifications>&times;</button>
            </div>
            <div class="notification-summary"><span data-notification-count><?= $unreadCount ? e((string) $unreadCount) . ' unread' : 'All caught up' ?></span><span class="muted"><?= e(__('select_message_read')) ?></span></div>
            <div data-notification-list><p class="muted">Loading updates...</p></div>
        </aside>
