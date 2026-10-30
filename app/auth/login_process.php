<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$isAjax = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

function login_redirect(string $path, ?string $error = null) {
    global $isAjax;
    if ($isAjax) {
        if ($error) {
            json_response(false, [], $error, 400);
        }
        json_response(true, ['redirect' => ltrim($path, '/')]);
    }
    if ($error) {
        $_SESSION['login_error'] = $error;
    }
    redirect($path);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    login_redirect('login.php');
}

verify_csrf($_POST['csrf'] ?? null);

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$roleGroup = (string)($_POST['role_group'] ?? '');

if ($username === '' || $password === '' || $roleGroup === '') {
    login_redirect('login.php', 'Please enter your username, password, and select your login type.');
}

// Security: 5 failed login attempts causing a 15-minute lockout
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    if (time() - $_SESSION['last_failed_login'] < 900) { // 900 seconds = 15 minutes
        $remaining = ceil((900 - (time() - $_SESSION['last_failed_login'])) / 60);
        login_redirect('login.php', "Too many failed attempts. Please try again in {$remaining} minutes.");
    } else {
        // Lockout expired, reset attempts
        $_SESSION['login_attempts'] = 0;
    }
}

$pdo = db();
// Fetch user and employee details
$stmt = $pdo->prepare('
    SELECT u.id, u.username, u.password_hash, u.role, u.employee_id, e.full_name as name, e.profile_completed_at 
    FROM users u 
    LEFT JOIN employees e ON u.employee_id = e.id 
    WHERE u.username = ? AND u.active = 1 
    LIMIT 1
');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    $isAdmin = ($user['role'] === 'ADMIN');
    if ($roleGroup === 'ADMIN' && !$isAdmin) {
        login_redirect('login.php', 'You do not have Administrator privileges.');
    }
    if ($roleGroup === 'STAFF' && $isAdmin) {
        login_redirect('login.php', 'Administrators must log in using the Administrator option.');
    }
    
    // Successful Login
    session_regenerate_id(true);
    
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'employee_id' => $user['employee_id'] ? (int)$user['employee_id'] : null,
        'name' => $user['name'] ?: $user['username'],
        'profile_completed_at' => $user['profile_completed_at']
    ];
    
    // Clear failed attempts and set session tracking variables
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_failed_login']);
    $_SESSION['last_activity'] = time();
    $_SESSION['created_at'] = time();
    
    $_SESSION['welcome_toast'] = "Welcome back, " . e($_SESSION['user']['name']) . "!";
    
    // Check if the user needs to complete their profile setup
    if (empty($user['profile_completed_at']) && $user['role'] !== 'ADMIN') {
        login_redirect('profile_setup.php');
    }
    
    login_redirect('dashboard.php');
} else {
    // Failed Login
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_failed_login'] = time();
    login_redirect('login.php', 'Invalid username or password.');
}
