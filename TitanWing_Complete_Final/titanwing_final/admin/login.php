<?php
require_once __DIR__ . '/../includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pwd   = $_POST['password'] ?? '';
    $admin = db()->fetchOne("SELECT * FROM admins WHERE email=? AND is_active=1", [$email]);
    if ($admin && password_verify($pwd, $admin['password_hash'])) {
        session_start();
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        db()->execute("UPDATE admins SET last_login=NOW() WHERE id=?", [$admin['id']]);
        header('Location: index.php'); exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Titan Wing Airlines</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#0a1628;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:white;border-radius:16px;padding:40px;width:400px;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{color:#0a1628;font-size:22px;font-weight:900;letter-spacing:1px}
.logo p{color:#888;font-size:12px;margin-top:4px}
label{display:block;font-size:13px;font-weight:600;color:#0a1628;margin-bottom:6px}
input{width:100%;padding:12px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;outline:none;margin-bottom:16px}
input:focus{border-color:#c9973a}
button{width:100%;background:linear-gradient(135deg,#c9973a,#e8b85c);color:#0a1628;border:none;padding:13px;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer}
.error{background:#fce4ec;color:#c62828;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
.badge{display:inline-block;background:#0a1628;color:#e8b85c;font-size:10px;padding:3px 10px;border-radius:100px;font-weight:700;margin-bottom:20px}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="badge">ADMIN PORTAL</div>
    <h1>✈ Titan Wing Airlines</h1>
    <p>Administration Panel</p>
  </div>
  <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Email Address</label>
    <input type="email" name="email" placeholder="admin@titanwing.com" required>
    <label>Password</label>
    <input type="password" name="password" placeholder="Enter your password" required>
    <button type="submit">Sign In to Admin Panel</button>
  </form>
</div>
</body>
</html>
