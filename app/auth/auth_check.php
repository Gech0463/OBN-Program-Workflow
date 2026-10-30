<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Returns the current authenticated user's data from the session.
 */
function user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Ensures the user is logged in. Handles session timeouts and periodic ID regeneration.
 */
function require_login(bool $requireProfileComplete = false): void {
    if (empty($_SESSION['user'])) {
        // Handle AJAX/fetch requests gracefully
        if (str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Please log in again.']);
            exit;
        }
        redirect('login.php');
    }

    // 1-Hour Inactivity Timeout (3600 seconds)
    $timeout = 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['login_error'] = 'Your session has expired due to inactivity. Please log in again.';
        redirect('login.php');
    }
    $_SESSION['last_activity'] = time();

    // Session ID Regeneration (prevent session fixation)
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    } else if (time() - $_SESSION['created_at'] > 900) { // Regenerate every 15 minutes
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }

    // Profile Completion Enforcement
    if ($requireProfileComplete) {
        if (empty($_SESSION['user']['profile_completed_at']) && basename($_SERVER['PHP_SELF']) !== 'profile_setup.php') {
            redirect('profile_setup.php');
        }
    }
}

/**
 * Restricts access to Administrators only.
 */
function require_admin(): void {
    require_login();
    if (user()['role'] !== 'ADMIN') {
        http_response_code(403);
        // Serve a custom 403 page or message if needed
        exit('Forbidden: Administrator access required.');
    }
}

