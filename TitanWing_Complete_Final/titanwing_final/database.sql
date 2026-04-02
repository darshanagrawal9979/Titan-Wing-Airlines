-- ============================================================
-- TITAN WING AIRLINES — Full Database Schema
-- Import in phpMyAdmin: titanwing_db → SQL → Run
-- ============================================================
CREATE DATABASE IF NOT EXISTS titanwing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE titanwing_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name  VARCHAR(100) NOT NULL DEFAULT '',
    email      VARCHAR(255) NOT NULL UNIQUE,
    phone      VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    is_verified   TINYINT(1) DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    otp_code      VARCHAR(255),
    otp_expires   DATETIME,
    otp_attempts  INT DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('super_admin','flight_manager','support') DEFAULT 'support',
    is_active     TINYINT(1) DEFAULT 1,
    last_login    DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Airports
CREATE TABLE IF NOT EXISTS airports (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    code           CHAR(3) NOT NULL UNIQUE,
    name           VARCHAR(200) NOT NULL,
    city           VARCHAR(100) NOT NULL,
    country        VARCHAR(100) NOT NULL,
    country_code   CHAR(2),
    timezone       VARCHAR(60),
    is_international TINYINT(1) DEFAULT 1,
    INDEX idx_code (code)
) ENGINE=InnoDB;

-- Aircraft
CREATE TABLE IF NOT EXISTS aircraft (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    model            VARCHAR(100) NOT NULL,
    registration     VARCHAR(20),
    total_seats      INT DEFAULT 200,
    economy_seats    INT DEFAULT 150,
    business_seats   INT DEFAULT 40,
    first_class_seats INT DEFAULT 10,
    is_active        TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Flights
CREATE TABLE IF NOT EXISTS flights (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    flight_number     VARCHAR(10) NOT NULL UNIQUE,
    aircraft_id       INT NOT NULL,
    origin_id         INT NOT NULL,
    destination_id    INT NOT NULL,
    departure_time    DATETIME NOT NULL,
    arrival_time      DATETIME NOT NULL,
    duration_minutes  INT,
    economy_price     DECIMAL(10,2) NOT NULL DEFAULT 0,
    business_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    first_class_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    available_economy  INT DEFAULT 0,
    available_business INT DEFAULT 0,
    available_first    INT DEFAULT 0,
    flight_type       ENUM('domestic','international') DEFAULT 'domestic',
    status            ENUM('scheduled','boarding','departed','arrived','cancelled','delayed') DEFAULT 'scheduled',
    is_active         TINYINT(1) DEFAULT 1,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aircraft_id)    REFERENCES aircraft(id),
    FOREIGN KEY (origin_id)      REFERENCES airports(id),
    FOREIGN KEY (destination_id) REFERENCES airports(id),
    INDEX idx_route_date (origin_id, destination_id, departure_time),
    INDEX idx_departure (departure_time)
) ENGINE=InnoDB;

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref    VARCHAR(20) NOT NULL UNIQUE,
    user_id        INT NOT NULL,
    flight_id      INT NOT NULL,
    return_flight_id INT,
    booking_type   ENUM('one-way','round-trip') DEFAULT 'one-way',
    class          VARCHAR(20) DEFAULT 'economy',
    total_passengers INT DEFAULT 1,
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    status         ENUM('pending','confirmed','checked-in','completed','cancelled') DEFAULT 'confirmed',
    payment_status ENUM('pending','paid','refunded','failed') DEFAULT 'paid',
    payment_method VARCHAR(50) DEFAULT 'card',
    transaction_id VARCHAR(100),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    INDEX idx_user (user_id),
    INDEX idx_ref  (booking_ref),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Passengers
CREATE TABLE IF NOT EXISTS passengers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    booking_id       INT NOT NULL,
    seat_id          INT,
    first_name       VARCHAR(100) NOT NULL,
    last_name        VARCHAR(100) NOT NULL,
    age              INT,
    gender           ENUM('male','female','other'),
    passport_no      VARCHAR(50),
    nationality      VARCHAR(60),
    class            VARCHAR(20) DEFAULT 'economy',
    meal_preference  VARCHAR(50) DEFAULT 'standard',
    checkin_status   TINYINT(1) DEFAULT 0,
    boarding_pass_issued TINYINT(1) DEFAULT 0,
    ticket_number    VARCHAR(50),
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB;

-- Seats
CREATE TABLE IF NOT EXISTS seats (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    flight_id    INT NOT NULL,
    seat_number  VARCHAR(5) NOT NULL,
    class        ENUM('economy','business','first') DEFAULT 'economy',
    is_available TINYINT(1) DEFAULT 1,
    is_window    TINYINT(1) DEFAULT 0,
    is_aisle     TINYINT(1) DEFAULT 0,
    booked_by    INT,
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    UNIQUE KEY uniq_seat (flight_id, seat_number),
    INDEX idx_available (flight_id, is_available)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    type       VARCHAR(50) DEFAULT 'system',
    title      VARCHAR(200) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- Email logs
CREATE TABLE IF NOT EXISTS email_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    recipient_email VARCHAR(255) NOT NULL,
    subject         VARCHAR(255),
    type            VARCHAR(50) DEFAULT 'system',
    status          ENUM('sent','failed','pending') DEFAULT 'sent',
    error_message   TEXT,
    sent_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ── Sample data ───────────────────────────────────────────────
INSERT INTO airports (code,name,city,country,country_code,timezone,is_international) VALUES
('DEL','Indira Gandhi International Airport','New Delhi','India','IN','Asia/Kolkata',1),
('BOM','Chhatrapati Shivaji Maharaj Intl','Mumbai','India','IN','Asia/Kolkata',1),
('BLR','Kempegowda International Airport','Bangalore','India','IN','Asia/Kolkata',1),
('MAA','Chennai International Airport','Chennai','India','IN','Asia/Kolkata',1),
('HYD','Rajiv Gandhi International Airport','Hyderabad','India','IN','Asia/Kolkata',1),
('CCU','Netaji Subhas Chandra Bose Intl','Kolkata','India','IN','Asia/Kolkata',1),
('DXB','Dubai International Airport','Dubai','UAE','AE','Asia/Dubai',1),
('LHR','Heathrow Airport','London','United Kingdom','GB','Europe/London',1),
('JFK','John F. Kennedy International','New York','USA','US','America/New_York',1),
('SIN','Changi Airport','Singapore','Singapore','SG','Asia/Singapore',1),
('BKK','Suvarnabhumi Airport','Bangkok','Thailand','TH','Asia/Bangkok',1),
('KUL','Kuala Lumpur International','Kuala Lumpur','Malaysia','MY','Asia/Kuala_Lumpur',1),
('SYD','Sydney Airport','Sydney','Australia','AU','Australia/Sydney',1),
('CDG','Charles de Gaulle Airport','Paris','France','FR','Europe/Paris',1),
('FRA','Frankfurt Airport','Frankfurt','Germany','DE','Europe/Berlin',1);

INSERT INTO aircraft (model,registration,total_seats,economy_seats,business_seats,first_class_seats) VALUES
('Boeing 737-800','TW-001',162,138,18,6),
('Airbus A320','TW-002',150,126,18,6),
('Boeing 777-300ER','TW-003',396,342,42,12),
('Airbus A380','TW-004',555,480,60,15),
('ATR 72-600','TW-005',70,66,4,0),
('Boeing 787-9','TW-006',294,252,30,12);
