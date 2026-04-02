# ✈ Titan Wing Airlines — Full Stack Project

## Setup Instructions

### 1. Prerequisites
- XAMPP (Apache + MySQL) installed
- PHP 8.2+
- Composer (for PHPMailer)

### 2. Installation
1. Copy `titanwing/` folder to `C:\xampp\htdocs\`
2. Start XAMPP — Apache + MySQL
3. Open phpMyAdmin → Create database `titanwing_db`
4. Import `database.sql` → creates all tables + airports + aircraft
5. Import `add_flights.sql` → adds current domestic flights
6. Import `add_40_intl_flights.sql` → adds 40 international flights

### 3. Install PHPMailer
```bash
cd C:\xampp\htdocs\titanwing
composer require phpmailer/phpmailer
```

### 4. Configure Email
Edit `includes/config.php`:
```php
define('SMTP_USER', 'your_gmail@gmail.com');
define('SMTP_PASS', 'your_16_char_app_password');
define('SMTP_FROM', 'your_gmail@gmail.com');
```

### 5. Create Admin Accounts
Open: `http://localhost/titanwing/setup_admin.php`
Then delete this file!

### 6. Run
- Frontend: `http://localhost/titanwing/`
- Admin: `http://localhost/titanwing/admin/login.php`

## Default Credentials
- Admin: `admin@titanwing.com` / `Admin@123`
- Manager: `manager@titanwing.com` / `Manager@123`

## Tech Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 8.2 (REST APIs)
- **Database**: MySQL 8.0 (PDO)
- **Email**: PHPMailer + Gmail SMTP
- **Auth**: JWT (HS256) + bcrypt
- **Server**: Apache (XAMPP)

## Project Structure
```
titanwing/
├── index.html          ← Homepage
├── about.html          ← About page
├── checkin.html        ← Online check-in
├── css/style.css       ← Main stylesheet
├── js/main.js          ← All frontend JavaScript
├── pages/
│   ├── dashboard.php   ← User dashboard
│   └── profile.php     ← User profile
├── api/
│   ├── auth.php        ← Register, login, OTP
│   ├── flights.php     ← Search, seat map
│   ├── bookings.php    ← Create, cancel, check-in
│   ├── user.php        ← Profile management
│   └── admin.php       ← Admin operations
├── includes/
│   ├── config.php      ← DB + SMTP + JWT config
│   ├── db.php          ← PDO database class
│   └── helpers.php     ← JWT, email, OTP helpers
├── admin/
│   ├── login.php       ← Admin login
│   ├── index.php       ← Admin dashboard
│   └── logout.php      ← Admin logout
├── database.sql        ← Full schema + seed data
├── add_flights.sql     ← Current domestic flights
├── add_40_intl_flights.sql ← International flights
└── setup_admin.php     ← Run once, then delete
```

## Color Palette
- Navy: `#0a1628`
- Gold: `#c9973a`
- Gold light: `#e8b85c`
- Crimson: `#c0392b`
