<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../includes/db.php';

$stats = [
    'users'    => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_active=1")['c'] ?? 0,
    'bookings' => db()->fetchOne("SELECT COUNT(*) as c FROM bookings")['c'] ?? 0,
    'flights'  => db()->fetchOne("SELECT COUNT(*) as c FROM flights WHERE is_active=1 AND departure_time > NOW()")['c'] ?? 0,
    'revenue'  => db()->fetchOne("SELECT COALESCE(SUM(total_amount),0) as c FROM bookings WHERE status='confirmed'")['c'] ?? 0,
];
$recentBookings = db()->fetchAll(
    "SELECT b.booking_ref, b.status, b.total_amount, b.created_at,
            u.first_name, u.last_name, u.email,
            o.code AS origin, d.code AS dest
     FROM bookings b
     JOIN users u    ON b.user_id = u.id
     JOIN flights f  ON b.flight_id = f.id
     JOIN airports o ON f.origin_id = o.id
     JOIN airports d ON f.destination_id = d.id
     ORDER BY b.created_at DESC LIMIT 10"
);
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — Titan Wing Airlines</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f0ece4;color:#0a1628}
.sidebar{width:220px;background:#0a1628;min-height:100vh;position:fixed;top:0;left:0;padding:24px 0}
.logo{color:#e8b85c;font-size:16px;font-weight:900;padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,.1)}
.nav a{display:block;color:rgba(255,255,255,.7);padding:12px 20px;font-size:13px;text-decoration:none;transition:.2s}
.nav a:hover,.nav a.active{background:rgba(201,151,58,.15);color:#e8b85c}
.main{margin-left:220px;padding:28px}
.page-title{font-size:22px;font-weight:700;margin-bottom:6px}
.subtitle{color:#888;font-size:13px;margin-bottom:24px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat{background:white;border-radius:12px;padding:20px;border:1.5px solid #e8e4dc}
.stat-val{font-size:28px;font-weight:900;color:#0a1628}
.stat-lbl{font-size:12px;color:#888;margin-top:4px}
.stat.gold{border-color:#c9973a;background:linear-gradient(135deg,#fff8ed,#fff)}
table{width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;border:1.5px solid #e8e4dc}
th{background:#0a1628;color:#e8b85c;padding:12px 14px;text-align:left;font-size:12px;font-weight:700}
td{padding:10px 14px;border-bottom:1px solid #f0ece4;font-size:13px}
tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700}
.badge-confirmed{background:#e8f5e9;color:#27ae60}
.badge-cancelled{background:#fce4ec;color:#c62828}
.badge-pending{background:#fff8e1;color:#e67e22}
.badge-checked-in{background:#ede7f6;color:#7b1fa2}
.topbar{background:white;border-radius:12px;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border:1.5px solid #e8e4dc}
.logout-btn{background:#0a1628;color:#e8b85c;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="sidebar">
  <div class="logo">✈ Titan Wing<br><span style="font-size:11px;color:rgba(255,255,255,.5);font-weight:400">Admin Panel</span></div>
  <nav class="nav" style="margin-top:16px">
    <a href="index.php" class="active">📊 Dashboard</a>
    <a href="index.php">✈ Manage Flights</a>
    <a href="index.php">📋 Bookings</a>
    <a href="index.php">👥 Users</a>
    <a href="index.php">🏢 Airports</a>
    <a href="index.php">🔔 Notifications</a>
    <a href="logout.php" style="color:#e74c3c;margin-top:40px">🚪 Logout</a>
  </nav>
</div>
<div class="main">
  <div class="topbar">
    <div>
      <div class="page-title">Dashboard</div>
      <div class="subtitle">Welcome back, <?= htmlspecialchars($adminName) ?></div>
    </div>
    <a href="logout.php" class="logout-btn">Sign Out</a>
  </div>

  <div class="stats">
    <div class="stat">
      <div class="stat-val"><?= number_format($stats['users']) ?></div>
      <div class="stat-lbl">Verified Users</div>
    </div>
    <div class="stat">
      <div class="stat-val"><?= number_format($stats['bookings']) ?></div>
      <div class="stat-lbl">Total Bookings</div>
    </div>
    <div class="stat">
      <div class="stat-val"><?= number_format($stats['flights']) ?></div>
      <div class="stat-lbl">Upcoming Flights</div>
    </div>
    <div class="stat gold">
      <div class="stat-val">₹<?= number_format($stats['revenue']) ?></div>
      <div class="stat-lbl">Confirmed Revenue</div>
    </div>
  </div>

  <h3 style="margin-bottom:12px;font-size:15px">Recent Bookings</h3>
  <table>
    <thead>
      <tr><th>Ref</th><th>Passenger</th><th>Email</th><th>Route</th><th>Amount</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
    <?php if(empty($recentBookings)): ?>
      <tr><td colspan="7" style="text-align:center;color:#888;padding:24px">No bookings yet.</td></tr>
    <?php else: ?>
      <?php foreach($recentBookings as $b): ?>
      <tr>
        <td><strong><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
        <td><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></td>
        <td style="color:#888;font-size:12px"><?= htmlspecialchars($b['email']) ?></td>
        <td><strong><?= $b['origin'] ?> → <?= $b['dest'] ?></strong></td>
        <td>₹<?= number_format($b['total_amount']) ?></td>
        <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
        <td style="color:#888;font-size:12px"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
