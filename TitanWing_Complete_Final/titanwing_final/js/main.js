/* ============================================================
   TITAN WING AIRLINE — Main JavaScript
   Handles: Auth, Search, Booking, AI Chat, OTP, Notifications
   ============================================================ */

'use strict';

// ============================================================
// APP STATE
// ============================================================
const TW = {
  user: JSON.parse(localStorage.getItem('tw_user') || 'null'),
  cart: JSON.parse(localStorage.getItem('tw_cart') || 'null'),
  airports: [],
  searchResults: [],
  selectedFlight: null,
  selectedSeats: [],
  currentBookingStep: 1,
  notifications: [],
  aiChatHistory: [],
  otpTimer: null,
};

// ============================================================
// AIRPORT DATA (matches DB)
// ============================================================
const AIRPORTS = [
  { code:'DEL', city:'New Delhi', name:'Indira Gandhi International Airport', country:'India', type:'intl' },
  { code:'BOM', city:'Mumbai', name:'Chhatrapati Shivaji Maharaj Intl Airport', country:'India', type:'intl' },
  { code:'BLR', city:'Bangalore', name:'Kempegowda International Airport', country:'India', type:'intl' },
  { code:'MAA', city:'Chennai', name:'Chennai International Airport', country:'India', type:'intl' },
  { code:'HYD', city:'Hyderabad', name:'Rajiv Gandhi International Airport', country:'India', type:'intl' },
  { code:'CCU', city:'Kolkata', name:'Netaji Subhas Chandra Bose Intl Airport', country:'India', type:'intl' },
  { code:'DXB', city:'Dubai', name:'Dubai International Airport', country:'UAE', type:'intl' },
  { code:'LHR', city:'London', name:'Heathrow Airport', country:'United Kingdom', type:'intl' },
  { code:'JFK', city:'New York', name:'John F. Kennedy International Airport', country:'USA', type:'intl' },
  { code:'SIN', city:'Singapore', name:'Changi Airport', country:'Singapore', type:'intl' },
  { code:'BKK', city:'Bangkok', name:'Suvarnabhumi Airport', country:'Thailand', type:'intl' },
  { code:'KUL', city:'Kuala Lumpur', name:'KL International Airport', country:'Malaysia', type:'intl' },
  { code:'SYD', city:'Sydney', name:'Sydney Airport', country:'Australia', type:'intl' },
  { code:'CDG', city:'Paris', name:'Charles de Gaulle Airport', country:'France', type:'intl' },
  { code:'FRA', city:'Frankfurt', name:'Frankfurt Airport', country:'Germany', type:'intl' },
  { code:'AMD', city:'Ahmedabad', name:'Sardar Vallabhbhai Patel Intl Airport', country:'India', type:'dom' },
  { code:'PNQ', city:'Pune', name:'Pune Airport', country:'India', type:'dom' },
  { code:'COK', city:'Kochi', name:'Cochin International Airport', country:'India', type:'dom' },
  { code:'GAU', city:'Guwahati', name:'Lokpriya Gopinath Bordoloi Airport', country:'India', type:'dom' },
  { code:'VNS', city:'Varanasi', name:'Lal Bahadur Shastri Airport', country:'India', type:'dom' },
];

// ============================================================
// MOCK FLIGHT DATA (simulates DB response)
// ============================================================
function generateMockFlights(from, to, date) {
  const fromAP = AIRPORTS.find(a => a.code === from);
  const toAP = AIRPORTS.find(a => a.code === to);
  if (!fromAP || !toAP) return [];
  const isIntl = fromAP.country !== toAP.country || toAP.country !== 'India';
  const priceMultiplier = isIntl ? 8 : 1;
  const flights = [];
  const times = [
    { dep:'06:00', dur:130+Math.floor(Math.random()*60) },
    { dep:'10:30', dur:130+Math.floor(Math.random()*60) },
    { dep:'14:15', dur:130+Math.floor(Math.random()*60) },
    { dep:'19:45', dur:130+Math.floor(Math.random()*60) },
  ];
  times.forEach((t, i) => {
    const depTime = new Date(`${date}T${t.dep}:00`);
    const arrTime = new Date(depTime.getTime() + t.dur * 60000);
    const ecoPrice = Math.round((3500 + Math.random()*2000) * priceMultiplier / 100) * 100;
    flights.push({
      id: `TW${100+i*100+Math.floor(Math.random()*99)}`,
      flightNo: `TW${String(100+i*100).padStart(3,'0')}`,
      aircraft: ['Boeing 737-800','Airbus A320','Boeing 777','Airbus A380'][i % 4],
      from: fromAP.code, fromCity: fromAP.city,
      to: toAP.code, toCity: toAP.city,
      departure: depTime.toISOString(),
      arrival: arrTime.toISOString(),
      duration: t.dur,
      stops: i === 2 ? 1 : 0,
      stopCity: i === 2 ? 'DEL' : null,
      ecoSeats: Math.floor(Math.random()*80)+40,
      bizSeats: Math.floor(Math.random()*12)+6,
      firstSeats: Math.floor(Math.random()*4)+2,
      ecoPrice: ecoPrice,
      bizPrice: ecoPrice * 2.8,
      firstPrice: ecoPrice * 5.2,
      type: isIntl ? 'international' : 'domestic',
    });
  });
  return flights;
}

// ============================================================
// DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initSearchWidget();
  initAutocomplete();
  initModals();
  initOTP();
  initAIChat();
  initScrollReveal();
  initNotifications();
  updateAuthUI();
  
  // Check if user is logged in for dashboard pages
  if (document.getElementById('user-dashboard')) initDashboard();
  if (document.getElementById('admin-panel')) initAdmin();
  if (document.getElementById('about-page')) initAbout();

  // Navbar scroll
  window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
  });
});

// ============================================================
// NAVBAR
// ============================================================
function initNavbar() {
  // Hamburger
  const ham = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobile-nav');
  if (ham) ham.addEventListener('click', () => {
    mobileNav?.classList.toggle('open');
  });

  // User dropdown
  const avatarBtn = document.getElementById('user-avatar-btn');
  const dropdown = document.getElementById('user-dropdown');
  if (avatarBtn) {
    avatarBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown?.classList.toggle('open');
    });
    document.addEventListener('click', () => dropdown?.classList.remove('open'));
  }

  // Notifications toggle
  const notifBtn = document.getElementById('notif-btn');
  const notifPanel = document.getElementById('notif-panel');
  if (notifBtn) {
    notifBtn.addEventListener('click', () => {
      notifPanel?.classList.toggle('open');
    });
  }
}

// ============================================================
// SEARCH WIDGET
// ============================================================
function initSearchWidget() {
  // Trip type tabs
  document.querySelectorAll('.search-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const type = tab.dataset.type;
      const returnField = document.getElementById('return-date-field');
      if (returnField) {
        returnField.style.display = type === 'round-trip' ? 'block' : 'none';
      }
      const sf = document.querySelector('.search-fields');
      if (sf) {
        sf.className = 'search-fields' + (type === 'round-trip' ? ' round-trip' : '');
      }
    });
  });

  // Swap button
  const swapBtn = document.getElementById('swap-btn');
  if (swapBtn) {
    swapBtn.addEventListener('click', () => {
      const fromInput = document.getElementById('from-input');
      const toInput = document.getElementById('to-input');
      if (fromInput && toInput) {
        const tmp = fromInput.value;
        fromInput.value = toInput.value;
        toInput.value = tmp;
        const tmpData = fromInput.dataset.code;
        fromInput.dataset.code = toInput.dataset.code;
        toInput.dataset.code = tmpData;
      }
    });
  }

  // Search form submit
  const searchForm = document.getElementById('flight-search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleFlightSearch();
    });
  }

  // Set min date to today
  const today = new Date().toISOString().split('T')[0];
  document.querySelectorAll('input[type="date"]').forEach(input => {
    input.min = today;
    if (!input.value) input.value = today;
  });
}

