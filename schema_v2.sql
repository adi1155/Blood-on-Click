-- ╔══════════════════════════════════════════════════════╗
-- ║   BLOOD ON CLICK v2 — Extended Database Schema       ║
-- ║   Run this file to set up the complete MySQL DB      ║
-- ╚══════════════════════════════════════════════════════╝

CREATE DATABASE IF NOT EXISTS blood_on_click CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blood_on_click;

-- ─────────────────────────────────────────────────────────
-- USERS (Admin, Donor, Blood Bank Staff, Seeker)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150)     NOT NULL,
    age           INT,
    gender        ENUM('Male','Female','Other'),
    blood_group   ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
    contact       VARCHAR(20),
    email         VARCHAR(200)     NOT NULL UNIQUE,
    password      VARCHAR(255)     NOT NULL,
    city          VARCHAR(100),
    latitude      DECIMAL(10,7)    DEFAULT NULL,
    longitude     DECIMAL(10,7)    DEFAULT NULL,
    last_donation_date DATE        DEFAULT NULL,
    role          ENUM('admin','donor','blood_bank','seeker') DEFAULT 'donor',
    is_available  TINYINT(1)       DEFAULT 1,
    profile_photo VARCHAR(255)     DEFAULT NULL,
    created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────────────────────
-- BLOOD BANKS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blood_banks (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT              NOT NULL,          -- linked staff account
    name          VARCHAR(200)     NOT NULL,
    address       TEXT             NOT NULL,
    city          VARCHAR(100)     NOT NULL,
    latitude      DECIMAL(10,7)    DEFAULT NULL,
    longitude     DECIMAL(10,7)    DEFAULT NULL,
    contact       VARCHAR(20),
    email         VARCHAR(200),
    license_no    VARCHAR(100),
    is_active     TINYINT(1)       DEFAULT 1,
    created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────
-- BLOOD STOCK (per blood bank, per blood group)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blood_stock (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    blood_bank_id   INT             NOT NULL,
    blood_group     ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    units_available INT             DEFAULT 0,
    units_reserved  INT             DEFAULT 0,
    threshold_alert INT             DEFAULT 5,        -- alert if below this
    last_updated    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bank_group (blood_bank_id, blood_group),
    FOREIGN KEY (blood_bank_id) REFERENCES blood_banks(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────
-- DONATIONS (each donation event)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS donations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    donor_id        INT             NOT NULL,
    blood_bank_id   INT             DEFAULT NULL,
    blood_group     ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    units           DECIMAL(4,2)    DEFAULT 1.00,
    donation_date   DATE            NOT NULL,
    status          ENUM('pending','completed','rejected') DEFAULT 'completed',
    notes           TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id)      REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (blood_bank_id) REFERENCES blood_banks(id)  ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────
-- MEDICAL ASSESSMENTS (per donor, per donation)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS medical_assessments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    donor_id        INT             NOT NULL,
    assessed_by     INT             NOT NULL,          -- admin/staff user id
    donation_id     INT             DEFAULT NULL,
    hemoglobin      DECIMAL(4,1),                      -- g/dL
    blood_pressure  VARCHAR(20),                       -- e.g. "120/80"
    pulse_rate      INT,                               -- bpm
    weight          DECIMAL(5,2),                      -- kg
    temperature     DECIMAL(4,1),                      -- °C
    hiv_status      ENUM('Negative','Positive','Unknown') DEFAULT 'Unknown',
    hepatitis_b     ENUM('Negative','Positive','Unknown') DEFAULT 'Unknown',
    hepatitis_c     ENUM('Negative','Positive','Unknown') DEFAULT 'Unknown',
    syphilis        ENUM('Negative','Positive','Unknown') DEFAULT 'Unknown',
    malaria         ENUM('Negative','Positive','Unknown') DEFAULT 'Unknown',
    passed          TINYINT(1)      DEFAULT 1,
    notes           TEXT,
    assessed_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id)    REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (assessed_by) REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (donation_id) REFERENCES donations(id)  ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────
-- BLOOD REQUESTS (by seekers or blood banks)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blood_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    requester_id    INT             NOT NULL,
    requester_type  ENUM('seeker','blood_bank','admin') DEFAULT 'seeker',
    blood_group     ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    units_needed    INT             DEFAULT 1,
    city            VARCHAR(100),
    hospital_name   VARCHAR(200),
    patient_name    VARCHAR(150),
    urgency         ENUM('normal','urgent','critical') DEFAULT 'normal',
    status          ENUM('open','fulfilled','cancelled') DEFAULT 'open',
    notes           TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT             DEFAULT NULL,          -- NULL = broadcast
    sender_id   INT             DEFAULT NULL,
    title       VARCHAR(255)    NOT NULL,
    message     TEXT            NOT NULL,
    type        ENUM('info','urgent','stock_alert','donation_request','assessment','system') DEFAULT 'info',
    is_read     TINYINT(1)      DEFAULT 0,
    link        VARCHAR(500)    DEFAULT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────────────────────────
-- RECOMMENDATIONS (admin → users)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS recommendations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT             NOT NULL,
    user_id         INT             NOT NULL,          -- recipient (seeker/donor)
    blood_bank_id   INT             NOT NULL,
    reason          TEXT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id)      REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (user_id)       REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (blood_bank_id) REFERENCES blood_banks(id)  ON DELETE CASCADE
);

