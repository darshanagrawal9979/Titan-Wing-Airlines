<?php
// ============================================================
// TITAN WING AIRLINES - Helper Functions
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ── PHPMailer autoload ─────────────────────────────────────────
// Load Composer's autoloader so PHPMailer classes are available.
// This MUST come before any sendEmail() call.
$_vendor = __DIR__ . '/../vendor/autoload.php';
if (file_exists($_vendor)) {
    require_once $_vendor;
}
unset($_vendor);

// ── JSON Response Helpers ─────────────────────────────────────
function jsonSuccess($data = [], string $message = 'Success'): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function jsonError(string $message = 'Error', int $code = 400): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── JWT Functions ─────────────────────────────────────────────
function generateJWT(array $payload): string {
    $header  = base64_url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payloadEnc = base64_url_encode(json_encode($payload));
    $sig = base64_url_encode(hash_hmac('sha256', "$header.$payloadEnc", JWT_SECRET, true));
    return "$header.$payloadEnc.$sig";
}

function verifyJWT(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $validSig = base64_url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($validSig, $sig)) return null;
    $data = json_decode(base64_url_decode($payload), true);
    if (!$data || $data['exp'] < time()) return null;
    return $data;
}

function base64_url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64_url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

// Get authenticated user from JWT in Authorization header or cookie
function getAuthUser(): ?array {
    $token = null;
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        $token = $m[1];
    } elseif (!empty($_COOKIE['tw_token'])) {
        $token = $_COOKIE['tw_token'];
    } elseif (!empty($_SESSION['tw_token'])) {
        $token = $_SESSION['tw_token'];
    }
    if (!$token) return null;
    $payload = verifyJWT($token);
    if (!$payload) return null;
    // Fetch fresh user data
    return db()->fetchOne("SELECT id,first_name,last_name,email,phone,is_verified,is_active FROM users WHERE id=? AND is_active=1", [$payload['user_id']]);
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) jsonError('Unauthorized. Please log in.', 401);
    return $user;
}

function getAuthAdmin(): ?array {
    // Check Authorization header first (used by fetch() calls)
    $token = null;
    $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        $token = $m[1];
    }
    // Fall back to session / cookie
    if (!$token) $token = $_SESSION['admin_token']   ?? null;
    if (!$token) $token = $_COOKIE['tw_admin_token'] ?? null;

    if (!$token) return null;
    $payload = verifyJWT($token);
    if (!$payload || empty($payload['is_admin'])) return null;
    return db()->fetchOne("SELECT id,name,email,role FROM admins WHERE id=? AND is_active=1", [$payload['admin_id']]);
}

function requireAdmin(): array {
    $admin = getAuthAdmin();
    if (!$admin) {
        // Always return JSON error — frontend handles the redirect
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin access required. Please log in.', 'redirect' => '/titanwing/admin/login.php']);
        exit;
    }
    return $admin;
}

// ── OTP Functions ─────────────────────────────────────────────
function generateOTP(): string {
    return str_pad((string)random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
}

function storeOTP(int $userId, string $otp): void {
    $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    db()->execute("UPDATE users SET otp_code=?, otp_expires=? WHERE id=?", [password_hash($otp, PASSWORD_BCRYPT), $expiry, $userId]);
}

function verifyOTP(int $userId, string $otp): bool {
    $user = db()->fetchOne("SELECT otp_code, otp_expires FROM users WHERE id=?", [$userId]);
    if (!$user || !$user['otp_code']) return false;
    if (new DateTime() > new DateTime($user['otp_expires'])) return false;
    return password_verify($otp, $user['otp_code']);
}

// ── Booking Reference ─────────────────────────────────────────
function generateBookingRef(): string {
    do {
        $ref = 'TW' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $exists = db()->fetchOne("SELECT id FROM bookings WHERE booking_ref=?", [$ref]);
    } while ($exists);
    return $ref;
}

// ── Email Sending ─────────────────────────────────────────────
function sendEmail(string $to, string $toName, string $subject, string $htmlBody, string $type = 'system', ?int $userId = null): bool {
    $sent  = false;
    $error = '';

    // ── Try PHPMailer first ───────────────────────────────────
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            // Use fully-qualified class name — no 'use' statements needed
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_PORT === 465)
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 10;

            $mail->setFrom(SMTP_FROM, SMTP_NAME);
            $mail->addAddress($to, $toName);
            $mail->addReplyTo(SMTP_FROM, SMTP_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

            $mail->send();
            $sent = true;

        } catch (\Exception $e) {
            $error = $e->getMessage();
            error_log('[TitanWing Email] PHPMailer error: ' . $error);

            // Write to a local debug file so we can read the exact error
            $logFile = __DIR__ . '/../email_error.log';
            $logEntry = date('Y-m-d H:i:s') . " | TO: $to | ERROR: " . $error . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }

    } else {
        // PHPMailer class not found — log this
        $logFile = __DIR__ . '/../email_error.log';
        $logEntry = date('Y-m-d H:i:s') . " | PHPMailer class NOT FOUND. vendor/autoload.php may not be loaded.\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // ── Fallback: PHP built-in mail() ────────────────────
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
        $sent = @mail($to, $subject, $htmlBody, $headers);
    }

    // ── Log to DB ─────────────────────────────────────────────
    try {
        db()->execute(
            "INSERT INTO email_logs (user_id, recipient_email, subject, type, status) VALUES (?,?,?,?,?)",
            [$userId, $to, $subject, $type, $sent ? 'sent' : 'failed']
        );
    } catch (\Exception $e) {
        // Don't fail silently — log but continue
        error_log('[TitanWing Email] DB log error: ' . $e->getMessage());
    }

    return $sent;
}

