<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard — Titan Wing Airlines</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { background: var(--off-white); padding-top: var(--nav-height); }
    .dash-main { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar scrolled" id="main-navbar">
  <a href="../index.html" class="nav-logo">
    <div class="logo-icon">✈</div>
    <div class="logo-text">
      <span class="brand-name">Titan Wing</span>
      <span class="brand-tagline">Airlines</span>
    </div>
  </a>
  <div class="nav-links">
    <a href="../index.html">Home</a>
    <a href="../index.html#search-section">Search Flights</a>
    <a href="dashboard.php" class="active">My Bookings</a>
    <a href="../checkin.html">Check-in</a>
    <a href="../about.html">About</a>
  </div>
  <div class="nav-actions">
    <div class="notif-btn-wrap" id="notif-wrap" style="display:none">
      <button class="nav-notif-btn" id="notif-btn">🔔
        <span class="notif-badge" id="notif-badge" style="display:none">0</span>
      </button>
    </div>
    <div class="user-menu" id="user-menu" style="display:none">
      <button class="user-avatar-btn" id="user-avatar-btn">
        <div class="avatar-circle user-avatar-letter">U</div>
        <span class="user-display-name" style="font-size:.875rem;color:white;font-weight:500;">User</span>
        <span style="color:rgba(255,255,255,.5);font-size:.7rem">▾</span>
      </button>
      <div class="user-dropdown" id="user-dropdown">
        <div class="user-dropdown-header">
          <div class="ud-name user-display-name">User</div>
          <div class="ud-email user-display-email"></div>
        </div>
        <a href="dashboard.php"><span>📋</span> My Bookings</a>
        <a href="profile.php"><span>👤</span> Profile</a>
        <a href="../checkin.html"><span>✅</span> Check-in</a>
        <button class="logout-btn" onclick="doLogout()"><span>🚪</span> Sign Out</button>
      </div>
    </div>
    <a href="../index.html" class="btn-login nav-login-btn" id="nav-login-link">Sign In</a>
  </div>
</nav>

<!-- NOTIFICATION PANEL -->
<div class="notif-panel" id="notif-panel">
  <div class="notif-panel-header">
    <div class="notif-panel-title">🔔 Notifications</div>
    <button onclick="document.getElementById('notif-panel').classList.remove('open')" style="background:none;border:none;cursor:pointer;font-size:1.1rem;">✕</button>
  </div>
  <div class="notif-list" id="notif-list">
    <div style="padding:2rem;text-align:center;color:var(--gray-400)">Loading...</div>
  </div>
</div>

<div class="dash-main">
  <div class="dash-layout">
    <!-- SIDEBAR -->
    <aside class="dash-sidebar">
      <div class="dash-user-info">
        <div class="dash-avatar user-avatar-letter" style="margin:0 auto .75rem">U</div>
        <div class="dash-name user-display-name">Loading...</div>
        <div class="dash-email user-display-email" style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem"></div>
      </div>
      <nav class="dash-menu">
        <a href="#bookings"      class="active" onclick="showTab('bookings',this,event)"><span class="menu-icon">📋</span> My Bookings</a>
        <a href="#checkin"                      onclick="showTab('checkin',this,event)"><span class="menu-icon">✅</span> Online Check-in</a>
        <a href="#notifications"               onclick="showTab('notifications',this,event)"><span class="menu-icon">🔔</span> Notifications</a>
        <a href="#profile"                     onclick="showTab('profile',this,event)"><span class="menu-icon">👤</span> Profile</a>
        <a href="#password"                    onclick="showTab('password',this,event)"><span class="menu-icon">🔒</span> Change Password</a>
        <a href="../index.html#search-section"><span class="menu-icon">🔍</span> Search Flights</a>
        <a href="javascript:void(0)" onclick="doLogout()" style="color:var(--crimson)"><span class="menu-icon">🚪</span> Logout</a>
      </nav>
    </aside>

    <!-- MAIN CONTENT TABS -->
    <div class="dash-content">

      <!-- ── MY BOOKINGS ── -->
      <div id="tab-bookings" class="dash-tab">
        <div class="dash-card">
          <div class="dash-card-title">📋 My Bookings
            <div style="margin-left:auto;display:flex;gap:.5rem">
              <select id="booking-filter" onchange="loadBookings()" style="padding:.4rem .75rem;border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);font-size:.85rem;outline:none">
                <option value="">All Bookings</option>
                <option value="confirmed">Confirmed</option>
                <option value="checked-in">Checked In</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div id="bookings-list">
            <div style="text-align:center;padding:2rem;color:var(--gray-400)">
              <div class="spinner" style="margin:0 auto 1rem"></div> Loading bookings...
            </div>
          </div>
        </div>
      </div>

      <!-- ── CHECK-IN ── -->
      <div id="tab-checkin" class="dash-tab" style="display:none">
        <div class="dash-card">
          <div class="dash-card-title">✅ Online Check-in</div>
          <p style="color:var(--gray-600);font-size:.9rem;margin-bottom:1.5rem">Check in online 48 hours before your departure. Closes 1 hour before departure.</p>
          <div class="form-row cols-2" style="max-width:500px">
            <div class="form-group">
              <label class="form-label">Booking Reference</label>
              <input type="text" id="ci-ref" class="form-control" placeholder="e.g. TWAB1234" style="text-transform:uppercase">
            </div>
            <div class="form-group" style="justify-content:flex-end">
              <button class="btn-gold" onclick="doCheckin()" style="width:100%;padding:.75rem">Check In →</button>
            </div>
          </div>
          <div id="checkin-result" style="margin-top:1.5rem"></div>
        </div>
      </div>

      <!-- ── NOTIFICATIONS ── -->
      <div id="tab-notifications" class="dash-tab" style="display:none">
        <div class="dash-card">
          <div class="dash-card-title">🔔 Notifications
            <button onclick="markAllRead()" style="margin-left:auto;padding:.35rem .9rem;border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);background:white;cursor:pointer;font-size:.8rem">Mark All Read</button>
          </div>
          <div id="notifications-list">
            <div style="text-align:center;padding:2rem;color:var(--gray-400)"><div class="spinner" style="margin:0 auto 1rem"></div></div>
          </div>
        </div>
      </div>

      <!-- ── PROFILE ── -->
      <div id="tab-profile" class="dash-tab" style="display:none">
        <div class="dash-card">
          <div class="dash-card-title">👤 My Profile</div>
          <form id="profile-form" onsubmit="saveProfile(event)">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">First Name</label><input type="text" id="pf-first" class="form-control"></div>
              <div class="form-group"><label class="form-label">Last Name</label><input type="text" id="pf-last" class="form-control"></div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Email</label><input type="email" id="pf-email" class="form-control" disabled style="opacity:.6"></div>
              <div class="form-group"><label class="form-label">Phone</label><input type="tel" id="pf-phone" class="form-control"></div>
            </div>
            <div class="form-row cols-3">
              <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" id="pf-dob" class="form-control"></div>
              <div class="form-group"><label class="form-label">Gender</label>
                <select id="pf-gender" class="form-control">
                  <option value="">Select</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">Nationality</label><input type="text" id="pf-nationality" class="form-control"></div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Passport Number</label><input type="text" id="pf-passport" class="form-control" placeholder="For international flights"></div>
            </div>
            <button type="submit" class="btn-gold" style="padding:.75rem 2rem;margin-top:.5rem">Save Profile</button>
          </form>
        </div>
      </div>

      <!-- ── CHANGE PASSWORD ── -->
      <div id="tab-password" class="dash-tab" style="display:none">
        <div class="dash-card">
          <div class="dash-card-title">🔒 Change Password</div>
          <form id="pwd-form" onsubmit="changePassword(event)" style="max-width:420px">
            <div class="form-group" style="margin-bottom:1rem">
              <label class="form-label">Current Password</label>
              <input type="password" id="pwd-old" class="form-control" placeholder="Enter current password">
            </div>
            <div class="form-group" style="margin-bottom:1rem">
              <label class="form-label">New Password</label>
              <input type="password" id="pwd-new" class="form-control" placeholder="Min 8 characters">
            </div>
            <div class="form-group" style="margin-bottom:1.5rem">
              <label class="form-label">Confirm New Password</label>
              <input type="password" id="pwd-confirm" class="form-control" placeholder="Repeat new password">
            </div>
            <button type="submit" class="btn-gold" style="padding:.75rem 2rem">Update Password</button>
          </form>
        </div>
      </div>

    </div><!-- /dash-content -->
  </div><!-- /dash-layout -->
</div>

<!-- Boarding Pass Modal -->
<div class="modal-overlay" id="boarding-pass-modal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div><div class="modal-title">🎫 Boarding Pass</div></div>
      <button class="modal-close" onclick="closeModal('boarding-pass-modal')">✕</button>
    </div>
    <div class="modal-body" id="boarding-pass-content"></div>
  </div>
</div>

<div id="toast-container" class="toast-container"></div>

<script src="../js/main.js?v=20260320"></script>
<script>
const API = '../api';
let currentUser = null;

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  currentUser = JSON.parse(localStorage.getItem('tw_user') || 'null');
  if (!currentUser) {
    showToast('info', 'Login Required', 'Please sign in to access your dashboard.');
    setTimeout(() => window.location.href = '../index.html', 1500);
    return;
  }
  // Normalize camelCase keys (old fake login) → snake_case (real API)
  if (!currentUser.first_name && currentUser.firstName) {
    currentUser.first_name = currentUser.firstName;
    currentUser.last_name  = currentUser.lastName || '';
    localStorage.setItem('tw_user', JSON.stringify(currentUser));
  }
  updateNavUI();
  loadBookings();
  loadNotifications();
  loadProfile();

  document.getElementById('notif-btn')?.addEventListener('click', () => {
    document.getElementById('notif-panel')?.classList.toggle('open');
  });
  document.getElementById('user-avatar-btn')?.addEventListener('click', (e) => {
    e.stopPropagation();
    document.getElementById('user-dropdown')?.classList.toggle('open');
  });
  document.addEventListener('click', () => document.getElementById('user-dropdown')?.classList.remove('open'));
});

