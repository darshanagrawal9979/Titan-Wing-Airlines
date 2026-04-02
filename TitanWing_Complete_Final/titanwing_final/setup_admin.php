<?php
// ============================================================
// TITAN WING - Admin Setup Script
// Run once: http://localhost/titanwing/setup_admin.php
// DELETE this file after running!
// ============================================================
require_once __DIR__ . '/includes/db.php';

$admins = [
    ['name' => 'Super Admin',    'email' => 'admin@titanwing.com',       'password' => 'Admin@123',    'role' => 'super_admin'],
    ['name' => 'Flight Manager', 'email' => 'manager@titanwing.com',     'password' => 'Manager@123',  'role' => 'flight_manager'],
    ['name' => 'Support Staff',  'email' => 'support@titanwing.com',     'password' => 'Support@123',  'role' => 'support'],
];

$results = [];
foreach ($admins as $admin) {
    $hash  = password_hash($admin['password'], PASSWORD_BCRYPT);
    $exists = db()->fetchOne("SELECT id FROM admins WHERE email=?", [$admin['email']]);
    if ($exists) {
        db()->execute("UPDATE admins SET password_hash=?, name=?, role=? WHERE email=?",
            [$hash, $admin['name'], $admin['role'], $admin['email']]);
        $results[] = "Updated: {$admin['email']}";
    } else {
        db()->execute("INSERT INTO admins (name, email, password_hash, role) VALUES (?,?,?,?)",
            [$admin['name'], $admin['email'], $hash, $admin['role']]);
        $results[] = "Created: {$admin['email']}";
    }
}
?>
<!DOCTYPE html>
<html><head><title>Setup</title><style>
body{font-family:Arial;background:#0a1628;color:#fff;padding:40px;text-align:center}
.card{background:#1a3a5c;border-radius:12px;padding:30px;max-width:500px;margin:0 auto}
.ok{color:#e8b85c;margin:8px 0;font-size:14px}
.note{color:rgba(255,255,255,.6);font-size:12px;margin-top:20px}
</style></head><body>
<div class="card">
  <h2 style="color:#e8b85c">✈ Admin Accounts Ready</h2>
  <?php foreach($results as $r): ?>
    <p class="ok">✅ <?= htmlspecialchars($r) ?></p>
  <?php endforeach; ?>
  <hr style="border-color:rgba(255,255,255,.2);margin:20px 0">
  <p class="ok">admin@titanwing.com / Admin@123</p>
  <p class="ok">manager@titanwing.com / Manager@123</p>
  <p class="ok">support@titanwing.com / Support@123</p>
  <p class="note">Delete this file after setup!</p>
  <a href="admin/login.php" style="display:inline-block;margin-top:16px;background:#c9973a;color:#0a1628;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700">Go to Admin Login →</a>
</div>
</body></html>