// ── Email Templates ───────────────────────────────────────────
function emailTemplate(string $title, string $bodyHtml): string {
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body{font-family:'DM Sans',Arial,sans-serif;background:#f0eee9;margin:0;padding:20px}
.wrap{max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.head{background:#0a1628;padding:28px 32px;text-align:center}
.head img{width:40px}
.head h1{color:#e8b85c;font-size:22px;margin:8px 0 4px}
.head p{color:rgba(255,255,255,.5);font-size:12px;margin:0}
.body{padding:32px}
.body p{color:#2d2a26;font-size:15px;line-height:1.7;margin-bottom:12px}
.box{background:#f8f6f1;border-radius:8px;padding:16px 20px;margin:16px 0;border-left:4px solid #c9973a}
.btn{display:inline-block;background:linear-gradient(135deg,#c9973a,#e8b85c);color:#0a1628;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;margin:12px 0}
.foot{background:#f0eee9;padding:20px 32px;text-align:center;font-size:12px;color:#9a9590}
.otp{font-size:36px;font-weight:900;color:#0a1628;letter-spacing:8px;text-align:center;padding:16px;background:#fff8ee;border-radius:8px;border:2px dashed #c9973a;margin:16px 0}
</style></head>
<body>
<div class="wrap">
  <div class="head">
    <h1>✈ Titan Wing Airlines</h1>
    <p>$title</p>
  </div>
  <div class="body">$bodyHtml</div>
  <div class="foot">
    <p>© 2025 Titan Wing Airlines Pvt. Ltd. | support@titanwing.com | 1800-TITANWING</p>
    <p style="font-size:11px;color:#bbb">This is an automated email. Please do not reply.</p>
  </div>
</div>
</body></html>
HTML;
}

function sendOTPEmail(int $userId, string $email, string $name, string $otp): bool {
    $body = emailTemplate('Email Verification', "
<p>Hi <strong>$name</strong>,</p>
<p>Welcome to Titan Wing Airlines! Please use the verification code below to complete your registration:</p>
<div class='otp'>$otp</div>
<div class='box'>
  <strong>⏱ This code expires in " . OTP_EXPIRY_MINUTES . " minutes.</strong><br>
  Do not share this code with anyone. Titan Wing staff will never ask for your OTP.
</div>
<p>If you did not create an account, please ignore this email.</p>
");
    return sendEmail($email, $name, 'Verify your Titan Wing account - OTP: ' . $otp, $body, 'otp', $userId);
}

function sendBookingConfirmationEmail(array $booking, array $user, array $flight): bool {
    $dep = date('D, d M Y H:i', strtotime($flight['departure_time']));
    $arr = date('H:i', strtotime($flight['arrival_time']));
    $body = emailTemplate('Booking Confirmed!', "
<p>Hi <strong>{$user['first_name']}</strong>,</p>
<p>🎉 Your booking has been confirmed! Here are your travel details:</p>
<div class='box'>
  <strong>Booking Reference:</strong> {$booking['booking_ref']}<br>
  <strong>Flight:</strong> {$flight['flight_number']}<br>
  <strong>Route:</strong> {$flight['origin_code']} → {$flight['dest_code']}<br>
  <strong>Departure:</strong> $dep<br>
  <strong>Arrival:</strong> $arr<br>
  <strong>Passengers:</strong> {$booking['total_passengers']}<br>
  <strong>Total Paid:</strong> ₹" . number_format($booking['total_amount'], 2) . "
</div>
<p>You can check in online starting 48 hours before departure.</p>
<a href='" . SITE_URL . "/pages/checkin.php' class='btn'>Online Check-in →</a>
<p style='font-size:12px;color:#9a9590;margin-top:16px'>Keep your booking reference safe for check-in.</p>
");
    return sendEmail($user['email'], $user['first_name'], "Booking Confirmed - {$booking['booking_ref']}", $body, 'booking_confirmation', $user['id']);
}

// ── Sanitization ───────────────────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function cleanInt(mixed $val): int {
    return (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ── Pagination ─────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $page): array {
    $totalPages = (int)ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'page'        => $page,
        'total_pages' => $totalPages,
        'offset'      => ($page - 1) * $perPage,
    ];
}

// ── CSRF ───────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
