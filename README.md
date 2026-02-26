# Blood on Click v2 — Setup Guide

## Overview
A complete multi-role blood donation management system built with PHP + MySQL.

**Roles:** Admin · Blood Bank Staff · Donor · Blood Seeker

---

## Requirements
- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ or MariaDB
- Apache / Nginx (XAMPP or WAMP on Windows)

---

## Quick Setup (XAMPP / WAMP)

### 1. Place Project Files
Copy the `blood-on-click-v2` folder into your web server root:
- XAMPP: `C:\xampp\htdocs\blood-on-click-v2\`
- WAMP:  `C:\wamp64\www\blood-on-click-v2\`

### 2. Create the Database
1. Open phpMyAdmin → http://localhost/phpmyadmin
2. Create a new database named: `blood_on_click`
3. Select the database, go to **Import**
4. Import the file: `schema_v2.sql`

### 3. Configure Database Connection
Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password
define('DB_NAME', 'blood_on_click');
```

### 4. Access the Application
Open your browser: **http://localhost/blood-on-click-v2**

---

## Test Accounts (all passwords: `Admin@123`)

| Role        | Email                        | Notes                          |
|-------------|------------------------------|--------------------------------|
| Admin       | admin@bloodonclick.com       | Full system access             |
| Blood Bank  | jinnah@bloodonclick.com      | Jinnah Hospital, Lahore        |
| Blood Bank  | agakhan@bloodonclick.com     | Aga Khan Centre, Karachi       |
| Blood Bank  | pims@bloodonclick.com        | PIMS Hospital, Islamabad       |
| Donor       | sarah@example.com            | A+, Lahore                     |
| Donor       | ahmed@example.com            | B+, Karachi                    |
| Seeker      | seeker@example.com           | O+, Lahore                     |

---

## File Structure

```
blood-on-click-v2/
├── index.php                  Entry point (redirects by role)
├── landing.php                Public landing page
├── login.php                  Login (all roles)
├── signup.php                 Registration (donor/seeker)
├── signup.php                 Registration (donor/seeker)
├── dashboard.php              Role-based router
├── search.php                 Global search (all roles)
├── notifications.php          View all notifications
├── logout.php                 Session destroy
├── schema_v2.sql              Full database schema + seed data
│
├── config/
│   ├── db.php                 Database connection
│   ├── auth.php               Auth guards + helpers
│   └── layout.php             Shared nav renderer
│
├── assets/
│   └── style.css              Complete design system
│
├── admin/
│   ├── index.php              User management
│   ├── add_user.php           Add new user
│   ├── edit_user.php          Edit user profile
│   ├── assessments.php        Medical assessments
│   ├── notifications.php      Broadcast notifications
│   └── recommendations.php    Recommend blood banks
│
├── blood_bank/
│   ├── dashboard.php          Stock overview + requests
│   ├── stock.php              Manage blood units
│   ├── requests.php           Incoming blood requests
│   └── send_notification.php  Alert donors
│
├── donor/
│   ├── dashboard.php          Donor home
│   ├── history.php            Donation history
│   └── assessments.php        View medical reports
│
├── seeker/
│   ├── dashboard.php          Seeker home + nearby donors
│   ├── search.php             Color-coded donor/bank search
│   └── request.php            Post & manage blood requests
│
└── api/
    └── update_request.php     Request status API
```

---

## Key Features

### Admin
- Add, edit, delete any user account
- Conduct and record medical assessments (vitals + 5 disease screenings)
- Broadcast targeted notifications by role, blood group, or city
- Recommend blood banks to seekers/donors
- View all system activity

### Blood Bank
- Real-time blood stock dashboard with visual bars
- Automatic low-stock alerts sent to matching donors
- Manage incoming blood requests (fulfill / cancel)
- Send targeted donor notifications

### Donor
- Personal dashboard with donation history
- Medical assessment reports (hemoglobin, BP, pulse, disease screening)
- Receive donation request notifications
- Find nearby blood banks

### Seeker
- Search donors with **color-coded blood group icons** (each type has unique color)
- Post blood requests with urgency levels (Normal / Urgent / Critical)
- Auto-notify matching donors on request submission
- View and manage all requests

---

## Security
- Passwords hashed with bcrypt (PASSWORD_BCRYPT)
- PDO prepared statements (SQL injection protected)
- Session-based role authentication
- CSRF protection on destructive operations

---

## Troubleshooting

**"Table doesn't exist" error:** Re-import `schema_v2.sql`

**Blank page / errors:** Enable PHP error display in php.ini: `display_errors = On`

**Login fails:** Check database credentials in `config/db.php`

**Port conflict:** XAMPP runs on port 80 by default. Change in XAMPP Control Panel if needed.