function updateNavUI() {
  if (!currentUser) return;
  document.getElementById('nav-login-link').style.display = 'none';
  document.getElementById('user-menu').style.display = 'flex';
  document.getElementById('notif-wrap').style.display = 'flex';
  const fn = currentUser.first_name || currentUser.firstName || '';
  const ln = currentUser.last_name  || currentUser.lastName  || '';
  document.querySelectorAll('.user-display-name').forEach(el => el.textContent = (fn + ' ' + ln).trim());
  document.querySelectorAll('.user-display-email').forEach(el => el.textContent = currentUser.email);
  document.querySelectorAll('.user-avatar-letter').forEach(el => el.textContent = (currentUser.first_name || currentUser.firstName || 'U').charAt(0).toUpperCase());
}

// ── Tab Navigation ────────────────────────────────────────────
function showTab(name, el, ev) {
  if (ev) ev.preventDefault();
  if (event) event.preventDefault();
  document.querySelectorAll('.dash-tab').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.dash-menu a').forEach(a => a.classList.remove('active'));
  const tab = document.getElementById('tab-' + name);
  if (tab) tab.style.display = 'block';
  if (el) el.classList.add('active');
  // Load data for specific tabs
  if (name === 'bookings')      loadBookings();
  if (name === 'notifications') loadNotifications();
  if (name === 'profile')       loadProfile();
}

