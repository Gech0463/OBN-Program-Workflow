<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/auth/auth_check.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(
[
'count' => unread_notifications_count((int) user()['id']),
'csrf' => csrf_token(),
'notifications' => user_notifications((int) user()['id']),
]
);
