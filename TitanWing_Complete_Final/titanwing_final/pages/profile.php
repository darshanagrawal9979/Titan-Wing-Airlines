<?php
// Profile page - redirects to login if not authenticated
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile — Titan Wing Airlines</title>
<link rel="stylesheet" href="../css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<div id="app">
  <header class="site-header">
    <a href="../index.html" class="logo-wrap">
      <div class="logo-icon">✈</div>
      <div><span class="logo-text">Titan Wing</span><span class="logo-sub">AIRLINES</span></div>
    </a>
    <nav class="main-nav">
      <a href="../index.html">Home</a>
      <a href="../index.html#search">Search Flights</a>
      <a href="dashboard.php" class="active">My Bookings</a>
      <a href="../checkin.html">Check-in</a>
      <a href="../about.html">About</a>
    </nav>
    <div class="nav-actions">
      <div class="user-avatar user-avatar-letter" id="nav-avatar">U</div>
    </div>
  </header>
  <div style="max-width:800px;margin:40px auto;padding:0 2rem">
    <h1 style="font-family:var(--font-display);font-size:2rem;margin-bottom:24px">My Profile</h1>
    <div id="profile-content" style="background:white;border-radius:16px;padding:32px;border:1.5px solid #e0ddd8">
      <div style="text-align:center;padding:2rem;color:var(--gray-400)">
        <div class="spinner" style="margin:0 auto 1rem"></div>
        Loading profile...
      </div>
    </div>
  </div>
</div>
<div class="loading-overlay" id="loading-overlay">
  <div class="spinner"></div>
  <div class="loading-text">Loading...</div>
</div>
<div class="toast-container" id="toast-container"></div>
<script src="../js/main.js?v=20260320"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const token = localStorage.getItem('tw_token') || sessionStorage.getItem('tw_token');
  const user  = JSON.parse(localStorage.getItem('tw_user') || 'null');
  if (!user || !token) { window.location.href = '../index.html'; return; }
  const letter = (user.first_name || user.firstName || 'U').charAt(0).toUpperCase();
  document.getElementById('nav-avatar').textContent = letter;
  document.getElementById('profile-content').innerHTML = `
    <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px">
      <div style="width:72px;height:72px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:var(--navy)">${letter}</div>
      <div>
        <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700">${user.first_name||user.firstName||''} ${user.last_name||user.lastName||''}</div>
        <div style="color:var(--gray-400)">${user.email||''}</div>
      </div>
    </div>
    <div style="border-top:1.5px solid #eee;padding-top:20px">
      <p style="color:var(--gray-400);font-size:14px;margin-bottom:16px">Profile editing coming soon. Your account is active and verified.</p>
      <a href="dashboard.php" class="btn-gold" style="display:inline-block">View My Bookings →</a>
    </div>`;
});
</script>
</body>
</html>
