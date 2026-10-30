<?php
require_once __DIR__ . '/../config/config.php';
$lang = $_GET['lang'] ?? 'en';
if (in_array($lang, ['en', 'om'])) {
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), "/"); // 30 days
}
$redirect = $_SERVER['HTTP_REFERER'] ?? '../login.php';
header("Location: $redirect");
exit;

