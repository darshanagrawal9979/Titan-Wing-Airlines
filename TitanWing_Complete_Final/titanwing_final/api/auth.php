<?php
error_reporting(0);
@ini_set("display_errors", 0);
// ============================================================
// TITAN WING AIRLINES - Auth API
// POST /api/auth.php?action=register|login|verify_otp|resend_otp|logout|forgot_password
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── REGISTER ──────────────────────────────────────────────
    case 'register':
        $firstName = clean($body['first_name'] ?? '');
        $lastName  = clean($body['last_name']  ?? '');
        $email     = strtolower(trim($body['email'] ?? ''));
        $phone     = clean($body['phone'] ?? '');
        $password  = $body['password'] ?? '';

        if (!$firstName || !$email || !$password)
            jsonError('First name, email and password are required.');
        if (!validateEmail($email))
            jsonError('Invalid email address.');
        if (strlen($password) < 8)
            jsonError('Password must be at least 8 characters.');
        if (db()->fetchOne("SELECT id FROM users WHERE email=?", [$email]))
            jsonError('This email is already registered.');

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = db()->insert(
            "INSERT INTO users (first_name,last_name,email,phone,password_hash) VALUES (?,?,?,?,?)",
            [$firstName, $lastName, $email, $phone, $hash]
        );

        $otp = generateOTP();
        storeOTP($userId, $otp);

        // Try to send email — but don't fail if it doesn't work
        $emailSent = false;
        try {
            $emailSent = sendOTPEmail($userId, $email, $firstName, $otp);
        } catch (Exception $e) {
            error_log('[TitanWing] OTP email error: ' . $e->getMessage());
        }

        // Always return OTP in response for reliability
        // Frontend shows it on screen so user never gets stuck
        jsonSuccess([
            'user_id'    => $userId,
            'email'      => $email,
            'otp_code'   => $otp,          // shown on screen as fallback
            'email_sent' => $emailSent,
        ], $emailSent
            ? 'OTP sent to ' . $email . '. Also shown below as backup.'
            : 'Account created! Email could not be sent — use the OTP shown below.'
        );

    // ── VERIFY OTP ────────────────────────────────────────────
    case 'verify_otp':
        $userId = cleanInt($body['user_id'] ?? 0);
        $otp    = trim($body['otp'] ?? '');

        if (!$userId || !$otp) jsonError('User ID and OTP are required.');

        $user = db()->fetchOne("SELECT id,email,first_name,last_name,is_verified FROM users WHERE id=?", [$userId]);
        if (!$user) jsonError('User not found.');
        if ($user['is_verified']) jsonError('Account already verified.');

        if (!verifyOTP($userId, $otp)) {
            // Check expiry separately for better message
            $u = db()->fetchOne("SELECT otp_expires FROM users WHERE id=?", [$userId]);
            if ($u && new DateTime() > new DateTime($u['otp_expires'])) {
                jsonError('OTP has expired. Please request a new one.');
            }
            jsonError('Invalid OTP. Please try again.');
        }

        db()->execute("UPDATE users SET is_verified=1, otp_code=NULL, otp_expires=NULL WHERE id=?", [$userId]);

        $token = generateJWT(['user_id' => $userId, 'email' => $user['email'], 'role' => 'user']);
        setcookie('tw_token', $token, time() + JWT_EXPIRY, '/', '', false, true);
        $_SESSION['tw_token'] = $token;

        jsonSuccess([
            'token'      => $token,
            'user'       => ['id' => $user['id'], 'first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'email' => $user['email']]
        ], 'Email verified! Welcome to Titan Wing.');

    // ── RESEND OTP ────────────────────────────────────────────
    case 'resend_otp':
        $userId = cleanInt($body['user_id'] ?? 0);
        if (!$userId) jsonError('User ID required.');
        $user = db()->fetchOne("SELECT id,email,first_name,is_verified FROM users WHERE id=?", [$userId]);
        if (!$user) jsonError('User not found.');
        if ($user['is_verified']) jsonError('Account already verified.');

        $otp = generateOTP();
        storeOTP($userId, $otp);
        $emailSent = false;
        try { $emailSent = sendOTPEmail($userId, $user['email'], $user['first_name'], $otp); } catch (Exception $e) {}
        jsonSuccess([
            'otp_code'   => $otp,
            'email_sent' => $emailSent,
        ], $emailSent ? 'New OTP sent to ' . $user['email'] : 'Use the OTP shown on screen.');

    // ── LOGIN ─────────────────────────────────────────────────
    case 'login':
        $email    = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if (!$email || !$password) jsonError('Email and password are required.');

        $user = db()->fetchOne("SELECT * FROM users WHERE email=?", [$email]);
        if (!$user || !password_verify($password, $user['password_hash']))
            jsonError('Invalid email or password.');
        if (!$user['is_active'])
            jsonError('Your account has been deactivated. Contact support.');
        if (!$user['is_verified']) {
            // Resend OTP automatically
            $otp = generateOTP();
            storeOTP($user['id'], $otp);
            sendOTPEmail($user['id'], $user['email'], $user['first_name'], $otp);
            jsonError('Please verify your email first. A new OTP has been sent.', 403);
        }

        $token = generateJWT(['user_id' => $user['id'], 'email' => $user['email'], 'role' => 'user']);
        setcookie('tw_token', $token, time() + JWT_EXPIRY, '/', '', false, true);
        $_SESSION['tw_token'] = $token;

        db()->execute("UPDATE users SET updated_at=NOW() WHERE id=?", [$user['id']]);

        jsonSuccess([
            'token' => $token,
            'user'  => [
                'id'         => $user['id'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'email'      => $user['email'],
                'phone'      => $user['phone'],
            ]
        ], 'Login successful.');

    // ── FORGOT PASSWORD ───────────────────────────────────────
    case 'forgot_password':
        $email = strtolower(trim($body['email'] ?? ''));
        if (!validateEmail($email)) jsonError('Invalid email address.');

        $user = db()->fetchOne("SELECT id,first_name FROM users WHERE email=? AND is_active=1", [$email]);
        // Always return success to prevent email enumeration
        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $expiry     = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            db()->execute("UPDATE users SET otp_code=?, otp_expires=? WHERE id=?", [hash('sha256', $resetToken), $expiry, $user['id']]);

            $resetLink = SITE_URL . '/pages/reset_password.php?token=' . $resetToken . '&uid=' . $user['id'];
            $body_html = emailTemplate('Reset Your Password', "
<p>Hi <strong>{$user['first_name']}</strong>,</p>
<p>We received a request to reset your Titan Wing password. Click the button below to set a new password:</p>
<a href='$resetLink' class='btn'>Reset My Password →</a>
<div class='box'>This link expires in 30 minutes. If you didn't request this, you can safely ignore this email.</div>
");
            sendEmail($email, $user['first_name'], 'Reset your Titan Wing password', $body_html, 'otp', $user['id']);
        }
        jsonSuccess([], 'If this email is registered, a reset link has been sent.');

    // ── LOGOUT ────────────────────────────────────────────────
    case 'logout':
        setcookie('tw_token', '', time() - 3600, '/');
        unset($_SESSION['tw_token']);
        jsonSuccess([], 'Logged out successfully.');

    default:
        jsonError('Invalid action.', 404);
}
