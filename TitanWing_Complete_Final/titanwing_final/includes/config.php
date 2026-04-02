<?php
// ============================================================
// TITAN WING AIRLINES - Database Configuration
// ============================================================
define('DB_HOST',     'localhost');
define('DB_USER',     'root');          // Change to your MySQL username
define('DB_PASS',     '');             // Change to your MySQL password
define('DB_NAME',     'titanwing_db');
define('DB_PORT',     3306);

define('SITE_NAME',   'Titan Wing Airlines');
define('SITE_URL',    'http://localhost/titanwing'); // Change for production
define('ADMIN_URL',   'http://localhost/titanwing/admin');

// JWT Secret (change this to a long random string in production)
define('JWT_SECRET',  'TitanWing@2025#SecretKey!ChangeThis');
define('JWT_EXPIRY',  86400); // 24 hours in seconds

// Email Configuration (SMTP)
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'Gmail@gmail.com'); // Gmail address
define('SMTP_PASS',   'seqrwti');           // Gmail App Password
define('SMTP_FROM',   'Gmail@gmail.com'); // Same as SMTP_USER
define('SMTP_NAME',   'Titan Wing Airlines');

// OTP Settings
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_LENGTH', 6);
define('OTP_MAX_ATTEMPTS', 3);

// File Uploads
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Error Reporting — errors logged to file, NOT displayed (would break JSON APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