// ── Load Bookings ─────────────────────────────────────────────
async function loadBookings() {
  const container = document.getElementById('bookings-list');
  if (!container) return;
  const status = document.getElementById('booking-filter')?.value || '';
  const token = localStorage.getItem('tw_token') || sessionStorage.getItem('tw_token') || '';

  // No token — not properly logged in
  if (!token || token === 'null' || token === 'undefined') {
    container.innerHTML = `
      <div style="text-align:center;padding:3rem">
        <div style="font-size:3rem;margin-bottom:1rem">🔐</div>
        <p style="color:var(--gray-600);font-size:1rem;margin-bottom:1.5rem">
          You need to sign in with your account to view bookings.
        </p>
        <a href="../index.html" class="btn-gold"
           style="display:inline-block;padding:.75rem 2rem;text-decoration:none;font-weight:700">
          Sign In to Continue →
        </a>
      </div>`;
    return;
  }

  container.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--gray-400)"><div class="spinner" style="margin:0 auto 1rem"></div>Loading your bookings...</div>';

  try {
    const res  = await fetch('../api/bookings.php?action=my' + (status ? '&status=' + status : ''), {
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' }
    });

    const rawText = await res.text();
    let data;
    try {
      data = JSON.parse(rawText);
    } catch(e) {
      // Show the actual PHP error so we can debug it
      container.innerHTML = `
        <div style="padding:1rem">
          <p style="color:#c62828;font-weight:700;margin-bottom:.5rem">⚠ PHP Error (not valid JSON):</p>
          <pre style="background:#111;color:#f66;padding:1rem;border-radius:8px;font-size:11px;white-space:pre-wrap;word-break:break-all;max-height:200px;overflow:auto">${rawText.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</pre>
          <p style="color:#888;font-size:12px;margin-top:.5rem">Check XAMPP Apache error log for details.</p>
        </div>`;
      return;
    }

    if (!data.success) {
      if (res.status === 401) {
        localStorage.removeItem('tw_token');
        container.innerHTML = `
          <div style="text-align:center;padding:3rem">
            <div style="font-size:3rem;margin-bottom:1rem">⏰</div>
            <p style="color:var(--gray-600);margin-bottom:1.5rem">Session expired. Please sign in again.</p>
            <a href="../index.html" class="btn-gold" style="display:inline-block;padding:.75rem 2rem;text-decoration:none">Sign In Again →</a>
          </div>`;
      } else {
        container.innerHTML = '<div style="padding:1.5rem;color:var(--crimson)">' + (data.message || 'Failed to load bookings.') + '</div>';
      }
      return;
    }

    const bookings = data.data || [];
    if (!bookings.length) {
      container.innerHTML = `
        <div style="text-align:center;padding:3rem;color:var(--gray-400)">
          <div style="font-size:3rem;margin-bottom:1rem">✈️</div>
          <p style="font-size:1rem;margin-bottom:1rem">No bookings yet.</p>
          <a href="../index.html" class="btn-gold" style="display:inline-block;padding:.65rem 1.75rem;text-decoration:none">Search Flights →</a>
        </div>`;
      return;
    }

    // Render bookings list
    container.innerHTML = bookings.map(b => {
      const dep = new Date(b.departure_time);
      const depStr = dep.toLocaleDateString('en-IN', {day:'numeric', month:'short', year:'numeric'});
      const amount = parseFloat(b.total_amount || 0).toLocaleString('en-IN');
      const statusColors = {confirmed:'#27ae60', completed:'#2980b9', cancelled:'#c62828', pending:'#e67e22', 'checked-in':'#8e44ad'};
      const color = statusColors[b.status] || '#888';
      return `<div style="border:1.5px solid #e0ddd8;border-radius:12px;padding:1.25rem;margin-bottom:1rem;background:white;transition:.2s" onmouseover="this.style.borderColor='#c9973a'" onmouseout="this.style.borderColor='#e0ddd8'">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
          <div style="font-family:Georgia,serif;font-weight:700;font-size:1rem;color:#0a1628">📋 ${b.booking_ref}</div>
          <span style="background:${color}22;color:${color};padding:.2rem .75rem;border-radius:100px;font-size:.78rem;font-weight:700;border:1px solid ${color}44">
            ${(b.status||'').charAt(0).toUpperCase()+(b.status||'').slice(1)}
          </span>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem">
          <span style="font-size:1.4rem;font-weight:900;color:#0a1628">${b.origin_code||'—'}</span>
          <span style="color:#c9973a;font-size:1.1rem">→</span>
          <span style="font-size:1.4rem;font-weight:900;color:#0a1628">${b.dest_code||'—'}</span>
          <span style="color:#888;font-size:.85rem;margin-left:.5rem">${b.flight_number||''} · ${depStr}</span>
          <span style="margin-left:auto;font-weight:700;color:#0a1628;font-size:1rem">₹${amount}</span>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          ${b.status === 'confirmed' ? `<button onclick="checkinByRef('${b.booking_ref}')" style="background:#0a1628;color:#e8b85c;border:none;padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.82rem;font-weight:600">✅ Check-in</button>` : ''}
          <button onclick="viewBookingDetail('${b.booking_ref}')" style="background:white;color:#0a1628;border:1.5px solid #ddd;padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.82rem">View Details</button>
          ${b.status === 'confirmed' ? `<button onclick="cancelBooking('${b.booking_ref}')" style="background:white;color:#c62828;border:1.5px solid #fce4ec;padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.82rem">Cancel</button>` : ''}
        </div>
      </div>`;
    }).join('');

  } catch(err) {
    console.error('loadBookings error:', err);
    container.innerHTML = '<div style="padding:1.5rem;color:#c62828">Network error loading bookings. Is XAMPP running?</div>';
  }
}