// ============================================================
// AUTOCOMPLETE
// ============================================================
function initAutocomplete() {
  const inputs = [
    { inputId: 'from-input', listId: 'from-list' },
    { inputId: 'to-input', listId: 'to-list' },
  ];
  inputs.forEach(({ inputId, listId }) => {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    if (!input || !list) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      if (!q) { list.classList.remove('open'); return; }
      const matches = AIRPORTS.filter(a =>
        a.code.toLowerCase().includes(q) ||
        a.city.toLowerCase().includes(q) ||
        a.name.toLowerCase().includes(q) ||
        a.country.toLowerCase().includes(q)
      ).slice(0, 6);
      renderAutocomplete(list, matches, input);
    });

    input.addEventListener('focus', () => {
      if (input.value) input.dispatchEvent(new Event('input'));
    });

    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !list.contains(e.target)) {
        list.classList.remove('open');
      }
    });
  });
}

function renderAutocomplete(list, airports, input) {
  if (!airports.length) { list.classList.remove('open'); return; }
  list.innerHTML = airports.map(a => `
    <div class="ac-item" data-code="${a.code}" data-city="${a.city}">
      <span class="ac-code">${a.code}</span>
      <div class="ac-details">
        <div class="ac-city">${a.city} — ${a.name}</div>
        <div class="ac-country">${a.country}</div>
      </div>
      <span class="ac-badge ${a.type}">${a.type === 'intl' ? 'International' : 'Domestic'}</span>
    </div>
  `).join('');
  list.classList.add('open');
  list.querySelectorAll('.ac-item').forEach(item => {
    item.addEventListener('click', () => {
      input.value = `${item.dataset.city} (${item.dataset.code})`;
      input.dataset.code = item.dataset.code;
      list.classList.remove('open');
    });
  });
}

