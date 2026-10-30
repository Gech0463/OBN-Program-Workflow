<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../app/auth/auth_check.php';
require_login(true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, [], 'Method not allowed.', 405);
$data = request_data();
verify_csrf($data['csrf'] ?? null);
$fullName = trim((string)($data['full_name'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$position = trim((string)($data['position'] ?? ''));
$departmentId = (int)($data['department_id'] ?? 0);
$bio = trim((string)($data['bio'] ?? ''));
$language = (string)($data['preferred_language'] ?? 'en');
$theme = (string)($data['theme_preference'] ?? 'light');
$errors = [];
if ($fullName === '') $errors['full_name'] = 'Full name is required.';
if (!preg_match('/^(09|\+2519)[0-9]{8}$/', $phone)) $errors['phone'] = 'Use 09XXXXXXXX or +2519XXXXXXXX.';
if ($position === '') $errors['position'] = 'Position is required.';
if ($departmentId < 1) $errors['department_id'] = 'Select a department.';
if (!in_array($language, ['en', 'om', 'am'], true)) $errors['preferred_language'] = 'Select a supported language.';
if (!in_array($theme, ['light', 'dark'], true)) $errors['theme_preference'] = 'Select a supported theme.';
if (mb_strlen($bio) > 200) $errors['bio'] = 'Bio must be 200 characters or fewer.';
if (!empty($_FILES['avatar']['name'])) {
    if ((int)$_FILES['avatar']['error'] !== UPLOAD_ERR_OK || (int)$_FILES['avatar']['size'] > 2 * 1024 * 1024) $errors['avatar'] = 'Avatar must be a JPG or PNG smaller than 2 MB.';
    $image = @getimagesize($_FILES['avatar']['tmp_name']);
    if (!$image || !in_array($image['mime'], ['image/jpeg', 'image/png'], true)) $errors['avatar'] = 'Avatar must be a JPG or PNG image.';
}
if ($errors) json_response(false, ['fields' => $errors], 'Please correct the highlighted fields.', 422);
$pdo = db();
$employee = $pdo->prepare('SELECT e.id FROM employees e INNER JOIN users u ON u.employee_id = e.id WHERE u.id = ? AND u.active = 1');
$employee->execute([user()['id']]);
$employeeId = (int)($employee->fetchColumn() ?: 0);
if (!$employeeId) json_response(false, [], 'Your account is not linked to an employee profile.', 422);
$avatarPath = null;
if (!empty($_FILES['avatar']['name'])) {
    $avatarDirectory = __DIR__ . '/../../assets/images/avatars';
    if (!is_dir($avatarDirectory) && !mkdir($avatarDirectory, 0755, true)) {
        json_response(false, [], 'The avatar directory could not be created.', 422);
    }
    $extension = $image['mime'] === 'image/png' ? 'png' : 'jpg';
    $avatarPath = 'assets/images/avatars/user-' . user()['id'] . '.' . $extension;
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDirectory . '/user-' . user()['id'] . '.' . $extension)) json_response(false, [], 'The avatar could not be saved.', 422);
}
$sql = 'UPDATE employees SET full_name = ?, phone = ?, position = ?, department_id = ?, bio = ?, preferred_language = ?, theme_preference = ?, profile_completed_at = NOW()';
$params = [$fullName, $phone, $position, $departmentId, $bio, $language, $theme];
if ($avatarPath !== null) { $sql .= ', avatar_path = ?'; $params[] = $avatarPath; }
$sql .= ' WHERE id = ?'; $params[] = $employeeId;
$pdo->prepare($sql)->execute($params);
$_SESSION['user']['name'] = $fullName;
$_SESSION['welcome_toast'] = t('welcome', ['name' => explode(' ', $fullName)[0]], $language);
json_response(true, ['redirect' => 'dashboard.php', 'message' => $_SESSION['welcome_toast']]);
