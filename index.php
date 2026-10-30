<?php
require_once __DIR__ . '/config/config.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');
redirect('login.php');
