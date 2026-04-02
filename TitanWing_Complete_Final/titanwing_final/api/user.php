<?php
error_reporting(0);
@ini_set("display_errors", 0);
// ============================================================
// TITAN WING AIRLINES - User Profile API
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? 'get';
$user   = requireAuth();

switch ($action) {
    case 'get':
        $profile = db()->fetchOne(
            "SELECT id,first_name,last_name,email,phone,dob,gender,nationality,passport_no,profile_pic,created_at FROM users WHERE id=?",
            [$user['id']]
        );
        jsonSuccess($profile);

    case 'update':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $allowed = ['first_name','last_name','phone','dob','gender','nationality','passport_no'];
        $sets = []; $params = [];
        foreach ($allowed as $field) {
            if (isset($body[$field])) {
                $sets[]   = "$field = ?";
                $params[] = clean($body[$field]);
            }
        }
        if (empty($sets)) jsonError('No fields to update.');
        $params[] = $user['id'];
        db()->execute("UPDATE users SET " . implode(', ', $sets) . " WHERE id=?", $params);
        jsonSuccess([], 'Profile updated successfully.');

    case 'change_password':
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $oldPass = $body['old_password'] ?? '';
        $newPass = $body['new_password'] ?? '';

        if (!$oldPass || !$newPass) jsonError('Old and new passwords required.');
        if (strlen($newPass) < 8) jsonError('New password must be at least 8 characters.');

        $userData = db()->fetchOne("SELECT password_hash FROM users WHERE id=?", [$user['id']]);
        if (!password_verify($oldPass, $userData['password_hash']))
            jsonError('Current password is incorrect.');

        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->execute("UPDATE users SET password_hash=? WHERE id=?", [$hash, $user['id']]);
        jsonSuccess([], 'Password changed successfully.');

    case 'upload_photo':
        if (empty($_FILES['photo'])) jsonError('No file uploaded.');
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($file['type'], $allowed)) jsonError('Only JPG, PNG, WEBP allowed.');
        if ($file['size'] > MAX_FILE_SIZE) jsonError('File too large. Max 2MB.');

        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
        $dir  = UPLOAD_DIR . 'profiles/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $dir . $name))
            jsonError('Failed to upload file.');

        db()->execute("UPDATE users SET profile_pic=? WHERE id=?", [$name, $user['id']]);
        jsonSuccess(['profile_pic' => $name], 'Photo uploaded successfully.');

    default:
        jsonError('Invalid action.', 404);
}