async function viewBookingDetail(ref) {
  const token = localStorage.getItem('tw_token') || sessionStorage.getItem('tw_token');
  const existingPanel = document.getElementById('detail-panel-' + ref);

  // Toggle: if already open, close it
  if (existingPanel) {
    existingPanel.remove();
    return;
  }

  // Find the booking card and inject detail below it
  const cards = document.querySelectorAll('#bookings-list > div');
  let targetCard = null;
  cards.forEach(card => {
    if (card.innerHTML.includes(ref)) targetCard = card;
  });
  if (!targetCard) { showToast('error','Error','Booking card not found.'); return; }

  // Create loading panel
  const panel = document.createElement('div');
  panel.id = 'detail-panel-' + ref;
  panel.style.cssText = 'background:#f8f6f1;border:1.5px solid #c9973a;border-radius:10px;padding:1.25rem;margin-top:.75rem;animation:fadeIn .3s ease';
  panel.innerHTML = '<div style="text-align:center;color:#888;padding:1rem"><div class="spinner" style="margin:0 auto .75rem"></div>Loading booking details...</div>';
  targetCard.appendChild(panel);

  try {
    const res  = await fetch('../api/bookings.php?action=detail&ref=' + ref, {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await res.json();

    if (!data.success) {
      panel.innerHTML = '<p style="color:#c62828">' + (data.message || 'Failed to load details.') + '</p>';
      return;
    }

    const b   = data.data;
    const dep = new Date(b.departure_time);
    const arr = new Date(b.arrival_time);
    const depStr = dep.toLocaleString('en-IN', {weekday:'short', day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:false});
    const arrStr = arr.toLocaleString('en-IN', {weekday:'short', day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:false});
    const amount  = parseFloat(b.total_amount || 0).toLocaleString('en-IN');
    const statusColor = {confirmed:'#27ae60','checked-in':'#8e44ad',completed:'#2980b9',cancelled:'#c62828',pending:'#e67e22'}[b.status] || '#888';

    // Build passengers HTML
    const paxHtml = (b.passengers && b.passengers.length)
      ? b.passengers.map((p,i) => `
          <div style="display:flex;gap:1rem;align-items:center;padding:.6rem .75rem;background:white;border-radius:7px;margin-bottom:.5rem;border:1px solid #eee">
            <div style="width:32px;height:32px;border-radius:50%;background:#0a1628;color:#e8b85c;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">${i+1}</div>
            <div style="flex:1">
              <div style="font-weight:600;color:#0a1628">${p.first_name} ${p.last_name}</div>
              <div style="font-size:.78rem;color:#888">${p.class ? p.class.charAt(0).toUpperCase()+p.class.slice(1) : 'Economy'} · Seat: ${p.seat_number || 'TBA'}</div>
            </div>
            <span style="font-size:.72rem;padding:.2rem .6rem;border-radius:100px;background:${p.checkin_status?'#e8f5e9':'#fff8ee'};color:${p.checkin_status?'#27ae60':'#e67e22'};font-weight:600">
              ${p.checkin_status ? '✅ Checked in' : '⏳ Pending'}
            </span>
          </div>`).join('')
      : '<p style="color:#888;font-size:.875rem">No passenger details available.</p>';

    panel.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <div>
          <div style="font-size:1.1rem;font-weight:700;color:#0a1628;font-family:Georgia,serif">📋 ${b.booking_ref}</div>
          <div style="font-size:.78rem;color:#888;margin-top:.2rem">${b.flight_number} · ${b.flight_type === 'international' ? '🌍 International' : '🇮🇳 Domestic'}</div>
        </div>
        <div style="text-align:right">
          <span style="background:${statusColor}22;color:${statusColor};padding:.25rem .8rem;border-radius:100px;font-size:.8rem;font-weight:700;border:1px solid ${statusColor}44">
            ${(b.status||'').charAt(0).toUpperCase()+(b.status||'').slice(1)}
          </span>
          <div style="font-size:1.1rem;font-weight:700;color:#0a1628;margin-top:.4rem">₹${amount}</div>
        </div>
      </div>

      <!-- Route -->
      <div style="background:white;border-radius:10px;padding:1rem;margin-bottom:.75rem;border:1px solid #eee">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem">
          <div style="text-align:center">
            <div style="font-size:2rem;font-weight:900;color:#0a1628;font-family:Georgia,serif">${b.origin_code}</div>
            <div style="font-size:.78rem;color:#888">${b.origin_city}</div>
            <div style="font-size:.9rem;font-weight:600;color:#0a1628;margin-top:.25rem">${dep.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
          </div>
          <div style="flex:1;text-align:center">
            <div style="font-size:.75rem;color:#c9973a;font-weight:600">${b.duration_minutes ? Math.floor(b.duration_minutes/60)+'h '+(b.duration_minutes%60)+'m' : ''}</div>
            <div style="height:2px;background:linear-gradient(90deg,#0a1628,#c9973a);border-radius:2px;margin:.35rem 0"></div>
            <div style="font-size:.72rem;color:#27ae60;font-weight:600">Non-stop ✈</div>
          </div>
          <div style="text-align:center">
            <div style="font-size:2rem;font-weight:900;color:#0a1628;font-family:Georgia,serif">${b.dest_code}</div>
            <div style="font-size:.78rem;color:#888">${b.dest_city}</div>
            <div style="font-size:.9rem;font-weight:600;color:#0a1628;margin-top:.25rem">${arr.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f0eee9">
          <div style="text-align:center"><div style="font-size:.68rem;color:#aaa;text-transform:uppercase;letter-spacing:.05em">Departure</div><div style="font-size:.8rem;font-weight:600;color:#0a1628;margin-top:.15rem">${depStr}</div></div>
          <div style="text-align:center"><div style="font-size:.68rem;color:#aaa;text-transform:uppercase;letter-spacing:.05em">Arrival</div><div style="font-size:.8rem;font-weight:600;color:#0a1628;margin-top:.15rem">${arrStr}</div></div>
          <div style="text-align:center"><div style="font-size:.68rem;color:#aaa;text-transform:uppercase;letter-spacing:.05em">Aircraft</div><div style="font-size:.8rem;font-weight:600;color:#0a1628;margin-top:.15rem">${b.aircraft || 'Titan Wing'}</div></div>
        </div>
      </div>

      <!-- Passengers -->
      <div style="margin-bottom:.75rem">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:.6rem">👤 Passengers (${b.total_passengers || 1})</div>
        ${paxHtml}
      </div>

      <!-- Payment -->
      <div style="background:white;border-radius:8px;padding:.85rem 1rem;margin-bottom:.75rem;border:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-size:.72rem;color:#aaa;text-transform:uppercase;letter-spacing:.05em">Payment</div>
          <div style="font-weight:600;color:#0a1628;margin-top:.15rem">${(b.payment_status||'').charAt(0).toUpperCase()+(b.payment_status||'').slice(1)} · ${b.payment_method||'Card'}</div>
        </div>
        <div style="font-size:1.2rem;font-weight:700;color:#0a1628">₹${amount}</div>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        ${b.status === 'confirmed' ? `<button onclick="checkinByRef('${b.booking_ref}')" style="background:#0a1628;color:#e8b85c;border:none;padding:.55rem 1.25rem;border-radius:7px;cursor:pointer;font-size:.85rem;font-weight:600">✅ Online Check-in</button>` : ''}
        ${b.status === 'confirmed' ? `<button onclick="cancelBooking('${b.booking_ref}')" style="background:white;color:#c62828;border:1.5px solid #fce4ec;padding:.55rem 1.25rem;border-radius:7px;cursor:pointer;font-size:.85rem">❌ Cancel Booking</button>` : ''}
        <button onclick="document.getElementById('detail-panel-${b.booking_ref}').remove()" style="background:white;color:#888;border:1.5px solid #ddd;padding:.55rem 1.25rem;border-radius:7px;cursor:pointer;font-size:.85rem">✕ Close</button>
      </div>`;

  } catch(err) {
    panel.innerHTML = '<p style="color:#c62828;padding:.5rem">Error loading details. Try again.</p>';
    console.error(err);
  }
}

// ── Check-in ──────────────────────────────────────────────────
async function doCheckin() {
  const ref = document.getElementById('ci-ref').value.trim().toUpperCase();
  if (!ref) { showToast('error', 'Error', 'Enter a booking reference.'); return; }
  checkinByRef(ref);
}

async function checkinByRef(ref) {
  showLoading('Processing check-in...');
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/bookings.php?action=checkin&ref=${ref}`, {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    hideLoading();
    const data = await res.json();
    if (data.success) {
      showToast('success', '✅ Check-in Complete!', data.message);
      loadBookings();
      document.getElementById('checkin-result').innerHTML = `<div style="background:#e8f5e9;border-radius:var(--radius-md);padding:1rem;color:#2e7d32"><strong>✅ Check-in successful!</strong> Boarding pass sent to your email.</div>`;
    } else {
      showToast('error', 'Check-in Failed', data.message);
    }
  } catch (e) {
    hideLoading();
    showToast('error', 'Error', 'Network error. Try again.');
  }
}

// ── Cancel Booking ────────────────────────────────────────────
async function cancelBooking(ref) {
  if (!confirm(`Cancel booking ${ref}? Refund depends on time before departure.`)) return;
  showLoading('Cancelling booking...');
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/bookings.php?action=cancel&ref=${ref}`, {
      method: 'DELETE',
      headers: { 'Authorization': 'Bearer ' + token }
    });
    hideLoading();
    const data = await res.json();
    if (data.success) {
      showToast('success', 'Booking Cancelled', data.message);
      loadBookings();
    } else {
      showToast('error', 'Error', data.message);
    }
  } catch (e) { hideLoading(); showToast('error', 'Error', 'Network error.'); }
}

// ── Boarding Pass ─────────────────────────────────────────────
function viewBoardingPass(booking) {
  const dep = new Date(booking.departure_time);
  const arr = new Date(booking.arrival_time);
  const pax = booking.passengers?.[0] || { first_name: currentUser?.first_name, last_name: currentUser?.last_name };
  const seat = pax.seat_number || 'TBA';
  const gate = 'B' + (Math.floor(Math.random()*20)+1);

  document.getElementById('boarding-pass-content').innerHTML = `
    <div class="boarding-pass">
      <div class="bp-header">
        <div><div class="bp-airline">✈ Titan Wing Airlines</div><div class="bp-flight-no">Flight ${booking.flight_number} · ${booking.status.toUpperCase()}</div></div>
        <div style="text-align:right"><div style="color:var(--gold-light);font-weight:700">${dep.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'})}</div><div style="font-size:.8rem;color:rgba(255,255,255,.5)">Boarding Pass</div></div>
      </div>
      <div class="bp-body">
        <div style="margin-bottom:.75rem"><div style="font-size:.7rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:.1em">Passenger</div><div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--navy)">${pax.first_name} ${pax.last_name}</div></div>
        <div class="bp-route">
          <div class="bp-airport"><div class="bp-code">${booking.origin_code}</div><div class="bp-city">${booking.origin_city}</div></div>
          <div class="bp-arrow">→</div>
          <div class="bp-airport"><div class="bp-code">${booking.dest_code}</div><div class="bp-city">${booking.dest_city}</div></div>
        </div>
        <div class="bp-details">
          <div class="bp-detail-item"><div class="bp-detail-label">Departs</div><div class="bp-detail-value">${dep.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div></div>
          <div class="bp-detail-item"><div class="bp-detail-label">Arrives</div><div class="bp-detail-value">${arr.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div></div>
          <div class="bp-detail-item"><div class="bp-detail-label">Seat</div><div class="bp-detail-value">${seat}</div></div>
          <div class="bp-detail-item"><div class="bp-detail-label">Gate</div><div class="bp-detail-value">${gate}</div></div>
        </div>
        <hr class="bp-divider">
        <div class="bp-barcode">|||||||||||||||||||||||||||||||||||||</div>
        <div class="bp-booking-ref">Booking Reference: <strong>${booking.booking_ref}</strong></div>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:center">
      <button class="btn-details" onclick="window.print()">🖨 Print</button>
      <button class="btn-gold" onclick="showToast('success','Downloaded!','Boarding pass saved.')">📥 Download</button>
    </div>
  `;
  openModal('boarding-pass-modal');
}

function downloadTicket(ref) {
  showToast('info', 'Downloading...', `Ticket for ${ref} will be downloaded shortly.`);
}

// ── Load Notifications ────────────────────────────────────────
async function loadNotifications() {
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/bookings.php?action=notifications`, {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const data  = await res.json();
    if (!data.success) return;
    const { notifications, unread_count } = data.data;

    // Update badge
    const badge = document.getElementById('notif-badge');
    if (badge) { badge.textContent = unread_count; badge.style.display = unread_count > 0 ? 'flex' : 'none'; }

    const icons = { booking:'🎫', flight:'✈', checkin:'✅', promotion:'🏷', system:'⚙' };
    const html  = notifications.length ? notifications.map(n => `
      <div class="notif-item ${n.is_read?'':'unread'}">
        <div class="notif-icon ${n.type}">${icons[n.type] || '🔔'}</div>
        <div class="notif-content">
          <div class="notif-title">${n.title}</div>
          <div class="notif-msg">${n.message}</div>
          <div class="notif-time">${new Date(n.created_at).toLocaleString('en-IN')}</div>
        </div>
      </div>
    `).join('') : '<div style="padding:2rem;text-align:center;color:var(--gray-400)">No notifications yet.</div>';

    document.getElementById('notif-list').innerHTML   = html;
    document.getElementById('notifications-list').innerHTML = html;
  } catch (e) {}
}

async function markAllRead() {
  const token = localStorage.getItem('tw_token');
  await fetch(`${API}/bookings.php?action=mark_read`, { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } });
  loadNotifications();
  showToast('success', 'Done', 'All notifications marked as read.');
}

// ── Load Profile ──────────────────────────────────────────────
async function loadProfile() {
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/user.php?action=get`, { headers: { 'Authorization': 'Bearer ' + token } });
    const data  = await res.json();
    if (!data.success) return;
    const p = data.data;
    document.getElementById('pf-first').value       = p.first_name || '';
    document.getElementById('pf-last').value        = p.last_name  || '';
    document.getElementById('pf-email').value       = p.email      || '';
    document.getElementById('pf-phone').value       = p.phone      || '';
    document.getElementById('pf-dob').value         = p.dob        || '';
    document.getElementById('pf-gender').value      = p.gender     || '';
    document.getElementById('pf-nationality').value = p.nationality || '';
    document.getElementById('pf-passport').value   = p.passport_no || '';
  } catch (e) {}
}

async function saveProfile(e) {
  e.preventDefault();
  showLoading('Saving profile...');
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/user.php?action=update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({
        first_name: document.getElementById('pf-first').value,
        last_name:  document.getElementById('pf-last').value,
        phone:      document.getElementById('pf-phone').value,
        dob:        document.getElementById('pf-dob').value,
        gender:     document.getElementById('pf-gender').value,
        nationality: document.getElementById('pf-nationality').value,
        passport_no: document.getElementById('pf-passport').value,
      })
    });
    hideLoading();
    const data = await res.json();
    showToast(data.success ? 'success' : 'error', data.success ? 'Profile Saved' : 'Error', data.message);
    if (data.success) { const u = JSON.parse(localStorage.getItem('tw_user')||'{}'); u.first_name = document.getElementById('pf-first').value; localStorage.setItem('tw_user', JSON.stringify(u)); updateNavUI(); }
  } catch (e) { hideLoading(); showToast('error', 'Error', 'Network error.'); }
}

async function changePassword(e) {
  e.preventDefault();
  const newPass = document.getElementById('pwd-new').value;
  if (newPass !== document.getElementById('pwd-confirm').value) { showToast('error', 'Mismatch', 'Passwords do not match.'); return; }
  showLoading('Updating password...');
  try {
    const token = localStorage.getItem('tw_token');
    const res   = await fetch(`${API}/user.php?action=change_password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
      body: JSON.stringify({ old_password: document.getElementById('pwd-old').value, new_password: newPass })
    });
    hideLoading();
    const data = await res.json();
    showToast(data.success ? 'success' : 'error', data.success ? 'Password Updated' : 'Error', data.message);
    if (data.success) document.getElementById('pwd-form').reset();
  } catch (e) { hideLoading(); showToast('error', 'Error', 'Network error.'); }
}

function doLogout() {
  localStorage.removeItem('tw_user');
  localStorage.removeItem('tw_token');
  window.location.href = '../index.html';
}
</script>
</body>
</html>