// ============================================================
// FLIGHT SEARCH
// ============================================================
async function handleFlightSearch() {
  const fromInput = document.getElementById('from-input');
  const toInput = document.getElementById('to-input');
  const dateInput = document.getElementById('dep-date');
  const passInput = document.getElementById('passengers');
  const classInput = document.getElementById('cabin-class');

  const fromCode = fromInput?.dataset.code || extractCode(fromInput?.value || '');
  const toCode = toInput?.dataset.code || extractCode(toInput?.value || '');
  const date = dateInput?.value;
  const passengers = parseInt(passInput?.value || '1');
  const cabinClass = classInput?.value || 'economy';

  if (!fromCode || !toCode || !date) {
    showToast('error', 'Missing Info', 'Please enter origin, destination, and date.');
    return;
  }
  if (fromCode === toCode) {
    showToast('error', 'Invalid Route', 'Origin and destination cannot be the same.');
    return;
  }

  showLoading('Searching flights...');

  // Try real API first, fall back to mock if no flights in DB
  try {
    const res  = await fetch(`api/flights.php?action=search&from=${fromCode}&to=${toCode}&date=${date}&passengers=${passengers}&class=${cabinClass}`);
    const data = await res.json();
    hideLoading();

    let results = [];
    // API returns { outbound: [...] } for one-way
    const flightList = data.data?.outbound || data.data || [];
    if (data.success && flightList.length > 0) {
      // Map real DB flights to the format renderFlightResults expects
      results = flightList.map(f => ({
        id:          String(f.id),                  // always string for consistent comparison
        flightNo:    f.flight_number,
        aircraft:    f.aircraft_model || 'Titan Wing Aircraft',
        from:        f.origin_code,
        fromCity:    f.origin_city,
        to:          f.dest_code,
        toCity:      f.dest_city,
        departure:   f.departure_time,
        arrival:     f.arrival_time,
        duration:    f.duration_minutes || 120,
        stops:       0,
        stopCity:    null,
        ecoSeats:    f.available_economy  || 50,
        bizSeats:    f.available_business || 12,
        firstSeats:  f.available_first    || 4,
        ecoPrice:    parseFloat(f.economy_price),
        bizPrice:    parseFloat(f.business_price),
        firstPrice:  parseFloat(f.first_class_price),
        type:        f.flight_type || 'domestic',
        isRealFlight: true
      }));
      showToast('success', 'Flights Found!', `${results.length} flight(s) available.`);
    } else {
      // No real flights in DB — use mock data for demo
      results = generateMockFlights(fromCode, toCode, date);
      if (results.length > 0) showToast('info', 'Demo Flights', 'Showing sample flights. Add real flights via Admin Panel.');
      else showToast('info', 'No Flights', 'No flights found for this route and date.');
    }

    TW.searchResults = results;
    TW.searchParams  = { fromCode, toCode, date, passengers, cabinClass };
    renderFlightResults(results, cabinClass);
    const section = document.getElementById('flight-results-section');
    if (section) {
      section.style.display = 'block';
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  } catch (err) {
    hideLoading();
    // Network error — fall back to mock
    const results = generateMockFlights(fromCode, toCode, date);
    TW.searchResults = results;
    TW.searchParams  = { fromCode, toCode, date, passengers, cabinClass };
    renderFlightResults(results, cabinClass);
    const section = document.getElementById('flight-results-section');
    if (section) { section.style.display = 'block'; section.scrollIntoView({ behavior:'smooth', block:'start' }); }
    console.error('Flight search error:', err);
  }
}

function extractCode(val) {
  const m = val.match(/\(([A-Z]{3})\)/);
  return m ? m[1] : val.toUpperCase().trim().substring(0, 3);
}

function renderFlightResults(flights, cabinClass) {
  const container = document.getElementById('flight-results-container');
  const countEl = document.getElementById('results-count');
  if (!container) return;

  if (countEl) countEl.textContent = `${flights.length} flight${flights.length !== 1 ? 's' : ''} found`;

  if (!flights.length) {
    container.innerHTML = `
      <div style="text-align:center;padding:3rem;color:var(--gray-400);">
        <div style="font-size:3rem;margin-bottom:1rem;">✈️</div>
        <div style="font-size:1.1rem;font-weight:600;">No flights found</div>
        <div style="font-size:0.875rem;margin-top:0.5rem;">Try different dates or destinations</div>
      </div>
    `;
    return;
  }

  container.innerHTML = flights.map(f => {
    const dep = new Date(f.departure);
    const arr = new Date(f.arrival);
    const depStr = dep.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', hour12:false });
    const arrStr = arr.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', hour12:false });
    const dur = `${Math.floor(f.duration/60)}h ${f.duration%60}m`;
    const price = cabinClass === 'business' ? f.bizPrice : cabinClass === 'first' ? f.firstPrice : f.ecoPrice;
    const seats = cabinClass === 'business' ? f.bizSeats : cabinClass === 'first' ? f.firstSeats : f.ecoSeats;
    const priceStr = `₹${price.toLocaleString('en-IN')}`;

    return `
      <div class="flight-card" data-flight-id="${f.id}">
        <div class="flight-card-top">
          <div class="flight-airline">
            <div class="flight-logo">✈</div>
            <div>
              <div style="font-weight:700;font-size:0.875rem;color:var(--navy);">Titan Wing</div>
              <div class="flight-number">${f.flightNo}</div>
            </div>
          </div>
          <div class="flight-time-block">
            <div class="flight-time">${depStr}</div>
            <div class="flight-airport">${f.from}</div>
          </div>
          <div class="flight-duration">
            <div class="duration-time">${dur}</div>
            <div class="duration-line"></div>
            <div class="duration-stops">${f.stops === 0 ? 'Non-stop' : `1 stop (${f.stopCity})`}</div>
          </div>
          <div class="flight-time-block">
            <div class="flight-time">${arrStr}</div>
            <div class="flight-airport">${f.to}</div>
          </div>
          <div class="flight-price-block">
            <div class="price-from">from</div>
            <div class="price-amount">${priceStr}</div>
            <div class="price-class">${cabinClass.charAt(0).toUpperCase()+cabinClass.slice(1)} · ${seats} seats left</div>
          </div>
          <button class="btn-book-now" onclick="openBookingModal('${f.id}','${cabinClass}')">
            Book Now →
          </button>
        </div>
        <div class="flight-card-actions">
          <div class="flight-badges">
            <span class="badge ${f.type === 'international' ? 'badge-intl' : 'badge-dom'}">${f.type}</span>
            <span class="badge badge-eco">Economy ₹${f.ecoPrice.toLocaleString('en-IN')}</span>
            <span class="badge badge-biz">Business ₹${f.bizPrice.toLocaleString('en-IN')}</span>
            <span class="badge badge-first">First ₹${f.firstPrice.toLocaleString('en-IN')}</span>
          </div>
          <button class="btn-details" onclick="showFlightDetails('${f.id}')">✈ ${f.aircraft}</button>
        </div>
      </div>
    `;
  }).join('');
}

function showFlightDetails(flightId) {
  const f = TW.searchResults.find(f => String(f.id) === String(flightId));
  if (!f) return;
  const dep = new Date(f.departure);
  const arr = new Date(f.arrival);
  showToast('info', 'Flight Details', `${f.flightNo}: ${dep.toLocaleString()} → ${arr.toLocaleString()} on ${f.aircraft}`);
}

// ============================================================
// BOOKING MODAL
// ============================================================
function openBookingModal(flightId, cabinClass) {
  if (!TW.user) {
    showToast('info', 'Login Required', 'Please login to book a flight.');
    openModal('login-modal');
    return;
  }
  // Use == (loose) so '42' == 42, also try String comparison
  const f = TW.searchResults.find(f => String(f.id) === String(flightId));
  if (!f) {
    console.error('Flight not found:', flightId, 'Available:', TW.searchResults.map(r => r.id));
    showToast('error', 'Error', 'Could not find flight. Please search again.');
    return;
  }

  TW.selectedFlight = { ...f, selectedClass: cabinClass };
  TW.selectedSeats = [];
  TW.currentBookingStep = 1;

  const modal = document.getElementById('booking-modal');
  if (!modal) { openFallbackBookingPage(f, cabinClass); return; }

  populateBookingModal(f, cabinClass);
  openModal('booking-modal');
}

function populateBookingModal(f, cabinClass) {
  const price = cabinClass === 'business' ? f.bizPrice : cabinClass === 'first' ? f.firstPrice : f.ecoPrice;
  const dep = new Date(f.departure);
  const arr = new Date(f.arrival);

  // Summary
  const sumEl = document.getElementById('booking-flight-summary');
  if (sumEl) {
    sumEl.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;background:var(--off-white);border-radius:var(--radius-md);padding:1rem;">
        <div style="text-align:center;">
          <div style="font-size:1.8rem;font-weight:900;font-family:var(--font-display);color:var(--navy);">${f.from}</div>
          <div style="font-size:0.75rem;color:var(--gray-400);">${f.fromCity}</div>
          <div style="font-weight:700;color:var(--navy);">${dep.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
        </div>
        <div style="text-align:center;flex:1;padding:0 1rem;">
          <div style="font-size:0.75rem;color:var(--gray-400);">${Math.floor(f.duration/60)}h ${f.duration%60}m</div>
          <div style="height:2px;background:var(--gray-200);margin:0.4rem 0;"></div>
          <div style="font-size:0.75rem;color:var(--gold);font-weight:600;">${f.stops === 0 ? 'Non-stop' : '1 stop'}</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:1.8rem;font-weight:900;font-family:var(--font-display);color:var(--navy);">${f.to}</div>
          <div style="font-size:0.75rem;color:var(--gray-400);">${f.toCity}</div>
          <div style="font-weight:700;color:var(--navy);">${arr.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
        </div>
        <div style="text-align:right;margin-left:1rem;">
          <div style="font-size:1.3rem;font-weight:700;font-family:var(--font-display);color:var(--navy);">₹${price.toLocaleString('en-IN')}</div>
          <div style="font-size:0.75rem;color:var(--gray-400);">${cabinClass}</div>
          <div style="font-size:0.75rem;color:var(--gray-400);">${f.flightNo}</div>
        </div>
      </div>
    `;
  }

  // Set step 1
  goToBookingStep(1);
}

function goToBookingStep(step) {
  TW.currentBookingStep = step;
  document.querySelectorAll('.booking-step-pane').forEach((pane, i) => {
    pane.style.display = (i + 1 === step) ? 'block' : 'none';
  });
  document.querySelectorAll('.step-item').forEach((item, i) => {
    item.classList.remove('active', 'completed');
    if (i + 1 < step) item.classList.add('completed');
    if (i + 1 === step) item.classList.add('active');
  });

  if (step === 2) renderSeatMap();
  if (step === 3) renderPassengerForms();
  if (step === 4) renderPaymentSummary();
}

function renderSeatMap() {
  const container = document.getElementById('seat-map');
  if (!container) return;
  const f = TW.selectedFlight;
  const cabinClass = f.selectedClass;
  const passengers = TW.searchParams?.passengers || 1;

  let html = '';
  // First class (rows 1-3)
  if (cabinClass === 'first' || cabinClass === 'all') {
    html += `<div class="seat-class-label" style="background:linear-gradient(135deg,#fff3e0,#ffe0b2);color:#e65100;">✨ FIRST CLASS</div>`;
    for (let r = 1; r <= 3; r++) {
      html += `<div class="seat-row"><span class="seat-row-num">${r}</span>`;
      ['A','C'].forEach(s => {
        const occ = Math.random() < 0.3;
        html += `<button class="seat-btn first ${occ?'occupied':''}" onclick="selectSeat('${r}${s}','first',this)" data-seat="${r}${s}">${r}${s}</button>`;
      });
      html += `<div class="seat-aisle"></div>`;
      ['D','F'].forEach(s => {
        const occ = Math.random() < 0.3;
        html += `<button class="seat-btn first ${occ?'occupied':''}" onclick="selectSeat('${r}${s}','first',this)" data-seat="${r}${s}">${r}${s}</button>`;
      });
      html += `</div>`;
    }
  }

  // Business class (rows 4-9)
  if (cabinClass === 'business' || cabinClass === 'all') {
    html += `<div class="seat-class-label" style="background:linear-gradient(135deg,#e3f0ff,#bbdefb);color:#1565c0;">💼 BUSINESS CLASS</div>`;
    for (let r = 4; r <= 9; r++) {
      html += `<div class="seat-row"><span class="seat-row-num">${r}</span>`;
      ['A','C'].forEach(s => {
        const occ = Math.random() < 0.35;
        html += `<button class="seat-btn business ${occ?'occupied':''}" onclick="selectSeat('${r}${s}','business',this)" data-seat="${r}${s}">${r}${s}</button>`;
      });
      html += `<div class="seat-aisle"></div>`;
      ['D','F'].forEach(s => {
        const occ = Math.random() < 0.35;
        html += `<button class="seat-btn business ${occ?'occupied':''}" onclick="selectSeat('${r}${s}','business',this)" data-seat="${r}${s}">${r}${s}</button>`;
      });
      html += `</div>`;
    }
  }

  // Economy (rows 10-32)
  html += `<div class="seat-class-label" style="background:linear-gradient(135deg,#e8f5e9,#c8e6c9);color:#2e7d32;">🪑 ECONOMY CLASS</div>`;
  for (let r = 10; r <= 32; r++) {
    html += `<div class="seat-row"><span class="seat-row-num">${r}</span>`;
    ['A','B','C'].forEach(s => {
      const occ = Math.random() < 0.45;
      const isWin = s === 'A';
      html += `<button class="seat-btn economy ${occ?'occupied':''} ${isWin?'window':''}" onclick="selectSeat('${r}${s}','economy',this)" data-seat="${r}${s}">${r}${s}</button>`;
    });
    html += `<div class="seat-aisle"></div>`;
    ['D','E','F'].forEach(s => {
      const occ = Math.random() < 0.45;
      const isWin = s === 'F';
      html += `<button class="seat-btn economy ${occ?'occupied':''} ${isWin?'window':''}" onclick="selectSeat('${r}${s}','economy',this)" data-seat="${r}${s}">${r}${s}</button>`;
    });
    html += `</div>`;
  }

  html += `
    <div class="seat-legend">
      <div class="legend-item"><div class="legend-swatch swatch-available"></div> Available</div>
      <div class="legend-item"><div class="legend-swatch swatch-selected"></div> Selected</div>
      <div class="legend-item"><div class="legend-swatch swatch-occupied"></div> Occupied</div>
      <div class="legend-item"><span style="font-size:0.7rem;">🟦</span> Window</div>
    </div>
    <div id="seat-selection-info" style="text-align:center;margin-top:1rem;padding:0.75rem;background:var(--off-white);border-radius:var(--radius-sm);font-size:0.875rem;color:var(--gray-600);">
      Select <strong>${passengers}</strong> seat(s) for your journey
    </div>
  `;

  container.innerHTML = html;
}

function selectSeat(seatNum, cls, btn) {
  if (btn.classList.contains('occupied')) return;
  const passengers = TW.searchParams?.passengers || 1;

  if (btn.classList.contains('selected')) {
    btn.classList.remove('selected');
    TW.selectedSeats = TW.selectedSeats.filter(s => s !== seatNum);
  } else {
    if (TW.selectedSeats.length >= passengers) {
      const oldest = TW.selectedSeats.shift();
      const oldBtn = document.querySelector(`[data-seat="${oldest}"]`);
      oldBtn?.classList.remove('selected');
    }
    btn.classList.add('selected');
    TW.selectedSeats.push(seatNum);
  }

  const info = document.getElementById('seat-selection-info');
  if (info) {
    info.innerHTML = TW.selectedSeats.length > 0
      ? `Selected: <strong>${TW.selectedSeats.join(', ')}</strong>`
      : `Select <strong>${passengers}</strong> seat(s) for your journey`;
  }
}

function renderPassengerForms() {
  const container = document.getElementById('passenger-forms');
  if (!container) return;
  const count = TW.searchParams?.passengers || 1;
  const user = TW.user;

  let html = '';
  for (let i = 0; i < count; i++) {
    html += `
      <div style="margin-bottom:1.5rem;padding:1.25rem;border:1.5px solid var(--gray-200);border-radius:var(--radius-md);">
        <div style="font-weight:700;color:var(--navy);margin-bottom:1rem;font-family:var(--font-display);">
          Passenger ${i+1} ${i===0 ? '(Primary)' : ''}
        </div>
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">First Name</label>
            <input class="form-control" placeholder="First Name" value="${i===0&&user?user.firstName:''}" required>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input class="form-control" placeholder="Last Name" value="${i===0&&user?user.lastName:''}" required>
          </div>
        </div>
        <div class="form-row cols-3">
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select class="form-control">
              <option value="">Select</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Meal Preference</label>
            <select class="form-control">
              <option>Standard</option>
              <option>Vegetarian</option>
              <option>Vegan</option>
              <option>Halal</option>
              <option>Kosher</option>
            </select>
          </div>
        </div>
        ${TW.selectedFlight?.type === 'international' ? `
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Passport Number</label>
            <input class="form-control" placeholder="Passport No.">
          </div>
          <div class="form-group">
            <label class="form-label">Nationality</label>
            <input class="form-control" placeholder="Nationality" value="${i===0&&user?'Indian':''}">
          </div>
        </div>
        ` : ''}
      </div>
    `;
  }
  container.innerHTML = html;
}

function renderPaymentSummary() {
  const f = TW.selectedFlight;
  if (!f) return;
  const passengers = TW.searchParams?.passengers || 1;
  const price = f.selectedClass === 'business' ? f.bizPrice : f.selectedClass === 'first' ? f.firstPrice : f.ecoPrice;
  const taxes = Math.round(price * 0.12 * passengers);
  const total = price * passengers + taxes;

  const sumEl = document.getElementById('payment-summary');
  if (sumEl) {
    sumEl.innerHTML = `
      <div style="background:var(--off-white);border-radius:var(--radius-md);padding:1.25rem;margin-bottom:1.5rem;">
        <div style="font-weight:700;color:var(--navy);margin-bottom:1rem;">Order Summary</div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">
          <span style="color:var(--gray-600);">${f.from} → ${f.to} × ${passengers}</span>
          <span style="font-weight:600;">₹${(price * passengers).toLocaleString('en-IN')}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">
          <span style="color:var(--gray-600);">Taxes & Fees (12%)</span>
          <span style="font-weight:600;">₹${taxes.toLocaleString('en-IN')}</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.875rem;">
          <span style="color:var(--gray-600);">Seats: ${TW.selectedSeats.join(', ') || 'Auto-assigned'}</span>
          <span style="color:var(--success);font-weight:600;">Included</span>
        </div>
        <div style="height:1px;background:var(--gray-200);margin:0.75rem 0;"></div>
        <div style="display:flex;justify-content:space-between;">
          <span style="font-weight:700;color:var(--navy);">Total Amount</span>
          <span style="font-size:1.2rem;font-weight:700;font-family:var(--font-display);color:var(--navy);">₹${total.toLocaleString('en-IN')}</span>
        </div>
      </div>
    `;
    TW.cart = { flight: f, passengers, seats: TW.selectedSeats, total, taxes };
  }
}

function nextBookingStep() {
  const next = TW.currentBookingStep + 1;
  if (next <= 4) goToBookingStep(next);
}

function prevBookingStep() {
  const prev = TW.currentBookingStep - 1;
  if (prev >= 1) goToBookingStep(prev);
}

async function confirmBooking() {
  if (!TW.cart || !TW.selectedFlight) return;
  const token = localStorage.getItem('tw_token');

  if (!token) {
    showToast('error', 'Login Required', 'Please log in to complete your booking.');
    openModal('login-modal');
    return;
  }

  showLoading('Processing your booking...');
  try {
    const f          = TW.selectedFlight;
    const passengers = TW.searchParams?.passengers || 1;
    const price      = f.selectedClass === 'business' ? f.bizPrice : f.selectedClass === 'first' ? f.firstPrice : f.ecoPrice;

    // Build passenger list
    const passengerList = [];
    for (let i = 0; i < passengers; i++) {
      passengerList.push({
        first_name: TW.user.firstName,
        last_name:  TW.user.lastName,
        seat_number: TW.selectedSeats[i] || null,
        class:       f.selectedClass || 'economy'
      });
    }

    const res  = await fetch('api/bookings.php?action=create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
        flight_id:        parseInt(f.id) || f.id,
        class:            f.selectedClass || 'economy',    // matches bookings.php
        cabin_class:      f.selectedClass || 'economy',
        booking_type:     'one-way',
        total_passengers: passengers,
        total_amount:     TW.cart.total || (price * passengers),
        payment_method:   'card',
        transaction_id:   'TXN_' + Date.now(),
        passengers:       passengerList,
        seats:            TW.selectedSeats
      })
    });
    const data = await res.json();
    hideLoading();

    if (data.success) {
      const ref = data.data?.booking_ref || ('TW' + Math.random().toString(36).substr(2,8).toUpperCase());
      TW.cart.ref = ref;
      closeModal('booking-modal');
      showBoardingPass(ref);
      sendBookingNotification(ref);
      addNotification('booking', '🎉 Booking Confirmed!', `Your booking ${ref} is confirmed.`);
    } else {
      showToast('error', 'Booking Failed', data.message || 'Please try again.');
      console.error('Booking failed:', data);
    }
  } catch (err) {
    hideLoading();
    showToast('error', 'Network Error', 'Could not complete booking. Check console for details.');
    console.error('Booking API error:', err);
  }
}

function showBoardingPass(ref) {
  const f = TW.selectedFlight;
  const modal = document.getElementById('boarding-pass-modal');
  if (!modal || !f) return;
  const dep = new Date(f.departure);
  const arr = new Date(f.arrival);
  const seat = TW.selectedSeats[0] || '14A';
  const user = TW.user || { firstName: 'Guest', lastName: '' };

  const bpEl = document.getElementById('boarding-pass-content');
  if (bpEl) {
    bpEl.innerHTML = `
      <div class="boarding-pass">
        <div class="bp-header">
          <div>
            <div class="bp-airline">✈ Titan Wing Airlines</div>
            <div class="bp-flight-no">Flight ${f.flightNo} · ${f.selectedClass.toUpperCase()}</div>
          </div>
          <div style="text-align:right;">
            <div style="color:var(--gold-light);font-weight:700;">${dep.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'})}</div>
            <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);">Boarding Pass</div>
          </div>
        </div>
        <div class="bp-body">
          <div style="margin-bottom:0.75rem;">
            <div style="font-size:0.7rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:0.1em;">Passenger</div>
            <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--navy);">${user.firstName} ${user.lastName}</div>
          </div>
          <div class="bp-route">
            <div class="bp-airport">
              <div class="bp-code">${f.from}</div>
              <div class="bp-city">${f.fromCity}</div>
            </div>
            <div class="bp-arrow">→</div>
            <div class="bp-airport">
              <div class="bp-code">${f.to}</div>
              <div class="bp-city">${f.toCity}</div>
            </div>
          </div>
          <div class="bp-details">
            <div class="bp-detail-item">
              <div class="bp-detail-label">Departs</div>
              <div class="bp-detail-value">${dep.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
            </div>
            <div class="bp-detail-item">
              <div class="bp-detail-label">Arrives</div>
              <div class="bp-detail-value">${arr.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:false})}</div>
            </div>
            <div class="bp-detail-item">
              <div class="bp-detail-label">Seat</div>
              <div class="bp-detail-value">${seat}</div>
            </div>
            <div class="bp-detail-item">
              <div class="bp-detail-label">Gate</div>
              <div class="bp-detail-value">B${Math.floor(Math.random()*20)+1}</div>
            </div>
          </div>
          <hr class="bp-divider">
          <div class="bp-barcode">|||||||||||||||||||||||||||||||||||||</div>
          <div class="bp-booking-ref">Booking Reference: <strong>${ref}</strong></div>
        </div>
      </div>
    `;
  }
  openModal('boarding-pass-modal');
  showToast('success', 'Booking Confirmed!', `Your booking reference is ${ref}. Check your email!`);
}

// ── Print boarding pass (1 page only) ────────────────────────
function printBoardingPass() {
  const bp = document.getElementById('boarding-pass-content');
  if (!bp) return;
  const printWin = window.open('', '_blank', 'width=600,height=700');
  printWin.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Boarding Pass - Titan Wing Airlines</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #fff; padding: 20px; }
        @page { size: A5 landscape; margin: 10mm; }
        .boarding-pass { max-width: 560px; margin: 0 auto; border: 3px solid #c9973a; border-radius: 14px; overflow: hidden; }
        .bp-header { background: linear-gradient(135deg, #0a1628, #1a3a5c); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
        .bp-airline { color: #e8b85c; font-size: 18px; font-weight: 900; letter-spacing: 2px; }
        .bp-flight-no { color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 3px; }
        .bp-body { padding: 20px 24px; background: #fff; }
        .bp-route { display: flex; align-items: center; justify-content: space-between; margin: 14px 0; }
        .bp-code { font-size: 40px; font-weight: 900; color: #0a1628; }
        .bp-city { font-size: 11px; color: #888; }
        .bp-arrow { font-size: 24px; color: #c9973a; }
        .bp-details { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 14px 0; background: #f8f6f0; padding: 12px; border-radius: 8px; }
        .bp-detail-label { font-size: 10px; color: #aaa; text-transform: uppercase; }
        .bp-detail-value { font-size: 16px; font-weight: 700; color: #0a1628; }
        .bp-divider { border: none; border-top: 2px dashed #ddd; margin: 14px 0; }
        .bp-barcode { font-family: monospace; font-size: 28px; letter-spacing: 2px; color: #0a1628; text-align: center; }
        .bp-booking-ref { text-align: center; font-size: 12px; color: #888; margin-top: 6px; }
        .passenger-name { font-size: 18px; font-weight: 700; color: #0a1628; }
      </style>
    </head>
    <body>` + bp.innerHTML + `</body></html>`);
  printWin.document.close();
  printWin.focus();
  setTimeout(() => { printWin.print(); printWin.close(); }, 400);
}

// ── Download boarding pass as PDF ─────────────────────────────
async function downloadBoardingPassPDF() {
  const bp = document.getElementById('boarding-pass-content');
  if (!bp) return;

  // Use html2canvas + jsPDF if available, else fallback to print-to-pdf
  try {
    // Try using browser's built-in print-to-PDF
    const ref = TW.cart?.ref || 'BOARDING_PASS';
    const printWin = window.open('', '_blank', 'width=700,height=900');
    printWin.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Boarding Pass - ${ref}</title>
        <style>
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body { font-family: Arial, sans-serif; background: #fff; }
          @page { size: A5; margin: 8mm; }
          @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          }
          .boarding-pass { max-width: 480px; margin: 10px auto; border: 3px solid #c9973a; border-radius: 14px; overflow: hidden; }
          .bp-header { background: linear-gradient(135deg,#0a1628,#1a3a5c) !important; color: white; padding: 16px 20px; display: flex; justify-content: space-between; }
          .bp-airline { color: #e8b85c !important; font-size: 16px; font-weight: 900; letter-spacing: 2px; }
          .bp-flight-no { color: rgba(255,255,255,0.7); font-size: 11px; margin-top: 2px; }
          .bp-body { padding: 16px 20px; background: #fff; }
          .bp-route { display: flex; align-items: center; justify-content: space-between; margin: 10px 0; }
          .bp-code { font-size: 36px; font-weight: 900; color: #0a1628; }
          .bp-city { font-size: 10px; color: #888; }
          .bp-arrow { font-size: 20px; color: #c9973a; }
          .bp-details { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; background: #f8f6f0 !important; padding: 10px; border-radius: 8px; margin: 10px 0; }
          .bp-detail-label { font-size: 9px; color: #aaa; text-transform: uppercase; }
          .bp-detail-value { font-size: 14px; font-weight: 700; color: #0a1628; }
          .bp-divider { border: none; border-top: 2px dashed #ddd; margin: 10px 0; }
          .bp-barcode { font-family: monospace; font-size: 22px; letter-spacing: 2px; color: #0a1628; text-align: center; }
          .bp-booking-ref { text-align: center; font-size: 11px; color: #888; margin-top: 4px; }
          .passenger-name { font-size: 16px; font-weight: 700; color: #0a1628; }
        </style>
      </head>
      <body>
        ${bp.innerHTML}
        <script>
          window.onload = function() {
            window.print();
            setTimeout(() => window.close(), 500);
          };
        <\/script>
      </body></html>`);
    printWin.document.close();
    showToast('info', 'Download PDF', 'In the print dialog, choose "Save as PDF" to download.');
  } catch(err) {
    // Ultimate fallback
    printBoardingPass();
    showToast('info', 'PDF Download', 'Select "Save as PDF" in the print dialog.');
  }
}

// ── Email boarding pass ───────────────────────────────────────
async function emailBoardingPass() {
  const token = localStorage.getItem('tw_token');
  const ref   = TW.cart?.ref;
  if (!ref) { showToast('error','Error','No booking found.'); return; }

  showLoading('Sending boarding pass...');
  try {
    const res  = await fetch('api/bookings.php?action=checkin&ref=' + ref, {
      method:  'POST',
      headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await res.json();
    hideLoading();
    if (data.success) {
      showToast('success', '📧 Boarding Pass Sent!', 'Check your email inbox.');
    } else {
      // Booking might not be checked in yet, try direct email
      showToast('info', '📧 Email', `Boarding pass will be emailed after check-in. Ref: ${ref}`);
    }
  } catch(e) {
    hideLoading();
    showToast('info', '📧 Note', 'Boarding pass is emailed automatically after check-in.');
  }
}

async function sendBookingNotification(ref) {
  if (!TW.user || !TW.cart) return;
  const token = localStorage.getItem('tw_token');

  try {
    // Send booking confirmation + boarding pass email via admin dispatch
    const res = await fetch('api/admin.php?action=dispatch_ticket', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + (token || '')
      },
      body: JSON.stringify({
        booking_ref: ref,
        email: TW.user.email,
        doc_type: 'boarding_pass'
      })
    });
    // Show toast regardless — booking is confirmed even if email fails
    showToast('info', '📧 Boarding Pass Sent!', `Check your inbox at ${TW.user.email}`);
  } catch (err) {
    // Non-fatal — booking is confirmed, email is bonus
    showToast('info', '📧 Booking Confirmed!', `Your reference is ${ref}`);
    console.error('Boarding pass email error:', err);
  }
}

// ============================================================
// MODALS
// ============================================================
function initModals() {
  // Open triggers
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(btn.dataset.modalOpen);
    });
  });

  // Close triggers  
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModal(btn.dataset.modalClose || btn.closest('.modal-overlay')?.id);
    });
  });

  // Overlay click to close
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // Auth form handlers
  const loginForm = document.getElementById('login-form');
  if (loginForm) loginForm.addEventListener('submit', handleLogin);

  const signupForm = document.getElementById('signup-form');
  if (signupForm) signupForm.addEventListener('submit', handleSignup);

  const forgotForm = document.getElementById('forgot-form');
  if (forgotForm) forgotForm.addEventListener('submit', handleForgotPassword);
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) { modal.classList.add('open'); document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) { modal.classList.remove('open'); document.body.style.overflow = ''; }
}

// ============================================================
// AUTH
// ============================================================
async function handleLogin(e) {
  e.preventDefault();
  const email    = document.getElementById('login-email')?.value?.trim();
  const password = document.getElementById('login-password')?.value;
  if (!email || !password) return;

  showLoading('Signing in...');
  try {
    const res  = await fetch('api/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const data = await res.json();
    hideLoading();

    if (data.success) {
      const u = data.data.user;
      const user = {
        id:        u.id,
        firstName: u.first_name,
        lastName:  u.last_name,
        email:     u.email,
        role:      'user',
        avatar:    u.first_name.charAt(0).toUpperCase()
      };
      // Store both key formats for compatibility
      const userToSave = {
        id:         u.id,
        first_name: u.first_name,
        last_name:  u.last_name,
        firstName:  u.first_name,
        lastName:   u.last_name,
        email:      u.email,
        role:       'user',
        avatar:     (u.first_name || 'U').charAt(0).toUpperCase()
      };
      TW.user = userToSave;
      localStorage.setItem('tw_user',  JSON.stringify(userToSave));
      localStorage.setItem('tw_token', data.data.token);
      sessionStorage.setItem('tw_token', data.data.token);
      closeModal('login-modal');
      updateAuthUI();
      showToast('success', `Welcome back, ${user.firstName}!`, 'You have successfully logged in.');
      addNotification('system', '👋 Welcome Back!', 'You are now logged in.');
    } else {
      showToast('error', 'Login Failed', data.message || 'Invalid email or password.');
    }
  } catch (err) {
    hideLoading();
    showToast('error', 'Network Error', 'Could not reach server. Is XAMPP running?');
    console.error('Login error:', err);
  }
}

async function handleSignup(e) {
  e.preventDefault();
  const firstName = document.getElementById('signup-firstname')?.value?.trim();
  const lastName  = document.getElementById('signup-lastname')?.value?.trim();
  const email     = document.getElementById('signup-email')?.value?.trim();
  const phone     = document.getElementById('signup-phone')?.value?.trim();
  const password  = document.getElementById('signup-password')?.value;
  const confirm   = document.getElementById('signup-confirm')?.value;

  if (!firstName || !email || !password) {
    showToast('error', 'Missing Fields', 'Please fill in all required fields.');
    return;
  }
  if (password !== confirm) {
    showToast('error', 'Password Mismatch', 'Passwords do not match.');
    return;
  }
  if (password.length < 8) {
    showToast('error', 'Weak Password', 'Password must be at least 8 characters.');
    return;
  }

  showLoading('Creating your account...');
  try {
    const res  = await fetch('api/auth.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ first_name: firstName, last_name: lastName, email, phone, password })
    });
    const data = await res.json();
    hideLoading();

    if (data.success) {
      closeModal('signup-modal');

      // Store pending signup info
      TW._pendingSignup = {
        userId: data.data.user_id,
        firstName, lastName, email, phone,
        otpCode: data.data.otp_code  // returned by API as fallback
      };

      // Set email display in OTP modal
      const emailEl = document.getElementById('otp-email-display');
      if (emailEl) emailEl.textContent = email;

      // Show OTP on screen if email didn't send
      const otpFallback = document.getElementById('otp-fallback-box');
      if (otpFallback && data.data.otp_code) {
        if (!data.data.email_sent) {
          otpFallback.style.display = 'block';
          const codeEl = document.getElementById('otp-fallback-code');
          if (codeEl) codeEl.textContent = data.data.otp_code;
          // Auto-fill the OTP inputs
          const inputs = document.querySelectorAll('.otp-input');
          const digits = String(data.data.otp_code).split('');
          inputs.forEach((inp, i) => { if (digits[i]) inp.value = digits[i]; });
        } else {
          otpFallback.style.display = 'none';
        }
      }

      openModal('otp-modal');
      startOTPTimer();

      if (data.data.email_sent) {
        showToast('success', 'OTP Sent! 📧', `Check your inbox at ${email}`);
      } else {
        showToast('info', 'Check Screen 👇', 'OTP shown below — email not configured yet.');
      }
    } else {
      showToast('error', 'Registration Failed', data.message);
    }
  } catch (err) {
    hideLoading();
    showToast('error', 'Network Error', 'Could not reach server. Is XAMPP running?');
    console.error('Signup error:', err);
  }
}

function handleForgotPassword(e) {
  e.preventDefault();
  const email = document.getElementById('forgot-email')?.value;
  showLoading('Sending reset link...');
  setTimeout(() => {
    hideLoading();
    closeModal('forgot-modal');
    showToast('success', 'Email Sent!', `Password reset link sent to ${email}`);
  }, 1000);
}

function updateAuthUI() {
  const user = TW.user;
  const loginBtns  = document.querySelectorAll('.nav-login-btn');
  const signupBtns = document.querySelectorAll('.nav-signup-btn');
  const userMenus  = document.querySelectorAll('.user-menu');
  const notifBtns  = document.querySelectorAll('.notif-btn-wrap');

  if (user) {
    // Support both camelCase (old) and snake_case (real API) keys
    const firstName = user.first_name || user.firstName || 'User';
    const avatar    = firstName.charAt(0).toUpperCase();

    loginBtns.forEach(b  => b.style.display = 'none');
    signupBtns.forEach(b => b.style.display = 'none');
    userMenus.forEach(m  => { m.style.display = 'flex'; });
    notifBtns.forEach(b  => b.style.display = 'flex');
    document.querySelectorAll('.user-display-name').forEach(el  => el.textContent = firstName);
    document.querySelectorAll('.user-display-email').forEach(el => el.textContent = user.email);
    document.querySelectorAll('.user-avatar-letter').forEach(el => el.textContent = avatar);
  } else {
    loginBtns.forEach(b  => b.style.display = '');
    signupBtns.forEach(b => b.style.display = '');
    userMenus.forEach(m  => m.style.display = 'none');
    notifBtns.forEach(b  => b.style.display = 'none');
  }
}

function logout() {
  TW.user = null;
  localStorage.removeItem('tw_user');
  updateAuthUI();
  showToast('info', 'Logged Out', 'You have been successfully logged out.');
  // Redirect if on protected page
  if (window.location.pathname.includes('dashboard') || window.location.pathname.includes('admin')) {
    window.location.href = 'index.html';
  }
}

// ============================================================
// OTP
// ============================================================
function initOTP() {
  const inputs = document.querySelectorAll('.otp-input');
  inputs.forEach((input, idx) => {
    input.addEventListener('input', () => {
      if (input.value.length === 1 && idx < inputs.length - 1) {
        inputs[idx + 1].focus();
      }
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && idx > 0) {
        inputs[idx - 1].focus();
      }
    });
  });

  const verifyBtn = document.getElementById('verify-otp-btn');
  if (verifyBtn) {
    verifyBtn.addEventListener('click', verifyOTP);
  }

  const resendBtn = document.getElementById('resend-otp-btn');
  if (resendBtn) {
    resendBtn.addEventListener('click', async () => {
      const pending = TW._pendingSignup;
      if (!pending?.userId) { showToast('error','Error','Please register again.'); return; }

      startOTPTimer();
      try {
        const res  = await fetch('api/auth.php?action=resend_otp', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: pending.userId })
        });
        const data = await res.json();
        if (data.success) {
          // Show new OTP on screen if email not sent
          if (data.data?.otp_code) {
            const fb   = document.getElementById('otp-fallback-box');
            const code = document.getElementById('otp-fallback-code');
            if (fb && code) {
              fb.style.display = 'block';
              code.textContent = data.data.otp_code;
              // Auto-fill inputs
              const inputs = document.querySelectorAll('.otp-input');
              const digits = String(data.data.otp_code).split('');
              inputs.forEach((inp,i) => { inp.value = digits[i] || ''; });
            }
            showToast('info','New OTP Ready 👇', data.data.email_sent ? 'Check your email.' : 'Code shown on screen.');
          } else {
            showToast('success','OTP Resent!','Check your email.');
          }
        }
      } catch(e) {
        showToast('info','OTP Resent!','Check your email or screen.');
      }
    });
  }
}

function startOTPTimer() {
  let seconds = 60;
  const timerEl = document.getElementById('otp-timer');
  const resendBtn = document.getElementById('resend-otp-btn');
  if (resendBtn) resendBtn.disabled = true;

  if (TW.otpTimer) clearInterval(TW.otpTimer);
  TW.otpTimer = setInterval(() => {
    seconds--;
    if (timerEl) timerEl.textContent = `Resend OTP in ${seconds}s`;
    if (seconds <= 0) {
      clearInterval(TW.otpTimer);
      if (timerEl) timerEl.textContent = 'OTP expired';
      if (resendBtn) resendBtn.disabled = false;
    }
  }, 1000);
}

async function verifyOTP() {
  const inputs = document.querySelectorAll('.otp-input');
  const otp    = Array.from(inputs).map(i => i.value.trim()).join('');

  if (otp.length !== 6 || !/^[0-9]{6}$/.test(otp)) {
    showToast('error', 'Invalid OTP', 'Please enter all 6 digits.');
    return;
  }

  const pending = TW._pendingSignup;
  if (!pending?.userId) {
    showToast('error', 'Session Lost', 'Please register again.');
    closeModal('otp-modal');
    return;
  }

  showLoading('Verifying your code...');
  try {
    const res  = await fetch('api/auth.php?action=verify_otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: pending.userId, otp })
    });
    const data = await res.json();
    hideLoading();

    if (data.success) {
      // Save user + token
      const token = data.data.token;
      const u     = data.data.user;
      const user  = {
        id:         u.id,
        first_name: u.first_name,
        last_name:  u.last_name,
        firstName:  u.first_name,
        lastName:   u.last_name,
        email:      u.email,
        avatar:     (u.first_name || 'U').charAt(0).toUpperCase(),
        role:       'user'
      };
      TW.user = user;
      localStorage.setItem('tw_user',  JSON.stringify(user));
      localStorage.setItem('tw_token', token);
      // Also store in sessionStorage as backup
      sessionStorage.setItem('tw_token', token);
      TW._pendingSignup = null;

      closeModal('otp-modal');
      updateAuthUI();
      showToast('success', `Welcome, ${user.firstName}! 🎉`, 'Your account is verified.');
      addNotification('system', '🎉 Welcome to Titan Wing!', 'Your account has been verified.');
    } else {
      showToast('error', 'Wrong OTP', data.message || 'Incorrect code. Try again.');
      // Clear inputs so user can retype
      document.querySelectorAll('.otp-input').forEach(i => i.value = '');
      document.querySelector('.otp-input')?.focus();
    }
  } catch (err) {
    hideLoading();
    showToast('error', 'Network Error', 'Could not verify. Is XAMPP running?');
    console.error('OTP verify error:', err);
  }
}

// ============================================================
// NOTIFICATIONS
// ============================================================
function initNotifications() {
  TW.notifications = [
    { id:1, type:'promo', title:'✈ Flash Sale!', msg:'30% off on international flights this week!', time:'2 hours ago', read:false },
    { id:2, type:'system', title:'📱 App Update', msg:'New features added to your profile.', time:'1 day ago', read:true },
  ];
  renderNotifications();
}

function addNotification(type, title, msg) {
  const notif = { id: Date.now(), type, title, msg, time: 'Just now', read: false };
  TW.notifications.unshift(notif);
  renderNotifications();
  updateNotifBadge();
}

function renderNotifications() {
  const list = document.getElementById('notif-list');
  if (!list) return;
  const icons = { booking:'🎫', flight:'✈', promo:'🏷', system:'⚙' };
  list.innerHTML = TW.notifications.map(n => `
    <div class="notif-item ${n.read?'':'unread'}" onclick="markNotifRead(${n.id})">
      <div class="notif-icon ${n.type}">${icons[n.type] || '🔔'}</div>
      <div class="notif-content">
        <div class="notif-title">${n.title}</div>
        <div class="notif-msg">${n.msg}</div>
        <div class="notif-time">${n.time}</div>
      </div>
    </div>
  `).join('');
  updateNotifBadge();
}

function markNotifRead(id) {
  const n = TW.notifications.find(n => n.id === id);
  if (n) { n.read = true; renderNotifications(); }
}

function updateNotifBadge() {
  const unread = TW.notifications.filter(n => !n.read).length;
  document.querySelectorAll('.notif-badge').forEach(b => {
    b.textContent = unread;
    b.style.display = unread > 0 ? 'flex' : 'none';
  });
}

// ============================================================
// AI ASSISTANT
// ============================================================
function initAIChat() {
  const chatBtn = document.getElementById('ai-chat-btn');
  const chatWindow = document.getElementById('ai-chat-window');
  const sendBtn = document.getElementById('ai-send-btn');
  const aiInput = document.getElementById('ai-input');
  const closeBtn = document.getElementById('ai-chat-close');

  if (chatBtn) chatBtn.addEventListener('click', () => chatWindow?.classList.toggle('open'));
  if (closeBtn) closeBtn.addEventListener('click', () => chatWindow?.classList.remove('open'));

  if (sendBtn) sendBtn.addEventListener('click', sendAIMessage);
  if (aiInput) aiInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') sendAIMessage(); });

  document.querySelectorAll('.ai-suggest-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (aiInput) aiInput.value = btn.textContent;
      sendAIMessage();
    });
  });

  // Initial greeting
  setTimeout(() => {
    if (document.getElementById('ai-chat-window')) {
      appendAIMessage('bot', "Hello! I'm Sky, your Titan Wing AI assistant. I can help you find flights, manage bookings, and answer any travel questions. How can I assist you today? ✈️");
    }
  }, 500);
}

function sendAIMessage() {
  const input = document.getElementById('ai-input');
  if (!input?.value.trim()) return;
  const msg = input.value.trim();
  input.value = '';

  appendAIMessage('user', msg);
  TW.aiChatHistory.push({ role: 'user', content: msg });

  // Show typing indicator
  const typingId = appendAIMessage('bot', '...', true);

  setTimeout(() => {
    removeTypingIndicator(typingId);
    const response = generateAIResponse(msg);
    appendAIMessage('bot', response);
    TW.aiChatHistory.push({ role: 'assistant', content: response });
  }, 800 + Math.random() * 400);
}

function generateAIResponse(msg) {
  const lower = msg.toLowerCase();
  if (lower.includes('flight') || lower.includes('book') || lower.includes('search')) {
    return "I can help you search for flights! You can use the search widget on the homepage. We operate domestic routes across India and international routes to Dubai, London, Singapore, Bangkok, and more. Want me to tell you about current deals? 🌍";
  }
  if (lower.includes('cancel') || lower.includes('refund')) {
    return "For cancellations, you can go to My Bookings → select your booking → click Cancel. Refunds for cancellations made 24+ hours before departure are processed within 5-7 business days. Would you like help with a specific booking? 📋";
  }
  if (lower.includes('check-in') || lower.includes('checkin')) {
    return "Online check-in opens 48 hours before departure and closes 1 hour before. Go to My Bookings → select your flight → Online Check-in. Your boarding pass will be emailed to you. Would you like to check in now? ✅";
  }
  if (lower.includes('baggage') || lower.includes('luggage')) {
    return "Baggage allowances: Economy: 15kg check-in + 7kg cabin | Business: 25kg + 10kg | First Class: 35kg + 12kg. Excess baggage charges: ₹500/kg domestic, ₹1500/kg international. Need more info? 🧳";
  }
  if (lower.includes('seat') || lower.includes('upgrade')) {
    return "Seat selection is available during booking or up to 24 hours before departure. Window seats (A/F) and extra legroom seats (rows 10-12) are popular choices. Business and First Class upgrades can be made in My Bookings. 💺";
  }
  if (lower.includes('price') || lower.includes('cheap') || lower.includes('discount')) {
    return "Get the best prices: Book 3+ weeks in advance, travel Tuesday-Thursday, and sign up for our newsletter for exclusive deals. Current offers: 20% off Dubai routes, 15% off Singapore! Use promo code TITAN20. 🏷️";
  }
  if (lower.includes('hello') || lower.includes('hi') || lower.includes('hey')) {
    return "Hello there! ✈️ Welcome to Titan Wing Airlines. I'm Sky, your virtual travel assistant. Ask me about flights, bookings, check-in, baggage, or any other travel queries. How can I make your journey smoother?";
  }
  if (lower.includes('contact') || lower.includes('help') || lower.includes('support')) {
    return "You can reach us at: 📧 support@titanwing.com | 📞 1800-TITANWING (24/7) | 💬 Live chat in the app. Our average response time is under 2 hours. Is there something specific I can help resolve? 🤝";
  }
  if (lower.includes('international') || lower.includes('passport') || lower.includes('visa')) {
    return "For international travel, ensure your passport is valid for 6+ months beyond your travel date. We fly to 15+ international destinations. Visa requirements vary by country—I recommend checking official embassy websites. Need help with anything else? 🌐";
  }
  return "That's a great question! Let me help you with that. Titan Wing operates 200+ daily flights connecting 20+ destinations. You can manage everything from search to boarding pass right here. What specific information do you need? 😊";
}

function appendAIMessage(role, text, isTyping = false) {
  const container = document.getElementById('ai-chat-messages');
  if (!container) return null;
  const id = `msg-${Date.now()}`;
  const avatar = role === 'bot' ? '🤖' : (TW.user?.avatar || '👤');
  container.innerHTML += `
    <div class="chat-msg ${role}" id="${id}">
      ${role === 'bot' ? `<div class="chat-msg-avatar">${avatar}</div>` : ''}
      <div class="chat-bubble ${isTyping ? 'typing-bubble' : ''}">${isTyping ? '<span class="typing-dots">●●●</span>' : text}</div>
      ${role === 'user' ? `<div class="chat-msg-avatar" style="background:var(--navy);color:white;">${avatar}</div>` : ''}
    </div>
  `;
  container.scrollTop = container.scrollHeight;
  return id;
}

function removeTypingIndicator(id) {
  if (id) document.getElementById(id)?.remove();
}

// ============================================================
// DASHBOARD
// ============================================================
function initDashboard() {
  if (!TW.user) { window.location.href = 'index.html'; return; }

  // Populate user info
  document.querySelectorAll('.dash-user-name').forEach(el => el.textContent = `${TW.user.firstName} ${TW.user.lastName}`);
  document.querySelectorAll('.dash-user-email').forEach(el => el.textContent = TW.user.email);
  document.querySelectorAll('.dash-avatar-letter').forEach(el => el.textContent = TW.user.avatar || TW.user.firstName.charAt(0));

  // Render mock bookings
  renderMyBookings();
}

function renderMyBookings() {
  const container = document.getElementById('bookings-list');
  if (!container) return;
  const mockBookings = [
    { ref:'TWAB1234', from:'DEL', to:'DXB', date:'15 Mar 2025', status:'confirmed', amount:'₹22,000', flight:'TW301' },
    { ref:'TWXY5678', from:'BOM', to:'BLR', date:'8 Feb 2025', status:'completed', amount:'₹3,800', flight:'TW501' },
    { ref:'TWCD9012', from:'DEL', to:'LHR', date:'1 Jan 2025', status:'completed', amount:'₹55,000', flight:'TW401' },
  ];

  container.innerHTML = mockBookings.map(b => `
    <div class="booking-item">
      <div class="booking-item-header">
        <div class="booking-ref-tag">📋 ${b.ref}</div>
        <span class="booking-status status-${b.status}">${b.status.charAt(0).toUpperCase()+b.status.slice(1)}</span>
      </div>
      <div class="booking-route">
        <span class="route-code">${b.from}</span>
        <span class="route-arrow">→</span>
        <span class="route-code">${b.to}</span>
        <span style="margin-left:1rem;font-size:0.85rem;color:var(--gray-500);">${b.flight} · ${b.date}</span>
        <span style="margin-left:auto;font-weight:700;color:var(--navy);">${b.amount}</span>
      </div>
      <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
        <button class="btn-details" onclick="viewBooking('${b.ref}')">View Details</button>
        ${b.status === 'confirmed' ? `
          <button class="btn-book-now" style="padding:0.4rem 1rem;font-size:0.8rem;" onclick="doCheckin('${b.ref}')">Online Check-in</button>
          <button class="btn-details" onclick="showBoardingPass('${b.ref}')">Boarding Pass</button>
        ` : ''}
      </div>
    </div>
  `).join('');
}

function viewBooking(ref) {
  showToast('info', 'Booking Details', `Loading booking ${ref}...`);
}

function doCheckin(ref) {
  showLoading('Processing check-in...');
  setTimeout(() => {
    hideLoading();
    showToast('success', 'Check-in Complete!', `You are now checked in for booking ${ref}. Your boarding pass is ready!`);
  }, 1500);
}

// ============================================================
// ADMIN
// ============================================================
function initAdmin() {
  // Admin nav
  document.querySelectorAll('.admin-nav-item[data-section]').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.admin-nav-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
      const section = item.dataset.section;
      document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
      const target = document.getElementById(`admin-${section}`);
      if (target) target.style.display = 'block';
      const titleEl = document.getElementById('admin-page-title');
      if (titleEl) titleEl.textContent = item.querySelector('.nav-text')?.textContent || section;
    });
  });

  // Init dashboard stats counters
  animateCounters();
}

function animateCounters() {
  document.querySelectorAll('.count-up').forEach(el => {
    const target = parseInt(el.dataset.target || '0');
    let current = 0;
    const step = Math.max(1, Math.floor(target / 60));
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current.toLocaleString('en-IN');
      if (current >= target) clearInterval(timer);
    }, 25);
  });
}

// ============================================================
// ABOUT PAGE
// ============================================================
function initAbout() {
  // Scroll reveal for timeline
  initScrollReveal();
}

// ============================================================
// SCROLL REVEAL
// ============================================================
function initScrollReveal() {
  const elements = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
  elements.forEach(el => observer.observe(el));
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(type, title, message, duration = 4000) {
  const container = document.getElementById('toast-container');
  if (!container) {
    const el = document.createElement('div');
    el.id = 'toast-container';
    el.className = 'toast-container';
    document.body.appendChild(el);
  }
  const toastContainer = document.getElementById('toast-container');
  const icons = { success:'✅', error:'❌', info:'ℹ️', warning:'⚠️' };
  const id = `toast-${Date.now()}`;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.id = id;
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || '🔔'}</div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-msg">${message}</div>
    </div>
    <button class="toast-close" onclick="closeToast('${id}')">✕</button>
  `;
  toastContainer.appendChild(toast);
  setTimeout(() => closeToast(id), duration);
}

function closeToast(id) {
  const toast = document.getElementById(id);
  if (toast) {
    toast.style.animation = 'toastOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }
}

// ============================================================
// LOADING OVERLAY
// ============================================================
function showLoading(text = 'Loading...') {
  let overlay = document.getElementById('loading-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `<div class="spinner"></div><div class="loading-text">${text}</div>`;
    document.body.appendChild(overlay);
  } else {
    overlay.querySelector('.loading-text').textContent = text;
  }
  overlay.classList.add('active');
}

function hideLoading() {
  document.getElementById('loading-overlay')?.classList.remove('active');
}

// ============================================================
// UTILITIES
// ============================================================
function formatPrice(amount) {
  return '₹' + amount.toLocaleString('en-IN');
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' });
}

function generateRef() {
  return 'TW' + Math.random().toString(36).substr(2, 8).toUpperCase();
}

// Expose to window for onclick handlers
window.openModal = openModal;
window.closeModal = closeModal;
window.openBookingModal = openBookingModal;
window.nextBookingStep = nextBookingStep;
window.prevBookingStep = prevBookingStep;
window.confirmBooking = confirmBooking;
window.selectSeat = selectSeat;
window.logout = logout;
window.doCheckin = doCheckin;
window.viewBooking = viewBooking;
window.showBoardingPass = showBoardingPass;
window.showFlightDetails = showFlightDetails;
window.closeToast = closeToast;
window.markNotifRead = markNotifRead;
window.verifyOTP = verifyOTP;

// CSS for typing dots
const style = document.createElement('style');
style.textContent = `
  @keyframes typing { 0%,80%,100% { opacity:0.3; } 40% { opacity:1; } }
  .typing-dots { display:inline-flex; gap:3px; }
  .typing-dots::after { content:'●●●'; letter-spacing:3px; animation:typing 1.2s infinite; }
  @keyframes toastOut { to { opacity:0; transform:translateX(100%); } }
`;
document.head.appendChild(style);