-- ═══════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════

-- Default admin (password: Admin@123)
INSERT INTO users (full_name, age, gender, blood_group, contact, email, password, city, role)
VALUES ('System Admin', 35, 'Male', 'O+', '03000000000',
        'admin@bloodonclick.com',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Lahore', 'admin');

-- Blood bank staff accounts (password: Admin@123)
INSERT INTO users (full_name, age, gender, blood_group, contact, email, password, city, latitude, longitude, role) VALUES
('Jinnah Hospital Staff',    30, 'Male',   'B+', '03001111111', 'jinnah@bloodonclick.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore',    31.5204, 74.3587, 'blood_bank'),
('Aga Khan Bank Staff',      28, 'Female', 'A+', '03002222222', 'agakhan@bloodonclick.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karachi',   24.8607, 67.0011, 'blood_bank'),
('PIMS Hospital Staff',      32, 'Male',   'O+', '03003333333', 'pims@bloodonclick.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Islamabad', 33.7215, 73.0433, 'blood_bank');

-- Blood banks
INSERT INTO blood_banks (user_id, name, address, city, latitude, longitude, contact, email, license_no) VALUES
(2, 'Jinnah Hospital Blood Bank',   'Jail Road, Lahore',                 'Lahore',    31.5204, 74.3587, '042-99200600', 'jinnah@bloodonclick.com',  'LHR-BB-001'),
(3, 'Aga Khan Blood Centre',        'Stadium Road, Karachi',             'Karachi',   24.8607, 67.0011, '021-34862000', 'agakhan@bloodonclick.com', 'KHI-BB-002'),
(4, 'PIMS Blood Transfusion Dept.', 'G-8/3 Shahra-e-Soharwardi, Islamabad', 'Islamabad', 33.7215, 73.0433, '051-9261170',  'pims@bloodonclick.com',    'ISB-BB-003');

-- Blood stock for each bank
INSERT INTO blood_stock (blood_bank_id, blood_group, units_available, threshold_alert) VALUES
-- Jinnah
(1,'A+',25,5),(1,'A-',8,3),(1,'B+',30,5),(1,'B-',4,3),(1,'AB+',12,3),(1,'AB-',2,2),(1,'O+',40,8),(1,'O-',6,3),
-- Aga Khan
(2,'A+',18,5),(2,'A-',3,3),(2,'B+',22,5),(2,'B-',7,3),(2,'AB+',9,3),(2,'AB-',1,2),(2,'O+',35,8),(2,'O-',4,3),
-- PIMS
(3,'A+',12,5),(3,'A-',5,3),(3,'B+',14,5),(3,'B-',2,3),(3,'AB+',6,3),(3,'AB-',0,2),(3,'O+',20,8),(3,'O-',3,3);

-- Sample donors (password: Admin@123)
INSERT INTO users (full_name, age, gender, blood_group, contact, email, password, city, latitude, longitude, last_donation_date, role, is_available) VALUES
('Sarah Johnson', 28, 'Female', 'A+', '03011234567', 'sarah@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore',    31.5497, 74.3436, '2024-10-15', 'donor', 1),
('Ahmed Khan',    35, 'Male',   'B+', '03111234567', 'ahmed@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karachi',   24.8778, 67.0650, '2024-08-20', 'donor', 1),
('Maria Garcia',  24, 'Female', 'O-', '03211234567', 'maria@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Islamabad', 33.6844, 73.0479, '2024-11-01', 'donor', 1),
('David Wilson',  42, 'Male',   'AB+','03311234567', 'david@example.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore',    31.5204, 74.3587, '2024-07-12', 'donor', 0),
('Fatima Ali',    31, 'Female', 'A-', '03411234567', 'fatima@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peshawar',  34.0150, 71.5805, '2024-09-05', 'donor', 1);

-- Sample seeker
INSERT INTO users (full_name, age, gender, blood_group, contact, email, password, city, latitude, longitude, role) VALUES
('Ali Raza', 45, 'Male', 'O+', '03511234567', 'seeker@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore', 31.5497, 74.3436, 'seeker');

-- Sample donations
INSERT INTO donations (donor_id, blood_bank_id, blood_group, units, donation_date, status, notes) VALUES
(5, 1, 'A+', 1.0, '2024-10-15', 'completed', 'Routine donation — all clear'),
(6, 2, 'B+', 1.0, '2024-08-20', 'completed', 'Walk-in donation'),
(7, 3, 'O-', 1.0, '2024-11-01', 'completed', 'Emergency unit donation'),
(8, 1, 'AB+',1.0, '2024-07-12', 'completed', 'Scheduled donation'),
(9, 1, 'A-', 1.0, '2024-09-05', 'completed', 'Routine check-in');

-- Sample assessments
INSERT INTO medical_assessments (donor_id, assessed_by, donation_id, hemoglobin, blood_pressure, pulse_rate, weight, temperature, hiv_status, hepatitis_b, hepatitis_c, syphilis, malaria, passed, notes) VALUES
(5, 1, 1, 13.5, '118/76', 72, 58.0, 36.8, 'Negative','Negative','Negative','Negative','Negative', 1, 'Donor in excellent health. Cleared for donation.'),
(6, 1, 2, 14.2, '122/80', 68, 75.0, 37.0, 'Negative','Negative','Negative','Negative','Negative', 1, 'Good hemoglobin levels.'),
(7, 1, 3, 12.8, '116/74', 74, 52.5, 36.6, 'Negative','Negative','Negative','Negative','Negative', 1, 'Universal donor in good shape.');

-- Sample blood requests
INSERT INTO blood_requests (requester_id, requester_type, blood_group, units_needed, city, hospital_name, patient_name, urgency, status, notes) VALUES
(10, 'seeker', 'O+', 2, 'Lahore', 'Services Hospital', 'Ali Raza', 'urgent', 'open', 'Need for surgery scheduled tomorrow'),
(10, 'seeker', 'B+', 1, 'Lahore', 'General Hospital', 'Zara Khan', 'normal', 'open', 'Elective procedure');

-- Sample notifications
INSERT INTO notifications (user_id, sender_id, title, message, type, is_read) VALUES
(5,  1, 'Assessment Ready',        'Your medical assessment report is available.',                        'assessment',       0),
(6,  1, 'Thank You for Donating!', 'Your B+ donation on Aug 20 has been recorded. See your history.',    'info',             0),
(1,  1, 'Low Stock Alert',         'AB- stock at Jinnah Hospital is critically low (2 units). Action needed.', 'stock_alert', 0),
(NULL,1,'System Announcement',    'Blood on Click v2 is now live. Welcome all new blood banks!',         'system',           0);
